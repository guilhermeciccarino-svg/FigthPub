<?php
session_start();
include 'header.php';

// Apenas instrutores podem aceder a esta página
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo "<script>alert('Acesso restrito a Mestres/Instrutores.'); window.location.href='index.php';</script>";
    exit;
}

$db = new SQLite3('academies.db');
$user_id = $_SESSION['user_id'];

// 1. Criar a tabela de presenças automaticamente se não existir
$query_create = "CREATE TABLE IF NOT EXISTS attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    schedule_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    class_date DATE NOT NULL,
    UNIQUE(schedule_id, student_id, class_date)
)";
$db->exec($query_create);

// 2. Descobrir a academia e o ID de instrutor associado a este login
$stmt_inst = $db->query("SELECT i.academy_id, i.id as inst_id FROM users u JOIN instructors i ON u.instructor_id = i.id WHERE u.id = $user_id");
$inst_data = $stmt_inst->fetchArray(SQLITE3_ASSOC);

if (!$inst_data) {
    die("<main><div class='alert-danger' style='margin: 2rem auto; max-width: 800px;'>Erro: Perfil de instrutor não encontrado ou sem academia vinculada.</div></main>");
}

$academy_id = $inst_data['academy_id'];
$inst_id = $inst_data['inst_id'];
$mensagem_alerta = '';

// 3. PROCESSAR O FORMULÁRIO DE GUARDAR PRESENÇAS (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $schedule_id = (int)$_POST['schedule_id'];
    $class_date = SQLite3::escapeString($_POST['class_date']);

    // Primeiro, apagamos as presenças desse dia/aula para recriar (assim permitimos desmarcar alunos se o instrutor se enganar)
    $db->exec("DELETE FROM attendance WHERE schedule_id = $schedule_id AND class_date = '$class_date'");

    // Inserir os alunos que foram marcados
    if (!empty($_POST['students']) && is_array($_POST['students'])) {
        $stmt_insert = $db->prepare("INSERT INTO attendance (schedule_id, student_id, class_date) VALUES (:sch_id, :st_id, :c_date)");

        $db->exec('BEGIN'); // Inicia a transação para gravar tudo super rápido
        foreach ($_POST['students'] as $st_id) {
            $stmt_insert->bindValue(':sch_id', $schedule_id, SQLITE3_INTEGER);
            $stmt_insert->bindValue(':st_id', (int)$st_id, SQLITE3_INTEGER);
            $stmt_insert->bindValue(':c_date', $class_date, SQLITE3_TEXT);
            $stmt_insert->execute();
        }
        $db->exec('COMMIT'); // Confirma as gravações
    }

    $mensagem_alerta = "<div class='alert-success'><strong> Sucesso!</strong> A lista de presenças do dia " . date('d/m/Y', strtotime($class_date)) . " foi guardada.</div>";
}

// 4. LER OS FILTROS DA PESQUISA (GET)
$selected_schedule = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : '';
$selected_date = isset($_GET['class_date']) ? $_GET['class_date'] : date('Y-m-d'); // Hoje por padrão

// Buscar as turmas que este instrutor dá
$schedules = $db->query("SELECT * FROM schedules WHERE instructor_id = $inst_id ORDER BY day, time");

// Buscar os alunos e as presenças se a turma estiver selecionada
$students = [];
$present_students = [];

if ($selected_schedule && $selected_date) {
    // Buscar todos os alunos que pertencem à academia do instrutor
    $res_students = $db->query("SELECT u.id as user_id, u.username, s.full_name
                                FROM users u
                                JOIN students s ON u.id = s.user_id
                                WHERE s.academy_id = $academy_id
                                ORDER BY s.full_name ASC");
    while($row = $res_students->fetchArray(SQLITE3_ASSOC)) {
        $students[] = $row;
    }

    // Buscar quem já tem presença marcada nesta aula e data
    $res_attendance = $db->query("SELECT student_id FROM attendance WHERE schedule_id = $selected_schedule AND class_date = '$selected_date'");
    while($row = $res_attendance->fetchArray(SQLITE3_ASSOC)) {
        $present_students[] = $row['student_id'];
    }
}
?>

<main style="max-width: 900px; margin: 0 auto; padding: 20px;">
    <div class="panel-banner" style="margin-bottom: 2rem;">
        <h1 style="margin: 0 0 10px 0;"> Controlo de Presenças</h1>
        <p style="margin:0; font-size: 1.05rem;">Faça a chamada dos seus alunos para manter o histórico de treinos em dia.</p>
    </div>

    <?php echo $mensagem_alerta; ?>

    <div class="admin-section">
        <h3>1. Selecionar Aula</h3>

        <form method="GET" action="" class="attendance-search-grid">

            <div class="form-group" style="margin: 0;">
                <label>Modalidade / Turma:</label>
                <select name="schedule_id" required>
                    <option value="">-- Escolha a sua turma --</option>
                    <?php while($sch = $schedules->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $sch['id']; ?>" <?php echo ($selected_schedule == $sch['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sch['day'] . " às " . $sch['time'] . " - " . $sch['class_type']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group" style="margin: 0;">
                <label>Data do Treino:</label>
                <input type="date" name="class_date" value="<?php echo htmlspecialchars($selected_date); ?>" required>
            </div>

            <button type="submit" class="btn-admin" style="height: 42px;">Buscar Lista</button>
        </form>
    </div>

    <?php if ($selected_schedule && $selected_date): ?>
        <div class="admin-section">
            <h3 style="border-bottom: 1px solid var(--card-border); padding-bottom: 10px;">2. Lista de Chamada</h3>

            <?php if (count($students) > 0): ?>
                <form method="POST" action="">
                    <input type="hidden" name="schedule_id" value="<?php echo $selected_schedule; ?>">
                    <input type="hidden" name="class_date" value="<?php echo htmlspecialchars($selected_date); ?>">

                    <div class="attendance-grid">
                        <?php foreach ($students as $student): ?>
                            <?php $is_present = in_array($student['user_id'], $present_students); ?>

                            <label class="attendance-item <?php echo $is_present ? 'present' : ''; ?>">
                                <input type="checkbox" name="students[]" value="<?php echo $student['user_id']; ?>" <?php echo $is_present ? 'checked' : ''; ?>>
                                <span>
                                    <?php echo !empty($student['full_name']) ? htmlspecialchars($student['full_name']) : htmlspecialchars($student['username']); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <button type="submit" name="save_attendance" class="btn-save-attendance"> Guardar Lista de Presenças</button>
                </form>
            <?php else: ?>
                <div class="fp-empty-state">

                    <p>A sua academia ainda não tem alunos matriculados para poder fazer a chamada.</p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<?php
$db->close();
include 'footer.php';
?>