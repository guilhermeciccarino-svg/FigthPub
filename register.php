<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$message = '';
$status  = 'none'; // 'none' | 'success' | 'error'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username          = trim($_POST['username'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $password          = $_POST['password'] ?? '';
    $confirm_password  = $_POST['confirm_password'] ?? '';
    $full_name         = trim($_POST['full_name'] ?? '');
    $cc                = trim($_POST['cc'] ?? '');
    $birth_date        = trim($_POST['birth_date'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $emergency_contact = trim($_POST['emergency_contact'] ?? '');

    if ($password !== $confirm_password) {
        $message = 'erro:As senhas não coincidem.';
        $status  = 'error';
    } else {
        $db = new SQLite3('academies.db');

        $check_user = $db->prepare("SELECT id FROM users WHERE username=:u OR email=:e");
        $check_user->bindValue(':u', $username, SQLITE3_TEXT);
        $check_user->bindValue(':e', $email,    SQLITE3_TEXT);

        $check_cc = $db->prepare("SELECT id FROM students WHERE CC=:cc");
        $check_cc->bindValue(':cc', $cc, SQLITE3_TEXT);

        if ($check_user->execute()->fetchArray()) {
            $message = 'erro:Este utilizador ou email já existem.';
            $status  = 'error';
        } elseif ($check_cc->execute()->fetchArray()) {
            $message = 'erro:Este documento já está registado.';
            $status  = 'error';
        } else {
            $db->exec('BEGIN');
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $su = $db->prepare("INSERT INTO users (username,email,password,role) VALUES (:u,:e,:p,'user')");
                $su->bindValue(':u', $username, SQLITE3_TEXT);
                $su->bindValue(':e', $email,    SQLITE3_TEXT);
                $su->bindValue(':p', $hash,     SQLITE3_TEXT);
                $su->execute();
                $new_id = $db->lastInsertRowID();

                $ss = $db->prepare("INSERT INTO students (user_id,full_name,birth_date,phone,emergency_contact,CC) VALUES (:uid,:fn,:bd,:ph,:ec,:cc)");
                $ss->bindValue(':uid', $new_id,           SQLITE3_INTEGER);
                $ss->bindValue(':fn',  $full_name,        SQLITE3_TEXT);
                $ss->bindValue(':bd',  $birth_date,       SQLITE3_TEXT);
                $ss->bindValue(':ph',  $phone,            SQLITE3_TEXT);
                $ss->bindValue(':ec',  $emergency_contact,SQLITE3_TEXT);
                $ss->bindValue(':cc',  $cc,               SQLITE3_TEXT);
                $ss->execute();

                $db->exec('COMMIT');
                $message = 'sucesso:Conta criada com sucesso! Pode fazer login agora.';
                $status  = 'success';
            } catch (Exception $e) {
                $db->exec('ROLLBACK');
                $message = 'erro:Erro interno. Tente novamente.';
                $status  = 'error';
            }
        }
        $db->close();
    }
}

include 'header.php';
?>

<!-- FUNDO ANIMADO DO REGISTO -->
<div id="reg-bg" class="reg-bg reg-bg-<?php echo $status; ?>"></div>

<main class="register-main" id="regMain">
    <div class="register-card" id="regCard">

        <!-- CABEÇALHO DO CARD -->
        <div class="register-card-header">
            <div class="register-logo">⚔️</div>
            <h1>Matrícula Oficial</h1>
            <p>Junta-te à comunidade Fight Pub</p>
        </div>

        <!-- ALERTAS -->
        <?php if ($message): ?>
        <?php if ($status === 'error'): ?>
        <div class="reg-alert reg-alert-error">
            ✖ <?php echo htmlspecialchars(substr($message, 5)); ?>
        </div>
        <?php elseif ($status === 'success'): ?>
        <div class="reg-alert reg-alert-success">
            ✔ <?php echo htmlspecialchars(substr($message, 8)); ?>
            <a href="login.php" style="color:#fff;font-weight:bold;margin-left:8px;">Entrar agora →</a>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- FORMULÁRIO -->
        <?php if ($status !== 'success'): ?>
        <form method="POST" id="regForm" novalidate>

            <div class="reg-section-label">👤 Dados Pessoais</div>

            <div class="form-group">
                <label>Nome Completo</label>
                <input type="text" name="full_name" required placeholder="O teu nome completo"
                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Cartão de Cidadão / RG</label>
                    <input type="text" name="cc" required placeholder="Nº do documento"
                           value="<?php echo htmlspecialchars($_POST['cc'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Data de Nascimento</label>
                    <input type="date" name="birth_date" required
                           value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Telemóvel</label>
                    <input type="text" name="phone" required placeholder="Contacto"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Contacto de Emergência</label>
                    <input type="text" name="emergency_contact" required placeholder="Nome e número"
                           value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                </div>
            </div>

            <div class="reg-section-label" style="margin-top:1.5rem">🔐 Dados de Acesso</div>

            <div class="form-row">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Para fazer login"
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="O teu email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Senha</label>
                    <input type="password" name="password" required placeholder="Cria uma senha">
                </div>
                <div class="form-group">
                    <label>Confirmar Senha</label>
                    <input type="password" name="confirm_password" required placeholder="Repete a senha">
                </div>
            </div>

            <button type="submit" class="reg-submit-btn" id="regBtn">
                <span id="regBtnText">Finalizar Matrícula</span>
                <span id="regBtnSpinner" style="display:none">⏳ A processar...</span>
            </button>
        </form>
        <?php endif; ?>

        <div class="register-card-footer">
            Já és filiado? <a href="login.php">Fazer login →</a>
        </div>
    </div>
</main>

<script>
(function() {
    var status = <?php echo json_encode($status); ?>;
    var bg     = document.getElementById('reg-bg');
    var card   = document.getElementById('regCard');

    // Aplica o estado inicial baseado no resultado PHP
    if (status === 'success') {
        bg.classList.add('reg-bg-success-anim');
        card.classList.add('reg-card-success');
    } else if (status === 'error') {
        bg.classList.add('reg-bg-error-anim');
        card.classList.add('reg-card-error');
    }

    // Animação no clique do botão antes de submeter
    var form = document.getElementById('regForm');
    if (form) {
        form.addEventListener('submit', function() {
            var btn     = document.getElementById('regBtn');
            var txt     = document.getElementById('regBtnText');
            var spinner = document.getElementById('regBtnSpinner');
            if (txt)     txt.style.display     = 'none';
            if (spinner) spinner.style.display = 'inline';
            if (btn)     btn.disabled           = true;
        });
    }
})();
</script>

<?php include 'footer.php'; ?>
