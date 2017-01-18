<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Block;

class Dailydeal extends \Magento\Framework\View\Element\Template
{
    protected $_scopeConfig;
    protected $_storeManager;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
    }

    public function getIdentities()
    {
        return [\Magebuzz\Dailydeal\Model\Deal::CACHE_TAG . '_' . 'dailydeal'];
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

}
