<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

api_handle_resource([
    'table' => 'reports',
    'primary_key' => 'id',
    'filters' => ['id', 'reporter_id', 'reported_user_id', 'status'],
    'owner_column' => 'reporter_id',
    'create' => ['reporter_id', 'reported_user_id', 'reason', 'status'],
    'update' => ['reason', 'status', 'resolved_at'],
]);

