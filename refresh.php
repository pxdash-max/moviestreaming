<?php
require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    refresh_movie($id);
}

header('Location: index.php');
exit;
