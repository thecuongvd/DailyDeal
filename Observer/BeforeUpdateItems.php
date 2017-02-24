<?php

namespace Magebuzz\Dailydeal\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class BeforeUpdateItems implements ObserverInterface {

    protected $_dealFactory;
    protected $_scopeConfig;
    protected $cart;
    protected $_messageManager;
    protected $_urlInterface;

    public function __construct(
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Message\Manager $messageManager,
        \Magento\Framework\UrlInterface $urlInterface
    ) {
        $this->_dealFactory = $dealFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->cart = $cart;
        $this->_messageManager = $messageManager;
        $this->_urlInterface = $urlInterface;
    }

    public function execute(Observer $observer) {
        if ($this->getScopeConfig('dailydeal/general/enable')) {
            $updateAction = $observer->getRequest()->getParam('update_cart_action');
            if ($updateAction != 'empty_cart') {
                $cartData = $observer->getRequest()->getParam('cart');
                $isValid = true;
                foreach ($cartData as $key=>$data) {
                    $productId = $this->cart->getQuote()->getItemById($key)->getProductId();
                    $deal = $this->_dealFactory->create()->loadByProductId($productId);
                    if ($deal->getId() && $deal->isAvailable()) {
                        $dealRemain = $deal->getQuantity() - $deal->getSold();
                        if ($data['qty'] > $dealRemain) {
                            $isValid = false;
                            break;
                        }
                    }
                }
                if (!$isValid) {
                    header("Location: " . $this->_urlInterface->getUrl('checkout/cart'));
                    $this->_messageManager->addError(__("We can't update the shopping cart because quantity you want to update is not valid."));
                    die();
                }
            }
        }
    }
    
    public function getScopeConfig($path) {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

}
