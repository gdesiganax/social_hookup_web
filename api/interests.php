<?php

declare(strict_types=1);

require __DIR__ . '/_resource.php';

api_handle_resource([
    'table' => 'interests',
    'primary_key' => 'id',
    'public_get' => true,
    'filters' => ['id', 'name'],
    'create' => ['name'],
    'update' => ['name'],
]);

