<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Block;

class Sidebar extends \Magento\Catalog\Block\Product\AbstractProduct
{
    protected $_dealFactory;
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_dailydealHelper;
    protected $urlHelper;
    protected $_formKey;

    public function __construct(
//        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Block\Product\Context $context,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Data\Form\FormKey $formKey,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_dealFactory = $dealFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_dailydealHelper = $dailydealHelper;
        $this->urlHelper = $urlHelper;
        $this->_formKey = $formKey;
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
            ->addFieldToFilter('status', \Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED)
            ->setTodayFilter()
            ->setStoreFilter($storeIds)
            ->setOrder('start_time', 'ASC');
        return $collection;
    }

    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();

    }
    
    public function getAddToCartPostParams(\Magento\Catalog\Model\Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'url' => $url,
            'product' => $product->getEntityId(),
            \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlHelper->getEncodedUrl($url),
            'formkey' => $this->_formKey->getFormKey()
        ];
    }
    
}
