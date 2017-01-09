<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */

namespace Magebuzz\Dailydeal\Plugin;

class BlockProductList
{
    protected $_scopeConfig;
    protected $_dealFactory;
    protected $_dailydealHelper;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_dealFactory = $dealFactory;
        $this->_dailydealHelper = $dailydealHelper;
    }

    public function aroundGetProductPrice(
        \Magento\Catalog\Block\Product\ListProduct $subject,
        \Closure $proceed,
        \Magento\Catalog\Model\Product $product
    )
    {
        $result = $proceed($product);
        if ($this->getScopeConfig('dailydeal/general/enable')) {
            $deal = $this->_dealFactory->create()->loadByProductId($product->getId());
            if ($deal->getId() && $deal->isAvailable()) {
                $currentTime = $this->_dailydealHelper->getCurrentTime();
                $endTime = strtotime($deal->getEndTime());
                $result .= '
                <div class="dailydeal-cat timeleft-block">
                    <label>DEAL TIME</label>
                    <span id="timeleft_cat_' . $deal->getId() . '" class="timeleft"> </span>
                </div>
                <script type="text/javascript">
                    require(["jquery", "dailydeal"], function ($) {
                        $(document).ready(function () {
                            var dTimeCounter_' . $deal->getId() . ' = new DealTimeCounter();
                            dTimeCounter_' . $deal->getId() .'.init(' . $currentTime . ', ' . $endTime . ', ' . $deal->getId() . ');
                            dTimeCounter_' . $deal->getId() . '.setTimeleft("timeleft_cat_' . $deal->getId() . '");
                        });
                    });
                </script>
                ';
            }
        }
        return $result;
    }
    
    public function getScopeConfig($path) {
        return $this->_scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
