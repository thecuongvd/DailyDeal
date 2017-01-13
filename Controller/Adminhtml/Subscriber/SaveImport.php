<?php

/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Dailydeal\Controller\Adminhtml\Subscriber;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;

class SaveImport extends \Magento\Backend\App\Action {

    protected $_fileSystem;
    protected $_fileUploaderFactory;
    protected $_logger;
    protected $jsHelper;
    protected $_date;
    protected $csvProcessor;
    protected $_subscriberFactory;

    public function __construct(
        Action\Context $context, 
        \Magento\Framework\Filesystem $fileSystem, 
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory, 
        \Psr\Log\LoggerInterface $logger, \Magento\Backend\Helper\Js $jsHelper, 
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magebuzz\Dailydeal\Model\SubscriberFactory $subscriberFactory
    ) {
        parent::__construct($context);
        $this->_fileSystem = $fileSystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_logger = $logger;
        $this->jsHelper = $jsHelper;
        $this->_date = $date;
        $this->csvProcessor = $csvProcessor;
        $this->_subscriberFactory = $subscriberFactory;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute() {
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($this->getRequest()->getPostValue()) {
            $importFile = $this->getRequest()->getFiles('subscriber_csv_file');
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
                $model = $this->_subscriberFactory->create();
                $collection = $model->getCollection();
                $subscribersImport = [];
                while (--$count > 0) {
                    $currentData = $importData[$count];
                    $data = array_combine($keys, $currentData);
                    $data['status'] = \Magebuzz\Dailydeal\Model\Subscriber::STATUS_ENABLED;
                    $data['confirm_code'] = (string) time();
                    $model->setData($data)->save();
                    $flag = true;
                    $i++;
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                return $resultRedirect->setPath('*/*/');
            }
            
            if ($flag) {
                $this->messageManager->addSuccess(__('Total of '.$i.' subscriber(s) were successfully imported'));
            } else {
                $this->messageManager->addError('There is no item to import');
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed() {
        return $this->_authorization->isAllowed('Magebuzz_Dailydeal::saveImport');
    }

}
