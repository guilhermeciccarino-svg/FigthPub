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
    
    $mensagem_alerta = "<div class='alert-success' style='background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; border-left: 5px solid #28a745;'>
                            <strong>✅ Sucesso!</strong> A lista de presenças do dia " . date('d/m/Y', strtotime($class_date)) . " foi guardada.
                        </div>";
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
    <div class="panel-banner" style="border-left-color: #111; margin-bottom: 2rem;">
        <h1 style="margin: 0 0 10px 0;">📋 Controlo de Presenças</h1>
        <p style="margin:0; font-size: 1.1rem; color: #555;">Faça a chamada dos seus alunos para manter o histórico de treinos em dia.</p>
    </div>

    <?php echo $mensagem_alerta; ?>

    <div class="admin-section" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 30px; border-top: 4px solid #d32f2f;">
        <h3 style="margin-top: 0;">1. Selecionar Aula</h3>
        
        <form method="GET" action="" style="margin: 0; padding: 0; border: none; box-shadow: none; display: grid; grid-template-columns: 2fr 1fr auto; gap: 15px; align-items: end;">
            
            <div class="form-group" style="margin: 0;">
                <label style="font-weight: bold; margin-bottom: 5px; display: block;">Modalidade / Turma:</label>
                <select name="schedule_id" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <option value="">-- Escolha a sua turma --</option>
                    <?php while($sch = $schedules->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $sch['id']; ?>" <?php echo ($selected_schedule == $sch['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sch['day'] . " às " . $sch['time'] . " - " . $sch['class_type']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label style="font-weight: bold; margin-bottom: 5px; display: block;">Data do Treino:</label>
                <input type="date" name="class_date" value="<?php echo htmlspecialchars($selected_date); ?>" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
            </div>
            
            <button type="submit" class="btn" style="height: 42px;">Buscar Lista</button>
        </form>
    </div>

    <?php if ($selected_schedule && $selected_date): ?>
        <div class="admin-section" style="background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-top: 4px solid #111;">
            <h3 style="margin-top: 0; border-bottom: 2px solid #eee; padding-bottom: 10px;">2. Lista de Chamada</h3>
            
            <?php if (count($students) > 0): ?>
                <form method="POST" action="" style="margin: 0; padding: 0; border: none; box-shadow: none;">
                    <input type="hidden" name="schedule_id" value="<?php echo $selected_schedule; ?>">
                    <input type="hidden" name="class_date" value="<?php echo htmlspecialchars($selected_date); ?>">
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px; margin-bottom: 25px;">
                        <?php foreach ($students as $student): ?>
                            <?php $is_present = in_array($student['user_id'], $present_students); ?>
                            
                            <label style="display: flex; align-items: center; background: <?php echo $is_present ? '#f0fdf4' : '#fafafa'; ?>; border: 1px solid <?php echo $is_present ? '#16a34a' : '#ddd'; ?>; padding: 12px; border-radius: 6px; cursor: pointer; transition: all 0.2s;">
                                <input type="checkbox" name="students[]" value="<?php echo $student['user_id']; ?>" <?php echo $is_present ? 'checked' : ''; ?> style="width: 20px; height: 20px; margin-right: 15px; cursor: pointer;">
                                <span style="font-size: 1.1rem; color: #333; font-weight: <?php echo $is_present ? 'bold' : 'normal'; ?>;">
                                    <?php echo !empty($student['full_name']) ? htmlspecialchars($student['full_name']) : htmlspecialchars($student['username']); ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="submit" name="save_attendance" style="background: #28a745; color: #fff; padding: 15px 30px; border: none; border-radius: 5px; font-size: 1.1rem; font-weight: bold; cursor: pointer; width: 100%; text-transform: uppercase; letter-spacing: 1px;">💾 Guardar Lista de Presenças</button>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 2rem; color: #777;">
                    <span style="font-size: 2rem; display: block; margin-bottom: 10px;">👻</span>
                    A sua academia ainda não tem alunos matriculados para poder fazer a chamada.
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</main>

<?php
$db->close();
include 'footer.php';
?>