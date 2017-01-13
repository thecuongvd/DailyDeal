<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Catalog data helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_CONFIRM_EMAIL = 'dailydeal/subscription/confirm_email_template';
    const XML_PATH_DAILY_NOTIFY_EMAIL = 'dailydeal/subscription/email_template';
    
    const CACHE_TODAY_DEALS_PRODUCT_IDS = 'MB_CACHE_TODAY_DEALS_PRODUCT_IDS';

    protected $_localDeals = [];
    protected $_scopeConfig;
    protected $_storeManager;
    protected $_currency;
    protected $_stockItem;
    protected $_objectManager;
    protected $_date;
    protected $prdImageHelper;
    protected $_urlInterface;
    protected $_customerSession;
    protected $_subscriberFactory;
    protected $_transportBuilder;
    protected $inlineTranslation;
    protected $urlModel;
    protected $logger;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Model\Currency $currency,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Magebuzz\Dailydeal\Model\SubscriberFactory $subscriberFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\UrlFactory $urlFactory,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_storeManager = $storeManager;
        $this->_currency = $currency;
        $this->stockItem = $stockItem;
        $this->_objectManager = $objectManager;
        $this->_date = $date;
        $this->prdImageHelper = $imageHelper;
        $this->_urlInterface = $urlInterface;
        $this->_customerSession = $customerSession;
        $this->_subscriberFactory = $subscriberFactory;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->urlModel = $urlFactory->create();
        $this->logger = $logger;

        parent::__construct($context);
    }
    
    public function getCurrencySymbol() {
        return $this->_currency->getCurrencySymbol();
    }
    
    public function getProductPrice($product) {
        if ($product && $product->getId()) {
            return number_format($product->getPrice(), 2);
        } 
        return '0.00';
    }
    
    public function getFinalProductPrice($product) {
        if ($product && $product->getId()) {
            return number_format($product->getFinalPrice(), 2);
        } 
        return '0.00';
    }
    
    public function getProductQuantity($product) {
        if ($product && $productId = $product->getId()) {
            return $this->stockItem->getStockQty($productId, $product->getStore()->getWebsiteId());
        } 
        return 0;
    }
    
    public function getPriceWithCurrency($price)
    {
        if ($price) {
            return $this->_objectManager->get('Magento\Framework\Pricing\Helper\Data')->currency(number_format($price, 2), true, false);
        }
        return 0;
    }
    
    public function getCurrentTime() {
        return $this->_date->gmtTimestamp() + $this->_date->getGmtOffset();
    }
    
    public function getCurrentUrl() {
        return $this->_urlInterface->getCurrentUrl();
    }

    public function getCustomerId()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_customerSession->getCustomerId();
        }
        return null;
    }

    public function getProductImageUrl($product, $size) {
        $imageSize = 'product_page_image_' . $size;
        if ($size == 'category') {
            $imageSize = 'category_page_list';
        }
        $imageUrl = $this->prdImageHelper->init($product, $imageSize)
            ->keepAspectRatio(TRUE)
            ->keepFrame(FALSE)
            ->getUrl();
        return $imageUrl;
    }

    public function sendSubscriptionEmail($subscriberData) {
        $confirmLink = $this->urlModel->getUrl('dailydeal/subscribe/confirm', ['subscriber_id' => $subscriberData['subscriber_id'], 'confirm_code' => $subscriberData['confirm_code']]);
        $vars = [];
        $vars['customer_name'] = $subscriberData['customer_name'];
        $vars['confirm_link'] = $confirmLink;
        $storeId = $this->_storeManager->getStore()->getId();
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($this->_scopeConfig->getValue(
                    self::XML_PATH_CONFIRM_EMAIL,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId))
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars($vars)
                ->setFrom(['email' => '', 'name' => 'Customer Support'])
                ->addTo($subscriberData['email'], $subscriberData['customer_name'])
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function sendTodayDealEmail() {
        $subscribers = $this->_subscriberFactory->create()->getCollection()->addFieldToFilter('status', \Magebuzz\Dailydeal\Model\Subscriber::STATUS_ENABLED);
        if ($subscribers->getSize() > 0) {
            $dealsLink = $this->urlModel->getUrl('dailydeal');
            $storeId = $this->_storeManager->getStore()->getId();
            foreach ($subscribers->getItems() as $subscriber) {
                $email = $subscriber->getEmail();
                $customerName = $subscriber->getCustomerName();
                $unsubscribeLink = $this->urlModel->getUrl('dailydeal/subscribe/unsubscribe', ['subscriber_id' => $subscriber->getId(), 'confirm_code' => $subscriber->getConfirmCode()]);
                $vars = [];
                $vars['customer_name'] = $customerName;
                $vars['deals_link'] = $dealsLink;
                $vars['unsubscribe_link'] = $unsubscribeLink;
                $this->inlineTranslation->suspend();
                try {
                    $transport = $this->_transportBuilder
                        ->setTemplateIdentifier($this->_scopeConfig->getValue(
                            self::XML_PATH_DAILY_NOTIFY_EMAIL,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId))
                        ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
                        ->setTemplateVars($vars)
                        ->setFrom(['email' => '', 'name' => 'Customer Support'])
                        ->addTo($email, $customerName)
                        ->getTransport();

                    $transport->sendMessage();
                    $this->inlineTranslation->resume();
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }
    
}