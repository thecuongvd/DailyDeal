<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model\Catalog\ResourceModel;

use Magento\Framework\Model\AbstractModel;

/**
 * Mysql resource
 */
class Product extends \Magento\Catalog\Model\ResourceModel\Product
{
    protected $_dealFactory;
    
    public function __construct(
        \Magento\Eav\Model\Entity\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Factory $modelFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category $catalogCategory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $setFactory,
        \Magento\Eav\Model\Entity\TypeFactory $typeFactory,
        \Magento\Catalog\Model\Product\Attribute\DefaultAttributes $defaultAttributes,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        $data = []
    )
    {
        parent::__construct($context,$storeManager,$modelFactory,$categoryCollectionFactory,$catalogCategory,$eventManager,$setFactory,$typeFactory,$defaultAttributes,$data);
        $this->_dealFactory = $dealFactory;
    }
    
    protected function _beforeDelete(\Magento\Framework\DataObject $object)
    {
        $productId = $object->getId();
        $deal = $this->_dealFactory->create()->loadByProductId($productId);
        if ($deal->getId() && count($deal->getProductIds()) == 1) {
            $deal->delete();
        }
        return parent::_beforeDelete($object);
    }

}
