<?php
require_once __DIR__ . '/functions.php';

$movies = load_movies(); // already sorted by manual drag-and-drop order
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Movie Tracker</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1>🎬 My Movie Tracker</h1>
    <p class="subtitle">Tracking against: <?= htmlspecialchars(implode(', ', $ALLOWED_SERVICES)) ?> (<?= htmlspecialchars(TMDB_COUNTRY) ?>)</p>

    <form method="get" action="search.php" class="add-form">
        <input type="text" name="q" placeholder="Search for a movie to add..." required>
        <button type="submit">Search</button>
    </form>

    <?php if (empty($movies)): ?>
        <p class="empty">Your watchlist is empty. Search above to add your first movie.</p>
    <?php else: ?>
        <div class="list-header">
            <span id="movie-count"><?= count($movies) ?> movie<?= count($movies) === 1 ? '' : 's' ?></span>
            <label class="filter-toggle">
                <input type="checkbox" id="hide-none-toggle">
                Hide "None" movies
            </label>
            <a href="refresh_all.php" class="refresh-all">↻ Refresh all</a>
        </div>

        <table id="movie-table">
            <thead>
                <tr>
                    <th class="drag-col"></th>
                    <th></th>
                    <th>Title</th>
                    <th>Available On</th>
                    <th>Runtime</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="movie-tbody">
                <?php foreach ($movies as $movie): ?>
                    <tr class="<?= empty($movie['available']) ? 'row-none' : '' ?>" draggable="true" data-id="<?= (int)$movie['id'] ?>">
                        <td class="drag-col drag-handle" title="Drag to reorder">⠿</td>
                        <td class="poster-cell">
                            <?php if (!empty($movie['poster_path'])): ?>
                                <img src="https://image.tmdb.org/t/p/w92<?= htmlspecialchars($movie['poster_path']) ?>" alt="">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($movie['title']) ?> <span class="year">(<?= htmlspecialchars($movie['year']) ?>)</span></td>
                        <td>
                            <?php if (!empty($movie['available'])): ?>
                                <?php foreach ($movie['available'] as $service): ?>
                                    <span class="badge badge-available"><?= htmlspecialchars($service) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="badge badge-none">None</span>
                            <?php endif; ?>
                        </td>
                        <td class="runtime-cell"><?= htmlspecialchars(format_runtime($movie['runtime'] ?? null)) ?></td>
                        <td class="actions-cell">
                            <a href="refresh.php?id=<?= (int)$movie['id'] ?>" title="Refresh">↻</a>
                            <a href="delete.php?id=<?= (int)$movie['id'] ?>" title="Remove" onclick="return confirm('Remove this movie from your list?')">✕</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
(function() {
    const tbody = document.getElementById('movie-tbody');
    const toggle = document.getElementById('hide-none-toggle');
    const countEl = document.getElementById('movie-count');
    if (!tbody) return;

    // ---- Filter: hide "None" rows, remembered across visits ----
    function applyFilter() {
        const hideNone = toggle.checked;
        let visibleCount = 0;
        tbody.querySelectorAll('tr').forEach(row => {
            const isNone = row.classList.contains('row-none');
            const shouldHide = hideNone && isNone;
            row.style.display = shouldHide ? 'none' : '';
            if (!shouldHide) visibleCount++;
        });
        countEl.textContent = visibleCount + ' movie' + (visibleCount === 1 ? '' : 's') +
            (hideNone ? ' (some hidden)' : '');
    }

    if (toggle) {
        toggle.checked = localStorage.getItem('movieTracker.hideNone') === 'true';
        applyFilter();
        toggle.addEventListener('change', () => {
            localStorage.setItem('movieTracker.hideNone', toggle.checked);
            applyFilter();
        });
    }

    // ---- Drag and drop reordering ----
    let draggedRow = null;

    tbody.querySelectorAll('tr').forEach(row => {
        row.addEventListener('dragstart', () => {
            draggedRow = row;
            row.classList.add('dragging');
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('dragging');
            draggedRow = null;
            saveOrder();
        });
    });

    tbody.addEventListener('dragover', (e) => {
        e.preventDefault();
        const afterRow = getRowAfter(tbody, e.clientY);
        if (!draggedRow) return;
        if (afterRow == null) {
            tbody.appendChild(draggedRow);
        } else {
            tbody.insertBefore(draggedRow, afterRow);
        }
    });

    function getRowAfter(container, y) {
        const rows = [...container.querySelectorAll('tr:not(.dragging)')];
        return rows.reduce((closest, row) => {
            const box = row.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: row };
            } else {
                return closest;
            }
        }, { offset: -Infinity, element: null }).element;
    }

    function saveOrder() {
        const ids = [...tbody.querySelectorAll('tr')].map(row => row.dataset.id);
        fetch('reorder.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order: ids }),
        }).catch(() => {
            // Silent fail is fine here; a page refresh will just restore the last saved order.
        });
    }
})();
</script>
</body>
</html>
