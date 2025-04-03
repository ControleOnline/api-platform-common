<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Order;
use ControleOnline\Entity\ProductGroupProduct;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PrintService
{
    private $initialSpace = 8;
    private $totalChars = 48;
    private $text = '';

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function addLine($prefix = '', $suffix = '', $delimiter = ' ')
    {
        $initialSpace = str_repeat(" ", $this->initialSpace);
        $count =   $this->totalChars - $this->initialSpace - strlen($prefix) - strlen($suffix);
        if ($count > 0)
            $delimiter = str_repeat($delimiter, $count);
        $this->text .= $initialSpace . $prefix . $delimiter . $suffix . "\n";
    }

    public function generatePrintData($printType, $deviceType)
    {

        if ($printType === 'pos') {
            if ($deviceType === 'cielo') {
                return [
                    "operation" => "PRINT_TEXT",
                    "styles" => [[]],
                    "value" => [$this->text]
                ];
            }
        }

        throw new Exception("Printer type not found", 1);
    }
}
