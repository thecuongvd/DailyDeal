<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model;

class Subscriber extends \Magento\Framework\Model\AbstractModel
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    
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

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [])
    {
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
    
    public function isExistedEmail($email) {
        $collection = $this->getCollection()
                ->addFieldToFilter('email', $email)
                ->addFieldToFilter('status', self::STATUS_ENABLED);
        if ($collection->getSize() > 0) {
            return true;
        } else {
            return false;
        }
    }

}
