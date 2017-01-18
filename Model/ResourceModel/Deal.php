<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;

/**
 * Mysql resource
 */
class Deal extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected $_dealStoreTable;
    protected $_productFactory;
    protected $_date;
    protected $_storeManager;
    protected $_dailydealHelper;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Catalog\Model\ProductFactory $prductFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        $resourcePrefix = null
    )
    {
        parent::__construct($context, $resourcePrefix);
        $this->_productFactory = $prductFactory;
        $this->_date = $date;
        $this->_storeManager = $storeManager;
        $this->_dailydealHelper = $dailydealHelper;
    }
    
    protected function _construct()
    {
        $this->_init('dailydeal_deal', 'deal_id');
        $this->_dealStoreTable = $this->getTable('dailydeal_deal_store');
        
    }
    
    public function getStoreIds($dealId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_dealStoreTable), 'store_id')
            ->where('deal_id = ?', $dealId);
        return $this->getConnection()->fetchCol($select);
    }
    
    public function getProduct($productId) {
        if ($productId) {
            $product = $this->_productFactory->create()->load($productId);
            if ($product->getId()) {
                return $product;
            }
        }
        return null;
    }
    
    protected function _afterLoad(AbstractModel $object)
    {
        parent::_afterLoad($object);
        if (!$object->getId()) {   //if create new
            return $this;
        }

        //Process time
        if ($object->hasStartTime()) {
            $startTime = date('Y-m-d H:i:s', $this->_date->timestamp($object->getStartTime()) + $this->_date->getGmtOffset());
            $object->setStartTime($startTime);
        }
        if ($object->hasEndTime()) {
            $endTime = date('Y-m-d H:i:s', $this->_date->timestamp($object->getEndTime()) + $this->_date->getGmtOffset());
            $object->setEndTime($endTime);
        }

        if ($object->getId()) {
            $object->setStores($this->getStoreIds((int)$object->getId()));
            $object->setProduct($this->getProduct((int)$object->getProductId()));
        }
        return $this;
    }
    
    protected function _beforeSave(AbstractModel $object)
    {
        if ($object->hasData('stores') && !is_array($object->getStores())) {
            $object->setStores([$object->getStores()]);
        }
        return parent::_beforeSave($object);
    }

    protected function _afterSave(AbstractModel $object)
    {
        $connection = $this->getConnection();
        $condition = ['deal_id = ?' => $object->getId()];
        $connection->delete($this->_dealStoreTable, $condition);
        
        //Save Deal Store
        $stores = $object->getStores();
        if (!empty($stores)) {
            $insertedStoreIds = [];
            foreach ($stores as $storeId) {
                if (in_array($storeId, $insertedStoreIds)) {
                    continue;
                }

                $insertedStoreIds[] = $storeId;
                $storeInsert = ['store_id' => $storeId, 'deal_id' => $object->getId()];
                $connection->insert($this->_dealStoreTable, $storeInsert);
            }
        }
        
        //Refresh cache for deals
        $this->_dailydealHelper->refreshLocalDeals();
        return $this;
    }
    
    protected function _afterDelete(AbstractModel $object) {
        $this->_dailydealHelper->refreshLocalDeals();
        return $this;
    }
    
    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }
    
    public function getDealByProductId($productId) {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->getTable('dailydeal_deal')), 'deal_id')
            ->where('product_id = ?', $productId);
        return $this->getConnection()->fetchOne($select);
    }

    public function getTodayDealsEndTime() {
        $productTable = $this->getTable('catalog_product_entity');
        $dailydealTable = $this->getTable('dailydeal_deal');
        $dealStoreTable = $this->getTable('dailydeal_deal_store');
        $storeIds = [0, $this->getCurrentStoreId()];

        $select = $this->getConnection()->quoteInto("
            SELECT d.`product_id`, d.`end_time`
            FROM $dailydealTable as d
            INNER JOIN $dealStoreTable as s
                ON s.`deal_id` = d.`deal_id`
            INNER JOIN $productTable as p
                ON d.`product_id` = p.`entity_id`
            WHERE (d.`start_time` < now() AND d.`end_time` > now())
                AND d.`status` = " . \Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED . "
                AND (d.`quantity` - d.`sold`) > 0
                AND s.`store_id` IN (?)
            GROUP BY d.`product_id`
            ORDER BY d.`price` ASC
        ", $storeIds);

        return $this->getConnection()->fetchAll($select);
    }
}
