<?php
namespace ControleOnline\Library\Company;

use ControleOnline\Library\Company\Entity\Company;

interface CompanyService
{
  public function query(string $cnpj) :Company;
}
