<?php
session_start();

// 1. SEGURANÇA: Só instrutor passa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header('Location: login.php');
    exit;
}

include 'header.php';

try {
    $db = new SQLite3('academies.db');
} catch (Exception $e) {
    die("<div class='alert-danger'>Erro ao conectar ao banco de dados: " . $e->getMessage() . "</div>");
}

$user_id = $_SESSION['user_id']; // ID de login na tabela users

// 2. IDENTIFICAÇÃO DO PERFIL E ACADEMIA
$stmt_profile = $db->prepare("SELECT 
                    i.id as instructor_id, 
                    i.name as instructor_name, 
                    i.academy_id, 
                    a.name as academy_name 
                  FROM users u
                  JOIN instructors i ON u.instructor_id = i.id 
                  JOIN academies a ON i.academy_id = a.id
                  WHERE u.id = :uid");
$stmt_profile->bindValue(':uid', $user_id, SQLITE3_INTEGER);
$profile_result = $stmt_profile->execute();
$profile = $profile_result->fetchArray(SQLITE3_ASSOC);

if (!$profile) {
    echo "<main>
            <div class='alert-danger'>
                <strong>Erro de Vínculo:</strong> O sistema não encontrou uma academia vinculada ao seu usuário.<br>
                <em>Dica: Verifique se o campo 'instructor_id' na tabela 'users' aponta para um ID válido na tabela 'instructors'.</em>
            </div>
          </main>";
    include 'footer.php';
    exit;
}

$meu_id = $profile['instructor_id']; // ID na tabela instructors
$minha_academia_id = $profile['academy_id'];
$minha_academia_nome = $profile['academy_name'];

// 3. PROCESSAMENTO DOS FORMULÁRIOS (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // --- A. ADICIONAR AULA ---
    if (isset($_POST['add_schedule'])) {
        $stmt_insert = $db->prepare("INSERT INTO schedules (academy_id, instructor_id, day, time, class_type) 
                                     VALUES (:academy_id, :instructor_id, :day, :time, :class_type)");
        $stmt_insert->bindValue(':academy_id', $minha_academia_id, SQLITE3_INTEGER);
        $stmt_insert->bindValue(':instructor_id', $meu_id, SQLITE3_INTEGER);
        $stmt_insert->bindValue(':day', $_POST['day'], SQLITE3_TEXT);
        $stmt_insert->bindValue(':time', $_POST['time'], SQLITE3_TEXT);
        $stmt_insert->bindValue(':class_type', $_POST['class_type'], SQLITE3_TEXT);

        if ($stmt_insert->execute()) {
            echo "<script>alert('Horário adicionado com sucesso para a unidade $minha_academia_nome!');</script>";
        } else {
            echo "<script>alert('Erro ao salvar no banco.');</script>";
        }
    }
    
    // --- B. EXCLUIR AULA ---
    if (isset($_POST['delete_schedule'])) {
        $schedule_id = (int)$_POST['schedule_id'];
        $stmt_delete = $db->prepare("DELETE FROM schedules WHERE id = :sid AND instructor_id = :iid");
        $stmt_delete->bindValue(':sid', $schedule_id, SQLITE3_INTEGER);
        $stmt_delete->bindValue(':iid', $meu_id, SQLITE3_INTEGER);
        $stmt_delete->execute();
    }
}
?>

<main>
    <div class="panel-banner">
        <h1>Painel do Instrutor</h1>
        <p>
            Bem-vindo, <strong><?php echo htmlspecialchars($profile['instructor_name']); ?></strong>! <br>
            Você está gerenciando a grade horária da unidade: <span class="highlight"><?php echo htmlspecialchars($minha_academia_nome); ?></span>.
        </p>
    </div>

    <div class="admin-section">
        <h2>Adicionar Nova Aula</h2>
        
        <form method="POST">
            <div class="form-grid-auto">
                <div class="form-group">
                    <label>Dia da Semana:</label>
                    <select name="day" required>
                        <option value="Segunda">Segunda</option>
                        <option value="Terça">Terça</option>
                        <option value="Quarta">Quarta</option>
                        <option value="Quinta">Quinta</option>
                        <option value="Sexta">Sexta</option>
                        <option value="Sábado">Sábado</option>
                        <option value="Domingo">Domingo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Horário:</label>
                    <input type="time" name="time" required>
                </div>

                <div class="form-group">
                    <label>Modalidade / Turma:</label>
                    <input type="text" name="class_type" placeholder="Ex: Jiu Jitsu - Avançado" required>
                </div>
            </div>
            
            <button type="submit" name="add_schedule" class="btn-admin" style="width: 100%; margin-top: 1rem;">
                Salvar Aula na Unidade <?php echo htmlspecialchars($minha_academia_nome); ?>
            </button>
        </form>
    </div>

    <div class="admin-section">
        <h2>Sua Grade Atual</h2>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Horário</th>
                        <th>Modalidade</th>
                        <th>Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt_aulas = $db->prepare("SELECT * FROM schedules WHERE instructor_id = :iid ORDER BY 
                        CASE day 
                            WHEN 'Segunda' THEN 1 WHEN 'Terça' THEN 2 WHEN 'Quarta' THEN 3 
                            WHEN 'Quinta' THEN 4 WHEN 'Sexta' THEN 5 WHEN 'Sábado' THEN 6 WHEN 'Domingo' THEN 7 
                        END, time");
                    $stmt_aulas->bindValue(':iid', $meu_id, SQLITE3_INTEGER);
                    $aulas = $stmt_aulas->execute();

                    $cont = 0;
                    while ($aula = $aulas->fetchArray(SQLITE3_ASSOC)): 
                        $cont++;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($aula['day']); ?></td>
                            <td><strong><?php echo htmlspecialchars($aula['time']); ?></strong></td>
                            <td><?php echo htmlspecialchars($aula['class_type']); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Excluir esta aula permanentemente?')">
                                    <input type="hidden" name="schedule_id" value="<?php echo $aula['id']; ?>">
                                    <button type="submit" name="delete_schedule" class="btn-danger">
                                        Excluir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; 
                    
                    if ($cont == 0) {
                        echo "<tr><td colspan='4' style='text-align: center; color: #888; padding: 2rem;'>Nenhuma aula cadastrada por você nesta unidade.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php 
$db->close();
include 'footer.php'; 
?>