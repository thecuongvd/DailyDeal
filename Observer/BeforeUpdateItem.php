<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BeforeUpdateItem implements ObserverInterface {

    protected $_dealFactory;
    protected $_scopeConfig;
    protected $_messageManager;
    protected $_urlInterface;

    public function __construct(
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->_dealFactory = $dealFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
        $this->_urlInterface = $urlInterface;
    }

    public function execute(Observer $observer) {
        if ($this->getScopeConfig('dailydeal/general/enable')) {
            $addedItemId = $observer->getRequest()->getParam('product');
            if ($addedItemId) {
                $deal = $this->_dealFactory->create()->loadByProductId($addedItemId);
                if ($deal->getId() && $deal->isAvailable()) {
                    $dealRemain = $deal->getQuantity() - $deal->getSold();
                
                    $addedQty = $observer->getRequest()->getParam('qty');
                    if ($addedQty > $dealRemain) {
                        $prep = ($dealRemain > 1) ? 'are' : 'is';
                        $dealText = ($dealRemain > 1) ? 'deals' : 'deal';
                        header("Location: " . $this->_urlInterface->getUrl('checkout/cart'));
                        $this->_messageManager->addError(__("This product is in deal and there $prep $dealRemain $dealText left."));
                        die();
                    }
                }
            }
        }
    }
    
    public function getScopeConfig($path) {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}
