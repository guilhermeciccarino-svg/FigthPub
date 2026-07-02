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
        $mensagem_alerta = "<div class='alert-success' style='background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>Aviso publicado com sucesso no mural!</div>";
    } else {
        $mensagem_alerta = "<div class='alert-danger' style='background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>Erro ao publicar o aviso.</div>";
    }
}

// PROCESSAR A EXCLUSÃO DE AVISO
if (isset($_GET['delete'])) {
    $id_to_delete = (int)$_GET['delete'];
    $db->exec("DELETE FROM announcements WHERE id = $id_to_delete");
    $mensagem_alerta = "<div class='alert-success' style='background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>Aviso removido do mural.</div>";
}

// BUSCAR TODOS OS AVISOS PARA MOSTRAR NA TABELA
$result = $db->query("SELECT * FROM announcements ORDER BY created_at DESC");
?>

<main style="max-width: 900px; margin: 0 auto; padding: 20px;">
    <h1 style="border-bottom: 3px solid #111; padding-bottom: 10px;">📢 Gerir Mural de Avisos</h1>
    
    <?php echo $mensagem_alerta; ?>

    <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; border-top: 5px solid #ffc107;">
        <h3 style="margin-top: 0;">Escrever Novo Aviso</h3>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            
            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Título do Aviso:</label>
                <input type="text" name="title" required placeholder="Ex: Dia de Graduação!" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Tipo / Cor do Aviso:</label>
                <select name="type" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="warning">🟡 Urgente / Atenção (Amarelo)</option>
                    <option value="info">⚪ Informação Geral (Cinzento)</option>
                    <option value="success">🟢 Sucesso / Boas Notícias (Verde)</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label style="font-weight: bold; display: block; margin-bottom: 5px;">Mensagem:</label>
                <textarea name="message" required rows="4" placeholder="Escreva a mensagem aqui..." style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;"></textarea>
            </div>
            
            <button type="submit" style="background: #111; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">Publicar no Mural</button>
        </form>
    </div>

    <div style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0;">Avisos Ativos no Mural</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Data</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Título</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: left;">Tipo</th>
                    <th style="padding: 10px; border-bottom: 2px solid #ddd; text-align: center;">Ação</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetchArray(SQLITE3_ASSOC)): ?>
                    <tr>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;"><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee;">
                            <?php 
                                if($row['type'] == 'warning') echo '🟡 Atenção';
                                elseif($row['type'] == 'success') echo '🟢 Sucesso';
                                else echo '⚪ Info';
                            ?>
                        </td>
                        <td style="padding: 10px; border-bottom: 1px solid #eee; text-align: center;">
                            <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Tem certeza que deseja apagar este aviso?')" style="color: #d32f2f; text-decoration: none; font-weight: bold;">❌ Apagar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</main>

<?php
$db->close();
include 'footer.php';
?>