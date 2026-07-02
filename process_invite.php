<?php
session_start();

// Verifica se está logado e se é um aluno
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $notif_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];

    $db = new SQLite3('academies.db');

    // Verifica se a notificação existe e pertence a este utilizador
    $stmt = $db->prepare("SELECT * FROM notifications WHERE id = :nid AND user_id = :uid AND status = 'pending'");
    $stmt->bindValue(':nid', $notif_id, SQLITE3_INTEGER);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $notif = $result->fetchArray(SQLITE3_ASSOC);

    if ($notif) {
        if ($action === 'accept') {
            // 1. Atualiza o status da notificação para aceite
            $update_notif = $db->prepare("UPDATE notifications SET status = 'accepted' WHERE id = :nid");
            $update_notif->bindValue(':nid', $notif_id, SQLITE3_INTEGER);
            $update_notif->execute();

            // 2. Associa o aluno à academia na tabela 'students'
            $academy_id = $notif['academy_id'];
            $update_student = $db->prepare("UPDATE students SET academy_id = :aid WHERE user_id = :uid");
            $update_student->bindValue(':aid', $academy_id, SQLITE3_INTEGER);
            $update_student->bindValue(':uid', $user_id, SQLITE3_INTEGER);
            $update_student->execute();

            $mensagem = "sucesso:Bem-vindo à sua nova academia! O seu perfil foi atualizado com sucesso.";
            
        } elseif ($action === 'reject') {
            // Apenas recusa a notificação e não altera a academia
            $update_notif = $db->prepare("UPDATE notifications SET status = 'rejected' WHERE id = :nid");
            $update_notif->bindValue(':nid', $notif_id, SQLITE3_INTEGER);
            $update_notif->execute();

            $mensagem = "sucesso:Você recusou o convite.";
        }
    } else {
        $mensagem = "erro:Convite inválido ou já respondido.";
    }

    $db->close();
    
    // Redireciona o aluno de volta para o perfil com uma mensagem
    header("Location: user_profiles.php?message=" . urlencode($mensagem));
    exit;
}

// Se não tiver action ou id, volta para o index
header("Location: index.php");
exit;