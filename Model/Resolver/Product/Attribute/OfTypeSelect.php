<?php declare(strict_types=1);
namespace DannegWork\OfTypeSelectGraphql\Model\Resolver\Product\Attribute;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;

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
     * @param ValueFactory $valueFactory
     */
    public function __construct(
        ValueFactory $valueFactory,
    ) {
        $this->valueFactory = $valueFactory;
    }

    /**
     * Format product's select attribute entry data to conform to GraphQL schema
     *
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $result = [];
        /** @var Product $product */
        $product = $value['model'];
        if($product->hasData($field->getName()))
        {
            $result = [];
        }

        return $this->valueFactory->create($result);
    }


}
