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
            if ($deal->getProductId()) {
                $deal->setData('prd_price', $deal->getProductPrice());
                $deal->setData('prd_qty', $deal->getProductQty());
            } 
        } 
        
        $fieldset->addField(
            'title', 'text', [
                'name' => 'title',
                'label' => __('Product Name'),
                'title' => __('Product Name'),
                'readonly' => 'readonly',
            ]
        );
        $fieldset->addField(
            'prd_price', 'text', [
                'name' => 'prd_price',
                'label' => __('Product Price ('.$this->_dailydealHelper->getCurrencySymbol().')'),
                'title' => __('Product Price'),
                'readonly' => 'readonly',
                'style' => 'border-width: 1px !important'
            ]
        );
        $fieldset->addField(
            'prd_qty', 'text', [
                'name' => 'prd_qty',
                'label' => __('Quantity in Stock'),
                'title' => __('Quantity in Stock'),
                'readonly' => 'readonly'
            ]
        );

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
                'class' => __('validate-digits validate-greater-than-zero')
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

        $ajaxUrl = $this->getUrl('*/*/getprdinfo');
        $getJs = $dealId ? '' : $this->_getJs($ajaxUrl);
        $fieldset->addField(
            'status', 'select', [
                'name' => 'status',
                'label' => __('Status'),
                'title' => __('Status'),
                'required' => true,
                'options' => ['1' => __('Enabled'), '2' => __('Disabled')],
                'after_element_html' => $getJs,
            ]
        );

        if (!$deal->getId()) {                         //Add new
            $deal->setData('status', '1');
        }

        $form->setValues($deal->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    protected function _getJs($ajaxUrl)
    {
        return <<<HTML
    <script type='text/javascript'>
        require(['jquery'], function ($) {
            $(document).ready(function () {
                var available = true;
                var url = '$ajaxUrl';
                $('body').on('change', 'input:radio[name="product_id"]', function() {
                    if (available) {
                        available = false;
                        var dealProductId = $(this).val();
                        $.ajax({
                            showLoader: true,
                            url: url,
                            data: {deal_product_id: dealProductId},
                            type: 'POST'
                        }).done(function (data) {
                            console.log(data);
                            available = true;
                            if (data) {
                                $('#deal_title').val(data.name);
                                $('#deal_prd_price').val(data.price);
                                $('#deal_prd_qty').val(data.qty);
                            }
                        }).fail(function (error) {
                            console.log(error);
                            available = true;
                        });
                    }
                });
            });
        });
    </script>
HTML;
    }

}
