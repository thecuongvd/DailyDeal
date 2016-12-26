<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model\Subscriber\Source;

class Status implements \Magento\Framework\Data\OptionSourceInterface
{

    protected $_subscriber;

    public function __construct(
        \Magebuzz\Dailydeal\Model\Subscriber $subscriber
    )
    {
        $this->_subscriber = $subscriber;
    }

    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->_subscriber->getAvailableStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }

}
