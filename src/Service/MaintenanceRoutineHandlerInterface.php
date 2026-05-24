<?php

namespace ControleOnline\Service;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('controleonline.maintenance_routine_handler')]
interface MaintenanceRoutineHandlerInterface
{
    public function getDefinition(): array;

    public function run(): array;
}
