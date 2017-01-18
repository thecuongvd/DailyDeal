<?php

/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Dailydeal\Controller\Adminhtml\Deal;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;

class SaveImport extends \Magento\Backend\App\Action {

    protected $csvProcessor;
    protected $_dealFactory;
    protected $_productFactory;
    protected $_linkFactory;
    protected $_productStatus;
    protected $_objectManager;
    protected $_date;

    public function __construct(
        Action\Context $context, 
        \Magento\Framework\File\Csv $csvProcessor,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\LinkFactory $linkFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    ) {
        parent::__construct($context);
        $this->csvProcessor = $csvProcessor;
        $this->_dealFactory = $dealFactory;
        $this->_productFactory = $productFactory;
        $this->_linkFactory = $linkFactory;
        $this->_productStatus = $productStatus;
        $this->_objectManager = $objectManager;
        $this->_date = $date;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->getRequest()->getPostValue()) {
            $importFile = $this->getRequest()->getFiles('deal_csv_file');
            if (!isset($importFile['tmp_name'])) {
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
                return $resultRedirect->setPath('*/*/');
            }
        
            $flag = false;
            $i = 0;
            try {
                $importData = $ratesRawData = $this->csvProcessor->getData($importFile['tmp_name']);
                $keys = $importData[0];
                foreach ($keys as $key => $value) {
                    $keys[$key] = str_replace(' ', '_', strtolower($value));
                }
                $count = count($importData);
                $deal = $this->_dealFactory->create();
                $product = $this->_productFactory->create();
                $validPrdIds = $this->getValidProductIds();
                $localeDate = $this->_objectManager->get('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
                $dealsImport = [];
                while (--$count > 0) {
                    $currentData = $importData[$count];
                    $data = array_combine($keys, $currentData);
                    $data['status'] = \Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED;
                    
                    //Check number type fields
                    if (!is_numeric($data['price']) || !is_numeric($data['quantity']) || !is_numeric($data['sold'])) {
                        continue;
                    }
                    
                    //Check if time is valid
                    $startTime = strtotime($data['start_time']);
                    $endTime = strtotime($data['end_time']);
                    if ($startTime && $endTime && $startTime >= $endTime) {
                        continue;
                    }
                    
                    //Process store
                    if (empty($data['stores'])) {
                        continue;
                    } else {
                        $data['stores'] = explode($data['stores']); 
                    } 
                    
                    //Process product
                    if (empty($data['product_id'])) {
                        continue;
                    } else {
                        if (in_array($data['product_id'], $validPrdIds)) {
                            $data['title'] = $product->load($data['product_id'])->getName();
                        } else {
                            continue;
                        }
                    }
                   
                    //Process time
                    $localStartTime = $data['start_time'];
                    $localEndTime = $data['end_time']; 
                    $data['start_time'] = date('Y-m-d H:i:s', $this->_date->timestamp($data['start_time']) - $this->_date->getGmtOffset());
                    $data['end_time'] = date('Y-m-d H:i:s', $this->_date->timestamp($data['end_time']) - $this->_date->getGmtOffset());

                    //Set progress_status
                    $nowTime = time();
                    $startTime = strtotime($data['start_time']);
                    $endTime = strtotime($data['end_time']);
                    $progressStatus = '';
                    if ($startTime > $nowTime) {
                        $progressStatus = 'coming';
                    } else if ($startTime <= $nowTime && $nowTime <= $endTime) {
                        $progressStatus = 'running';
                    } else if ($endTime < $nowTime) {
                        $progressStatus = 'ended';
                    }
                    $data['progress_status'] = $progressStatus;
                    
                    $deal->setData($data)->save();
                    //Save Special Price for Product
                    $product = $deal->load($deal->getId())->getProduct();
                    $product->setSpecialPrice($data['price']);
                    $product->setSpecialFromDate($localStartTime);
                    $product->setSpecialFromDateIsFormated(true);
                    $product->setSpecialToDate($localEndTime);
                    $product->setSpecialToDateIsFormated(true);
                    $product->save();
                    $flag = true;
                    $i++;
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
            
            if ($flag) {
                $this->messageManager->addSuccess(__('Total of '.$i.' deal(s) were successfully imported'));
            } else {
                $this->messageManager->addError('There is no item to import');
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
    
    protected function getValidProductIds()
    {
        $collection = $this->_linkFactory->create()->getProductCollection();
                                                               
        $associatedProductIds = [];
        $deals = $this->_dealFactory->create()->getCollection();
        foreach ($deals->getItems() as $deal) {
            $associatedProductIds[] = $deal->getProductId();
        }
        
        if ($associatedProductIds) {
            $collection->addFieldToFilter('entity_id', ['nin' => $associatedProductIds]);
        }
        $excludedPrdType = ['configurable', 'bundle', 'grouped'];
        $collection->addAttributeToFilter('type_id', ['nin' => $excludedPrdType]);
        $collection->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
        $collection->addAttributeToFilter('is_deal', 1);
        $ids = [];
        foreach ($collection->getItems() as $product) {
            $ids[] = $product->getId();
        }
        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed() {
        return $this->_authorization->isAllowed('Magebuzz_Dailydeal::saveImport');
    }

}
