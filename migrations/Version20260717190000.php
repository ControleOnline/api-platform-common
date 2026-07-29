<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use ControleOnline\Migration\TenantAwareMigration;
use Doctrine\DBAL\Schema\Schema;

final class Version20260717190000 extends TenantAwareMigration
{
    public function getDescription(): string
    {
        return 'Create the cron_jobs table.';
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
