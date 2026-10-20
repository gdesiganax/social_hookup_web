<?php

declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

function api_columns(string $table): array
{
    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        api_error('Invalid table.', 500);
    }
    $rows = db()->query('DESCRIBE `' . $table . '`')->fetchAll();
    $cache[$table] = array_column($rows, 'Field');
    return $cache[$table];
}

function api_pick(array $source, array $allowed): array
{
    $data = [];
    foreach ($allowed as $key) {
        if (array_key_exists($key, $source)) {
            $data[$key] = $source[$key] === '' ? null : $source[$key];
        }
    }
    return $data;
}

function api_redact_row(array $row): array
{
    foreach (['password_hash', 'provider_payload', 'government_id_path'] as $hidden) {
        unset($row[$hidden]);
    }
    return $row;
}

function api_redact_rows(array $rows): array
{
    return array_map('api_redact_row', $rows);
}

function api_row_by_id(string $table, string $primaryKey, int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM `' . $table . '` WHERE `' . $primaryKey . '` = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function api_assert_owner_or_admin(array $config, array $row, array $user): void
{
    if (api_is_admin($user)) {
        return;
    }

    $ownerColumn = $config['owner_column'] ?? '';
    if ($ownerColumn === '') {
        api_error('Admin access required.', 403);
    }

    if (!array_key_exists($ownerColumn, $row) || (int) $row[$ownerColumn] !== (int) $user['id']) {
        api_error('You cannot modify this resource.', 403);
    }
}

function api_list_resource(array $config, ?array $user): void
{
    $table = $config['table'];
    $primaryKey = $config['primary_key'] ?? 'id';
    $columns = api_columns($table);
    $filters = $config['filters'] ?? $columns;
    $where = [];
    $params = [];

    if (in_array('deleted_at', $columns, true) && empty($_GET['include_deleted'])) {
        $where[] = '`deleted_at` IS NULL';
    }

    foreach ($filters as $column) {
        if (array_key_exists($column, $_GET) && in_array($column, $columns, true)) {
            $where[] = '`' . $column . '` = :' . $column;
            $params[$column] = $_GET[$column];
        }
    }

    $shouldScopeToOwner = !api_is_admin($user)
        && (!empty($config['scope_to_owner']) || (!empty($config['owner_column']) && empty($config['public_get'])));
    if ($shouldScopeToOwner) {
        $ownerColumn = (string) ($config['owner_column'] ?? 'user_id');
        if (in_array($ownerColumn, $columns, true)) {
            $where[] = '`' . $ownerColumn . '` = :current_user_id';
            $params['current_user_id'] = (int) $user['id'];
        }
    }

    $limit = max(1, min(100, api_int('limit', 25)));
    $offset = max(0, api_int('offset', 0));
    $orderColumn = in_array('created_at', $columns, true) ? 'created_at' : $primaryKey;
    $sql = 'SELECT * FROM `' . $table . '`';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY `' . $orderColumn . '` DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    api_json(['ok' => true, 'data' => api_redact_rows($stmt->fetchAll()), 'limit' => $limit, 'offset' => $offset]);
}

function api_get_resource(array $config, ?array $user, int $id): void
{
    $row = api_row_by_id($config['table'], $config['primary_key'] ?? 'id', $id);
    if (!$row) {
        api_error('Resource not found.', 404);
    }

    $shouldScopeToOwner = !api_is_admin($user)
        && (!empty($config['scope_to_owner']) || (!empty($config['owner_column']) && empty($config['public_get'])));
    if ($shouldScopeToOwner) {
        api_assert_owner_or_admin($config, $row, $user);
    }

    api_json(['ok' => true, 'data' => api_redact_row($row)]);
}

function api_create_resource(array $config, array $user): void
{
    $table = $config['table'];
    $columns = api_columns($table);
    $input = api_input();
    $allowed = array_values(array_intersect($config['create'] ?? [], $columns));
    $data = api_pick($input, $allowed);

    if (!api_is_admin($user) && !empty($config['owner_column'])) {
        $ownerColumn = (string) $config['owner_column'];
        if (in_array($ownerColumn, $allowed, true)) {
            $data[$ownerColumn] = (int) $user['id'];
        }
    }

    if (!$data) {
        api_error('No accepted fields supplied.');
    }

    $fieldSql = implode(', ', array_map(fn ($field) => '`' . $field . '`', array_keys($data)));
    $valueSql = implode(', ', array_map(fn ($field) => ':' . $field, array_keys($data)));
    $stmt = db()->prepare('INSERT INTO `' . $table . '` (' . $fieldSql . ') VALUES (' . $valueSql . ')');
    $stmt->execute($data);

    $primaryKey = $config['primary_key'] ?? 'id';
    $id = $primaryKey === 'id' ? (int) db()->lastInsertId() : (int) ($data[$primaryKey] ?? 0);
    $row = $id > 0 ? api_row_by_id($table, $primaryKey, $id) : $data;
    api_json(['ok' => true, 'data' => api_redact_row($row ?: $data)], 201);
}

function api_update_resource(array $config, array $user, int $id): void
{
    $table = $config['table'];
    $primaryKey = $config['primary_key'] ?? 'id';
    $row = api_row_by_id($table, $primaryKey, $id);
    if (!$row) {
        api_error('Resource not found.', 404);
    }
    api_assert_owner_or_admin($config, $row, $user);

    $columns = api_columns($table);
    $allowed = array_values(array_intersect($config['update'] ?? [], $columns));
    $data = api_pick(api_input(), $allowed);
    if (!$data) {
        api_error('No accepted fields supplied.');
    }

    $sets = [];
    foreach (array_keys($data) as $field) {
        $sets[] = '`' . $field . '` = :' . $field;
    }
    $data['_id'] = $id;
    $stmt = db()->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE `' . $primaryKey . '` = :_id');
    $stmt->execute($data);

    api_json(['ok' => true, 'data' => api_redact_row(api_row_by_id($table, $primaryKey, $id) ?: [])]);
}

function api_delete_resource(array $config, array $user, int $id): void
{
    $table = $config['table'];
    $primaryKey = $config['primary_key'] ?? 'id';
    $row = api_row_by_id($table, $primaryKey, $id);
    if (!$row) {
        api_error('Resource not found.', 404);
    }
    api_assert_owner_or_admin($config, $row, $user);

    if (in_array('deleted_at', api_columns($table), true)) {
        $stmt = db()->prepare('UPDATE `' . $table . '` SET deleted_at = NOW() WHERE `' . $primaryKey . '` = :id');
    } else {
        $stmt = db()->prepare('DELETE FROM `' . $table . '` WHERE `' . $primaryKey . '` = :id');
    }
    $stmt->execute(['id' => $id]);
    api_json(['ok' => true, 'deleted' => true]);
}

function api_handle_resource(array $config): void
{
    $publicGet = (bool) ($config['public_get'] ?? false);
    $method = api_method();
    $user = $publicGet && $method === 'GET' ? api_auth_user(false) : api_auth_user(true);
    $id = api_int('id');

    if (!empty($config['admin_only']) && !api_is_admin($user)) {
        api_error('Admin access required.', 403);
    }

    if ($method === 'GET') {
        if ($id > 0) {
            api_get_resource($config, $user, $id);
        }
        api_list_resource($config, $user);
    }

    if ($method === 'POST') {
        api_create_resource($config, $user);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        if ($id <= 0) {
            api_error('Missing id.');
        }
        api_update_resource($config, $user, $id);
    }

    if ($method === 'DELETE') {
        if ($id <= 0) {
            api_error('Missing id.');
        }
        api_delete_resource($config, $user, $id);
    }

    api_error('Method not allowed.', 405);
}
