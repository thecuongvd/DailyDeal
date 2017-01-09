<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Cron;

class SendDailyEmail
{
    private $_dailydealHelper;

    public function __construct(
        \Magebuzz\Dailydeal\Helper\Data $dailydealHelper
    )
    {
        $this->_dailydealHelper = $dailydealHelper;
    }

    /**
     * Cron job method to send email everyday
     *
     * @return void
     */
    public function execute()
    {
        $this->_dailydealHelper->sendTodayDealEmail();
    }
}
