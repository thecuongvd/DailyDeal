<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model;

class Deal extends \Magento\Framework\Model\AbstractModel
{

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'dailydeal_deal';

    protected $_cacheTag = 'dailydeal_deal';

    /**
     * Prefix of model name
     *
     * @var string
     */
    protected $_dealPrefix = 'dailydeal_deal';
    
    protected $_scopeConfig;
    protected $_productFactory;
    protected $_stockItem;
    protected $_date;
    protected $_dailydealHelper;
    protected $_formKey;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_productFactory = $productFactory;
        $this->stockItem = $stockItem;
        $this->_date = $date;
        $this->_dailydealHelper = $dailydealHelper;
        $this->_formKey = $formKey;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Magebuzz\Dailydeal\Model\ResourceModel\Deal');
    }
    
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_ENABLED => __('Enabled'), 
            self::STATUS_DISABLED => __('Disabled')
                ];
    }
    
//    public function getProduct() {
//        $product = $this->_productFactory->create()->load($this->getProductId());
//        if ($product->getId()) {
//            return $product;
//        }
//        return null;
//    }

    public function getProductPrice()
    {
        $product = $this->getProduct();
        if ($product && $product->getId()) {
            return number_format($product->getPrice(), 2);
        } 
        return '0.00';
    }
    
    public function getProductQty()
    {
        $product = $this->getProduct();
        if ($product && $productId = $product->getId()) {
            return $this->stockItem->getStockQty($productId, $product->getStore()->getWebsiteId());
        } 
        return 0;
    }
    
    public function loadByProductId($productId) {
        $dealId = $this->getResource()->getDealByProductId($productId);
        return $this->load($dealId);
    }
    
    public function getScopeConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
    
    public function calSaving() {
        $isRoundSaving = $this->getScopeConfig('dailydeal/general/is_round_saving');
        $product = $this->getProduct();
        if ($product && $product->getFinalPrice() > 0) {
            $decrease = floatval($product->getFinalPrice()) - floatval($this->getPrice());
            if ($isRoundSaving) {
                $saving = round(100 * $decrease / floatval($product->getFinalPrice()), 0);
            } else {
                $saving = round(100 * $decrease / floatval($product->getFinalPrice()), 2);
            }
        } else {
            $saving = 0;
        }
        return $saving;
    }
    
    public function isAvailable() {
        $nowTime = $this->_dailydealHelper->getCurrentTime() ;
        $startTime = strtotime($this->getStartTime());
        $endTime = strtotime($this->getEndTime());
        if (($this->getStatus() == \Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED) && (($this->getQuantity() - $this->getSold()) > 0) && ($startTime <= $nowTime) && ($nowTime <= $endTime)) {
            return true;
        }
        return false;
    }

}
