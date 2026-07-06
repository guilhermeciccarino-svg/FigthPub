<?php
session_start();

// Apenas administradores podem aceder a esta página
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

include 'header.php';

$db = new SQLite3('academies.db');

// Criar a tabela automaticamente se ela ainda não existir
$query_create = "CREATE TABLE IF NOT EXISTS announcements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    message TEXT NOT NULL,
    type TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)";
$db->exec($query_create);

$mensagem_alerta = '';

// PROCESSAR O FORMULÁRIO (ADICIONAR AVISO)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'add') {
    $title = SQLite3::escapeString($_POST['title']);
    $message = SQLite3::escapeString($_POST['message']);
    $type = SQLite3::escapeString($_POST['type']); // 'warning', 'info', ou 'success'

    $stmt = $db->prepare("INSERT INTO announcements (title, message, type) VALUES (:title, :message, :type)");
    $stmt->bindValue(':title', $title, SQLITE3_TEXT);
    $stmt->bindValue(':message', $message, SQLITE3_TEXT);
    $stmt->bindValue(':type', $type, SQLITE3_TEXT);

    if ($stmt->execute()) {
        $mensagem_alerta = "<div class='alert-success'>Aviso publicado com sucesso no mural!</div>";
    } else {
        $mensagem_alerta = "<div class='alert-danger'>Erro ao publicar o aviso.</div>";
    }
}

// PROCESSAR A EXCLUSÃO DE AVISO
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];
    $db->exec("DELETE FROM announcements WHERE id = $id_to_delete");
    $mensagem_alerta = "<div class='alert-success'>Aviso removido do mural.</div>";
}

// BUSCAR TODOS OS AVISOS PARA MOSTRAR NA TABELA
$result = $db->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>

<main style="max-width: 900px; margin: 0 auto; padding: 20px;">
    <div class="admin-header">
        <h1> Gerir Mural de Avisos</h1>
    </div>

    <?php echo $mensagem_alerta; ?>

    <div class="admin-section" style="border-top: 5px solid var(--gold);">
        <h3>Escrever Novo Aviso</h3>
        <form method="POST" action="" class="admin-form-inner">
            <input type="hidden" name="action" value="add">

            <div class="form-group">
                <label>Título do Aviso:</label>
                <input type="text" name="title" required placeholder="Ex: Dia de Graduação!">
            </div>

            <div class="form-group">
                <label>Tipo / Cor do Aviso:</label>
                <select name="type" required>
                    <option value="warning"> Urgente / Atenção</option>
                    <option value="info"> Informação Geral</option>
                    <option value="success"> Sucesso / Boas Notícias</option>
                </select>
            </div>

            <div class="form-group">
                <label>Mensagem:</label>
                <textarea name="message" required rows="4" placeholder="Escreva a mensagem aqui..."></textarea>
            </div>

            <button type="submit" class="btn-admin">Publicar no Mural</button>
        </form>
    </div>

    <div class="admin-section">
        <h3>Avisos Ativos no Mural</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th style="text-align:center">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $tem_aviso = false; while($row = $result->fetchArray(SQLITE3_ASSOC)): $tem_aviso = true; ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td>
                                <?php
                                    if($row['type'] == 'warning') echo ' Atenção';
                                    elseif($row['type'] == 'success') echo ' Sucesso';
                                    else echo ' Info';
                                ?>
                            </td>
                            <td style="text-align:center">
                                <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja apagar este aviso?')" class="btn-text-danger">Apagar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$tem_aviso): ?>
                        <tr><td colspan="4" style="text-align:center; color:var(--text-muted); padding:2rem;">Nenhum aviso publicado ainda.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
$db->close();
include 'footer.php';
?>