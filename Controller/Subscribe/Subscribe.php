<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Controller\Subscribe;

use Magento\Framework\App\Action\Action;

class Subscribe extends Action
{
    protected $_subscriberFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magebuzz\Dailydeal\Model\SubscriberFactory $subscriberFactory
    )
    {
        $this->_subscriberFactory = $subscriberFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $customerName = $this->getRequest()->getParam('customer_name');
            $email = $this->getRequest()->getParam('email');
            $confirmCode = time();
            $subscriberData = ['customer_name' => $customerName, 'email' => $email, 'confirm_code' => (string)$confirmCode, 'status' => '0'];
            $subscriber = $this->_subscriberFactory->create();
            $subscriber->setData($subscriberData)->save();
            
            $subscriberData['subscriber_id'] = $subscriber->getId();
            $subscriber->sendSubscriptionEmail($subscriberData);
            $this->messageManager->addSuccess(__('An email has sent to you. Please check email and confirm.'));
            
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('There was a problem when subscribing. Please try again.'));
        } finally {
            return $resultRedirect->setPath('dailydeal');
        }

    }
}
