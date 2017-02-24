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
    protected $_dealProductTable;
    protected $_productFactory;
    protected $_dealFactory;
    protected $_date;
    protected $_storeManager;
    protected $_dailydealHelper;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Catalog\Model\ProductFactory $prductFactory,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        $resourcePrefix = null
    )
    {
        parent::__construct($context, $resourcePrefix);
        $this->_productFactory = $prductFactory;
        $this->_dealFactory = $dealFactory;
        $this->_date = $date;
        $this->_storeManager = $storeManager;
        $this->_dailydealHelper = $dailydealHelper;
    }
    
    protected function _construct()
    {
        $this->_init('dailydeal_deal', 'deal_id');
        $this->_dealStoreTable = $this->getTable('dailydeal_deal_store');
        $this->_dealProductTable = $this->getTable('dailydeal_deal_product');
        
    }
    
    public function getStoreIds($dealId)
    {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_dealStoreTable), 'store_id')
            ->where('deal_id = ?', $dealId);
        return $this->getConnection()->fetchCol($select);
    }
    
    public function getProductIds($dealId) {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->_dealProductTable), 'product_id')
            ->where('deal_id = ?', $dealId);
        return $this->getConnection()->fetchCol($select);
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
            $object->setProductIds($this->getProductIds((int)$object->getId()));
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
        
        //Save Deal Stores
        $condition = ['deal_id = ?' => $object->getId()];
        $connection->delete($this->_dealStoreTable, $condition);
        $stores = $object->getStores();
        if (!empty($stores)) {
            $insertedStoreIds = [];
            $fullStoreIds = $this->getAllStoreIds();
            foreach ($stores as $storeId) {
                if (in_array($storeId, $insertedStoreIds) || !in_array((int)$storeId, $fullStoreIds)) {
                    continue;
                }
                $insertedStoreIds[] = $storeId;
                $storeInsert = ['store_id' => $storeId, 'deal_id' => $object->getId()];
                $connection->insert($this->_dealStoreTable, $storeInsert);
            }
        }
        
        //Save Deal Products
        $productIds = $object->getProductIds();
        if ($object->isObjectNew() && !empty($productIds)) { //When create new and product is selected
            $insertedProductIds = [];
            foreach ($productIds as $productId) {
                if (in_array($productId, $insertedProductIds)) {
                    continue;
                }
                $insertedProductIds[] = $productId;
                $insert = ['product_id' => $productId, 'deal_id' => $object->getId()];
                $connection->insert($this->_dealProductTable, $insert);
            }
        }
        
        //Save Special Price for Product
        $deal = $this->_dealFactory->create()->load($object->getId()); 
        if ($deal->isNotEnded()) {
            $localStartTime = date('Y-m-d H:i:s', $this->_date->timestamp($object->getStartTime()) + $this->_date->getGmtOffset());
            $localEndTime = date('Y-m-d H:i:s', $this->_date->timestamp($object->getEndTime()) + $this->_date->getGmtOffset());
            foreach ($productIds as $id) {
                $product = $this->_productFactory->create()->load($id);
                $product->setSpecialPrice($object->getPrice());
                $product->setSpecialFromDate($localStartTime);
                $product->setSpecialFromDateIsFormated(true);
                $product->setSpecialToDate($localEndTime);
                $product->setSpecialToDateIsFormated(true);
                $product->setStoreId(0);
                $product->save();
            }
        }
        
        //Refresh cache for deals
        $this->_dailydealHelper->refreshLocalDeals();
        return $this;
    }
    
    protected function _afterDelete(AbstractModel $object) {
        //Save Special Price for Product
        $productIds = $this->getProductIds((int)$object->getId());
        foreach ($productIds as $id) {
            $product = $this->_productFactory->create()->load($id);
            if ($product->getId()) {
                $product->setSpecialPrice(null);
                $product->setSpecialFromDate(null);
                $product->setSpecialFromDateIsFormated(true);
                $product->setSpecialToDate(null);
                $product->setSpecialToDateIsFormated(true);
                $product->setStoreId(0);
                $product->save();
            }
        }
        
        //Delete product_deal association
        $condition = ['deal_id = ?' => $object->getId()];
        $connection = $this->getConnection();
        $connection->delete($this->_dealProductTable, $condition);
        
        //Refresh cache for deals
        $this->_dailydealHelper->refreshLocalDeals();
        return parent::_afterDelete($object);
    }
    
    public function deleteAssociations($productId) {
        if ($productId) {
            $condition = ['product_id = ?' => $productId];
            $connection = $this->getConnection();
            $connection->delete($this->_dealProductTable, $condition);
        }
    }


    public function getCurrentStoreId()
    {
        return $this->_storeManager->getStore(true)->getId();
    }
    
    public function getAllStoreIds() {
        $stores = $this->_storeManager->getStores();
        $ids = array_keys($stores);
        array_unshift($ids, 0); 
        return $ids;
    } 

    public function getDealByProductId($productId) {
        $dailydealTable = $this->getTable('dailydeal_deal');
        $dealProductTable = $this->getTable('dailydeal_deal_product');
        $dealStoreTable = $this->getTable('dailydeal_deal_store');
        $storeIds = ['0', $this->getCurrentStoreId()];

        $select = $this->getConnection()->quoteInto("
            SELECT d.`deal_id`
            FROM $dailydealTable as d
            INNER JOIN $dealStoreTable as s
                ON s.`deal_id` = d.`deal_id`
            INNER JOIN $dealProductTable as dp
                ON d.`deal_id` = dp.`deal_id`
                AND s.`store_id` IN (?)
                AND dp.product_id = $productId
            GROUP BY d.`deal_id`
        ", $storeIds);
        return $this->getConnection()->fetchOne($select);
    }

    public function getTodayDealsEndTime() {
        $productTable = $this->getTable('catalog_product_entity');
        $dailydealTable = $this->getTable('dailydeal_deal');
        $dealProductTable = $this->getTable('dailydeal_deal_product');
        $dealStoreTable = $this->getTable('dailydeal_deal_store');
        $storeIds = [0, $this->getCurrentStoreId()];

        $select = $this->getConnection()->quoteInto("
            SELECT dp.`product_id`, d.`end_time`
            FROM $dailydealTable as d
            INNER JOIN $dealStoreTable as s
                ON s.`deal_id` = d.`deal_id`
            INNER JOIN $dealProductTable as dp
                ON d.`deal_id` = dp.`deal_id`
            INNER JOIN $productTable as p
                ON dp.`product_id` = p.`entity_id`
            WHERE (d.`start_time` < now() AND d.`end_time` > now())
                AND d.`status` = " . \Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED . "
                AND (d.`quantity` - d.`sold`) > 0
                AND s.`store_id` IN (?)
            GROUP BY dp.`product_id`
            ORDER BY d.`price` ASC
        ", $storeIds);

        return $this->getConnection()->fetchAll($select);
    }
    

}
