<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260720131500 extends TenantAwareMigration
{
    private const MENU_KEY = 'product_showcases';

    public function getDescription(): string
    {
        return 'Move product showcases menu visibility seed to the final tenant seed migration.';
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
