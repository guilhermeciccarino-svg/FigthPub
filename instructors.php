<?php
session_start();
include 'header.php';

$db = new SQLite3('academies.db');

// Lógica de busca
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT instructors.*, academies.name as academy_name, users.avatar as user_avatar
          FROM instructors
          JOIN academies ON instructors.academy_id = academies.id
          LEFT JOIN users ON users.instructor_id = instructors.id";

// Se tiver algo na busca, adicionamos o filtro
if (!empty($search)) {
    $safe_search = SQLite3::escapeString($search);
    $query .= " WHERE instructors.name LIKE '%$safe_search%'
                OR instructors.bio LIKE '%$safe_search%'";
}

// Ordenar por nome para ficar mais organizado
$query .= " ORDER BY instructors.name ASC";

$result = $db->query($query);
?>

<main>
    <div class="page-hero-bar" style="margin: -3rem -2rem 3rem;">
        <div class="page-hero-bar-inner">
            <h1> Nossos Mestres</h1>
            <p>Conheça a elite de instrutores que vai forjar o seu caminho no tatame.</p>
            <form class="fp-search-form" method="GET">
                <input type="text" name="search" placeholder="Buscar por nome ou estilo de luta..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"> Buscar</button>
            </form>
            <?php if (!empty($search)): ?>
            <a href="instructors.php" class="clear-search"> Limpar pesquisa</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="instructor-grid" style="max-width:1200px; margin:0 auto;">
        <?php
        $count = 0;
        while ($instructor = $result->fetchArray(SQLITE3_ASSOC)):
            $count++;
        ?>
            <div class="instructor-card-pro">
                <div style="width: 80px; height: 80px; margin: 0 auto 15px auto; border-radius: 50%; overflow: hidden; border: 3px solid var(--primary); background: #222; display: flex; align-items: center; justify-content: center;">
                    <?php if (!empty($instructor['user_avatar'])): ?>
                        <img src="<?php echo htmlspecialchars($instructor['user_avatar']); ?>" alt="Avatar" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>

                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($instructor['name']); ?></h3>
                <p class="academy-card-addr"> <?php echo htmlspecialchars($instructor['academy_name']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></p>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($count === 0): ?>
        <div class="fp-empty-state" style="margin-top: 3rem;">

            <p>Nenhum mestre encontrado. Tente buscar por outro nome ou estilo de luta.</p>
            <?php if (!empty($search)): ?>
                <a href="instructors.php" class="btn" style="margin-top: 1rem;">Limpar Busca</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<?php
$db->close();
include 'footer.php';
?>