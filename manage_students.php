<?php
// 1. GESTÃO DE ACESSO E CONEXÃO
session_start();
include 'header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo "<script>alert('Acesso restrito a Instrutores.'); window.location.href='index.php';</script>";
    exit;
}

$db = new SQLite3('academies.db');
$db->busyTimeout(5000);

$logged_in_user_id = $_SESSION['user_id'];

// Buscar perfil do instrutor para saber qual academia ele gere
$stmt = $db->prepare("
    SELECT instructors.id AS instructor_id, instructors.academy_id
    FROM users
    JOIN instructors ON users.instructor_id = instructors.id
    WHERE users.id = :uid
");
$stmt->bindValue(':uid', $logged_in_user_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$instructor = $result->fetchArray(SQLITE3_ASSOC);

if (!$instructor) {
    die("<main style='padding:4rem; text-align:center;'><h2>Erro</h2><p>Perfil de instrutor não configurado.</p></main>");
}

$academy_id = $instructor['academy_id'];
$mensagem = "";
// 2. LÓGICA DE ENVIO DE CONVITE (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_user_id = (int)($_POST['student_user_id'] ?? 0);
    $cc_inserido = trim($_POST['cc'] ?? '');

    if ($student_user_id === 0 || empty($cc_inserido)) {
        $mensagem = "<div class='alert-danger'> Preencha todos os campos.</div>";
    } else {
        // VALIDAR CC: Verifica se o CC bate com o que o aluno registou no perfil dele
        $check_cc = $db->prepare("SELECT CC FROM students WHERE user_id = :uid LIMIT 1");
        $check_cc->bindValue(':uid', $student_user_id, SQLITE3_INTEGER);
        $res_cc = $check_cc->execute();
        $aluno_db = $res_cc->fetchArray(SQLITE3_ASSOC);

        if ($aluno_db && $aluno_db['CC'] === $cc_inserido) {
            // Validar se já existe convite pendente para este aluno nesta academia
            $check_notif = $db->prepare("SELECT id FROM notifications WHERE user_id = :uid AND academy_id = :aid AND status = 'pending'");
            $check_notif->bindValue(':uid', $student_user_id, SQLITE3_INTEGER);
            $check_notif->bindValue(':aid', $academy_id, SQLITE3_INTEGER);

            if ($check_notif->execute()->fetchArray()) {
                $mensagem = "<div class='alert-danger'> Este aluno já tem um convite em espera.</div>";
            } else {
                // Criar a notificação que o aluno verá no painel dele
                $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, academy_id, type, message, status) VALUES (:uid, :sid, :aid, 'invite', 'Foste convidado para treinar connosco!', 'pending')");
                $notif_stmt->bindValue(':uid', $student_user_id, SQLITE3_INTEGER);
                $notif_stmt->bindValue(':sid', $logged_in_user_id, SQLITE3_INTEGER);
                $notif_stmt->bindValue(':aid', $academy_id, SQLITE3_INTEGER);

                if ($notif_stmt->execute()) {
                    $mensagem = "<div class='alert-success'> Convite enviado! O aluno precisa de aceitar no perfil dele.</div>";
                }
            }
        } else {
            $mensagem = "<div class='alert-danger'> <strong>Erro de Validação:</strong> O CC não coincide com os dados do utilizador.</div>";
        }
    }
}

// 3. CONSULTAS PARA A INTERFACE (GET)

// A) Utilizadores registados que ainda não estão nesta academia e não têm convites pendentes
$available_users = $db->query("
    SELECT u.id, u.username, u.email
    FROM users u
    JOIN students s ON u.id = s.user_id
    WHERE u.role = 'user'
    AND (s.academy_id IS NULL OR s.academy_id != $academy_id)
    AND u.id NOT IN (SELECT user_id FROM notifications WHERE academy_id = $academy_id AND status = 'pending')
");

// B) Lista de Alunos Ativos
$stmt_active = $db->prepare("SELECT * FROM students WHERE academy_id = :aid ORDER BY full_name ASC");
$stmt_active->bindValue(':aid', $academy_id, SQLITE3_INTEGER);
$active_students = $stmt_active->execute();
?>

<main style="max-width: 900px; margin: 0 auto; padding: 20px;">

    <div class="panel-banner" style="margin-bottom: 2rem;">
        <h1 style="margin:0;">‍ Gerir Alunos e Inscrições</h1>
        <p>Adicione novos talentos e controle a sua base de atletas.</p>
    </div>

    <?php echo $mensagem; ?>

    <div class="admin-section">
        <h3> Enviar Novo Convite</h3>
        <form method="POST" class="invite-form-grid">
            <div class="form-group" style="margin:0;">
                <label>Utilizador:</label>
                <select name="student_user_id" required>
                    <option value="">-- Selecione --</option>
                    <?php while ($u = $available_users->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?> (<?php echo $u['email']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin:0;">
                <label>Validar CC:</label>
                <input type="text" name="cc" placeholder="Nº do Cartão de Cidadão" required>
            </div>
            <button type="submit" class="btn-admin" style="height:42px;">Convidar</button>
        </form>
    </div>

    <h2 style="color: #4caf50; border-bottom: 2px solid #28a745; padding-bottom: 10px; margin-top: 2.5rem;"> Alunos Matriculados</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin-top: 20px;">
        <?php
        $has_students = false;
        while ($s = $active_students->fetchArray(SQLITE3_ASSOC)):
            $has_students = true;
        ?>
            <div class="student-card">
                <h4><?php echo htmlspecialchars($s['full_name']); ?></h4>
                <div class="meta">
                    <p> <?php echo htmlspecialchars($s['phone']); ?></p>
                    <p> CC: <?php echo htmlspecialchars($s['CC']); ?></p>
                    <hr>
                    <p class="emergency"><strong>Emergência:</strong> <?php echo htmlspecialchars($s['emergency_contact']); ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if (!$has_students): ?>
        <div class="fp-empty-state" style="margin-top:1.5rem;">

            <p>Ainda não tens alunos matriculados.</p>
        </div>
    <?php endif; ?>

</main>

<?php $db->close(); include 'footer.php'; ?>