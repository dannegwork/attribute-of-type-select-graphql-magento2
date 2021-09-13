<?php
namespace DannegWork\CatalogGraphql\Model\Config;

use DannegWork\CatalogGraphql\GraphQl\ProductAttributeOfTySelectResolverInterface;
use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\Entity\MapperInterface;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\EavGraphQl\Model\Resolver\Query\Type;
use Magento\CatalogGraphQl\Model\Resolver\Products\Attributes\Collection;

/**
 * Update the type of the select/int product attributes
 * The service is added to the list of readers for Magento\Framework\GraphQlSchemaStitching\Reader in di.xml
 *
 * @author Dana Negrescu <contact@danneg.work>
 */
class AttributeOfTypeSelectReader implements ReaderInterface
{

    /**
     * @var MapperInterface
     */
    private $mapper;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @param MapperInterface $mapper
     * @param Collection $collection
     */
    public function __construct(
        MapperInterface $mapper,
        Collection $collection
    ) {
        $this->mapper = $mapper;
        $this->collection = $collection;
    }

    /**
     * Read configuration scope
     *
     * @param string|null $scope
     * @return array
     * @throws GraphQlInputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function read($scope = null) : array
    {
        $config =[];
        $typeNames = $this->mapper->getMappedTypes(\Magento\Catalog\Model\Product::ENTITY);
        /** @var \Magento\Catalog\Model\ResourceModel\Eav\Attribute $attribute */
        foreach ($this->collection->getAttributes() as $attribute)
        {
            if($attribute->getFrontendInput() === "select" && $attribute->getBackendType() === "int")
            {
                foreach ($typeNames as $typeName) {
                    $config[$typeName]['fields'][$attribute->getAttributeCode()] = [
                        'name' => $attribute->getAttributeCode(),
                        'resolver' => ProductAttributeOfTySelectResolverInterface::RESOLVER,
                        'type' =>  $this->getLocatedTypeByAttributeCode($attribute->getAttributeCode()),
                        'arguments' => []
                    ];
                }
            }
        }

        return $config;
    }


    /**
     * @TODO can be extended by setting flags on attributes or in configuration (for which attribute codes to be updated)
     * (as seen on default M2 productDynamicAttributeReader)
     *
     * @param string $attributeCode
     * @return string
     */
    protected function getLocatedTypeByAttributeCode(string $attributeCode) : string
    {
        return \DannegWork\CatalogGraphql\GraphQl\ProductAttributeOfTySelectResolverInterface::RESOLVER_TYPE;
    }


}
