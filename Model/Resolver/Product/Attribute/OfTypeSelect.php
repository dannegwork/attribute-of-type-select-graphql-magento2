<?php declare(strict_types=1);
namespace DannegWork\CatalogGraphql\Model\Resolver\Product\Attribute;

use DannegWork\CatalogGraphql\Model\ResourceModel\Attribute\OfTypeSelect\Collection;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Store\Api\Data\StoreInterface;

/**
 * Retrieves the information about the dropdown (single option) attribute
 */
class OfTypeSelect implements ResolverInterface
{
    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var Collection
     */
    private $entityAttributeCollection;

    /**
     * @param ValueFactory $valueFactory
     * @param Collection $entityAttributeCollection
     */
    public function __construct(
        ValueFactory $valueFactory,
        Collection $entityAttributeCollection
    ) {
        $this->valueFactory = $valueFactory;
        $this->entityAttributeCollection = $entityAttributeCollection;
    }

    /**
     * Format product's select attribute entry data to conform to GraphQL schema
     * @TODO can be changed to a collectionprocessor element
     *
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];
        if($product->hasData($field->getName()))
        {
            /** @var StoreInterface $store */
            $store = $context->getExtensionAttributes()->getStore();
            $this->entityAttributeCollection->setStoreId((int)$store->getId());

            /** adding an attribute id filter can be redundant when the attribute is single-value */
            $optionId = (int)$product->getData($field->getName());
            $this->entityAttributeCollection->addOptionIdFilters($optionId);

            $result = function () use ($optionId) {
                return $this->entityAttributeCollection->getDataForOptionId($optionId);
            };

            return $this->valueFactory->create($result);
        }

        return [];
    }


}
