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
    protected $urlModel;
    protected $_formKey;
    protected $_orderItemCollectionFactory;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_productFactory = $productFactory;
        $this->stockItem = $stockItem;
        $this->_date = $date;
        $this->_dailydealHelper = $dailydealHelper;
        $this->urlModel = $urlFactory->create();
        $this->_formKey = $formKey;
        $this->_orderItemCollectionFactory = $orderItemCollectionFactory;
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
    
    public function getAvailableProgressStatuses()
    {
        return [
            'running' => __('Running'), 
            'coming' => __('Coming'), 
            'ended' => __('Ended')
                ];
    }
    
    public function loadByProductId($productId) {
        $dealId = $this->getResource()->getDealByProductId($productId);
        return $this->load($dealId);
    }
    
    public function getScopeConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
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

    public function isNotEnded() {
        $nowTime = $this->_dailydealHelper->getCurrentTime() ;
        $startTime = strtotime($this->getStartTime());
        $endTime = strtotime($this->getEndTime());
        if (($this->getStatus() == \Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED) && (($this->getQuantity() - $this->getSold()) > 0) && ($nowTime <= $endTime)) {
            return true;
        }
        return false;
    }
    
    public function getOrderItemCollection()
    {
        $productIds = $this->getProductIds();
        return $this->_orderItemCollectionFactory->create()->addFieldToFilter('product_id', ['in' => $productIds]);
    }

    public function getTodayDealsEndTime() {
        return $this->getResource()->getTodayDealsEndTime();
    }
    
    public function getTitleFromProducts() {
        $product = $this->_productFactory->create();
        $title = '';
        $productIds = $this->getProductIds();
        foreach ($productIds as $productId) { 
            $product->load($productId);
            $title .= $product->getName() . ',';
        }
        $title = rtrim($title, ',');
        return $title;
    }
}
