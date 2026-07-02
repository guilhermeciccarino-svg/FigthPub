<?php
session_start();
include 'header.php';

$db = new SQLite3('academies.db');

// Lógica de pesquisa
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$is_searching = !empty($search);
$search_escaped = SQLite3::escapeString($search);

// Buscar eventos (com ou sem pesquisa)
$query = "SELECT * FROM events";
if ($is_searching) {
    $query .= " WHERE name LIKE '%$search_escaped%' OR martial_art_type LIKE '%$search_escaped%'";
}
$query .= " ORDER BY id DESC";

$result = $db->query($query);
?>

<main style="max-width: 1200px; margin: 0 auto; padding: 2rem;">
    <div style="text-align: center; margin-bottom: 3rem;">
        <h1 style="font-size: 2.5rem; color: #111;">🏆 Calendário de Eventos</h1>
        <p style="font-size: 1.1rem; color: #555;">Descubra os próximos campeonatos, torneios e seminários. Prepare-se para testar os seus limites.</p>
    </div>

    <div class="search-container" style="max-width: 600px; margin: 0 auto 3rem auto; text-align: center;">
        <form method="GET" style="display: flex; gap: 10px;">
            <input type="text" name="search" placeholder="Buscar por torneio ou estilo de luta (ex: BJJ, Karate)..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; padding: 12px; border: 2px solid #ddd; border-radius: 5px; font-size: 1rem;">
            <button type="submit" class="btn" style="background: #111; color: white; border: none; padding: 0 20px; border-radius: 5px; cursor: pointer; font-weight: bold;">Procurar</button>
        </form>
        <?php if ($is_searching): ?>
            <div style="margin-top: 10px;">
                <a href="events.php" style="color: #d32f2f; text-decoration: none; font-weight: bold;">Limpar Pesquisa ✖</a>
            </div>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
        <?php 
        $has_events = false;
        while ($event = $result->fetchArray(SQLITE3_ASSOC)): 
            $has_events = true;
        ?>
            <div style="background: #fff; border: 1px solid #eee; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 20px; display: flex; flex-direction: column;">
                <span style="display: inline-block; background: #ff9800; color: #fff; font-size: 0.8rem; font-weight: bold; padding: 4px 8px; border-radius: 4px; align-self: flex-start; margin-bottom: 10px; text-transform: uppercase;">
                    <?php echo htmlspecialchars($event['martial_art_type']); ?>
                </span>
                
                <h3 style="margin: 0 0 10px 0; font-size: 1.5rem; color: #111;">
                    <?php echo htmlspecialchars($event['name']); ?>
                </h3>
                
                <p style="color: #666; font-size: 0.95rem; margin-bottom: 20px; flex-grow: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                    <?php echo htmlspecialchars($event['description']); ?>
                </p>
                
                <a href="event_details.php?id=<?php echo $event['id']; ?>" style="display: block; text-align: center; background: #111; color: #fff; text-decoration: none; padding: 10px; border-radius: 4px; font-weight: bold; transition: background 0.3s;">
                    Ver Detalhes do Torneio
                </a>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if (!$has_events): ?>
        <div style="text-align: center; padding: 4rem 2rem; background: #f9f9f9; border-radius: 8px; border: 1px dashed #ccc;">
            <h2 style="color: #777;">Nenhum evento encontrado.</h2>
            <p style="color: #999;">Ainda não existem torneios registados com esses critérios.</p>
        </div>
    <?php endif; ?>

</main>

<?php
$db->close();
include 'footer.php';
?>