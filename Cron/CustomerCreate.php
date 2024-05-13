<?php
/**
 * Copyright Ã‚Â© Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Tech\System\Cron;


use Magento\Framework\HTTP\Client\Curl;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\AccountManagementInterface;


class CustomerCreate {

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
    protected $apiUrl = 'www.memorials.com/index.php/fuseaction/backend_members.fetch-members-api/none/1/offset/99500';
  

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

   
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;


    /**
     * @var AddressInterfaceFactory
     */
    protected $dataAddressFactory;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

   /**
   * @var Session;
   */

    protected $customerSession;

    

    
    /**
    
     * @param StoreManagerInterface      $storeManager
     * @param Curl          $curl
     * @param Context      $context
     * @param CustomerInterfaceFactory    $customerFactory
     * @param AddressInterfaceFactory $dataAddressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param EncryptorInterface $encryptor
     * @param CustomerRepositoryInterface $customerRepository
     * @param Session $customerSession     
    */
   
     public function __construct(
       StoreManagerInterface $storeManager,
       Curl $curl,
        Context $context,
        CustomerInterfaceFactory $customerFactory,
        AddressRepositoryInterface $addressRepository,
        EncryptorInterface $encryptor,
        CustomerRepositoryInterface $customerRepository,
       AddressInterfaceFactory $dataAddressFactory,
       Session $customerSession
       
           ) {
      
       $this->storeManager = $storeManager;
   
       $this->curlClient = $curl;

       $this->customerFactory  = $customerFactory;

       $this->addressRepository  = $addressRepository;
        $this->encryptor          = $encryptor;
        $this->customerRepository = $customerRepository;
        $this->dataAddressFactory = $dataAddressFactory;
        $this->customerSession = $customerSession;
        

    //parent::__construct($context);
      
        }

        /**
     * @return string
     */
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
    

            
    
     $ola = json_encode($response);

        $ten = json_decode($ola);
    

            $texts = $ten[1];
        
    
         foreach($texts as $text) {
        
        
        
        
        //$customer->setWebsiteId($websiteId);
        
     try {  
    $customer = $this->customerFactory->create();
    $store = $this->storeManager->getStore();
        $websiteId  = $this->storeManager->getStore()->getWebsiteId();
        $customer->setWebsiteId($websiteId);
	
	//$ola = $this->customerRepository->get($text->email, $websiteId);
	
	
     if(empty($text->addresses) || empty($text->firstname) || empty($text->lastname) || empty($text->email) || empty($text->addresses[0]->addressFirstName) || empty($text->addresses[0]->addressLastName)) {
    
    var_dump("Empty address or First Name or Last Name or Email");
        continue;

    }elseif(!filter_var($text->email, FILTER_VALIDATE_EMAIL)) {

        var_dump("Invalid Email Address");
        continue;


    } elseif (preg_match('/\s/',$text->firstname)) {
     var_dump("First Name Has Space");
        continue;
      } elseif (preg_match('/\s/',$text->lastname)) {
    var_dump("Last Name Has Space");

        continue;
        
      } elseif (preg_match('/\s/',$text->addresses[0]->addressFirstName)) {
    var_dump("Address First Name Has Space");

        continue;
      } elseif (preg_match('/\s/',$text->addresses[0]->addressLastName)) {
    var_dump("Address Last Name Has Space");

        continue;
      }elseif (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+:-]/', $text->firstname)) {

        continue;
    var_dump("First Name Invalid");
      }elseif (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+:-:]/', $text->lastname)) {

        continue;
    var_dump("Last Name Invalid");
      }elseif (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+:-:]/', $text->addresses[0]->addressFirstName)) {

        continue;
    var_dump("Address First Name Invalid");
      }elseif (preg_match('/[\'^£$%&*()}{@#~?><>,|=_+:-:]/', $text->addresses[0]->addressLastName)) {

        continue;
    var_dump("Address Last Name Invalid");
     
    }    
	    
        $customer->setEmail($text->email);
        $customer->setFirstname($text->firstname);
        $customer->setLastname($text->lastname);
        $customer->setConfirmation(null);
         $customer =  $this->customerRepository->save($customer);
     $address = $this->dataAddressFactory->create();
    $address->setCustomerId($customer->getId());
    
     $address->setFirstname($text->addresses[0]->addressFirstName);
        $address->setLastname($text->addresses[0]->addressLastName);
        $address->setTelephone($text->phone);
        
            $address->setStreet(array($text->addresses[0]->address));
        $address->setCountryId('US');            
            $address->setCity($text->addresses[0]->city);
            $address->setPostcode($text->addresses[0]->zip);
            $address->setIsDefaultShipping(1);
            $address->setCompany($text->company);
            $address->setRegionId(57);
            //$address->setRegion($text->addresses[0]->state);
            $address->setIsDefaultBilling(1); 

      $this->addressRepository->save($address);    
    
            //echo "customer saved" . $den . "correctly";
   
           
   }catch (Exception $e) {
        echo $e->getException();
    }
         


    }             
 }
     

                            
            
        public function getCustomer($email) {
    
    return $this->customerRepository->get($email);

    }

                 
            /**
             * @return Curl
             */
            public function getCurlClient()
            {
                return $this->curlClient;
            }        
               
}

    
