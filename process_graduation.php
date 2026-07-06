<?php
session_start();

// 1. VERIFICAÇÃO DE SEGURANÇA: Apenas instrutores podem aceder a esta página
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    // Se não for instrutor, manda para o login e para a execução
    header('Location: login.php');
    exit;
}

// 2. VERIFICA SE OS DADOS FORAM ENVIADOS POR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Liga à base de dados
    $db = new SQLite3('academies.db');

    // 3. RECOLHER OS DADOS DO FORMULÁRIO E DA SESSÃO
    $instructor_id = $_SESSION['user_id']; // O ID do instrutor é quem está logado!
    $student_id = (int)$_POST['student_id'];
    $martial_art = trim($_POST['martial_art']);
    $belt_rank = trim($_POST['belt_rank']);
    $graduation_date = trim($_POST['graduation_date']);

    // 4. INSERIR NA BASE DE DADOS (Com segurança máxima)
    try {
        $query = "INSERT INTO graduations (student_id, instructor_id, martial_art, belt_rank, graduation_date) 
                  VALUES (:student_id, :instructor_id, :martial_art, :belt_rank, :graduation_date)";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':student_id', $student_id, SQLITE3_INTEGER);
        $stmt->bindValue(':instructor_id', $instructor_id, SQLITE3_INTEGER);
        $stmt->bindValue(':martial_art', $martial_art, SQLITE3_TEXT);
        $stmt->bindValue(':belt_rank', $belt_rank, SQLITE3_TEXT);
        $stmt->bindValue(':graduation_date', $graduation_date, SQLITE3_TEXT);

        if ($stmt->execute()) {
            // Se correr bem, guardamos uma mensagem de sucesso na sessão
            $_SESSION['msg_sucesso'] = "Aluno graduado com sucesso! A nova faixa já está registada.";
        } else {
            $_SESSION['msg_erro'] = "Ocorreu um erro ao tentar guardar a graduação.";
        }
        
    } catch (Throwable $e) {
        $_SESSION['msg_erro'] = "Erro de sistema: " . $e->getMessage();
    }

    // Fecha a ligação à base de dados
    $db->close();

    // 5. REDIRECIONAR DE VOLTA PARA A PÁGINA DE GRADUAÇÃO
    header('Location: graduation.php');
    exit;
} else {
    // Se alguém tentar aceder a esta página escrevendo o link diretamente no navegador
    header('Location: graduation.php');
    exit;
}