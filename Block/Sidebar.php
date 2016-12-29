<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Block;

class Sidebar extends \Magento\Framework\View\Element\Template
{

    protected $_dealFactory;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_dailydealHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_dealFactory = $dealFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_dailydealHelper = $dailydealHelper;
    }

    public function getIdentities()
    {
        return [\Magebuzz\Dailydeal\Model\Deal::CACHE_TAG . '_' . 'sidebar'];
    }
    
    public function getHelper() {
        return $this->_dailydealHelper;
    }

    public function isShowOnSidebar()
    {
        if ($this->getScopeConfig('dailydeal/general/show_on_sidebar')) {
            return true;
        } 
        return false;
    }
    
    public function isRoundSaving() {
        if ($this->getScopeConfig('dailydeal/general/is_round_saving')) {
            return true;
        } 
        return false;
    }

    public function getScopeConfig($path)
    {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getTodayDealCollection()
    {
        $storeIds = [0, $this->getCurrentStoreId()];
        $collection = $this->_dealFactory->create()->getCollection()
            ->addFieldToFilter('status', 1)
            ->setTodayFilter()
            ->setStoreFilter($storeIds)
            ->setOrder('start_time', 'ASC');
        return $collection;
    }

    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();

    }
    
}
