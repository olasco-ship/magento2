<?php

namespace Tech\System\Cron;

use Magento\Framework\App\Bootstrap;

class CreateOrder {



public function execute() 
{

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

$storeManager = $objectManager->create(\Magento\Store\Model\StoreManagerInterface::class);
$quoteManagement = $objectManager->create(\Magento\Quote\Api\CartManagementInterface::class);
$customerRepository = $objectManager->create(\Magento\Customer\Api\CustomerRepositoryInterface::class);
$quoteFactory = $objectManager->create(\Magento\Quote\Model\QuoteFactory::class);
$productRepository = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$addressRepository = $objectManager->create(\Magento\Customer\Api\AddressRepositoryInterface::class);
$productSkus = ['PR-110283'];
$customerEmail = 'kay23@comcast.net';


try {
   $customer = $customerRepository->get($customerEmail);

	var_dump("Customer found");
}
catch (NoSuchEntityException $e) {
  return ['error' => 1, 'msg' => 'Undefined user by email: ' . $customerEmail];
}

$quote = $quoteFactory->create();

$store = $storeManager->getStore();

$quote->setStore($store);
$quote->assignCustomer($customer);

//add products in quote
$productQty = 1;
foreach ($productSkus as $productSku) {
  try {
     $product = $productRepository->get($productSku);
     $quote->addProduct($product, $productQty);
var_dmp("Product seen");
  }
  catch (NoSuchEntityException $e) {}
}

$billingAddress = null;
$shippingAddress = null;
try {
   $billingAddress = $addressRepository->getById($customer->getDefaultBilling());
   $shippingAddress = $addressRepository->getById($customer->getDefaultShipping());

	Var_dump("Shipping and Address Found");
}
catch (NoSuchEntityException $e) {}

if ($billingAddress) {
    $quote->getBillingAddress()->importCustomerAddressData($billingAddress);
}

if ($shippingAddress) {
    $quote->getShippingAddress()->importCustomerAddressData($shippingAddress);
    $shippingAddress = $quote->getShippingAddress();

$shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('flatrate_flatrate');
}

$quote->setPaymentMethod('checkmo');
$quote->setInventoryProcessed(false);
$quote->save();

Var_dump("It's save Convinenetly");

// Set Sales Order Payment
$quote->getPayment()->importData(['method' => 'checkmo']);

// Collect Totals & Save Quote
$quote->collectTotals()->save();

var_dump("another Save");

// Create Order From Quote
$order = $quoteManagement->submit($quote);

return ['success' => 1, 'Order was successfully placed, order number: ' . $order->getIncrementId()];


    }

}