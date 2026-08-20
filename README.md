# My Movie Tracker

A tiny personal PHP app: add movies you want to watch, and it tells you
which of *your* streaming services (if any) each one is available on.
Uses [TMDb](https://www.themoviedb.org/) for search/posters/runtime and
[Watchmode](https://api.watchmode.com/) for streaming availability.

**Why two APIs?** TMDb's own watch-provider data is a cache of JustWatch
that updates slowly — titles can sit stale for a couple weeks after they
leave or join a service. Watchmode is built specifically for streaming
availability and is refreshed daily, so it catches adds/drops much closer
to when they actually happen.

## Setup

1. **Get a free TMDb API key** (search, posters, runtime)
   - Create an account at https://www.themoviedb.org/signup
   - Go to Settings → API → request an API key (choose "Developer", personal use is fine)
   - Copy the "API Key (v3 auth)" value

2. **Get a free Watchmode API key** (streaming availability)
   - Sign up at https://api.watchmode.com/requestApiKey/ — no credit card needed
   - Free tier: 2,500 requests/month, non-commercial use, up to 3 countries
   - This app uses ~2 requests per new movie added and ~1 request per refresh, so 2,500/month is plenty for a personal list

3. **Edit `config.php`**
   - Paste your keys into `TMDB_API_KEY` and `WATCHMODE_API_KEY`
   - Set `WATCHMODE_REGION` to one of the (up to 3) countries you registered with Watchmode
   - Edit `$ALLOWED_SERVICES` to the exact list of services you subscribe to.
     **Names must match Watchmode's exact provider names**, which sometimes
     differ from TMDb's (e.g. `Paramount+` not `Paramount Plus`). See
     "Finding exact provider names" below if you're not sure.

4. **Make sure `movies.json` is writable**
   - `chmod 664 movies.json` (or equivalent) if you get a permissions error

5. **Run it**
   - Locally: `php -S localhost:8000` in this folder, then open http://localhost:8000
   - Or upload the whole folder to any PHP web host

## Using it

- Type a title in the search box → pick the right result (year/poster shown to
  disambiguate remakes, sequels, etc.) → it's added and checked immediately.
- The list shows a colored badge per matching service, or a red "None" badge.
- Drag rows by the ⠿ handle to reorder your list manually — the order is saved automatically.
- Toggle "Hide 'None' movies" to filter out anything not on your services.
- Click ↻ next to a movie to re-check just that one, or "Refresh all" at the
  top to re-check everything.
- Click ✕ to remove a movie from the list.

## Finding exact provider names

Open **`debug.php`** in your browser and enter a TMDb movie id for a title
you know is on the service in question — it'll show you the exact source
names Watchmode returns for it (subscription and free only, not rent/buy).
Copy those exact strings into `$ALLOWED_SERVICES` in `config.php`.

(You can get a TMDb id from the URL when you view a movie on themoviedb.org,
e.g. `themoviedb.org/movie/419430-babadook` → id is `419430`.)

## Notes

- Availability only checks **subscription and free/ad-supported** sources
  (not rent/buy) — the goal is "is it on something I already pay for."
  Edit `watchmode_get_raw_sources()` in `functions.php` if you want to
  include rent/buy too (Watchmode's source `type` field also has `rent`
  and `buy`).
- Each movie's Watchmode ID is cached after the first lookup, so refreshes
  don't re-search — they just re-check that title's current sources.
- Runtime is fetched once from TMDb and cached; it doesn't need refreshing.
- All data is stored locally in `movies.json` — nothing is shared anywhere.
- No data source is instant — even Watchmode can lag actual streaming
  service changes by a day or so. If something looks wrong, refresh that
  movie a day or two after a service change and it should catch up.
