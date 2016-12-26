<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Controller\Adminhtml\Deal;

class Getprdinfo extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $_productFactory;
    protected $_dailydealHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_productFactory = $productFactory;
        $this->_dailydealHelper = $dailydealHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        if ($this->getRequest()->isAjax()) {
            $dealProductId = $this->getRequest()->getPost('deal_product_id');
            if ($dealProductId) {
                $product = $this->_productFactory->create()->load($dealProductId);
                $prdName = $product->getName();
                $prdPrice = $this->_dailydealHelper->getProductPrice($product);
                $prdQty = $this->_dailydealHelper->getProductQuantity($product);
                $result = ['name' => $prdName, 'price' =>$prdPrice, 'qty' => $prdQty];
                return $this->resultJsonFactory->create()->setJsonData(json_encode($result));
            }
        }
        
    }

}
