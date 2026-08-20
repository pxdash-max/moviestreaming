<?php
require_once __DIR__ . '/config.php';

/**
 * Make a GET request to the TMDb API and return decoded JSON.
 */
function tmdb_request($endpoint, $params = []) {
    $params['api_key'] = TMDB_API_KEY;
    $url = "https://api.themoviedb.org/3{$endpoint}?" . http_build_query($params);

    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return $data;
}

/**
 * Search TMDb for movies matching a title. Returns an array of results
 * (id, title, year, poster_path) or an empty array.
 */
function tmdb_search_movie($title) {
    $data = tmdb_request('/search/movie', ['query' => $title]);
    if (!$data || empty($data['results'])) {
        return [];
    }

    $results = [];
    foreach ($data['results'] as $r) {
        $year = !empty($r['release_date']) ? substr($r['release_date'], 0, 4) : '—';
        $results[] = [
            'id' => $r['id'],
            'title' => $r['title'],
            'year' => $year,
            'poster_path' => $r['poster_path'] ?? null,
        ];
    }
    return $results;
}

/**
 * Fetch watch providers for a given TMDb movie id, filtered against
 * the allowed services list. Returns an array of matched service names
 * (empty array means "none").
 */
function tmdb_get_available_services($movieId) {
    global $ALLOWED_SERVICES;

    $data = tmdb_request("/movie/{$movieId}/watch/providers");
    if (!$data || empty($data['results'][TMDB_COUNTRY])) {
        return [];
    }

    $countryData = $data['results'][TMDB_COUNTRY];
    $found = [];
    foreach (['flatrate', 'ads', 'free'] as $type) {
        foreach ($countryData[$type] ?? [] as $provider) {
            $found[] = $provider['provider_name'];
        }
    }

    // De-dupe, then keep only ones on the allowed list
    $found = array_unique($found);
    return array_values(array_intersect($ALLOWED_SERVICES, $found));
}

/**
 * Fetch a movie's runtime (in minutes) from TMDb. Returns null if
 * unavailable.
 */
function tmdb_get_runtime($movieId) {
    $data = tmdb_request("/movie/{$movieId}");
    if (!$data || !isset($data['runtime']) || !$data['runtime']) {
        return null;
    }
    return (int)$data['runtime'];
}

/**
 * Format a runtime in minutes as h:mm (e.g. 136 -> "2:16").
 * Returns an em dash if runtime is null/unknown.
 */
function format_runtime($minutes) {
    if ($minutes === null || $minutes <= 0) {
        return '—';
    }
    $hours = intdiv($minutes, 60);
    $mins = $minutes % 60;
    return sprintf('%d:%02d', $hours, $mins);
}

/**
 * Load the saved movie watchlist from disk, sorted by the user's
 * manual drag-and-drop order. Movies saved before this feature existed
 * (no 'order' key) get one assigned based on their position in the file.
 */
function load_movies() {
    if (!file_exists(MOVIES_FILE)) {
        return [];
    }
    $json = file_get_contents(MOVIES_FILE);
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return [];
    }

    $needsBackfill = false;
    foreach ($data as $i => &$movie) {
        if (!isset($movie['order'])) {
            $movie['order'] = $i;
            $needsBackfill = true;
        }
    }
    unset($movie);

    usort($data, fn($a, $b) => $a['order'] <=> $b['order']);

    if ($needsBackfill) {
        save_movies($data);
    }

    return $data;
}

/**
 * Save the movie watchlist to disk.
 */
function save_movies($movies) {
    file_put_contents(MOVIES_FILE, json_encode($movies, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

/**
 * Return the next order value to assign to a newly added movie
 * (keeps new movies at the bottom of the list).
 */
function next_order_value() {
    $movies = load_movies();
    if (empty($movies)) {
        return 0;
    }
    return max(array_column($movies, 'order')) + 1;
}

/**
 * Persist a new manual ordering. $orderedIds is an array of movie ids
 * in the order the user dragged them into.
 */
function save_new_order($orderedIds) {
    $movies = load_movies();
    $positions = array_flip($orderedIds); // id => new index
    foreach ($movies as &$movie) {
        if (isset($positions[$movie['id']])) {
            $movie['order'] = $positions[$movie['id']];
        }
    }
    unset($movie);
    usort($movies, fn($a, $b) => $a['order'] <=> $b['order']);
    save_movies($movies);
}

/**
 * Refresh availability info for a single movie entry (by TMDb id)
 * and persist it back to the watchlist.
 */
function refresh_movie($movieId) {
    $movies = load_movies();
    foreach ($movies as &$movie) {
        if ($movie['id'] == $movieId) {
            $movie['available'] = tmdb_get_available_services($movieId);
            $movie['runtime'] = tmdb_get_runtime($movieId);
            $movie['last_checked'] = date('Y-m-d H:i');
        }
    }
    unset($movie);
    save_movies($movies);
    return $movies;
}
