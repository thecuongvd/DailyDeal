<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model;

class Subscriber extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    
    const XML_PATH_SUBSCRIPTION_EMAIL = 'dailydeal/subscription/email_template';
    const XML_PATH_DEAL_NOTIFY_EMAIL = 'dailydeal/subscription/deal_notity_email_template';
    
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'dailydeal_subscriber';

    protected $_cacheTag = 'dailydeal_subscriber';

    /**
     * Prefix of model name
     *
     * @var string
     */
    protected $_subscriberPrefix = 'dailydeal_subscriber';
    
    protected $_storeManager;
    protected $_transportBuilder;
    protected $inlineTranslation;
    protected $urlModel;
    protected $logger;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\UrlFactory $urlFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->urlModel = $urlFactory->create();
        $this->logger = $logger;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Magebuzz\Dailydeal\Model\ResourceModel\Subscriber');
    }
    
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_ENABLED => __('Enabled'), 
            self::STATUS_DISABLED => __('Disabled')
                ];
    }
    
    public function sendSubscriptionEmail($subscriberData)
    {
        $dailydealLink = $this->urlModel->getUrl('dailydeal');
        $confirmLink = $this->urlModel->getUrl('dailydeal/subscribe/confirm', ['subscriber_id' => $subscriberData['subscriber_id'], 'confirm_code' => $subscriberData['confirm_code']]);
        $unsubscribeLink = $this->urlModel->getUrl('dailydeal/subscribe/unsubscribe', ['subscriber_id' => $subscriberData['subscriber_id'], 'confirm_code' => $subscriberData['confirm_code']]);
        $logo_url = '';
        $lolg_alt = '';
        $vars = [];
        $vars['customer_name'] = $subscriberData['customer_name'];
        $vars['dailydeal_link'] = $dailydealLink;
        $vars['confirm_link'] = $confirmLink;
        $vars['unsubscibe_link'] = $unsubscribeLink;
        $vars['logo_url'] = $logo_url;
        $vars['logo_alt'] = $lolg_alt;
        
        $storeId = $this->_storeManager->getStore()->getId();
        $this->inlineTranslation->suspend();
        try {
            $transport = $this->_transportBuilder
                ->setTemplateIdentifier($this->_scopeConfig->getValue(
                    self::XML_PATH_SUBSCRIPTION_EMAIL,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId))
                ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
                ->setTemplateVars($vars)
                ->setFrom(['email' => '', 'name' => 'Subscription'])
                ->addTo($subscriberData['email'])
                ->getTransport();

            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }

    public function sendNewDealEmail($deal) {
        $product = $deal->getProduct();
        $dealLink = $product->getProductUrl();
        $storeId = $this->_storeManager->getStore()->getId();
        
        $subscribers = $this->getCollection()->addAttributeToFilter('status', '1');
        if ($subscribers->getSize() > 0) {
            foreach ($subscribers->getItems() as $subscriber) {
                $customerName = $subscriber->getCustomerName();
                $email = $subscriber->getEmail();
                $unsubscribeLink = $this->urlModel->getUrl('dailydeal/subscribe/unsubscribe', ['subscriber_id' => $subscriber->getId(), 'confirm_code' => $subscriber->getConfirmCode()]);
                $this->inlineTranslation->suspend();
                try {
                    $transport = $this->_transportBuilder
                        ->setTemplateIdentifier($this->_scopeConfig->getValue(
                            self::XML_PATH_SUBSCRIPTION_EMAIL,
                            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                            $storeId))
                        ->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $storeId])
                        ->setTemplateVars(['customer_name' => $customerName, 'deal_link' => $dealLink, 'unsubscribe_link' => $unsubscribeLink])
                        ->setFrom(['email' => '', 'name' => 'Subscription'])
                        ->addTo($email)
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
