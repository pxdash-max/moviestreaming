# My Movie Tracker

A tiny personal PHP app: add movies you want to watch, and it tells you
which of *your* streaming services (if any) each one is available on.
Uses [TMDb](https://www.themoviedb.org/)'s watch-provider data.

## Setup

1. **Get a free TMDb API key**
   - Create an account at https://www.themoviedb.org/signup
   - Go to Settings → API → request an API key (choose "Developer", personal use is fine)
   - Copy the "API Key (v3 auth)" value

2. **Edit `config.php`**
   - Paste your key into `TMDB_API_KEY`
   - Set `TMDB_COUNTRY` to your country code (e.g. `US`, `GB`, `CA`)
   - Edit `$ALLOWED_SERVICES` to the exact list of services you subscribe to.
     Names must match TMDb's provider names — common ones:
     `Netflix`, `Hulu`, `Max`, `Amazon Prime Video`, `Disney Plus`,
     `Apple TV Plus`, `Paramount Plus`, `Peacock`, `Peacock Premium`.
     If a service you add doesn't match, it just won't show up — see
     "Finding exact provider names" below.

3. **Make sure `movies.json` is writable**
   - `chmod 664 movies.json` (or equivalent) if you get a permissions error

4. **Run it**
   - Locally: `php -S localhost:8000` in this folder, then open http://localhost:8000
   - Or upload the whole folder to any PHP web host

## Using it

- Type a title in the search box → pick the right result (year/poster shown to
  disambiguate remakes, sequels, etc.) → it's added and checked immediately.
- The list shows a colored badge per matching service, or a red "None" badge.
- Drag rows by the ⠿ handle to reorder your list manually — the order is saved automatically.
- Toggle "Hide 'None' movies" to filter out anything not on your services.
- Click ↻ next to a movie to re-check just that one, or "Refresh all" at the
  top to re-check everything (useful to run occasionally since availability
  changes over time).
- Click ✕ to remove a movie from the list.

## Finding exact provider names

TMDb's provider names are specific (e.g. it's "Max" not "HBO Max" as of the
2023 rebrand, and "Amazon Prime Video" not "Prime Video"). If a service
isn't matching how you expect, add a movie you know is on that service,
temporarily add every provider name you can think of to `$ALLOWED_SERVICES`,
refresh, and see which exact string appears as a badge — then trim the list
down to just what you want.

## Notes

- This only checks **subscription/ad-supported/free** availability
  (`flatrate`, `ads`, `free` in TMDb's data), not rent/buy — since the goal
  is "is it on a service I already pay for." Edit the `foreach` loop in
  `tmdb_get_available_services()` in `functions.php` if you also want
  rent/buy included.
- Runtime is fetched once when a movie is added and cached; refreshing a
  movie will backfill it if missing (e.g. for movies added before this
  feature existed) but won't re-fetch it otherwise.
- All data is stored locally in `movies.json` — nothing is shared anywhere.
- TMDb's free API tier is generous enough for personal use (no practical
  limit for occasional refreshes of a watchlist this size).
- Heads up: TMDb's watch-provider data is a cache of JustWatch and can lag
  behind real-world changes (additions/removals) by a week or two. There's
  no perfect fix for this without a second paid or free-tier-limited API —
  if it becomes a real problem, refreshing a specific movie right before
  you plan to watch it is the simplest workaround.
