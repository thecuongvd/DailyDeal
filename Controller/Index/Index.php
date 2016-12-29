<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Controller\Index;

use Magento\Framework\App\Action\Action;

class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Daily Deals'));
        return $resultPage;

    }
}
