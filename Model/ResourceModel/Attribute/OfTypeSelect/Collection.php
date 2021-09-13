<?php declare(strict_types=1);
namespace DannegWork\CatalogGraphql\Model\ResourceModel\Attribute\OfTypeSelect;

use \Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;

/**
 * Collection to fetch attribute data at resolution time.
 *
 * @author Dana Negrescu <contact@danneg.work>
 */
class Collection
{
    /**
     * @var int[]
     */
    protected $entityIds = [];

    /**
     * @var int[]
     */
    protected $optionIds = [];

    /**
     * @var array
     */
    protected $optionValues = [];

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var int
     */
    protected $storeId = 0;

    /**
     * Attribute code for the checked out attribute
     *
     * @var string
     */
    protected $code;

    /**
     * @var
     */
    protected $entityType = \Magento\Catalog\Model\Product::ENTITY;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->connection = $resourceConnection->getConnection();
    }

    /**
     * Add entity and id filter to filter for fetch.
     *
     * @param int $entityId
     * @return void
     */
    public function addIdFilters(int $entityId) : void
    {
        if (!in_array($entityId, $this->entityIds)) {
            $this->entityIds[] = $entityId;
        }
    }

    /**
     * Add option id to filter for fetch.
     *
     * @param int $optionId
     * @return void
     */
    public function addOptionIdFilters(int $optionId) : void
    {
        if (!in_array($optionId, $this->optionIds)) {
            $this->optionIds[] = $optionId;
        }
    }

    /**
     * @param string $code
     */
    public function addAttributeCodeFilters(string $code) : void
    {
        $this->code = $code;
    }

    /**
     * Retrieve attribute for passed in entity id.
     *
     * @param int $entityId
     * @return array
     */
    public function getAttributeForEntityId(int $entityId) : array
    {
        $optionValues = $this->fetch();
        if (!isset($optionValues[$entityId])) {
            return [];
        }

        return $optionValues[$entityId];
    }

    /**
     * Retrieve attribute for passed in entity id.
     *
     * @param int $entityId
     * @return array
     */
    public function getSchemaForOptionId(int $optionId) : array
    {
        $optionValues = $this->fetchForOptionId();
        if (!isset($optionValues[$optionId])) {
            return [];
        }

        return $optionValues[$optionId];
    }

    /**
     * Fetch attributes data and return in array format.
     *
     * @return array
     */
    protected function fetch() : array
    {
        if (empty($this->entityIds) || !empty($this->optionValues)) {
            return $this->optionValues;
        }
        $this->optionValues = $this->getAttributeValues();

        return $this->optionValues;
    }


    /**
     * Loading values per product
     *
     * @return array
     */
    protected function getAttributeValues() : array
    {
        $select = $this->connection->select()
            ->from(
                ['main'=> new \Zend_Db_Expr("( ". $this->getAttributeValueSql()->__toString() . " )")],
                ['main.product_id', 'option.code', 'option.value']
            )->joinLeft(
                ['option' => new \Zend_Db_Expr("( ". $this->getOptionValuesSql()->__toString() . " )")],
                "main.option_id = option.option_id AND option.attribute_id=main.attribute_id"
            );

        return $this->connection->fetchAssoc($select);
    }

    /**
     * @param string $attributeId
     * @return Select
     */
    protected function getAttributeValueSql(): Select
    {
        $attributeId = $this->getAttributeIdByAttributeCodeAndEntityType();
        $select = $this->connection->select()
            ->from(
                ['c_p_e' => $this->connection->getTableName('catalog_product_entity')],
                [
                    'product_id' => 'c_p_e.entity_id',
                    'attribute_id' => 'c_p_e_a.attribute_id',
                    new \Zend_Db_Expr("CASE WHEN c_p_e_b.value IS NULL THEN c_p_e_a.value ELSE c_p_e_b.value END as option_id")
                ]
            )
            ->joinLeft(
                ['c_p_e_a' => $this->connection->getTableName('catalog_product_entity_int')],
                'c_p_e_a.entity_id = c_p_e.entity_id AND c_p_e_a.store_id = 0 AND c_p_e_a.attribute_id = ' . $attributeId,
                []
            )
            ->joinLeft(
                ['c_p_e_b' => $this->connection->getTableName('catalog_product_entity_int')],
                'c_p_e_b.entity_id = c_p_e.entity_id AND c_p_e_b.store_id = ' . $this->storeId . ' AND c_p_e_b.attribute_id = ' . $attributeId,
                []
            )
            ->where('c_p_e.entity_id IN (?)', $this->entityIds);

        return $select;
    }

    /**
     * Fetch attributes data and return in array format for a given option id
     *
     * @return array
     */
    protected function fetchForOptionId() : array
    {
        if (empty($this->entityIds) || !empty($this->optionValues)) {
            return $this->optionValues;
        }
        $this->optionValues = $this->getAttributeOptionValues();

        return $this->optionValues;
    }

    /**
     * The output matches the required SCHEMA for the select type
     * [code => 'value', value => 'value']
     *
     * Generic
     * Can be reused for other entities
     * @return array
     */
    protected function getAttributeOptionValues() : array
    {
        $select = $this->connection->select()
            ->from(
                ['option'=> new \Zend_Db_Expr("( ". $this->getOptionValuesSql()->__toString() . " )")],
                ['option.option_id', 'option.code', 'option.value']
            )->where('option.option_id IN (?)', $this->optionIds)
            ->where('option.attribute_id = ?', $this->getAttributeIdByAttributeCodeAndEntityType());

        return $this->connection->fetchAssoc($select);
    }

    /**
     * Localized join for option-values
     * Generic
     * Can be reused for other entities
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function getOptionValuesSql() : Select
    {
        $select = $this->connection->select()
            ->from(
                ['a_o' => $this->connection->getTableName('eav_attribute_option')],
                [
                    'a_o.attribute_id',
                    'a_o.option_id',
                    new \Zend_Db_Expr("b_o.value as code"),
                    new \Zend_Db_Expr("CASE WHEN c_o.value IS NULL THEN b_o.value ELSE c_o.value END as value")
                ]
            )->joinLeft(['b_o' => $this->connection->getTableName('eav_attribute_option_value')],
                'b_o.option_id = a_o.option_id AND b_o.store_id = 0',
                []
            )->joinLeft(['c_o' => $this->connection->getTableName('eav_attribute_option_value')],
                'c_o.option_id = a_o.option_id AND c_o.store_id = ' . $this->storeId,
                []
            );

        return $select;
    }

    /**
     * Helper function to access attribute ID by the attribute name
     *
     * @param $code
     * @param $type
     * @return string
     */
    public function getAttributeIdByAttributeCodeAndEntityType() : string
    {
        $whereConditions = [
            $this->connection->quoteInto('attr.attribute_code = ?', $this->code),
            $this->connection->quoteInto('attr.entity_type_id = ?', $this->type)
        ];

        $attributeIdSql = $this->connection->select()
            ->from(['attr'=>'eav_attribute'], ['attribute_id'])
            ->where(implode(' AND ', $whereConditions));

        return $this->connection->fetchOne($attributeIdSql);
    }

    /**
     * @param int $storeId
     * @return self
     */
    public function setStoreId(int $storeId) : self
    {
        $this->storeId = $storeId;
        return $this;
    }


    /**
     * @param string $type
     * @return $this
     */
    public function setEntityType(string $type) : self
    {
        $this->entityType = $type;
        return $this;
    }


}