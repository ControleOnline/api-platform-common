<?php

namespace ControleOnline\Event;

use ControleOnline\Entity\People;
use Symfony\Contracts\EventDispatcher\Event;

final class Food99DelegationEvent extends Event
{
    public const ACTION_CATALOG_MARK_PRODUCTS_SYNCED = 'catalog.mark_products_synced';
    public const ACTION_CATALOG_SYNC_INTEGRATION_STATE = 'catalog.sync_integration_state';
    public const ACTION_STORE_PERSIST_PROVIDER_LAST_ERROR = 'store.persist_provider_last_error';
    public const ACTION_STORE_PERSIST_PROVIDER_MENU_UPLOAD_SUBMISSION = 'store.persist_provider_menu_upload_submission';
    public const ACTION_STORE_NORMALIZE_MENU_TASK_RESPONSE = 'store.normalize_menu_task_response';
    public const ACTION_STORE_PERSIST_PROVIDER_MENU_TASK_STATE = 'store.persist_provider_menu_task_state';
    public const ACTION_STORE_GET_STORED_INTEGRATION_STATE = 'store.get_stored_integration_state';

    public bool $handled = false;

    public mixed $result = null;

    public function __construct(
        public readonly string $action,
        public readonly ?People $provider = null,
        public readonly array $payload = [],
    ) {
    }

    public function markHandled(mixed $result = null): void
    {
        $this->handled = true;
        $this->result = $result;
    }
}
