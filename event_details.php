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

    <div style="text-align: center; margin-top: 2rem;">
        <a href="events.php" class="clear-search">← Voltar ao Calendário</a>
    </div>

</main>

<?php
$db->close();
include 'footer.php';
?>