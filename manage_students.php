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
        $mensagem = "<div class='alert-danger' style='background:#f8d7da; padding:15px; border-radius:5px; margin-bottom:20px;'>⚠️ Preencha todos os campos.</div>";
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
                $mensagem = "<div style='background:#fff3cd; padding:15px; border-radius:5px; margin-bottom:20px;'>⚠️ Este aluno já tem um convite em espera.</div>";
            } else {
                // Criar a notificação que o aluno verá no painel dele
                $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, academy_id, type, message, status) VALUES (:uid, :sid, :aid, 'invite', 'Foste convidado para treinar connosco!', 'pending')");
                $notif_stmt->bindValue(':uid', $student_user_id, SQLITE3_INTEGER);
                $notif_stmt->bindValue(':sid', $logged_in_user_id, SQLITE3_INTEGER);
                $notif_stmt->bindValue(':aid', $academy_id, SQLITE3_INTEGER);
                
                if ($notif_stmt->execute()) {
                    $mensagem = "<div style='background:#d4edda; color:#155724; padding:15px; border-radius:5px; margin-bottom:20px;'>📨 Convite enviado! O aluno precisa de aceitar no perfil dele.</div>";
                }
            }
        } else {
            $mensagem = "<div style='background:#f8d7da; color:#721c24; padding:15px; border-left:5px solid #d32f2f; margin-bottom:20px;'>🔒 <strong>Erro de Validação:</strong> O CC não coincide com os dados do utilizador.</div>";
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
    
    <div class="panel-banner" style="border-left: 5px solid #d32f2f; background: #fff; padding: 20px; margin-bottom: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h1 style="margin:0;">👨‍🎓 Gerir Alunos e Inscrições</h1>
        <p style="color:#666;">Adicione novos talentos e controle a sua base de atletas.</p>
    </div>

    <?php echo $mensagem; ?>

    <div style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; border-top: 4px solid #111;">
        <h3 style="margin-top:0;">📨 Enviar Novo Convite</h3>
        <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end; border:none; padding:0; box-shadow:none;">
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Utilizador:</label>
                <select name="student_user_id" required style="width:100%; padding:10px; border-radius:4px; border:1px solid #ccc;">
                    <option value="">-- Selecione --</option>
                    <?php while ($u = $available_users->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['username']); ?> (<?php echo $u['email']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Validar CC:</label>
                <input type="text" name="cc" placeholder="Nº do Cartão de Cidadão" required style="width:100%; padding:10px; border-radius:4px; border:1px solid #ccc;">
            </div>
            <button type="submit" class="btn" style="height:42px; background:#111;">Convidar</button>
        </form>
    </div>

    <h2 style="color: #155724; border-bottom: 2px solid #28a745; padding-bottom: 10px;">✅ Alunos Matriculados</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;">
        <?php 
        $has_students = false;
        while ($s = $active_students->fetchArray(SQLITE3_ASSOC)): 
            $has_students = true;
        ?>
            <div style="background:#fff; border:1px solid #ddd; padding:15px; border-radius:6px; position:relative;">
                <h4 style="margin:0 0 10px 0;"><?php echo htmlspecialchars($s['full_name']); ?></h4>
                <div style="font-size: 0.85rem; color:#555;">
                    <p style="margin:4px 0;">📞 <?php echo htmlspecialchars($s['phone']); ?></p>
                    <p style="margin:4px 0;">💳 CC: <?php echo htmlspecialchars($s['CC']); ?></p>
                    <hr style="border:0; border-top:1px solid #eee;">
                    <p style="margin:4px 0; color:#d32f2f;"><strong>Emergência:</strong> <?php echo htmlspecialchars($s['emergency_contact']); ?></p>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if (!$has_students): ?>
        <p style="text-align:center; color:#777; padding:40px; background:#f9f9f9; border-radius:8px;">Ainda não tens alunos matriculados.</p>
    <?php endif; ?>

</main>

<?php $db->close(); include 'footer.php'; ?>