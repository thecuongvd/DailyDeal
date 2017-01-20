<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Controller\Adminhtml\Deal;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action
{
    protected $_date;
    protected $jsHelper;
    protected $_productFactory;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) 
    {
        parent::__construct($context);
        $this->_date = $date;
        $this->jsHelper = $jsHelper;
        $this->_productFactory = $productFactory;
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
                    throw new \Magento\Framework\Exception\LocalizedException(__('The wrong deal is specified'));
                }
                $data['product_ids'] = $deal->getProductIds();
            }

            //Check if time is valid
            $startTime = strtotime($data['start_time']);
            $endTime = strtotime($data['end_time']);
            if ($startTime >= $endTime) {
                $this->messageManager->addError(__('Start Time must be earlier than End Time'));
                $this->_getSession()->setFormData($data);
                if ($id) {
                    return $resultRedirect->setPath('*/*/edit', ['deal_id' => $id, '_current' => true]);
                } else {
                    return $resultRedirect->setPath('*/*/new', ['_current' => true]);
                }
            }
            
            //Check if add new and product is selected
            if (!$id && empty($data['product_ids'])) {
                $this->messageManager->addError(__('You must select product before saving'));
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
            
            //Process product_ids data and set title when add new
            
            if (!$id) {     
                $data['product_ids'] = array_keys($this->jsHelper->decodeGridSerializedInput($data['product_ids']));
                $product = $this->_productFactory->create();
                $title = '';
                foreach ($data['product_ids'] as $productId) { 
                    $product->load($productId);
                    $title .= $product->getName() . ',';
                }
                $title = rtrim($title, ',');
                $data['title'] = $title;
            }

            $deal->setData($data);
            $this->_eventManager->dispatch(
                'dailydeal_deal_prepare_save', ['deal' => $deal, 'request' => $this->getRequest()]
            );

            try {
                $deal->save();
                
                $this->messageManager->addSuccess(__('You saved this Deal'));
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
                $this->messageManager->addException($e, __('Something went wrong while saving the deal'));
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
