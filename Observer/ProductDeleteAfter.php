<?php
namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;


class ProductDeleteAfter implements ObserverInterface
{

    protected $_dealFactory;

    public function __construct(
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory
    )
    {
        $this->_dealFactory = $dealFactory;
    }

    public function execute(Observer $observer)
    {
        $productId = (int)$observer->getProduct()->getId();
        $deal = $this->_dealFactory->create();
        $dealId = $deal->getResource()->getDealByProductId($productId);
        if ($dealId) {
            $deal->load($dealId)->delete();
        }
    }
}