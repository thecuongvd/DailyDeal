<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterOrderPlacedSetDealQty implements ObserverInterface {

    protected $_dealFactory;
    protected $_scopeConfig;
    protected $logger;

    public function __construct(
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory, 
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig, 
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_dealFactory = $dealFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    public function execute(Observer $observer) {
        if ($this->getScopeConfig('dailydeal/general/enable')) {
            $orderModel = $observer->getOrder();
            $items = $orderModel->getItemsCollection();
            foreach ($items as $item) {
                $deal = $this->_dealFactory->create()->loadByProductId($item->getProductId());
                if ($deal->getId() && $deal->isAvailable()) {
                    $deal->setSold((int) $deal->getSold() + 1);

                    try {
                        $deal->save();
                    } catch (Exception $e) {
                        $this->logger->critical($e);
                    }
                }
            }
        }
    }

    public function getScopeConfig($path) {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}
