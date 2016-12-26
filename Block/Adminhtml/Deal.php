<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Block\Adminhtml;

class Deal extends \Magento\Backend\Block\Widget\Grid\Container
{

    protected function _construct()
    {
        $this->_controller = 'adminhtml_deal';
        $this->_blockGroup = 'Magebuzz_Dailydeal';
        $this->_headerText = __('Manage Deals');

        parent::_construct();

        if ($this->_isAllowedAction('Magebuzz_Dailydeal::save')) {
            $this->buttonList->update('add', 'label', __('Add New Deal'));
        } else {
            $this->buttonList->remove('add');
        }
    }

    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

}
