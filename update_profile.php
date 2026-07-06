<?php
session_start();

// 1. SEGURANÇA: Bloqueio de acesso não autorizado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $db = new SQLite3('academies.db');
    $user_id = (int)$_SESSION['user_id'];

    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    // 2. BUSCAR DADOS ATUAIS
    $stmt = $db->prepare("SELECT password, email FROM users WHERE id = :id");
    $stmt->bindValue(':id', $user_id, SQLITE3_INTEGER);
    $user = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    // 3. VALIDAÇÕES DE SEGURANÇA
    if (!$user || !password_verify($current_password, $user['password'])) {
        $message = 'erro:A senha atual está incorreta.';
    } 
    // Verificar se o novo e-mail já existe em outra conta
    else {
        $stmt_check = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt_check->bindValue(':email', $email, SQLITE3_TEXT);
        $stmt_check->bindValue(':id', $user_id, SQLITE3_INTEGER);
        $email_exists = $stmt_check->execute()->fetchArray();

        if ($email_exists) {
            $message = 'erro:Este e-mail já está a ser utilizado por outra conta.';
        } else {
            // 4. ATUALIZAÇÃO DOS DADOS
            $db->exec('BEGIN TRANSACTION');
            try {
                // Atualizar E-mail
                $update_email = $db->prepare("UPDATE users SET email = :email WHERE id = :id");
                $update_email->bindValue(':email', $email, SQLITE3_TEXT);
                $update_email->bindValue(':id', $user_id, SQLITE3_INTEGER);
                $update_email->execute();

                // Atualizar Senha (se fornecida)
                if (!empty($new_password)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pass = $db->prepare("UPDATE users SET password = :pass WHERE id = :id");
                    $update_pass->bindValue(':pass', $hashed_password, SQLITE3_TEXT);
                    $update_pass->bindValue(':id', $user_id, SQLITE3_INTEGER);
                    $update_pass->execute();
                }

                $db->exec('COMMIT');
                $message = 'sucesso:Dados atualizados com sucesso!';
            } catch (Throwable $e) {
                $db->exec('ROLLBACK');
                $message = 'erro:Erro interno ao guardar os dados.';
            }
        }
    }
    $db->close();
}

// Redireciona com o feedback
header('Location: user_profiles.php?message=' . urlencode($message));
exit;