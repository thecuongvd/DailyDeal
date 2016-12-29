<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductListGetFinalPrice implements ObserverInterface {

    protected $_dealFactory;

    public function __construct(
    \Magebuzz\Dailydeal\Model\DealFactory $dealFactory
    ) {
        $this->_dealFactory = $dealFactory;
    }

    public function execute(Observer $observer) {
        $products = $observer->getCollection();
        if ($products->getSize() > 0) {
            foreach ($products as $product) {
                $deal = $this->_dealFactory->create()->loadByProductId($product->getId());
                if ($deal->getId() && $deal->isAvailable()) {
                    $product->setFinalPrice($deal->getPrice());
                }
            }
        }
    }

}
