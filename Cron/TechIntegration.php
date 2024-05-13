<?php
namespace Tech\System\Cron;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\File;

class TechIntegration
{
    
    /**
     * @var Curl
     */
    protected $curlClient;

    /**
     * @var string
     */
    protected $urlPrefix = 'https://';

    /**
     * @var string
     */
      protected $apiUri ='www.memorials.com/index.php/fuseaction/backend_products.fetch-products-api/none/1/offset/34360';

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */

    protected $productRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ProductInterfaceFactory
     */

    protected $productInterfaceFactory;

    /**
     * Directory List
     *
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * File interface
     *
     * @var File
     */
    protected $file;


    /**
     * @param Curl $curl
     * @param ProductInterfaceFactory $productInterfaceFactory
     * @param ProductRepositoryInterface $productRepository
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(Curl $curl, ProductInterfaceFactory $productInterfaceFactory, ProductRepositoryInterface $productRepository, DirectoryList $directoryList,
        File $file)
    {
        $this->curlClient = $curl;
        $this->productInterfaceFactory = $productInterfaceFactory;
        $this->productRepository =$productRepository;
        $this->directoryList = $directoryList;
        $this->file = $file;
    }

    /**
     * @return string
     */

	public function getApiUrls() {
	
	return $this->urlPrefix . $this->apiUri;
        }

    /**
     * Gets productInfo json
     *
     * @return array
     */
    public function execute()
    {
	
        $apiUri = $this->getApiUrls();
        $this->getCurlClient()->addHeader("Content-Type", "application/json");
        $this->getCurlClient()->get($apiUri);
        $trent = json_decode($this->getCurlClient()->getBody());
        
        $ola = json_encode($trent);

        $te = json_decode($ola);
	

            $text = ($te[1]);
		//var_dump($text);
		//die();
	

	 foreach ($text as $t) {

	//if (array($t->photos)) {

  // $adex = $t->photos[0];

   //var_dump($adex);
	//die();
  // }

           
	
            /** @var \Magento\Catalog\Api\Data\ProductInterface $newData */
               
                    $newData = $this->productInterfaceFactory->create();
        try {
	     $newData->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
	    $newData->setAttributeSetId(4);
	    $newData->setName($t->name);
            $newData->setSku($t->sku);
	    $newData->setPrice($t->price);
            $newData->setStatus(1);
            $newData->setVisibility(4);
            if (array($t->photos)) {

                $imageUrl = $t->photos[0];
            
        $tmpDir = $this->getMediaDirTmpDir();
        /** create folder if it is not exists */
        $this->file->checkAndCreateFolder($tmpDir);
        
        $newFileName = $tmpDir . baseName($imageUrl);
        
        $result = $this->file->read($imageUrl, $newFileName);
        if ($result) {
            
            $newData->addImageToMediaGallery($newFileName, array('image','thumbnail','small_image'), false, false);
        }
        
    }
    


            
	    //$newData->setImage($adex);
            //$newData->setSmallImage($adex);
            //$newData->setThumbnail($adex);
            
            
            $newData->setDescription($t->description);
	    $newData->setStockData(
        array(
        'use_config_manage_stock' => 0, 
        // checkbox for 'Use config settings' 
        'manage_stock' => 1, // manage stock
        'min_sale_qty' => 1, // Shopping Cart Minimum Qty Allowed 
        'max_sale_qty' => 2, // Shopping Cart Maximum Qty Allowed
        'is_in_stock' => 1, // Stock Availability of product
        'qty' => 100 // qty of product
        )
    );

	       
                   $this->productRepository->save($newData);
	      // $this->productRepository->save($newData);

        }
	      		
		catch(Exception $e) {
			//$e->getMessage();

            throw new \Exception("Oops! something went wrong. Please retry ");
            

        }
		
                    
                    }

            
                
       
    }


    /**
     * Media directory name for the temporary file storage
     * pub/media/tmp
     *
     * @return string
     */
    protected function getMediaDirTmpDir()
    {
        return $this->directoryList->getPath(DirectoryList::MEDIA) . DIRECTORY_SEPARATOR . 'tmp';
    }

    /**
     * @return Curl
     */
    public function getCurlClient()
    {
        return $this->curlClient;
    }
}