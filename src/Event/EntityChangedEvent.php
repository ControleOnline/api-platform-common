<?php

namespace ControleOnline\Event;

use Symfony\Contracts\EventDispatcher\Event;

class EntityChangedEvent extends Event
{
    public const PRE_PERSIST  = 'entity.pre_persist';
    public const POST_PERSIST = 'entity.post_persist';
    public const PRE_UPDATE   = 'entity.pre_update';
    public const POST_UPDATE  = 'entity.post_update';
    public const PRE_REMOVE   = 'entity.pre_remove';

    public function __construct(
        public readonly object $entity,
        public readonly string $phase,
        public readonly ?object $oldEntity = null
    ) {}

    public function getOldEntity(): object
    {
        return $this->entity;
    }

    public function getEntity(): object
    {
        return $this->entity;
    }

    public function getPhase(): string
    {
        return $this->phase;
    }
}
