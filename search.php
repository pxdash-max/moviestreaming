<?php
require_once __DIR__ . '/functions.php';

$query = trim($_GET['q'] ?? '');
$results = [];
if ($query !== '') {
    $results = tmdb_search_movie($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search — Movie Tracker</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <a href="index.php" class="back-link">&larr; Back to list</a>
    <h1>Search results for "<?= htmlspecialchars($query) ?>"</h1>

    <?php if ($query === ''): ?>
        <p class="empty">Type a movie title on the previous page to search.</p>
    <?php elseif (empty($results)): ?>
        <p class="empty">No movies found for that title.</p>
    <?php else: ?>
        <div class="results-grid">
            <?php foreach ($results as $r): ?>
                <div class="result-card">
                    <?php if ($r['poster_path']): ?>
                        <img src="https://image.tmdb.org/t/p/w200<?= htmlspecialchars($r['poster_path']) ?>" alt="">
                    <?php else: ?>
                        <div class="no-poster">No image</div>
                    <?php endif; ?>
                    <div class="result-info">
                        <strong><?= htmlspecialchars($r['title']) ?></strong>
                        <span>(<?= htmlspecialchars($r['year']) ?>)</span>
                        <form method="post" action="add.php">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <input type="hidden" name="title" value="<?= htmlspecialchars($r['title']) ?>">
                            <input type="hidden" name="year" value="<?= htmlspecialchars($r['year']) ?>">
                            <input type="hidden" name="poster_path" value="<?= htmlspecialchars($r['poster_path'] ?? '') ?>">
                            <button type="submit">+ Add to my list</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
