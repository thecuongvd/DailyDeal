<?php

/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Dailydeal\Block\Adminhtml\Deal\Edit\Tab;

use Magento\Backend\Block\Widget\Form\Generic;

class Main extends Generic
{

    protected $_systemStore;
    protected $_dailydealHelper;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context, 
            \Magento\Framework\Registry $registry, 
            \Magento\Framework\Data\FormFactory $formFactory, 
            \Magento\Store\Model\System\Store $systemStore, 
            \Magebuzz\Dailydeal\Helper\Data $dailydealHelper, 
            array $data = []
    )
    {
        $this->_systemStore = $systemStore;
        $this->_dailydealHelper = $dailydealHelper;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        $deal = $this->_coreRegistry->registry('dailydeal_deal');
        $dealId = $deal->getId();

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('deal_');

        $fieldset = $form->addFieldset(
            'base_fieldset', ['legend' => __('Deal Information'), 'class' => 'fieldset-wide']
        );

        if ($dealId) {
            $fieldset->addField('deal_id', 'hidden', ['name' => 'deal_id']);
        } 

        $dateFormat = $this->_localeDate->getDateFormat(\IntlDateFormatter::SHORT);
        $timeFormat = $this->_localeDate->getTimeFormat(\IntlDateFormatter::SHORT);
        $style = 'color: #000;background-color: #fff; font-weight: bold; font-size: 13px;';

        $fieldset->addField(
            'start_time', 'date', [
                'name' => 'start_time',
                'label' => __('Start Time'),
                'title' => __('Start Time'),
                'style' => $style,
                'required' => true,
                'class' => __('validate-date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'note' => $this->_localeDate->getDateTimeFormat(\IntlDateFormatter::SHORT)
            ]
        );

        $fieldset->addField(
            'end_time', 'date', [
                'name' => 'end_time',
                'label' => __('End Time'),
                'title' => __('End Time'),
                'style' => $style,
                'required' => true,
                'class' => __('validate-date'),
                'date_format' => $dateFormat,
                'time_format' => $timeFormat,
                'note' => $this->_localeDate->getDateTimeFormat(\IntlDateFormatter::SHORT),
            ]
        );

        $fieldset->addField(
            'price', 'text', [
                'name' => 'price',
                'label' => __('Deal Price'),
                'title' => __('Deal Price'),
                'required' => true,
                'class' => __('validate-zero-or-greater'),
                'style' => 'border-width: 1px !important'
            ]
        );
        $fieldset->addField(
            'quantity', 'text', [
                'name' => 'quantity',
                'label' => __('Quantity for sale via Daily Deal'),
                'title' => __('Quantity for sale via Daily Deal'),
                'required' => true,
                'class' => __('validate-greater-than-zero')
            ]
        );

        if (!$this->_storeManager->hasSingleStore()) { 
            $field = $fieldset->addField(
                'select_stores', 'multiselect', [
                    'label' => __('Store View'),
                    'required' => true,
                    'name' => 'stores[]',
                    'values' => $this->_systemStore->getStoreValuesForForm(false, true)
                ]
            );
            $renderer = $this->getLayout()->createBlock(
                'Magento\Backend\Block\Store\Switcher\Form\Renderer\Fieldset\Element'
            );
            $field->setRenderer($renderer);
            $deal->setSelectStores($deal->getStores());
        } else {
            $fieldset->addField(
                'select_stores', 'hidden', [
                    'name' => 'stores[]',
                    'value' => $this->_storeManager->getStore(true)->getId()
                ]
            );
            $deal->setSelectStores($this->_storeManager->getStore(true)->getId());
        }

        $fieldset->addField(
            'status', 'select', [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'options' => [\Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED => __('Enabled'), \Magebuzz\Dailydeal\Model\Deal::STATUS_DISABLED => __('Disabled')],
            ]
        );

        if (!$deal->getId()) {                                                  //Add new
            $deal->setData('status', \Magebuzz\Dailydeal\Model\Deal::STATUS_ENABLED);
        }

        $form->setValues($deal->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

}
