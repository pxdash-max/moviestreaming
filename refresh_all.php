<?php
require_once __DIR__ . '/functions.php';

$movies = load_movies();
foreach ($movies as &$movie) {
    $result = get_available_services($movie['id'], $movie['watchmode_id'] ?? null);
    $movie['available'] = $result['available'];
    $movie['watchmode_id'] = $result['watchmode_id'];
    if (empty($movie['runtime'])) {
        $movie['runtime'] = tmdb_get_runtime($movie['id']);
    }
    $movie['last_checked'] = date('Y-m-d H:i');
}
unset($movie);
save_movies($movies);

header('Location: index.php');
exit;
