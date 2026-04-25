<?php

namespace ControleOnline\Service;

class MaintenanceRunnerService
{
    public function __construct(
        private MaintenanceRoutineService $maintenanceRoutineService,
    ) {}

    public function runDueRoutines(?\DateTimeImmutable $now = null): array
    {
        $referenceTime = $now ?? new \DateTimeImmutable('now');
        $dueRoutines = $this->maintenanceRoutineService->getDueRoutines($referenceTime);
        $executed = [];

        foreach ($dueRoutines as $routine) {
            $executed[] = $this->maintenanceRoutineService->runRoutine(
                (string) ($routine['key'] ?? '')
            );
        }

        return [
            'ranAt' => $referenceTime->format(\DateTimeInterface::ATOM),
            'dueCount' => count($dueRoutines),
            'executedCount' => count($executed),
            'executed' => $executed,
        ];
    }
}
