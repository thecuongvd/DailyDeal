<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductGetFinalPrice implements ObserverInterface {

    protected $_dealFactory;

    public function __construct(
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory
    ) {
        $this->_dealFactory = $dealFactory;
    }

    public function execute(Observer $observer) {
        $product = $observer->getProduct();
        $deal = $this->_dealFactory->create()->loadByProductId($product->getId());
        if ($deal->getId() && $deal->isNotEnded()) {
            $product->setFinalPrice($deal->getPrice());
        }
    }

}
