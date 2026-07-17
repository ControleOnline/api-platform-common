<?php

namespace ControleOnline\Tests\Service;

use ControleOnline\Service\CronJobCommandCatalogService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application as FrameworkConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\HttpKernel\KernelInterface;

class CronJobCommandCatalogServiceTest extends TestCase
{
    public function testFiltersInternalAndForeignCommands(): void
    {
        $kernel = $this->createStub(KernelInterface::class);
        $kernel->method('getEnvironment')->willReturn('test');
        $kernel->method('isDebug')->willReturn(true);

        $commands = [
            'tenant:integration:start' => (new Command('tenant:integration:start'))
                ->setDescription('Processa a fila de integracoes por tenant.'),
            'app:cron:run-job' => (new Command('app:cron:run-job'))
                ->setDescription('Executa um job cron configurado no banco.'),
            'websocket:start' => (new Command('websocket:start'))
                ->setDescription('Mantem o servidor WebSocket da API ativo.'),
            'cache:clear' => (new Command('cache:clear'))
                ->setDescription('Limpa o cache do Symfony.'),
        ];

        $application = new class($kernel, $commands) extends FrameworkConsoleApplication {
            public function __construct(
                KernelInterface $kernel,
                private array $commands,
            ) {
                parent::__construct($kernel);
            }

            public function all(?string $namespace = null): array
            {
                return $this->commands;
            }
        };

        $service = new class($kernel, $application) extends CronJobCommandCatalogService {
            public function __construct(
                KernelInterface $kernel,
                private FrameworkConsoleApplication $application,
            ) {
                parent::__construct($kernel);
            }

            protected function createApplication(): FrameworkConsoleApplication
            {
                return $this->application;
            }

            protected function resolveCommandClass(Command $command): string
            {
                return match ($command->getName()) {
                    'tenant:integration:start' => 'ControleOnline\Command\TenantIntegrationStartCommand',
                    'app:cron:run-job' => 'ControleOnline\Command\CronRunJobCommand',
                    'websocket:start' => 'ControleOnline\Command\WebsocketStartCommand',
                    'cache:clear' => 'Symfony\Bundle\FrameworkBundle\Command\CacheClearCommand',
                    default => parent::resolveCommandClass($command),
                };
            }
        };

        $catalog = $service->getAvailableCommands();

        self::assertSame(
            ['tenant:integration:start', 'websocket:start'],
            array_column($catalog, 'name')
        );
        self::assertSame('tenant', $catalog[0]['group']);
        self::assertSame('websocket', $catalog[1]['group']);
        self::assertSame('ControleOnline\Command\TenantIntegrationStartCommand', $catalog[0]['class']);
        self::assertSame('ControleOnline\Command\WebsocketStartCommand', $catalog[1]['class']);
    }
}
