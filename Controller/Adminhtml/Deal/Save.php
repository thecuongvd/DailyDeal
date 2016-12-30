<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Controller\Adminhtml\Deal;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action
{
    protected $_fileSystem;
    protected $_fileUploaderFactory;
    protected $_logger;
    protected $jsHelper;
    protected $_date;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\Stdlib\DateTime\DateTime $date
    )
    {
        parent::__construct($context);
        $this->_fileSystem = $fileSystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_logger = $logger;
        $this->jsHelper = $jsHelper;
        $this->_date = $date;
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $deal = $this->_objectManager->create('Magebuzz\Dailydeal\Model\Deal');
            $id = $this->getRequest()->getParam('deal_id');
            if ($id) {
                $deal->load($id);
                if ($id != $deal->getId()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong deal is specified.'));
                }
            }

            //Check if time is valid
            $startTime = strtotime($data['start_time']);
            $endTime = strtotime($data['end_time']);
            if ($startTime >= $endTime) {
                $this->messageManager->addError(__('Start Time must be earlier than End Time.'));
                $this->_getSession()->setFormData($data);
                if ($id) {
                    return $resultRedirect->setPath('*/*/edit', ['deal_id' => $id, '_current' => true]);
                } else {
                    return $resultRedirect->setPath('*/*/new', ['_current' => true]);
                }
            }
            
            //Check if add new and product is selected
            if (!$id && empty($data['product_id'])) {
                $this->messageManager->addError(__('You must select a product before saving.'));
                $this->_getSession()->setFormData($data);
                if ($id) {
                    return $resultRedirect->setPath('*/*/edit', ['deal_id' => $id, '_current' => true]);
                } else {
                    return $resultRedirect->setPath('*/*/new', ['_current' => true]);
                }
            }
            
            //Check if quantity of deal is greater than quantity of product
            $prdQty = $data['prd_qty'];
            $dealQty = $data['quantity'];
            if ($dealQty > $prdQty) {
                $this->messageManager->addError(__('Quantity of deal must not greater than quantity of product.'));
                $this->_getSession()->setFormData($data);
                if ($id) {
                    return $resultRedirect->setPath('*/*/edit', ['deal_id' => $id, '_current' => true]);
                } else {
                    return $resultRedirect->setPath('*/*/new', ['_current' => true]);
                }
            }

            //Process time
            $localeDate = $this->_objectManager->get('Magento\Framework\Stdlib\DateTime\TimezoneInterface');
            $data['start_time'] = $localeDate->date($data['start_time'])->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $data['end_time'] = $localeDate->date($data['end_time'])->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            
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
            
            $deal->setData($data);

            $this->_eventManager->dispatch(
                'dailydeal_deal_prepare_save', ['deal' => $deal, 'request' => $this->getRequest()]
            );

            try {
                $deal->save();
                $this->messageManager->addSuccess(__('You saved this Deal.'));
                $this->_objectManager->get('Magento\Backend\Model\Session')->setFormData(false);
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['deal_id' => $deal->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the deal.'));
            }

            $this->_getSession()->setFormData($data);
            if ($id) {
                return $resultRedirect->setPath('*/*/edit', ['deal_id' => $id, '_current' => true]);
            } else {
                return $resultRedirect->setPath('*/*/new', ['_current' => true]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Magebuzz_Dailydeal::save');
    }

}
