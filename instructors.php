<?php
session_start();
include 'header.php';

$db = new SQLite3('academies.db');

// Lógica de busca
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT instructors.*, academies.name as academy_name 
          FROM instructors 
          JOIN academies ON instructors.academy_id = academies.id";

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
    <div style="text-align: center; margin-bottom: 2rem;">
        <h1>🥋 Nossos Mestres</h1>
        <p>Conheça a elite de instrutores que vai forjar o seu caminho no tatame.</p>
    </div>

    <div class="search-container">
        <form class="search-form" method="GET">
            <input type="text" name="search" placeholder="Buscar por nome ou estilo de luta..." value="<?php echo htmlspecialchars($search); ?>">
            <input type="submit" value="Buscar">
        </form>
    </div>

    <div class="instructor-list">
        <?php 
        $count = 0;
        while ($instructor = $result->fetchArray(SQLITE3_ASSOC)): 
            $count++;
        ?>
            <div class="instructor-card">
                <h3><?php echo htmlspecialchars($instructor['name']); ?></h3>
                
                <div class="academy-stats" style="margin-bottom: 1rem;">
                    <span>📍 Dojo:</span> <?php echo htmlspecialchars($instructor['academy_name']); ?>
                </div>
                
                <p><?php echo nl2br(htmlspecialchars($instructor['bio'])); ?></p>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if ($count === 0): ?>
        <div class="empty-schedule" style="margin-top: 3rem;">
            <h2>Nenhum mestre encontrado.</h2>
            <p>Tente buscar por outro nome ou estilo de luta.</p>
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