<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\ProductGroupProduct;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PrintService
{
    private $noQueue = 'Sem fila definida';
    private $initialSpace = 8;
    private $totalChars = 30;
    private $text = '';

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    private function addLine($pre = '', $pos = '', $delimiter = ' ')
    {
        $initialSpace = str_repeat(" ", $this->initialSpace);
        $delimiter = str_repeat($delimiter, $this->totalChars - $initialSpace - strlen($pre) - strlen($pos));
        $this->text .= $initialSpace . $pre . $delimiter . $pos . "\n";
    }


    private function getQueues(Order $order)
    {
        $queues = [];
        foreach ($order->getOrderProducts() as $orderProduct) {
            $queueEntries = $orderProduct->getOrderProductQueues();
            if ($queueEntries->isEmpty()) {
                if (!isset($queues[$this->noQueue]))
                    $queues[$this->noQueue] = [];
                $queues[$this->noQueue][] = $orderProduct;
            } else
                foreach ($queueEntries as $queueEntry) {
                    $queue = $queueEntry->getQueue();
                    $queueName = $queue ? $queue->getQueue() : $this->noQueue;


                    if (!isset($queues[$queueName]))
                        $queues[$queueName] = [];

                    $queues[$queueName][] = $orderProduct;
                }
        }
        return $queues;
    }

    public  function generatePrintData(Order $order, string $printType, string $deviceType)
    {

        if ($printType === 'pos') {
            $this->addLine("PEDIDO #" . $order->getId());
            $this->addLine($order->getOrderDate()->format('d/m/Y H:i'));
            $client = $order->getClient();
            $this->addLine(($client !== null ? $client->getName() : 'Não informado'));
            $this->addLine(number_format($order->getPrice(), 2, ',', '.'));
            $this->addLine("", "", "-");

            $queues = $this->getQueues($order);


            foreach ($queues as $queueName => $products) {
                $this->addLine(strtoupper($queueName) . ":");
                foreach ($products as $orderProduct) {
                    $product = $orderProduct->getProduct();
                    $unit = $product->getProductUnit()->getProductUnit();
                    $quantity = $orderProduct->getQuantity();

                    $this->addLine(
                        "- " . $product->getProduct() . " (" . $quantity . " " . $unit . ")",
                        " R$ " . number_format($product->getPrice() * $quantity, 2, ',', '.'),
                        '.'
                    );


                    if ($product->getType() === 'custom') {
                        $this->addLine("    Personalizações:");
                        $productGroupProducts = $this->entityManager->getRepository(ProductGroupProduct::class)
                            ->findBy(['product' => $product->getId()]);

                        foreach ($productGroupProducts as $pgp) {
                            $childProduct = $pgp->getProductChild();
                            if ($childProduct)
                                $this->addLine("    - " . $childProduct->getProduct() . " (" . $pgp->getQuantity() . " " . $childProduct->getProductUnit()->getProductUnit() . ")");
                        }
                    }
                }
                $this->addLine('', '', ' ');
            }

            $this->addLine('', '', '-');


            if ($deviceType === 'cielo')
                return   [
                    "operation" => "PRINT_TEXT",
                    "styles" => [[]],
                    "value" => [$this->text]
                ];
        }

        throw new Exception("Unsupported print type", 1);
    }
}
