<?php

namespace ControleOnline\Tests\Command;

use ControleOnline\Command\CronRunJobCommand;
use ControleOnline\Service\CronJobRunnerService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class CronRunJobCommandTest extends TestCase
{
    public function testReturnsSuccessForNonErrorJobStatuses(): void
    {
        $runner = $this->createMock(CronJobRunnerService::class);
        $runner->expects(self::once())
            ->method('run')
            ->with('maintenance_run')
            ->willReturn([
                'status' => 'ignored',
            ]);

        $tester = new CommandTester(new CronRunJobCommand($runner));
        $exitCode = $tester->execute([
            'jobKey' => 'maintenance_run',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    public function testReturnsFailureWhenRunnerReportsError(): void
    {
        $runner = $this->createMock(CronJobRunnerService::class);
        $runner->expects(self::once())
            ->method('run')
            ->with('maintenance_run')
            ->willReturn([
                'status' => 'error',
            ]);

        $tester = new CommandTester(new CronRunJobCommand($runner));
        $exitCode = $tester->execute([
            'jobKey' => 'maintenance_run',
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
    }
}
