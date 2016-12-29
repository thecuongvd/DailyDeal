<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class AfterOrderPlacedSetDealQty implements ObserverInterface {

    protected $_dealFactory;
    protected $logger;

    public function __construct(
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_dealFactory = $dealFactory;
        $this->logger = $logger;
    }

    public function execute(Observer $observer) {
        $orderModel = $observer->getOrder();
        $items = $orderModel->getItemsCollection();
        foreach ($items as $item) {
            $deal = $this->_dealFactory->create()->loadByProductId($item->getProductId());
            if ($deal->getId() && $deal->isAvailable()) {
                $deal->setSold((int) $deal->getSold() + 1);
//                $remainQuantity = $deal->getQuantity() - $deal->getSold();
//                if ($remainQuantity <= 0) {
//                    $deal->setStatus('soldout');
//                }
                try {
                    $deal->save();
                } catch (Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

}
