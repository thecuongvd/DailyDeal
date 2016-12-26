<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Dailydeal\Block\Adminhtml\Grid\Column\Renderer;

class Thumbnail extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected $imageHelper;

    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper
    )
    {
        $this->imageHelper = $imageHelper;
    }

    public function render(\Magento\Framework\DataObject $row)
    {
        $imageHelper = $this->imageHelper->init($row, 'product_listing_thumbnail');
        $src = $imageHelper->getUrl();
        $alt = $this->getAlt($row) ?: $imageHelper->getLabel();

        $imageHtml = "<img alt='$alt' src='$src' class='admin__control-thumbnail' />";
        return $imageHtml;
    }
}