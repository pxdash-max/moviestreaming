<?php
// ------------------------------------------------------------------
// CONFIG — edit these values for your setup
// ------------------------------------------------------------------

// Get a free API key at https://www.themoviedb.org/settings/api
define('TMDB_API_KEY', '4eb99111f556740d6ca86109c7f14ea7');

// Country code for availability lookups (ISO 3166-1). Examples: US, GB, CA, AU
define('TMDB_COUNTRY', 'US');

// The ONLY streaming services you want checked against.
// Names must match TMDb's provider names. Common examples:
// 'Netflix', 'Hulu', 'Max', 'Amazon Prime Video', 'Disney Plus',
// 'Apple TV Plus', 'Paramount Plus', 'Peacock', 'Peacock Premium'
$ALLOWED_SERVICES = [
    'Netflix',
    'Hulu',
    'HBO Max',
    'Amazon Prime',
    'Disney+',
    'Paramount',
    'Paramount Plus',
	'Paramount+',
    'Paramount Plus Premium',
        'MGM Plus',
        'Apple TV+',
        'Peacock Premium',
        'Monsters and Nightmares',
        'YouTube Free',
];

// Where the watchlist is stored (must be writable by the web server)
define('MOVIES_FILE', __DIR__ . '/movies.json');
