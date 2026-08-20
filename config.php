<?php
// ------------------------------------------------------------------
// CONFIG — edit these values for your setup
// ------------------------------------------------------------------

// TMDb is used for search, posters, and runtime.
// Get a free API key at https://www.themoviedb.org/settings/api
define('TMDB_API_KEY', 'YOUR_TMDB_API_KEY_HERE');

// Watchmode is used for streaming availability (more current than TMDb's
// watch-provider data, which is a slower-updating cache of JustWatch).
// Get a free API key (2,500 requests/month, non-commercial) at:
// https://api.watchmode.com/requestApiKey/
define('WATCHMODE_API_KEY', 'LrrFTQBGiPzV918Wfuo21yAuqRU5UaDThRHuKhgh');

// Region for Watchmode availability lookups. Watchmode's free tier lets
// you choose up to 3 countries when you request your key — use one of
// those here. Examples: US, GB, CA, AU
define('WATCHMODE_REGION', 'US');

// The ONLY streaming services you want checked against.
// IMPORTANT: these must match Watchmode's exact provider names, which
// sometimes differ from TMDb's (e.g. "Paramount+" not "Paramount Plus").
// Matching is case-insensitive, but the exact wording still has to match.
// Common examples: 'Netflix', 'Hulu', 'Max', 'Amazon Prime Video',
// 'Disney+', 'Apple TV+', 'Paramount+', 'Peacock', 'Peacock Premium'
// Not sure of the exact name? Add a movie you know is on the service,
// then visit debug.php?id=<its TMDb id> to see the raw names Watchmode
// returns for it.
$ALLOWED_SERVICES = [
    'Netflix',
    'Hulu',
    'HBO Max',
    'Amazon Prime Video',
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
