<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Model\Deal\Source;

class ProgressStatus implements \Magento\Framework\Data\OptionSourceInterface
{

    protected $_deal;

    public function __construct(
        \Magebuzz\Dailydeal\Model\Deal $deal
    )
    {
        $this->_deal = $deal;
    }

    public function toOptionArray()
    {
        $options[] = ['label' => '', 'value' => ''];
        $availableOptions = $this->_deal->getAvailableProgressStatuses();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }

}
