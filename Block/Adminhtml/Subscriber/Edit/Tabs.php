<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Dailydeal\Block\Adminhtml\Subscriber\Edit;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\Translate\InlineInterface;

class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * @var InlineInterface
     */
    protected $_translateInline;

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        Session $authSession,
        Registry $registry,
        InlineInterface $translateInline,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->_translateInline = $translateInline;
        parent::__construct($context, $jsonEncoder, $authSession, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('dailydeal_subscriber_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Subscriber'));
    }

    protected function _prepareLayout()
    {
        $this->addTab(
            'main',
            [
                'label' => __('Information'),
                'content' => $this->getLayout()->createBlock(
                    'Magebuzz\Dailydeal\Block\Adminhtml\Subscriber\Edit\Tab\Main'
                )->toHtml()
            ]
        );
        return parent::_prepareLayout();
    }

    public function getSubscriber()
    {
        if (!$this->getData('dailydeal_subscriber') instanceof \Magebuzz\Dailydeal\Model\Subscriber) {
            $this->setData('dailydeal_subscriber', $this->_coreRegistry->registry('dailydeal_subscriber'));
        }
        return $this->getData('dailydeal_subscriber');
    }

    /**
     * Translate html content
     *
     * @param string $html
     * @return string
     */
    protected function _translateHtml($html)
    {
        $this->_translateInline->processResponseBody($html);
        return $html;
    }
}