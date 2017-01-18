<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Block\Product;

class Deal extends \Magento\Framework\View\Element\Template
{
    protected $_coreRegistry = null;
    protected $_dealFactory;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_dailydealHelper;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_dealFactory = $dealFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_dailydealHelper = $dailydealHelper;
    }

    public function getIdentities()
    {
        return [\Magebuzz\Dailydeal\Model\Deal::CACHE_TAG . '_' . 'product_deal'];
    }
    
    public function getHelper() {
        return $this->_dailydealHelper;
    }

    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }
    
    public function getScopeConfig($path)
    {
        $storeId = $this->getCurrentStoreId();
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    public function getProduct() {
        return $this->_coreRegistry->registry('current_product');
    }
    
    public function getDealForProduct($productId) {
        return $this->_dealFactory->create()->loadByProductId($productId);
    }

}
