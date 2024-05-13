<?php

namespace Tech\System\Cron;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;

class OrderIntegration
{

   /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curlClient;

    /**
     * @var string
     */
    protected $urlPrefix = 'https://';


    /**
     * @var string
     */
    protected $apiUrl = 'www.memorials.com/index.php/fuseaction/backend_orders.fetch-orders-api/none/1/offset/40000/limit/500';
  
     /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

   protected $quoteManagement;
   
    /**
     * @var CustomerRepositoryInterface
     */
   protected $customerRepository;

   protected $quoteFactory;

   protected $productRepository;

    /**
     * @var AddressRepositoryInterface
     */

   protected $addressRepository;
    
   protected $customerFactory;
   
   	
   

    /**
    
     * @param StoreManagerInterface      $storeManager
     * @param Curl          $curl
     * @param QuoteManagement $quoteManagement
     * @param QuoteFactory      $quoteFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepositoryInterface   $productRepository
     * @param CustomerFactory $customerFactory
        
    */



   public function __construct(
     StoreManagerInterface $storeManager,
     QuoteManagement $quoteManagement,
     CustomerRepositoryInterface $customerRepository,
     QuoteFactory $quoteFactory,
     ProductRepositoryInterface $productRepository,
     AddressRepositoryInterface $addressRepository,
     Curl $curl,
     CustomerFactory $customerFactory
    )
   {
     $this->storeManager = $storeManager;
     $this->quoteManagement = $quoteManagement;
     $this->customerRepository = $customerRepository;
     $this->quoteFactory = $quoteFactory;
     $this->productRepository = $productRepository;
     $this->addressRepository = $addressRepository;
     $this->curlClient = $curl;
     $this->customerFactory = $customerFactory;
       }

     public function getApiUrl()
    {
        return $this->urlPrefix . $this->apiUrl;
    }


   public function execute()
   {

    $apiUrl = $this->getApiUrl();

         $this->getCurlClient()->addHeader("Content-Type", "application/json");
        //$this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYHOST,false);
        //$this->getCurlClient()->setOption(CURLOPT_SSL_VERIFYPEER,false);
        $this->getCurlClient()->get($apiUrl);
        $response = json_decode($this->getCurlClient()->getBody());

    //$ola = json_encode($response);

        //$ten = json_decode($ola);
    

            $texts = $response[1];
        //var_dump($texts);
        //die();
        
    
         foreach($texts as $text) {

    if(empty($text->items)) {
        continue;
      }
	
	if(!$text->member) {

	var_dump("Test 1 Passed");

	continue;
   }

    //var_dump($text->items[0]->productsku);    
    //die();    
    
    

    // Data Information



    $orderData =[
            'email'        => $text->member->email, //customer email id
            'currency_id'  => 'USD',
        'address' =>[
                'firstname'    => $text->billingAddress->firstname,
                'lastname'     => $text->billingAddress->lastname,
                'prefix' => '',
                'suffix' => '',
                'street' => $text->billingAddress->address,
                'city' => $text->billingAddress->city,
                'country_id' => 'US',
                'region' => 'california',
                'region_id' => '12', // State region id
                'postcode' => '12345',
                'telephone' => '',
                'fax' => '',
                'save_in_address_book' => 1
            ],
            'items'=>
                [
                    //simple product
                    [
                        'product_sku' => $text->items[0]->productsku,
                        'qty' => $text->items[0]->quantity                    
            ],
                ]
        ];

    
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
         //var_dump($storeId);

	//die();
    
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
       $customer = $customer->loadByEmail($orderData['email']);
    if(!$customer->getId()){
            //if customer is not exist then skip that customer 
        continue;            
        }
        $quote = $this->quoteFactory->create(); 
        $quote->setStoreId(1); 
        
        $customer= $this->customerRepository->getById($customer->getId());
        $quote->setCurrency();
        $quote->assignCustomer($customer); // Quote assign to customer
        
    
 	

   	
    try {
        
        $product = $this->productRepository->get($text->items[0]->productsku);
            
    var_dump("Product Created with SKU" ."" . $product->getId() . "". "Successfully");

    if($text->items[0]->quantity > 2) {

	var_dump("Quantity is biggefr than 2");
	
	continue;

	}
    
    $quote->addProduct($product, intval($text->items[0]->quantity));

     
    
           } catch (NoSuchEntityException $e) {
        
    var_dump("No such product");

    continue;
    
    }
       

       
          
    //Ending of Foreach loop for getting product


    //Starting of Billing and Shipping
    
       $quote->getBillingAddress()->addData($orderData['address']);
        $quote->getShippingAddress()->addData($orderData['address']);

        // Collect Rates and Set Shipping & Payment Method

        $shippingAddress = $quote->getShippingAddress();
        
        //$str = $text->items[0]->productoptions;
        //$explode = preg_split('/[0-9]+\.+[0-9]/', $str); 
        // $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod($explode);
 
        $shippingAddress->setCollectShippingRates(true)->collectShippingRates()->setShippingMethod('flatrate_flatrate');
      //set shipping method
        $quote->setPaymentMethod('checkmo'); //set payment method
        $quote->setInventoryProcessed(false);
	
        $quote->save(); //quote save 
        
        $quote->getPayment()->importData(['method' => 'checkmo']);
 
        // Collect Quote Total and Save
       if($quote->collectTotals()->save()) {
	var_dump("Are you joking?");
        
	}
        
        // Create Order From Quote Object

	
    	$order = $this->quoteManagement->submit($quote);
	          var_dump("Successfully Created Cheers");
	               
        //order id for placed order 

       // try {

        //$orderId = $order->getIncrementId();
       // var_dump("Successfully Created Cheers");
        
        //} catch (NoSuchEntityException $e) {
        
        //var_dump("OrderId Null");
         //continue;
    
    //}

        //if($orderId){
           // $result['success']= $orderId;

       //var_dump("Successfully Created Cheers");
    
       // }else{
           $result=['error'=>true,'msg'=>'something went wrong when Order placed'];
       // }
        //return $result;  
    //Ending of Billing and Shipping 
         
    }
    
  }


              /**
             * @return Curl
             */
            public function getCurlClient()
            {
                return $this->curlClient;
            }        

}

