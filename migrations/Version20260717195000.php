<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260717195000 extends TenantAwareMigration
{
    private const MENU_MODULE_NAME = 'ui-manager';
    private const MENU_CATEGORY_NAME = 'Configuracoes';
    private const MENU_ROUTE_NAME = 'PeopleDomainsPage';
    private const MENU_KEY = 'people_domains';
    private const MENU_LABEL = 'Dominios';

    public function getDescription(): string
    {
        return 'Move the people domains menu seed to the final tenant seed migration.';
    }

    public function up(Schema $schema): void
    {
        return;
    }

    public function down(Schema $schema): void
    {
        return;
    }
}
