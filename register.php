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
            } catch (Throwable $e) {
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

<!-- FOLHA DE ESTILO CUSTOMIZADA PARA REESCREVER O DESIGN VELHO DA MATRÍCULA -->
<style>
.register-page-container {
    background: radial-gradient(circle at top, #1a0808 0%, #050505 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 100px 20px 40px;
    box-sizing: border-box;
}

.register-card-dark {
    background: rgba(17, 17, 17, 0.75);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    border: 1px solid #222222;
    border-top: 4px solid #d32f2f;
    border-radius: 12px;
    width: 100%;
    max-width: 680px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
    overflow: hidden;
    transition: all 0.5s ease;
}

.register-card-dark.card-success-glow {
    border-color: #28a745;
    box-shadow: 0 0 40px rgba(40, 167, 69, 0.2), 0 20px 50px rgba(0, 0, 0, 0.7);
}

.register-card-dark.card-error-glow {
    border-color: #d32f2f;
    box-shadow: 0 0 40px rgba(211, 47, 47, 0.2), 0 20px 50px rgba(0, 0, 0, 0.7);
}

.register-header-dark {
    background: #111111;
    padding: 30px;
    text-align: center;
    border-bottom: 1px solid #222222;
}

.register-header-dark h1 {
    font-family: 'Oswald', sans-serif;
    color: #ffffff;
    font-size: 2.2rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin: 0 0 5px 0;
    border: none;
    padding: 0;
}

.register-header-dark p {
    color: #666666;
    margin: 0;
    font-size: 0.95rem;
}

.register-form-dark {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    padding: 35px 40px !important;
    margin: 0 !important;
}

.section-divider-dark {
    font-family: 'Oswald', sans-serif;
    font-size: 0.85rem;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: #ffd700; /* Detalhe em ouro igual aos seus badges de admin */
    margin: 25px 0 15px 0;
    padding-bottom: 6px;
    border-bottom: 1px solid #222222;
}

.register-form-dark .form-group label {
    color: #aaaaaa !important;
    font-size: 0.8rem;
    letter-spacing: 0.5px;
}

.register-form-dark .form-group input {
    background: #0d0d0d !important;
    border: 1px solid #222222 !important;
    color: #ffffff !important;
    padding: 12px 15px !important;
    border-radius: 6px !important;
    font-size: 0.95rem !important;
}

.register-form-dark .form-group input:focus {
    border-color: #d32f2f !important;
    box-shadow: 0 0 0 3px rgba(211, 47, 47, 0.22) !important;
    background: #111111 !important;
}

.submit-btn-dark {
    width: 100%;
    background: #d32f2f;
    color: #ffffff;
    font-family: 'Oswald', sans-serif;
    font-size: 1.1rem;
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    padding: 15px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-top: 20px;
    box-shadow: 0 4px 15px rgba(211, 47, 47, 0.3);
    transition: all 0.25s ease;
}

.submit-btn-dark:hover:not(:disabled) {
    background: #b71c1c;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(211, 47, 47, 0.5);
}

.submit-btn-dark:disabled {
    background: #333333;
    color: #777777;
    cursor: not-allowed;
    box-shadow: none;
}

.alert-box-dark {
    padding: 15px;
    border-radius: 6px;
    margin: 25px 40px 0 40px;
    font-size: 0.95rem;
    text-align: center;
    font-weight: bold;
}

.alert-box-dark-error {
    background: rgba(211, 47, 47, 0.1);
    color: #ff6b6b;
    border: 1px solid rgba(211, 47, 47, 0.3);
}

.alert-box-dark-success {
    background: rgba(40, 167, 69, 0.1);
    color: #6bcf7f;
    border: 1px solid rgba(40, 167, 69, 0.3);
}

.register-footer-dark {
    text-align: center;
    padding: 20px;
    background: #0d0d0d;
    border-top: 1px solid #222222;
    font-size: 0.9rem;
    color: #666666;
}

.register-footer-dark a {
    color: #d32f2f;
    font-weight: 700;
    text-decoration: none;
}
.register-footer-dark a:hover {
    text-decoration: underline;
}
</style>

<div class="register-page-wrapper">
    <div class="register-page-container">
        <div class="register-card-dark" id="regCard">

            <!-- CABEÇALHO DO CARD -->
            <div class="register-header-dark">
                <div class="register-logo"></div>
                <h1>Matrícula Oficial</h1>
                <p>Junta-te à comunidade Fight Pub</p>
            </div>

            <!-- EXIBIÇÃO DE ALERTAS CUSTOMIZADOS -->
            <?php if ($message): ?>
                <?php if ($status === 'error'): ?>
                    <div class="alert-box-dark alert-box-dark-error">
                         <?php echo htmlspecialchars(substr($message, 5)); ?>
                    </div>
                <?php elseif ($status === 'success'): ?>
                    <div class="alert-box-dark alert-box-dark-success">
                         <?php echo htmlspecialchars(substr($message, 8)); ?><br><br>
                        <a href="login.php" style="background:#28a745; color:white; padding:8px 16px; border-radius:4px; text-decoration:none; font-weight:bold; display:inline-block;">Entrar no Tatame →</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- FORMULÁRIO -->
            <?php if ($status !== 'success'): ?>
            <form method="POST" id="regForm" class="register-form-dark" novalidate>

                <div class="section-divider-dark"> Dados Pessoais</div>

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
                        <input type="text" name="phone" required placeholder="Contacto telefónico"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label>Contacto de Emergência</label>
                        <input type="text" name="emergency_contact" required placeholder="Nome e número do contacto"
                               value="<?php echo htmlspecialchars($_POST['emergency_contact'] ?? ''); ?>">
                    </div>
                </div>

                <div class="section-divider-dark"> Dados de Acesso</div>

                <div class="form-row">
                    <div class="form-grid-3" style="grid-column: 1 / -1; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; width: 100%;">
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
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="password" required placeholder="Cria uma senha de acesso">
                    </div>
                    <div class="form-group">
                        <label>Confirmar Senha</label>
                        <input type="password" name="confirm_password" required placeholder="Repete a senha criada">
                    </div>
                </div>

                <button type="submit" class="submit-btn-dark" id="regBtn">
                    <span id="regBtnText">Finalizar Matrícula</span>
                    <span id="regBtnSpinner" style="display:none">⏳ A processar...</span>
                </button>
            </form>
            <?php endif; ?>

            <div class="register-footer-dark">
                Já és filiado? <a href="login.php">Fazer login →</a>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var status = <?php echo json_encode($status); ?>;
    var card   = document.getElementById('regCard');

    if (status === 'success') {
        card.classList.add('card-success-glow');
    } else if (status === 'error') {
        card.classList.add('card-error-glow');
    }

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