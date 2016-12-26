<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Catalog data helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_storeManager;
    protected $_fileSystem;
    protected $_customerSession;
    protected $_currency;
    protected $_stockItem;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Directory\Model\Currency $currency,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem
    )
    {
        $this->_storeManager = $storeManager;
        $this->_fileSystem = $fileSystem;
        $this->_customerSession = $customerSession;
        $this->_currency = $currency;
        $this->stockItem = $stockItem;

        parent::__construct($context);
    }
    
    public function getCurrencySymbol() {
        return $this->_currency->getCurrencySymbol();
    }
    
    public function getProductPrice($product) {
        if ($product && $product->getId()) {
            return number_format($product->getPrice(), 2);
        } 
        return '0.00';
    }
    
    public function getProductQuantity($product) {
        if ($product && $productId = $product->getId()) {
            return $this->stockItem->getStockQty($productId, $product->getStore()->getWebsiteId());
        } 
        return 0;
    }

    /**
     * get image url.
     *
     * @return array
     */
//    public function getImageUrl($imageName, $dir)
//    {
//        $path = $this->_fileSystem->getDirectoryRead(
//            DirectoryList::MEDIA
//        )->getAbsolutePath($dir);
//        if (file_exists($path . $imageName)) {
//            $path = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
//            return $path . $dir . $imageName;
//        } else {
//            return '';
//        }
//    }
//
//    public function isHeaderlinkEnabled()
//    {
//        return (bool)$this->scopeConfig->getValue(
//            'events/general_setting/show_url_in_header_link',
//            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
//        );
//    }
//
//    public function getCustomerId()
//    {
//        if ($this->_customerSession->isLoggedIn()) {
//            return $this->_customerSession->getCustomerId();
//        }
//        return null;
//    }

}