<?php
/**
 * Copyright (c) 2021. All rights reserved.
 * @author: Volodymyr Hryvinskyi <mailto:volodymyr@hryvinskyi.com>
 */

declare(strict_types=1);

namespace Hryvinskyi\SeoCanonicalCatalogProductFrontend\Model;

use Hryvinskyi\SeoCanonicalApi\Api\CanonicalUrl\ProcessorInterface;
use Hryvinskyi\SeoCanonicalFrontend\Model\AbstractCanonicalUrlProcess;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\HttpRequestInterface;
use Magento\Framework\Registry;

class CanonicalUrlProcess extends AbstractCanonicalUrlProcess
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ProcessorInterface
     */
    private $associatedProductProcessor;

    /**
     * @var CollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @param Registry $registry
     * @param ProcessorInterface $associatedProductProcessor
     * @param CollectionFactory $productCollectionFactory
     * @param array $actions
     */
    public function __construct(
        Registry $registry,
        ProcessorInterface $associatedProductProcessor,
        CollectionFactory $productCollectionFactory,
        array $actions = [])
    {
        parent::__construct($actions);

        $this->registry = $registry;
        $this->associatedProductProcessor = $associatedProductProcessor;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @inheritDoc
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(HttpRequestInterface $request): ?string
    {
        $product = $this->registry->registry('current_product');

        if (!$product) {
            return null;
        }

        $associatedProductId = $this->getAssociatedProductId($product);
        $productId = $associatedProductId ?? $product->getId();

        $collection = $this->productCollectionFactory->create()
            ->addFieldToFilter('entity_id', $productId)
            ->addStoreFilter()
            ->addUrlRewrite();

        $collection->setFlag('has_stock_status_filter');

        $product = $collection->getFirstItem();
        return $product->getProductUrl();
    }

    /**
     * Get associated product id
     *
     * @param Product $product
     *
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAssociatedProductId(Product $product): ?int
    {
        if ($product->getTypeId() !== Type::TYPE_SIMPLE) {
            return null;
        }

        $data = ['productId' => $product->getId()];
        $this->associatedProductProcessor->execute($data);

        return isset($data['associatedProductId']) ? (int)$data['associatedProductId'] : null;
    }
}
