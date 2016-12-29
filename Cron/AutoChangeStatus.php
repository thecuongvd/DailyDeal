<?php
/**
 * @copyright Copyright (c) 2016 www.magebuzz.com
 */
namespace Magebuzz\Dailydeal\Cron;

class AutoChangeStatus
{
    /**
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magebuzz\Dailydeal\Model\DealFactory
     */
    private $_dealFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime
     * @param \Magebuzz\Dailydeal\Model\DealFactory $dealFactory
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magebuzz\Dailydeal\Model\DealFactory $dealFactory,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_date = $date;
        $this->_dealFactory = $dealFactory;
        $this->logger = $logger;
    }

    /**
     * Cron job method to change status in time
     *
     * @return void
     */
    public function execute()
    {
        $deals = $this->_dealFactory->create()->getCollection();
        if (count($deals) > 0) {
            try {
                foreach ($deals as $deal) {
                    $now = $this->_date->gmtTimestamp();
                    $startTime = strtotime($deal->getStartTime());
                    $endTime = strtotime($deal->getEndTime());
                    if ($endTime < $now) {
                        $status = 'ended';
                    } else if ($startTime < $now && $now < $endTime) {
                        $status = 'running';
                    } else if ($now < $startTime) {
                        $status = 'coming';
                    }

                    $data = ['deal_id' => $deal->getId(), 'progress_status' => $status];
                    $deal->setData($data);
                    $deal->save();
                }
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }
    }
}
