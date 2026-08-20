<?php
require_once __DIR__ . '/functions.php';

// Usage: debug.php?id=<TMDb movie id>
// Find a TMDb id by searching search.php, or from a movie already in
// your list (check movies.json).

$tmdbId = (int)($_GET['id'] ?? 0);
$watchmodeId = null;
$rawSources = null;
$error = null;

if ($tmdbId) {
    $watchmodeId = watchmode_find_id_by_tmdb($tmdbId);
    if ($watchmodeId) {
        $rawSources = watchmode_get_raw_sources($watchmodeId);
    } else {
        $error = "No Watchmode match found for TMDb id {$tmdbId}. Double-check the id, or the title may not be in Watchmode's database.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Debug — Watchmode Provider Names</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">&larr; Back to list</a>
    <h1>Provider name debug tool</h1>
    <p class="subtitle">Look up the exact service names Watchmode returns for a movie, so you can match them precisely in <code>config.php</code>'s <code>$ALLOWED_SERVICES</code>.</p>

    <form method="get" class="add-form">
        <input type="text" name="id" placeholder="TMDb movie id (e.g. 419430)" value="<?= htmlspecialchars($tmdbId ?: '') ?>" required>
        <button type="submit">Look up</button>
    </form>

    <?php if ($tmdbId && $error): ?>
        <p class="empty"><?= htmlspecialchars($error) ?></p>
    <?php elseif ($tmdbId && $rawSources !== null): ?>
        <p>Watchmode ID: <strong><?= htmlspecialchars($watchmodeId) ?></strong></p>
        <?php if (empty($rawSources)): ?>
            <p class="empty">No subscription or free sources found for this title in <?= htmlspecialchars(WATCHMODE_REGION) ?>.</p>
        <?php else: ?>
            <p>Subscription / free sources found (use these exact strings in <code>$ALLOWED_SERVICES</code>):</p>
            <ul>
                <?php foreach ($rawSources as $name): ?>
                    <li><code><?= htmlspecialchars($name) ?></code></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
