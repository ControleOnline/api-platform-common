<?php

namespace ControleOnline\Library\Company\ReceitaWS;


use ControleOnline\Library\Company\CompanyProvider;
use ControleOnline\Library\Company\CompanyService;


class ReceitaWSProvider extends CompanyProvider
{
  public function __construct()
  {
  }

  public function getCompanyService(): CompanyService
  {
    return new ReceitaWSService();
  }
}
