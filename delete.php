<?php
require_once __DIR__ . '/functions.php';

$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $movies = load_movies();
    $movies = array_values(array_filter($movies, fn($m) => $m['id'] != $id));
    save_movies($movies);
}

header('Location: index.php');
exit;
