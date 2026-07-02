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
        return "<p style='color: #888; font-style: italic;'>Nenhuma informação fornecida.</p>";
    }

    // Divide o texto em pedaços usando a quebra de linha (\n)
    $linhas = explode("\n", trim($texto_do_banco));
    
    $html = "<ul style='padding-left: 20px; margin: 0;'>";
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if (!empty($linha)) {
            // Cria um "item de lista" para cada linha
            $html .= "<li style='margin-bottom: 8px; color: #444;'>" . htmlspecialchars($linha) . "</li>";
        }
    }
    $html .= "</ul>";
    
    return $html;
}
?>

<main style="max-width: 1000px; margin: 0 auto; padding: 2rem;">
    
    <div style="background: #111; color: white; padding: 3rem 2rem; border-radius: 8px 8px 0 0; text-align: center; border-bottom: 5px solid #ff9800;">
        <span style="background: rgba(255, 255, 255, 0.2); padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; letter-spacing: 1px; text-transform: uppercase;">
            <?php echo htmlspecialchars($event['martial_art_type']); ?>
        </span>
        <h1 style="margin: 20px 0 10px 0; font-size: 3rem;"><?php echo htmlspecialchars($event['name']); ?></h1>
    </div>

    <div style="background: #fff; padding: 2rem; border-left: 1px solid #eee; border-right: 1px solid #eee;">
        <h2 style="color: #d32f2f; margin-top: 0; font-size: 1.5rem;">📖 Sobre o Torneio</h2>
        <p style="font-size: 1.1rem; color: #555; line-height: 1.8;">
            <?php echo nl2br(htmlspecialchars($event['description'])); ?>
        </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; padding: 2rem; background: #fdfdfd; border: 1px solid #eee; border-radius: 0 0 8px 8px;">
        
        <div style="background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #d32f2f;">
            <h3 style="margin-top: 0; color: #111; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">⚖️</span> Livro de Regras
            </h3>
            <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 15px;">
            <?php echo formatar_lista($event['rules']); ?>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #ff9800;">
            <h3 style="margin-top: 0; color: #111; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">⚖️</span> Tabela de Pesos
            </h3>
            <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 15px;">
            <?php echo formatar_lista($event['weight_classes']); ?>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border-top: 4px solid #28a745;">
            <h3 style="margin-top: 0; color: #111; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 1.5rem;">🥋</span> Graduações
            </h3>
            <hr style="border: 0; border-top: 1px solid #eee; margin-bottom: 15px;">
            <?php echo formatar_lista($event['belt_ranks']); ?>
        </div>

    </div>

    <div style="text-align: center; margin-top: 2rem;">
        <a href="events.php" style="color: #666; text-decoration: none; font-weight: bold;">← Voltar ao Calendário</a>
    </div>

</main>

<?php
$db->close();
include 'footer.php';
?>