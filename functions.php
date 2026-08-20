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
 * Make a GET request to the Watchmode API and return decoded JSON.
 */
function watchmode_request($endpoint, $params = []) {
    $params['apiKey'] = WATCHMODE_API_KEY;
    $url = "https://api.watchmode.com/v1{$endpoint}?" . http_build_query($params);

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

    return json_decode($response, true);
}

/**
 * Look up a title's Watchmode ID using its TMDb movie ID.
 * Returns null if no match is found.
 */
function watchmode_find_id_by_tmdb($tmdbId) {
    $data = watchmode_request('/search/', [
        'search_field' => 'tmdb_movie_id',
        'search_value' => $tmdbId,
    ]);

    if (!$data || empty($data['title_results'])) {
        return null;
    }

    return $data['title_results'][0]['id'] ?? null;
}

/**
 * Case-insensitive match of found service names against the allowed list.
 * Returns matches using the casing from $ALLOWED_SERVICES (so your config
 * controls how names are displayed).
 */
function match_allowed_services($foundNames, $allowedNames) {
    $normalizedAllowed = [];
    foreach ($allowedNames as $a) {
        $normalizedAllowed[strtolower(trim($a))] = $a;
    }

    $matches = [];
    foreach ($foundNames as $f) {
        $key = strtolower(trim($f));
        if (isset($normalizedAllowed[$key])) {
            $matches[] = $normalizedAllowed[$key];
        }
    }
    return array_values(array_unique($matches));
}

/**
 * Fetch a title's raw list of subscription/free source names from
 * Watchmode (unfiltered — useful for debugging exact provider names).
 */
function watchmode_get_raw_sources($watchmodeId) {
    $data = watchmode_request("/title/{$watchmodeId}/sources/", [
        'regions' => WATCHMODE_REGION,
    ]);

    if (!$data || !is_array($data)) {
        return [];
    }

    $names = [];
    foreach ($data as $source) {
        // "sub" = subscription (flatrate), "free" = ad-supported/free.
        // Excludes "rent" and "buy" — the goal is "on something I already pay for".
        if (in_array($source['type'] ?? '', ['sub', 'free'], true)) {
            $names[] = $source['name'];
        }
    }
    return array_values(array_unique($names));
}

/**
 * Given a TMDb movie id and an optional cached Watchmode id, fetch
 * current availability filtered against the allowed services list.
 * Returns ['watchmode_id' => ..., 'available' => [...]].
 */
function get_available_services($tmdbId, $cachedWatchmodeId = null) {
    global $ALLOWED_SERVICES;

    $watchmodeId = $cachedWatchmodeId ?: watchmode_find_id_by_tmdb($tmdbId);
    if (!$watchmodeId) {
        return ['watchmode_id' => null, 'available' => []];
    }

    $found = watchmode_get_raw_sources($watchmodeId);
    $matches = match_allowed_services($found, $ALLOWED_SERVICES);

    return ['watchmode_id' => $watchmodeId, 'available' => $matches];
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
            $result = get_available_services($movieId, $movie['watchmode_id'] ?? null);
            $movie['available'] = $result['available'];
            $movie['watchmode_id'] = $result['watchmode_id'];
            if (empty($movie['runtime'])) {
                $movie['runtime'] = tmdb_get_runtime($movieId);
            }
            $movie['last_checked'] = date('Y-m-d H:i');
        }
    }
    unset($movie);
    save_movies($movies);
    return $movies;
}
