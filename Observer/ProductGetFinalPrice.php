<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductGetFinalPrice implements ObserverInterface {

    protected $_dealFactory;
    protected $_scopeConfig;

    public function __construct(
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->_dealFactory = $dealFactory;
        $this->_scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer) {
        if ($this->getScopeConfig('dailydeal/general/enable')) {
            $product = $observer->getProduct();
            $deal = $this->_dealFactory->create()->loadByProductId($product->getId());
            if ($deal->getId()) {
                $startTime = date('Y-m-d H:i:s', strtotime($deal->getStartTime()) - 86400);
                $endTime = date('Y-m-d H:i:s', strtotime($deal->getEndTime()) - 86400);
                $product->setData('special_from_date', $startTime);
                $product->setData('special_to_date', $endTime);
            }
        }
    }
    
    public function getScopeConfig($path) {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}
