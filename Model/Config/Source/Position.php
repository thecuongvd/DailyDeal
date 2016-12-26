<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model\Config\Source;

class Position implements \Magento\Framework\Option\ArrayInterface
{

    public function toOptionArray()
    {
        return [['value' => 'none', 'label' => __('None')], ['value' => 'leftsidebar', 'label' => __('Left Sidebar')], ['value' => 'rightsidebar', 'label' => __('Right Sidebar')]];
    }
}
