<?php

namespace ControleOnline\Command;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WebSocketServerCommand extends Command
{
    protected static $defaultName = 'app:websocket-server';

    protected function configure()
    {
        $this
            ->setDescription('Starts the WebSocket server')
            ->setHelp('This command starts a WebSocket server on port 8080');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Starting WebSocket server on port 8080...');

        $server = IoServer::factory(
            new HttpServer(
                new WsServer(
                    new class implements MessageComponentInterface {
                        protected \SplObjectStorage $clients;

                        public function __construct()
                        {
                            $this->clients = new \SplObjectStorage();
                        }

                        public function onOpen(ConnectionInterface $conn)
                        {
                            $this->clients->attach($conn);
                            echo "New connection! ({$conn->resourceId})\n";
                        }

                        public function onMessage(ConnectionInterface $from, $msg)
                        {
                            foreach ($this->clients as $client) {
                                if ($from !== $client) {
                                    $client->send($msg);
                                }
                            }
                        }

                        public function onClose(ConnectionInterface $conn)
                        {
                            $this->clients->detach($conn);
                            echo "Connection closed! ({$conn->resourceId})\n";
                        }

                        public function onError(ConnectionInterface $conn, \Exception $e)
                        {
                            echo "An error occurred: {$e->getMessage()}\n";
                            $conn->close();
                        }
                    }
                )
            ),
            8080
        );

        $server->run();

        return Command::SUCCESS;
    }
}