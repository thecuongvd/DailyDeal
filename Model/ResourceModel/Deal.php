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

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Catalog\Model\ProductFactory $prductFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        $resourcePrefix = null
    )
    {
        parent::__construct($context, $resourcePrefix);
        $this->_productFactory = $prductFactory;
        $this->_date = $date;
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
        
        return $this;
    }
    
    public function getDealByProductId($productId) {
        $select = $this->getConnection()->select()->from(
            $this->getTable($this->getTable('dailydeal_deal')), 'deal_id')
            ->where('product_id = ?', $productId);
        return $this->getConnection()->fetchOne($select);
    }

}
