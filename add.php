<?php
require_once __DIR__ . '/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
$title = trim($_POST['title'] ?? '');
$year = trim($_POST['year'] ?? '');
$poster_path = trim($_POST['poster_path'] ?? '');

if (!$id || $title === '') {
    header('Location: index.php');
    exit;
}

$movies = load_movies();

// Don't add a duplicate
$alreadyExists = false;
foreach ($movies as $m) {
    if ($m['id'] == $id) {
        $alreadyExists = true;
        break;
    }
}

if (!$alreadyExists) {
    $movies[] = [
        'id' => $id,
        'title' => $title,
        'year' => $year,
        'poster_path' => $poster_path,
        'available' => tmdb_get_available_services($id),
        'runtime' => tmdb_get_runtime($id),
        'last_checked' => date('Y-m-d H:i'),
        'order' => next_order_value(),
    ];
    save_movies($movies);
}

header('Location: index.php');
exit;
