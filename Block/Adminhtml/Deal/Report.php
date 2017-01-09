<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Block\Adminhtml\Deal;

class Report extends \Magento\Backend\Block\Widget\Container
{
    protected $_coreRegistry = null;
    protected $_dailydealHelper;
    protected $_date;

    public function __construct(
        \Magento\Backend\Block\Widget\Context $context, 
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        array $data = []
            )
    {
        $this->_coreRegistry = $registry;
        $this->_dailydealHelper = $dailydealHelper;
        $this->_date = $date;
        parent::__construct($context, $data);
    }
    
    protected function _construct()
    {
        $this->_controller = 'adminhtml_deal';
        $this->_headerText = __('Report Deal');
        parent::_construct();
    }

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }
    
    public function getDeal() {
        return $this->_coreRegistry->registry('dailydeal_deal');
    }
    
    public function getHelper() {
        return $this->_dailydealHelper;
    }


}
