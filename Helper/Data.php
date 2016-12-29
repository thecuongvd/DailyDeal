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
    protected $_currency;
    protected $_stockItem;
    protected $_objectManager;
    protected $_date;
    protected $prdImageHelper;
    protected $_urlInterface;
    protected $_customerSession;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Directory\Model\Currency $currency,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_currency = $currency;
        $this->stockItem = $stockItem;
        $this->_objectManager = $objectManager;
        $this->_date = $date;
        $this->prdImageHelper = $imageHelper;
        $this->_urlInterface = $urlInterface;
        $this->_customerSession = $customerSession;

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
    
    public function getFinalProductPrice($product) {
        if ($product && $product->getId()) {
            return number_format($product->getFinalPrice(), 2);
        } 
        return '0.00';
    }
    
    public function getProductQuantity($product) {
        if ($product && $productId = $product->getId()) {
            return $this->stockItem->getStockQty($productId, $product->getStore()->getWebsiteId());
        } 
        return 0;
    }
    
    public function getPriceWithCurrency($price)
    {
        if ($price) {
            return $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data')->currency(number_format($price, 2), true, false);
        }
        return 0;
    }
    
    public function getCurrentTime() {
        return $this->_date->gmtTimestamp();
    }
    
    public function getCurrentUrl() {
        return $this->_urlInterface->getCurrentUrl();
    }

    public function getCustomerId()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_customerSession->getCustomerId();
        }
        return null;
    }

    public function getProductImageUrl($product, $size) {
        if ($size == 'large') {
            $imageSize = 'product_page_image_large';
        } else if ($size == 'medium') {
            $imageSize = 'product_page_image_medium';
        } else {
            $imageSize = 'product_page_image_small';
        }
        $imageUrl = $this->prdImageHelper->init($product, $imageSize)
            ->keepAspectRatio(TRUE)
            ->keepFrame(FALSE)
            ->getUrl();
        return $imageUrl;
    }
    

}