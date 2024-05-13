<?php

namespace Tech\System\Cron;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class UpdateQuantity
{
    protected $productRepository;
    protected $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        LoggerInterface $logger
    ) {
        $this->productRepository = $productRepository;
        $this->logger = $logger;
    }

    public function execute()
    {
        try {
            $maxOrderQty = 10; // Set the maximum order quantity

            $productCollection = $this->productRepository->getList();
            foreach ($productCollection->getItems() as $product) {
                /** @var Product $product */
                $product->setData('max_order_qty', $maxOrderQty);
                $this->productRepository->save($product);
                $this->logger->info("Updated maximum order quantity for product: " . $product->getName());
            }
        } catch (\Exception $e) {
            $this->logger->error("Error updating maximum order quantity: " . $e->getMessage());
        }
    }
}