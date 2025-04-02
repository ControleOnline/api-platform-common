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
    private $totalChars = 48;
    private $text = '';

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    private function addLine($prefix = '', $suffix = '', $delimiter = ' ')
    {
        $initialSpace = str_repeat(" ", $this->initialSpace);
        $delimiter = str_repeat($delimiter, $this->totalChars - $this->initialSpace - strlen($prefix) - strlen($suffix));
        $this->text .= $initialSpace . $prefix . $delimiter . $suffix . "\n";
    }

    private function getQueues(Order $order)
    {
        $queues = [];
        foreach ($order->getOrderProducts() as $orderProduct) {
            $queueEntries = $orderProduct->getOrderProductQueues();
            if ($queueEntries->isEmpty()) {
                if (!isset($queues[$this->noQueue])) {
                    $queues[$this->noQueue] = [];
                }
                $queues[$this->noQueue][] = $orderProduct;
            } else {
                foreach ($queueEntries as $queueEntry) {
                    $queue = $queueEntry->getQueue();
                    $queueName = $queue ? $queue->getQueue() : $this->noQueue;
                    if (!isset($queues[$queueName])) {
                        $queues[$queueName] = [];
                    }
                    $queues[$queueName][] = $orderProduct;
                }
            }
        }
        return $queues;
    }

    private function printProduct($orderProduct, $indent = "- ")
    {
        $product = $orderProduct->getProduct();
        $productUnit = $product->getProductUnit();
        $unitName = $productUnit->getProductUnit();
        $quantity = $orderProduct->getQuantity();
        $this->addLine(
            $indent . $product->getProduct() . " (" . $quantity . " " . $unitName . ")",
            " R$ " . number_format($orderProduct->getTotal(), 2, ',', '.'),
            '.'
        );
    }

    private function printChildren($orderProducts)
    {
        $groupedChildren = [];
        
        if (empty($orderProducts)) {
            $this->addLine("Nenhum filho encontrado");
            error_log("printChildren: Nenhum filho encontrado para os orderProducts passados.");
            return;
        }

        error_log("printChildren: Encontrados " . count($orderProducts) . " orderProducts filhos.");
        foreach ($orderProducts as $orderProductChild) {
            $productGroup = $orderProductChild->getProductGroup();
            $groupName = $productGroup ? $productGroup->getProductGroup() : 'Sem Grupo';
            $product = $orderProductChild->getProduct();
            error_log("printChildren: Filho ID " . $orderProductChild->getId() . " - Produto: " . $product->getProduct() . " - Grupo: " . $groupName);
            
            if (!isset($groupedChildren[$groupName])) {
                $groupedChildren[$groupName] = [];
            }
            $groupedChildren[$groupName][] = $orderProductChild;
        }

        foreach ($groupedChildren as $groupName => $orderProductChildren) {
            $this->addLine(strtoupper($groupName) . ":");
            foreach ($orderProductChildren as $orderProductChild) {
                $product = $orderProductChild->getProduct();
                $this->addLine("  - " . $product->getProduct());
            }
        }
    }

    private function printQueueProducts($orderProducts)
    {
        $parentOrderProducts = array_filter($orderProducts, fn($orderProduct) => $orderProduct->getOrderProduct() === null);
        
        error_log("printQueueProducts: Total de orderProducts: " . count($orderProducts));
        error_log("printQueueProducts: Total de pais encontrados: " . count($parentOrderProducts));
        
        foreach ($parentOrderProducts as $parentOrderProduct) {
            $product = $parentOrderProduct->getProduct();
            error_log("printQueueProducts: Pai ID " . $parentOrderProduct->getId() . " - Produto: " . $product->getProduct());
            
            $this->printProduct($parentOrderProduct);
            
            // Usa a relação orderProductComponents para obter os filhos diretamente
            $childOrderProducts = $parentOrderProduct->getOrderProductComponents();
            
            error_log("printQueueProducts: Pai ID " . $parentOrderProduct->getId() . " - Total de filhos encontrados (via orderProductComponents): " . count($childOrderProducts));
            
            $this->printChildren($childOrderProducts);
        }
    }

    private function printQueues($queues)
    {
        foreach ($queues as $queueName => $orderProducts) {
            $parentOrderProducts = array_filter($orderProducts, fn($orderProduct) => $orderProduct->getOrderProduct() === null);
            if (!empty($parentOrderProducts)) {
                $this->addLine(strtoupper($queueName) . ":");
                $this->printQueueProducts($orderProducts);
                $this->addLine('', '', ' ');
            }
        }
    }

    public function generatePrintData(Order $order, string $printType, string $deviceType)
    {
        if ($printType === 'pos') {
            $this->addLine("PEDIDO #" . $order->getId());
            $this->addLine($order->getOrderDate()->format('d/m/Y H:i'));
            $client = $order->getClient();
            $this->addLine(($client !== null ? $client->getName() : 'Não informado'));
            $this->addLine("R$ " . number_format($order->getPrice(), 2, ',', '.'));
            $this->addLine("", "", "-");

            $queues = $this->getQueues($order);
            $this->printQueues($queues);

            $this->addLine("", "", "-");

            if ($deviceType === 'cielo') {
                return [
                    "operation" => "PRINT_TEXT",
                    "styles" => [[]],
                    "value" => [$this->text]
                ];
            }
        }

        throw new Exception("Tipo de impressão não suportado", 1);
    }
}