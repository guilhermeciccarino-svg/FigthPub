<?php
session_start();
include 'header.php';

// Verifica se foi passado um ID de evento válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: events.php');
    exit;
}

$event_id = (int)$_GET['id'];
$db = new SQLite3('academies.db');

// --- PROCESSAMENTO DE REVIEW DE FOTO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_photo_review'])) {
    if (isset($_SESSION['user_id'])) {
        $gallery_id = (int)$_POST['gallery_id'];
        $rating  = min(5, max(1, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        $uid     = (int)$_SESSION['user_id'];
        $s = $db->prepare("INSERT INTO gallery_reviews (gallery_id, user_id, rating, comment) VALUES (:g, :u, :r, :c)");
        $s->bindValue(':g', $gallery_id, SQLITE3_INTEGER);
        $s->bindValue(':u', $uid,        SQLITE3_INTEGER);
        $s->bindValue(':r', $rating,     SQLITE3_INTEGER);
        $s->bindValue(':c', $comment,    SQLITE3_TEXT);
        $s->execute();
        header("Location: event_details.php?id=$event_id#galeria");
        exit;
    }
}

// BUSCAR GALERIA
$gallery_stmt = $db->prepare("SELECT * FROM event_gallery WHERE event_id = :eid ORDER BY id DESC");
$gallery_stmt->bindValue(':eid', $event_id, SQLITE3_INTEGER);
$gallery_res = $gallery_stmt->execute();


// Procura o evento na base de dados
$stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
$stmt->bindValue(':id', $event_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$event = $result->fetchArray(SQLITE3_ASSOC);

// Se o evento não existir, volta para a lista
if (!$event) {
    header('Location: events.php');
    exit;
}

// =========================================================================
// FUNÇÃO MÁGICA: Transforma o texto com "Enter" numa Lista HTML (<ul><li>)
// =========================================================================
function formatar_lista($texto_do_banco) {
    // Se estiver vazio, retorna uma mensagem padrão
    if (empty(trim($texto_do_banco))) {
        return "<p class='empty-note'>Nenhuma informação fornecida.</p>";
    }

    // Divide o texto em pedaços usando a quebra de linha (\n)
    $linhas = explode("\n", trim($texto_do_banco));

    $html = "<ul>";
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if (!empty($linha)) {
            // Cria um "item de lista" para cada linha
            $html .= "<li>" . htmlspecialchars($linha) . "</li>";
        }
    }
    $html .= "</ul>";

    return $html;
}
?>

<main style="max-width: 1000px; margin: 0 auto; padding: 2rem;">

    <div class="event-detail-hero">
        <span class="event-tag"><?php echo htmlspecialchars($event['martial_art_type']); ?></span>
        <h1><?php echo htmlspecialchars($event['name']); ?></h1>
    </div>

    <div class="event-detail-body">
        <h2>📖 Sobre o Torneio</h2>
        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
    </div>

    <div class="event-info-grid">

        <div class="event-info-card">
            <h3><span>⚖️</span> Livro de Regras</h3>
            <hr>
            <?php echo formatar_lista($event['rules']); ?>
        </div>

        <div class="event-info-card">
            <h3><span>⚖️</span> Tabela de Pesos</h3>
            <hr>
            <?php echo formatar_lista($event['weight_classes']); ?>
        </div>

        <div class="event-info-card">
            <h3><span>🥋</span> Graduações</h3>
            <hr>
            <?php echo formatar_lista($event['belt_ranks']); ?>
        </div>

    </div>


    <div id="galeria" style="margin-top: 3rem; border-top: 1px solid #333; padding-top: 2rem;">
        <h2 style="margin-bottom: 1.5rem; text-align: center;">📸 Galeria Oficial do Evento</h2>
        <div style="column-count: 2; column-gap: 20px;">
            <?php while ($photo = $gallery_res->fetchArray(SQLITE3_ASSOC)):
                $gal_id = $photo['id'];
                $rev_stmt = $db->query("SELECT r.*, u.username, u.avatar FROM gallery_reviews r JOIN users u ON r.user_id = u.id WHERE r.gallery_id = $gal_id ORDER BY r.id DESC");

                $avg_stmt = $db->querySingle("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM gallery_reviews WHERE gallery_id = $gal_id", true);
                $avg = round($avg_stmt['avg_rating'] ?? 0, 1);
            ?>
            <div style="break-inside: avoid; margin-bottom: 20px; background: #111; border-radius: 8px; overflow: hidden; border: 1px solid #222;">
                <img src="<?php echo htmlspecialchars($photo['image_url']); ?>" alt="Foto" style="width: 100%; display: block;">
                <div style="padding: 15px;">
                    <?php if (!empty($photo['description'])): ?>
                        <p style="margin: 0 0 10px 0; font-size: 0.95rem;"><?php echo htmlspecialchars($photo['description']); ?></p>
                    <?php endif; ?>

                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                        <span style="color: #ffb74d; font-weight: bold;">★ <?php echo $avg; ?> <small style="color: #888;">(<?php echo $avg_stmt['total']; ?> avaliações)</small></span>
                    </div>

                    <div style="max-height: 150px; overflow-y: auto; margin-bottom: 15px; border-top: 1px solid #222; padding-top: 10px;">
                        <?php while ($rev = $rev_stmt->fetchArray(SQLITE3_ASSOC)): ?>
                            <div style="margin-bottom: 8px; font-size: 0.85rem;">
                                <strong style="color: #ccc;">@<?php echo htmlspecialchars($rev['username']); ?></strong>
                                <span style="color: #ffb74d;">[<?php echo $rev['rating']; ?>★]</span>
                                <?php if (!empty($rev['comment'])): ?>
                                    <br><span style="color: #999;">"<?php echo htmlspecialchars($rev['comment']); ?>"</span>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <form method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                            <input type="hidden" name="add_photo_review" value="1">
                            <input type="hidden" name="gallery_id" value="<?php echo $gal_id; ?>">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <label style="font-size: 0.85rem;">Nota:</label>
                                <select name="rating" style="padding: 3px; background: #222; color: #fff; border: 1px solid #444;">
                                    <option value="5">5 ★</option>
                                    <option value="4">4 ★</option>
                                    <option value="3">3 ★</option>
                                    <option value="2">2 ★</option>
                                    <option value="1">1 ★</option>
                                </select>
                            </div>
                            <input type="text" name="comment" placeholder="Adicionar um comentário..." style="padding: 6px; background: #222; border: 1px solid #444; color: #fff; border-radius: 4px; font-size: 0.85rem;">
                            <button type="submit" style="background: var(--primary); color: #fff; border: none; padding: 6px; border-radius: 4px; cursor: pointer; font-size: 0.85rem; font-weight: bold;">Avaliar Foto</button>
                        </form>
                    <?php else: ?>
                        <p style="font-size: 0.8rem; color: #888; margin: 0; text-align: center;"><a href="login.php" style="color: var(--primary);">Faça login</a> para avaliar.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="events.php" class="clear-search">← Voltar ao Calendário</a>
    </div>

</main>

<?php
$db->close();
include 'footer.php';
?>