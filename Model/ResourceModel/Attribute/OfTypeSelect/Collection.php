<?php declare(strict_types=1);
namespace DannegWork\CatalogGraphql\Model\ResourceModel\Attribute\OfTypeSelect;

use Magento\Catalog\Setup\CategorySetup;
use \Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

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
     * @var string
     */
    protected $code;

    /**
     * @var int
     */
    protected $entityType = CategorySetup::CATALOG_PRODUCT_ENTITY_TYPE_ID;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->connection = $resourceConnection->getConnection();
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
     * @param int $code
     */
    public function addAttributeIdFilters(int $id) : void
    {
        $this->attributeId = $id;
    }

    /**
     * Retrieve attribute for passed in entity id.
     *
     * @param int $entityId
     * @return array
     */
    public function getSchemaForOptionId(int $optionId) : array
    {
        $optionValues = $this->fetch();
        if (!isset($optionValues[$optionId])) {
            return [];
        }

        return $optionValues[$optionId];
    }

    /**
     * Fetch attributes data and return in array format for a given option id
     *
     * @return array
     */
    protected function fetch() : array
    {
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
            )->where('option.option_id IN (?)', $this->optionIds);

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
     * @return int
     */
    public function getAttributeIdByAttributeCodeAndEntityType() : int
    {
        $whereConditions = [
            $this->connection->quoteInto('attr.attribute_code = ?', $this->code),
            $this->connection->quoteInto('attr.entity_type_id = ?', $this->entityType)
        ];

        $attributeIdSql = $this->connection->select()
            ->from(['attr'=>'eav_attribute'], ['attribute_id'])
            ->where(implode(' AND ', $whereConditions));

        return (int) $this->connection->fetchOne($attributeIdSql);
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
