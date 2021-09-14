<?php
namespace DannegWork\CatalogGraphql\GraphQl;

/**
 * Interface to handle attribute reader
 */
interface ProductAttributeOfTySelectReaderInterface
{

    /**
     * @param string $attributeCode
     * @return string
     */
    public function getLocatedTypeByAttributeCode(string $attributeCode) : string;


    /**
     * @param string $attributeCode
     * @return string
     */
    public function getResolverByAttributeCode(string $attributeCode) : string;


}
