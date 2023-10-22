<?php

namespace ControleOnline\Library\Quote\Calculator;

use ControleOnline\Library\Quote\Core\AbstractFormula;
use ControleOnline\Library\Quote\Exception\InvalidArgumentException;
use ControleOnline\Library\Quote\Exception\ClassNotFoundException;
use ControleOnline\Library\Quote\Exception\PropertyNotFoundException;

use ControleOnline\Library\Quote\Core\DataBag;

class Calculator
{
  private $calcs = [];

  private $taxes = [];

  public function getCalculation(string $calcId): Calculation
  {
    if (array_key_exists($calcId, $this->calcs))
      return $this->calcs[$calcId];

    return $this->calcs[$calcId] = new Calculation();
  }

  public function getTaxTotal(DataBag $tax)
  {
    $taxFormulaName = $this->getTaxFormulaName($tax);

    if (!array_key_exists($taxFormulaName, $this->taxes))
      $this->taxes[$taxFormulaName] = $this->getTaxFormulaInstance($taxFormulaName);

    try {

      return $this->taxes[$taxFormulaName]->getTotal($tax);

    } catch (\Exception $e) {
      if ($e instanceof PropertyNotFoundException) {
        return null;
      }

      throw new \Exception($e->getMessage());
    }
  }

  public function getCalculationResults(): array
  {
    $results = [];

    if (empty($this->calcs))
      return $results;

    foreach ($this->calcs as $calculationName => $calculation) {
      if (!$calculation->isEmpty())
        $results[$calculationName] = $calculation->result();
    }

    return $results;
  }

  private function getTaxFormulaInstance(string $taxFormulaName): AbstractFormula
  {
    $class = '\\ControleOnline\\Library\\Quote\\Formula\\';
    $class = $class . $taxFormulaName;

    if (!class_exists($class))
      throw new ClassNotFoundException(
        sprintf('Quote formula with name "%s" not defined.', $taxFormulaName)
      );

    return $class::getInstance();
  }

  private function getTaxFormulaName(DataBag $tax): string
  {
    return ucfirst(strtolower($tax->type)) . ucfirst(strtolower($tax->subType));
  }
}
