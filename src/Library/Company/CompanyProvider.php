<?php
namespace ControleOnline\Library\Company;

use ControleOnline\Library\Company\Entity\Company;
use ControleOnline\Library\Company\CompanyService;

abstract class CompanyProvider
{
  abstract public function getCompanyService(): CompanyService;

  public function getCnpj(string $postalCode): Company
  {
    return $this->getCompanyService()->query($postalCode);
  }
}
