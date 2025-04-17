<?php

namespace ControleOnline\Command;

use React\EventLoop\Loop;
use React\Socket\Server;
use React\Socket\ConnectionInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'websocket:start',
    description: 'Inicia o servidor WebSocket com ReactPHP'
)]
class WebSocketServerCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('port', InputArgument::OPTIONAL, 'Porta para o servidor WebSocket', 8080);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = $input->getArgument('port');
        $output->writeln("Iniciando servidor WebSocket ReactPHP na porta {$port}...");

        $loop = Loop::get();
        $socket = new Server("0.0.0.0:{$port}", $loop);
        $clients = new \SplObjectStorage();

        $socket->on('connection', function (ConnectionInterface $conn) use ($clients, $output) {
            $clients->attach($conn);
            $output->writeln("Nova conexão! ({$conn->resourceId})");

            $conn->on('data', function ($data) use ($conn, $clients) {
                foreach ($clients as $client) {
                    if ($client !== $conn) {
                        $client->write($data);
                    }
                }
            });

            $conn->on('close', function () use ($conn, $clients, $output) {
                $clients->detach($conn);
                $output->writeln("Conexão fechada! ({$conn->resourceId})");
            });
        });

        $output->writeln('Servidor WebSocket ReactPHP iniciado!');
        $loop->run();

        return Command::SUCCESS;
    }
}