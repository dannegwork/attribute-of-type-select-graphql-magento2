<?php
namespace DannegWork\OfTypeSelectGraphql\Model\Config;

use Magento\Framework\Config\ReaderInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\Entity\MapperInterface;
use Magento\Framework\Reflection\TypeProcessor;
use Magento\EavGraphQl\Model\Resolver\Query\Type;
use Magento\CatalogGraphQl\Model\Resolver\Products\Attributes\Collection;
use Psr\Log\LoggerInterface;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param MapperInterface $mapper
     * @param Collection $collection
     */
    public function __construct(
        MapperInterface $mapper,
        Collection $collection,
        LoggerInterface $logger
    ) {
        $this->mapper = $mapper;
        $this->collection = $collection;
        $this->logger = $logger;
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
                        'type' =>  $this->getLocatedTypeByAttributeCode($attribute->getAttributeCode()),
                        'arguments' => []
                    ];
                }
            }
        }

        return $config;
    }


    /**
     * @todo can be extended by setting flags on attributes or in configuration (for which attribute codes to be updated)
     *
     * @param string $attributeCode
     * @return string
     */
    protected function getLocatedTypeByAttributeCode(string $attributeCode) : string
    {
        return \DannegWork\OfTypeSelectGraphql\GraphQl\ProductAttributeOfTySelectResolverInterface::RESOLVER_TYPE;
    }


}
