<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Dailydeal\Block\Adminhtml\Deal\Edit\Tab;

class Products extends \Magento\Backend\Block\Widget\Grid\Extended
{
    protected $_coreRegistry = null;
    protected $_dealFactory;
    protected $_linkFactory;
    protected $_productStatus;
    protected $_productVisibility;
    protected $_productType;
    protected $_setsFactory;
    protected $_productFactory;
    protected $_websiteFactory;
    protected $moduleManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magento\Catalog\Model\Product\LinkFactory $linkFactory,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\Type $productType,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    )
    {
        $this->_coreRegistry = $coreRegistry;
        $this->_dealFactory = $dealFactory;
        $this->_linkFactory = $linkFactory;
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;
        $this->_productType = $productType;
        $this->_setsFactory = $setsFactory;
        $this->_productFactory = $productFactory;
        $this->_websiteFactory = $websiteFactory;
        $this->moduleManager = $moduleManager;
        parent::__construct($context, $backendHelper, $data);
        
    }

    /**
     * Rerieve grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->_getData('grid_url') ? $this->_getData('grid_url') : $this->getUrl('*/*/productgrid', ['_current' => true]);
    }

    /**
     * Set grid params
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('deal_product_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection
     *
     * @return \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected function _prepareCollection()
    {
        $collection = $this->_linkFactory->create()->getProductCollection()
            ->addAttributeToSelect('*');
        $dealId = $this->getRequest()->getParam('deal_id');
        $deal = $this->_dealFactory->create();
        if ($dealId) {
            $deal->load($dealId);
        }
        if ($deal->getId() && $deal->getProductId()) {                          //Edit
            $productId = $deal->getProductId();
            $collection->addFieldToFilter('entity_id', $productId);
        } else {                                                                //Add New
            $associatedProductIds = [];
            $deals = $this->_dealFactory->create()->getCollection();
            foreach ($deals->getItems() as $deal) {
                $associatedProductIds[] = $deal->getProductId();
            }

            if ($associatedProductIds) {
                $collection->addFieldToFilter('entity_id', ['nin' => $associatedProductIds]);
            }
            $excludedPrdType = ['configurable', 'bundle', 'grouped'];
            $collection->addAttributeToFilter('type_id', ['nin' => $excludedPrdType]);
            $collection->addAttributeToFilter('status', ['in' => $this->_productStatus->getVisibleStatusIds()]);
            $collection->addAttributeToFilter('is_deal', 1);
                
            //Product In Stock
//            $collection->getSelect()->distinct(true)->join(
//                ['stock_table' => $collection->getTable('cataloginventory_stock_status')],
//                'e.entity_id = stock_table.product_id',
//                [])
//                ->where('stock_table.stock_status = 1');
//            
//            $collection->setOrder('sort_order', 'ASC');
        }
        //Add Quantity of Product
        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $collection->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'inner'
            );
        }
        
        $collection->addWebsiteNamesToResult();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Add columns to grid
     *
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_product',
            [
                'type' => 'radio',
                'html_name' => 'product_id',
                'values' => $this->_getSelectedProduct(),
                'align' => 'center',
                'index' => 'entity_id',
            ]
        );
        
        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'type' => 'number',
                'index' => 'entity_id',
            ]
        );
        
        $this->addColumn(
            'product_name',
            [
                'header' => __('Name'),
                'index' => 'name',
            ]
        );
        $this->addColumn(
            'product_type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->_productType->getOptionArray(),
            ]
        );
        
        $sets = $this->_setsFactory->create()->setEntityTypeFilter(
            $this->_productFactory->create()->getResource()->getTypeId()
        )->load()->toOptionHash();
        $this->addColumn(
            'product_set_name',
            [
                'header' => __('Attribute Set'),
                'index' => 'attribute_set_id',
                'type' => 'options',
                'options' => $sets,
            ]
        );
        $this->addColumn(
            'product_sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
            ]
        );
        $this->addColumn(
            'product_price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    \Magento\Directory\Model\Currency::XML_PATH_CURRENCY_BASE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ),
                'index' => 'price',
            ]
        );
        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $this->addColumn(
                'product_qty',
                [
                    'header' => __('Qty in Stock'),
                    'type' => 'number',
                    'index' => 'qty'
                ]
            );
        }
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'product_websites',
                [
                    'header' => __('Websites'),
                    'index' => 'websites',
                    'type' => 'options',
                    'options' => $this->_websiteFactory->create()->getCollection()->toOptionHash(),
                ]
            );
        }
        $this->addColumn(
            'product_visibility',
            [
                'header' => __('Visibility'),
                'index' => 'visibility',
                'type' => 'options',
                'options' => $this->_productVisibility->getOptionArray(),
            ]
        );
        $this->addColumn(
            'product_status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->_productStatus->getOptionArray(),
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve selected items key
     *
     * @return array
     */
    protected function _getSelectedProduct()
    {
        $dealId = $this->getRequest()->getParam('deal_id', 0);
        $deal = $this->_dealFactory->create();
        if ($dealId) {
            $deal->load($dealId);
        }
        $productIdArr = [$deal->getProductId()];
        return $productIdArr;
    }
}