<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model;

class Deal extends \Magento\Framework\Model\AbstractModel
{

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    
    const XML_PATH_SUBSCRIPTION_EMAIL = 'dailydeal/subscription/email_template';
    /**
     * CMS page cache tag
     */
    const CACHE_TAG = 'dailydeal_deal';

    protected $_cacheTag = 'dailydeal_deal';

    /**
     * Prefix of model name
     *
     * @var string
     */
    protected $_dealPrefix = 'dailydeal_deal';

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $_storeManager;
    protected $_scopeConfig;
    protected $_transportBuilder;
    protected $inlineTranslation;
    protected $urlModel;
    protected $_productFactory;
    protected $_stockItem;
    protected $_date;
    protected $_dailydealHelper;
    protected $_formKey;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\UrlFactory $urlFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\CatalogInventory\Api\StockStateInterface $stockItem,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Psr\Log\LoggerInterface $logger,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
        $this->_storeManager = $storeManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->urlModel = $urlFactory->create();
        $this->_productFactory = $productFactory;
        $this->stockItem = $stockItem;
        $this->_date = $date;
        $this->_dailydealHelper = $dailydealHelper;
        $this->_formKey = $formKey;
        $this->logger = $logger;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Magebuzz\Dailydeal\Model\ResourceModel\Deal');
    }
    
    public function getAvailableStatuses()
    {
        return [
            self::STATUS_ENABLED => __('Enabled'), 
            self::STATUS_DISABLED => __('Disabled')
                ];
    }
    
    public function getProduct() {
        $product = $this->_productFactory->create()->load($this->getProductId());
        if ($product->getId()) {
            return $product;
        }
        return null;
    }

    public function getProductPrice()
    {
        $product = $this->getProduct();
        if ($product && $product->getId()) {
            return number_format($product->getPrice(), 2);
        } 
        return '0.00';
    }
    
    public function getProductQty()
    {
        $product = $this->getProduct();
        if ($product && $productId = $product->getId()) {
            return $this->stockItem->getStockQty($productId, $product->getStore()->getWebsiteId());
        } 
        return 0;
    }

}
