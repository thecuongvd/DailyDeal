<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Controller\Subscribe;

use Magento\Framework\App\Action\Action;

class Subscribe extends Action
{
    protected $_subscriberFactory;
    protected $_dailydealHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magebuzz\Dailydeal\Model\SubscriberFactory $subscriberFactory,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper
    )
    {
        $this->_subscriberFactory = $subscriberFactory;
        $this->_dailydealHelper = $dailydealHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $subscriber = $this->_subscriberFactory->create();
        $email = $this->getRequest()->getParam('email');
        if (!$subscriber->isExistedEmail($email)) {
            try {
                $customerName = $this->getRequest()->getParam('customer_name');
                $confirmCode = time();
                $subscriberData = ['customer_name' => $customerName, 'email' => $email, 
                    'confirm_code' => (string)$confirmCode, 'status' => \Magebuzz\Dailydeal\Model\Subscriber::STATUS_DISABLED];

                $subscriber->setData($subscriberData)->save();

                $subscriberData['subscriber_id'] = $subscriber->getId();
                $this->_dailydealHelper->sendSubscriptionEmail($subscriberData);
                $this->messageManager->addSuccess(__('An email has sent to you. Please check email and confirm.'));

            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('There was a problem when subscribing. Please try again.'));
            } finally {
                return $resultRedirect->setPath('dailydeal');
            }
        } else {
            $this->messageManager->addError(__('This email has been used to subscriber. Please use other email and try again!'));
            return $resultRedirect->setPath('dailydeal');
        }

    }
    
    public function randomSequence($length=32)
    {
        $id = '';
        $par = array();
        $char = array_merge(range('a','z'),range(0,9));
        $charLen = count($char)-1;
        for ($i=0;$i<$length;$i++){
            $disc = mt_rand(0, $charLen);
            $par[$i] = $char[$disc];
            $id = $id.$char[$disc];
        }
        return $id;
    }
}
