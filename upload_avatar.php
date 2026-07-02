<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('erro:Não autorizado. Faça login primeiro.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['imagem'])) {
    $db = new SQLite3('academies.db');
    
    $user_id = $_SESSION['user_id'];
    $imagem = trim($_POST['imagem']); // Recebe o Base64 ou a URL

    // Atualiza a coluna avatar do usuário
    $stmt = $db->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
    $stmt->bindValue(':avatar', $imagem, SQLITE3_TEXT);
    $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
    
    if ($stmt->execute()) {
        echo 'sucesso';
    } else {
        echo 'erro:Falha ao guardar a imagem no banco de dados.';
    }
    
    $db->close();
} else {
    echo 'erro:Nenhum dado recebido.';
}
?>