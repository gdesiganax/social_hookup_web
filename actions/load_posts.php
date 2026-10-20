<?php
require dirname(__DIR__) . '/app/bootstrap.php';

$user = require_auth();
$offset = filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT) ?: 0;
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 5;
$limit = max(1, min(10, $limit));

$posts = get_posts($limit + 1, $offset);
$hasMore = count($posts) > $limit;
$posts = array_slice($posts, 0, $limit);

header('Content-Type: text/html; charset=UTF-8');
header('X-Has-More-Posts: ' . ($hasMore ? '1' : '0'));
header('X-Next-Offset: ' . ($offset + count($posts)));

foreach ($posts as $post) {
    require APP_ROOT . '/app/post-card.php';
}
