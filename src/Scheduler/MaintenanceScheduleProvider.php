<?php

namespace ControleOnline\Scheduler;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('maintenance')]
class MaintenanceScheduleProvider implements ScheduleProviderInterface
{
    private ?Schedule $schedule = null;

    public function __construct(
        private LockFactory $lockFactory,
        #[Autowire(service: 'cache.scheduler')]
        private CacheInterface $schedulerCache,
    ) {}

    public function getSchedule(): Schedule
    {
        if ($this->schedule instanceof Schedule) {
            return $this->schedule;
        }

        $this->schedule = (new Schedule())
            ->lock($this->lockFactory->createLock('scheduler:maintenance'))
            ->stateful($this->schedulerCache)
            ->processOnlyLastMissedRun(true);

        return $this->schedule;
    }
}
