<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductListGetFinalPrice implements ObserverInterface {

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
        $products = $observer->getCollection();
        if ($products->getSize() > 0) {
            foreach ($products as $product) {
                if ($this->getScopeConfig('dailydeal/general/enable')) {
                    $deal = $this->_dealFactory->create()->loadByProductId($product->getId());
                    if ($deal->getId() && $deal->isAvailable()) {
                        $product->setFinalPrice($deal->getPrice());
                        $product->setSpecialPrice($deal->getPrice());
                    }
                } 
            }
        }
    }
    
    public function getScopeConfig($path) {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}
