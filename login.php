<?php
session_start();

// Se já estiver logado, manda pro início
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';

// PROCESSAR O LOGIN ANTES DE ENVIAR QUALQUER HTML
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = $_POST['password'] ?? '';

    $db = new SQLite3('academies.db');
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindValue(':username', $username, SQLITE3_TEXT);
    $result = $stmt->execute();
    $user = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: index.php');
        exit;
    } else {
        $message = 'Nome de usuário ou senha incorretos.';
    }
}

// SÓ DEPOIS DO PROCESSAMENTO É QUE SE INCLUI O HEADER (envia HTML)
include 'header.php';
?>

<main>
    <div class="auth-container">
        <div class="auth-header">
            <h1> Entrar</h1>
            <p>Acesse sua conta para continuar.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert-danger" style="margin-top: 0; margin-bottom: 1.5rem; text-align: center;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Nome de Usuário:</label>
                <input type="text" id="username" name="username" required placeholder="Digite seu usuário">
            </div>

            <div class="form-group">
                <label for="password">Senha:</label>
                <input type="password" id="password" name="password" required placeholder="Digite sua senha">
            </div>

            <input type="submit" value="Entrar no Tatame" style="width: 100%; margin-top: 1rem; font-size: 1.1rem; padding: 1rem;">
        </form>

        <div class="auth-footer">
            <p>Ainda não é filiado? <br> <a href="register.php">Registre-se aqui</a>.</p>
        </div>
    </div>
</main>

<?php include 'footer.php'; ?>
