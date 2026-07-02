<?php
session_start();
include 'header.php';

$db = new SQLite3('academies.db');

$db->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    academy_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(academy_id, user_id)
)");

$search         = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_escaped = SQLite3::escapeString($search);
$is_searching   = !empty($search);

// TOP 5
if (!$is_searching) {
    $result_top5 = $db->query("
        SELECT a.*, COUNT(DISTINCT s.id) as real_num_students,
               ROUND(AVG(CAST(r.rating AS FLOAT)),1) as avg_rating,
               COUNT(DISTINCT r.id) as total_reviews
        FROM academies a
        LEFT JOIN students s  ON a.id = s.academy_id
        LEFT JOIN reviews  r  ON a.id = r.academy_id
        GROUP BY a.id
        ORDER BY (a.num_titles + COUNT(DISTINCT s.id)) DESC
        LIMIT 5
    ");
}

// TODAS / PESQUISA
$result_all = $db->query("
    SELECT a.*, COUNT(DISTINCT s.id) as real_num_students,
           ROUND(AVG(CAST(r.rating AS FLOAT)),1) as avg_rating,
           COUNT(DISTINCT r.id) as total_reviews
    FROM academies a
    LEFT JOIN students s  ON a.id = s.academy_id
    LEFT JOIN reviews  r  ON a.id = r.academy_id
    WHERE a.name    LIKE '%$search_escaped%'
       OR a.address LIKE '%$search_escaped%'
    GROUP BY a.id
    ORDER BY a.name ASC
");

function renderStarsMini($rating, $total) {
    if (!$rating) return '';
    $html = '<div class="card-stars">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="fp-star ' . ($i <= round($rating) ? 'on' : '') . '">★</span>';
    }
    $html .= '<span class="card-stars-score">' . number_format($rating, 1) . ' <small>(' . $total . ')</small></span>';
    $html .= '</div>';
    return $html;
}
?>

<main class="academies-main">

    <!-- CABEÇALHO DA PÁGINA -->
    <div class="page-hero-bar">
        <div class="page-hero-bar-inner">
            <h1>Academias Filiadas</h1>
            <p>Encontra a tua próxima academia de artes marciais.</p>
            <form class="fp-search-form" method="GET">
                <input type="text" name="search"
                       placeholder="Buscar por nome ou morada..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">🔍 Buscar</button>
            </form>
            <?php if ($is_searching): ?>
            <a href="academies.php" class="clear-search">✖ Limpar pesquisa</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$is_searching): ?>
    <!-- TOP 5 -->
    <div class="section-block">
        <div class="section-block-header">
            <h2 class="section-title-gold">🏆 Top 5 Elite</h2>
            <p>Ranking oficial baseado em títulos e alunos activos.</p>
        </div>
        <div class="top5-grid">
            <?php
            $rank = 1;
            while ($ac = $result_top5->fetchArray(SQLITE3_ASSOC)):
                $score = $ac['num_titles'] + $ac['real_num_students'];
                $rank_cls = $rank <= 3 ? "rank-$rank" : '';
            ?>
            <div class="academy-card-dark <?php echo $rank_cls; ?>">
                <div class="academy-card-rank">#<?php echo $rank; ?></div>
                <div class="academy-card-body">
                    <h3><?php echo htmlspecialchars($ac['name']); ?></h3>
                    <p class="academy-card-addr">📍 <?php echo htmlspecialchars($ac['address']); ?></p>
                    <?php echo renderStarsMini($ac['avg_rating'], $ac['total_reviews']); ?>
                    <div class="academy-card-stats">
                        <span>🥋 <strong><?php echo $ac['real_num_students']; ?></strong> Alunos</span>
                        <span>🏆 <strong><?php echo $ac['num_titles']; ?></strong> Títulos</span>
                        <span class="score-badge">⭐ <?php echo $score; ?> pts</span>
                    </div>
                    <a href="academy_details.php?id=<?php echo $ac['id']; ?>" class="btn-card-details">Ver Academia →</a>
                </div>
            </div>
            <?php $rank++; endwhile; ?>
            <?php if ($rank == 1): ?>
            <div class="fp-empty-state" style="grid-column:1/-1">
                <span>🏟️</span><p>Nenhuma academia no ranking ainda.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="fp-divider" style="margin:0 2rem"></div>
    <div class="section-block-header" style="padding:2rem 2rem 0">
        <h2 class="section-title-gold">📋 Todas as Academias</h2>
    </div>
    <?php else: ?>
    <div class="section-block-header" style="padding:2rem 2rem 0">
        <h2 class="section-title-gold">🔍 Resultados para "<?php echo htmlspecialchars($search); ?>"</h2>
    </div>
    <?php endif; ?>

    <!-- LISTA COMPLETA -->
    <div class="academy-all-grid">
        <?php
        $has = false;
        while ($ac = $result_all->fetchArray(SQLITE3_ASSOC)):
            $has = true;
        ?>
        <div class="academy-card-dark">
            <div class="academy-card-body">
                <h3><?php echo htmlspecialchars($ac['name']); ?></h3>
                <p class="academy-card-addr">📍 <?php echo htmlspecialchars($ac['address']); ?></p>
                <?php echo renderStarsMini($ac['avg_rating'], $ac['total_reviews']); ?>
                <p class="academy-card-desc"><?php echo htmlspecialchars(mb_substr($ac['description'] ?? '', 0, 100)) . (mb_strlen($ac['description'] ?? '') > 100 ? '…' : ''); ?></p>
                <div class="academy-card-stats">
                    <span>🥋 <strong><?php echo $ac['real_num_students']; ?></strong> Alunos</span>
                    <span>🏆 <strong><?php echo $ac['num_titles']; ?></strong> Títulos</span>
                </div>
                <a href="academy_details.php?id=<?php echo $ac['id']; ?>" class="btn-card-details">Ver Academia →</a>
            </div>
        </div>
        <?php endwhile; ?>
        <?php if (!$has): ?>
        <div class="fp-empty-state" style="grid-column:1/-1">
            <span>🏟️</span><p>Nenhuma academia encontrada.</p>
        </div>
        <?php endif; ?>
    </div>

</main>

<?php $db->close(); include 'footer.php'; ?>
