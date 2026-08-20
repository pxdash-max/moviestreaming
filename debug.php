<?php
require_once __DIR__ . '/functions.php';

// Usage: debug.php?id=<TMDb movie id>
// Find a TMDb id from the URL on themoviedb.org (e.g. .../movie/25078-28-weeks-later -> 25078)
// or by searching search.php.

$tmdbId = (int)($_GET['id'] ?? 0);
$searchResults = [];
$watchmodeId = null;
$allSources = [];
$error = null;

if ($tmdbId) {
    $searchResults = watchmode_search_by_tmdb($tmdbId);
    if (empty($searchResults)) {
        $error = "Watchmode's /search/ endpoint returned no match at all for TMDb id {$tmdbId}. Either the id is wrong, or this title isn't in Watchmode's database.";
    } else {
        $watchmodeId = watchmode_find_id_by_tmdb($tmdbId);
        $allSources = watchmode_get_all_sources($watchmodeId);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Debug — Watchmode Raw Data</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">&larr; Back to list</a>
    <h1>Watchmode raw data debug tool</h1>
    <p class="subtitle">Shows exactly what Watchmode's API returns for a title — unfiltered — so you can see why a service is or isn't matching.</p>

    <form method="get" class="add-form">
        <input type="text" name="id" placeholder="TMDb movie id (e.g. 25078)" value="<?= htmlspecialchars($tmdbId ?: '') ?>" required>
        <button type="submit">Look up</button>
    </form>

    <?php if ($tmdbId && $error): ?>
        <p class="empty"><?= htmlspecialchars($error) ?></p>

    <?php elseif ($tmdbId): ?>

        <h2>1. Watchmode search match(es)</h2>
        <table>
            <thead>
                <tr><th>Watchmode ID</th><th>Name</th><th>Year</th><th>Type</th></tr>
            </thead>
            <tbody>
                <?php foreach ($searchResults as $r): ?>
                    <tr class="<?= ($r['id'] == $watchmodeId) ? '' : 'row-none' ?>">
                        <td><?= htmlspecialchars($r['id'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['name'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['year'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($r['type'] ?? '—') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="subtitle">The highlighted row (not dimmed) is the one the app uses. Confirm the name/year actually matches the movie you're checking — if it's the wrong match, that alone explains missing services.</p>

        <h2>2. Region being queried</h2>
        <p>Requesting sources for region: <strong><?= htmlspecialchars(WATCHMODE_REGION) ?></strong> (from <code>WATCHMODE_REGION</code> in <code>config.php</code>). If this doesn't match a country your Watchmode key was approved for, results can come back empty.</p>

        <h2>3. All sources Watchmode returned (completely unfiltered)</h2>
        <?php if (empty($allSources)): ?>
            <p class="empty">Watchmode returned zero sources of any kind (subscription, free, rent, or buy) for this title in this region. That's Watchmode's data, not a filtering issue in the app — worth double-checking the title/region, or reporting to Watchmode if you're confident it's wrong.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Name</th><th>Type</th><th>Region</th><th>Format</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($allSources as $s): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($s['name'] ?? '—') ?></code></td>
                            <td><?= htmlspecialchars($s['type'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['region'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($s['format'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="subtitle">The app only counts rows with type <code>sub</code> or <code>free</code> as "available" — matched against <code>$ALLOWED_SERVICES</code> in <code>config.php</code> using the exact <strong>Name</strong> column above (case-insensitive). If your service shows here with a different type (like <code>rent</code>/<code>buy</code>/<code>tve</code>), that's why it's not counted. If it doesn't appear here at all, that's Watchmode's data for this title/region.</p>
        <?php endif; ?>

    <?php endif; ?>
</div>
</body>
</html>
