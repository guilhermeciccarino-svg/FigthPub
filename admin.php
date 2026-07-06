<?php
session_start();
include 'header.php';

// 1. VERIFICAÇÃO DE SEGURANÇA: Só ADMIN entra
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new SQLite3('academies.db');

// ===================================================================
// CRIA A TABELA DE EVENTOS AUTOMATICAMENTE (Se não existir)
// ===================================================================
$query_create_events = "CREATE TABLE IF NOT EXISTS events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    martial_art_type TEXT NOT NULL,
    description TEXT,
    rules TEXT,
    weight_classes TEXT,
    belt_ranks TEXT
)";
$db->exec($query_create_events);
// ===================================================================

// --- PROCESSAMENTO DE FORMULÁRIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // A. ADICIONAR ACADEMIA
    if (isset($_POST['add_academy'])) {
        $stmt_ac = $db->prepare("INSERT INTO academies (name, address, description, num_students, num_titles, avatar)
                                 VALUES (:name, :address, :description, :num_students, :num_titles, :avatar)");
        $stmt_ac->bindValue(':name', trim($_POST['name']), SQLITE3_TEXT);
        $stmt_ac->bindValue(':address', trim($_POST['address']), SQLITE3_TEXT);
        $stmt_ac->bindValue(':description', trim($_POST['description']), SQLITE3_TEXT);
        $stmt_ac->bindValue(':num_students', 0, SQLITE3_INTEGER);
        $stmt_ac->bindValue(':num_titles', (int)$_POST['num_titles'], SQLITE3_INTEGER);
        $stmt_ac->bindValue(':avatar', trim($_POST['academy_avatar']), SQLITE3_TEXT);
        $stmt_ac->execute();
    }

    // B. EXCLUIR ACADEMIA
    elseif (isset($_POST['delete_academy'])) {
        $id = (int)$_POST['academy_id'];
        $stmt_del = $db->prepare("DELETE FROM academies WHERE id = :id");
        $stmt_del->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_del->execute();
        $stmt_del2 = $db->prepare("DELETE FROM instructors WHERE academy_id = :id");
        $stmt_del2->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_del2->execute();
        $stmt_del3 = $db->prepare("DELETE FROM schedules WHERE academy_id = :id");
        $stmt_del3->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_del3->execute();
    }

    // C. ADICIONAR INSTRUTOR
    elseif (isset($_POST['add_instructor'])) {
        $instructor_name = $_POST['instructor_name'];
        $academy_id = (int)$_POST['academy_id'];
        $bio = $_POST['bio'];

        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Inicia a transação: ou salva tudo ou não salva nada
        $db->exec('BEGIN');

        try {
            // 1. Inserir na tabela instructors
            $stmt_inst = $db->prepare("INSERT INTO instructors (name, academy_id, bio) VALUES (:name, :academy_id, :bio)");
            $stmt_inst->bindValue(':name', $instructor_name, SQLITE3_TEXT);
            $stmt_inst->bindValue(':academy_id', $academy_id, SQLITE3_INTEGER);
            $stmt_inst->bindValue(':bio', $bio, SQLITE3_TEXT);
            $stmt_inst->execute();

            $instructor_id = $db->lastInsertRowID();

            // 2. Inserir na tabela users
            $stmt_user = $db->prepare("INSERT INTO users (username, password, email, role, instructor_id, avatar) VALUES (:username, :password, :email, 'instructor', :instructor_id, :avatar)");
            $stmt_user->bindValue(':username', $username, SQLITE3_TEXT);
            $stmt_user->bindValue(':password', $password, SQLITE3_TEXT);
            $stmt_user->bindValue(':email', $email, SQLITE3_TEXT);
            $stmt_user->bindValue(':instructor_id', $instructor_id, SQLITE3_INTEGER);
            $stmt_user->bindValue(':avatar', trim($_POST['instructor_avatar'] ?? '' ), SQLITE3_TEXT);
            $stmt_user->execute();

            // Sucesso! Confirma as alterações.
            $db->exec('COMMIT');
            $msg_sucesso = "Instrutor e login criados com sucesso!";

        } catch (Throwable $e) {
            // Se falhar (ex: username repetido), cancela tudo para não deixar dados pela metade
            $db->exec('ROLLBACK');
            $msg_erro = "Erro ao criar instrutor: Já existe um utilizador com esse Username ou Email.";
        }
    }

    // D. EXCLUIR INSTRUTOR
    elseif (isset($_POST['delete_instructor'])) {
        $id = (int)$_POST['instructor_id'];
        $stmt_di1 = $db->prepare("DELETE FROM users WHERE instructor_id = :id");
        $stmt_di1->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_di1->execute();
        $stmt_di2 = $db->prepare("DELETE FROM instructors WHERE id = :id");
        $stmt_di2->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_di2->execute();
        $stmt_di3 = $db->prepare("DELETE FROM schedules WHERE instructor_id = :id");
        $stmt_di3->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_di3->execute();
    }

    // E. EXCLUIR HORÁRIO
    elseif (isset($_POST['delete_schedule'])) {
        $id = (int)$_POST['schedule_id'];
        $stmt_ds = $db->prepare("DELETE FROM schedules WHERE id = :id");
        $stmt_ds->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_ds->execute();
    }

    // EXCLUIR UTILIZADOR
    elseif (isset($_POST['delete_user'])) {
        $id = (int)$_POST['user_id'];
        $stmt_du = $db->prepare("DELETE FROM users WHERE id = :id");
        $stmt_du->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_du->execute();
    }

    // F. ADICIONAR EVENTO / CAMPEONATO
    elseif (isset($_POST['add_event'])) {
        $stmt_ev = $db->prepare("INSERT INTO events (name, martial_art_type, description, rules, weight_classes, belt_ranks)
                                 VALUES (:name, :martial_art_type, :description, :rules, :weight_classes, :belt_ranks)");
        $stmt_ev->bindValue(':name', trim($_POST['event_name']), SQLITE3_TEXT);
        $stmt_ev->bindValue(':martial_art_type', trim($_POST['martial_art_type']), SQLITE3_TEXT);
        $stmt_ev->bindValue(':description', trim($_POST['event_description']), SQLITE3_TEXT);
        $stmt_ev->bindValue(':rules', trim($_POST['rules']), SQLITE3_TEXT);
        $stmt_ev->bindValue(':weight_classes', trim($_POST['weight_classes']), SQLITE3_TEXT);
        $stmt_ev->bindValue(':belt_ranks', trim($_POST['belt_ranks']), SQLITE3_TEXT);
        $stmt_ev->execute();
    }

    // ADICIONAR GALERIA DE EVENTOS
    elseif (isset($_POST['add_event_gallery'])) {
        $event_id = (int)$_POST['gallery_event_id'];
        $image_url = trim($_POST['gallery_image_url']);
        $description = trim($_POST['gallery_description']);
        if (!empty($event_id) && !empty($image_url)) {
            $stmt_gal = $db->prepare("INSERT INTO event_gallery (event_id, image_url, description) VALUES (:eid, :img, :desc)");
            $stmt_gal->bindValue(':eid', $event_id, SQLITE3_INTEGER);
            $stmt_gal->bindValue(':img', $image_url, SQLITE3_TEXT);
            $stmt_gal->bindValue(':desc', $description, SQLITE3_TEXT);
            $stmt_gal->execute();
        }
    }

    // G. EXCLUIR EVENTO
    elseif (isset($_POST['delete_event'])) {
        $id = (int)$_POST['event_id'];
        $stmt_dev = $db->prepare("DELETE FROM events WHERE id = :id");
        $stmt_dev->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt_dev->execute();
    }
}

// Buscar dados para exibir
$academies = $db->query("SELECT * FROM academies ORDER BY name");
$events = $db->query("SELECT * FROM events ORDER BY id DESC");
?>

<main>
    <div class="admin-header">
        <h1>Painel de Administração</h1>
    </div>

    <div class="admin-section section-events" style="border-top: 5px solid #ff9800;">
        <h2>Gerenciar Eventos / Campeonatos </h2>

        <h3>Cadastrar Novo Torneio</h3>
        <form method="POST" class="admin-form-inner">
            <div class="form-group">
                <label>Nome do Evento:</label>
                <input type="text" name="event_name" placeholder="Ex: ADCC 2026, Campeonato Nacional de Karaté..." required minlength="3" maxlength="150">
            </div>
            <div class="form-group">
                <label>Estilo de Luta:</label>
                <input type="text" name="martial_art_type" placeholder="Ex: Jiu-Jitsu Brasileiro (BJJ), Muay Thai..." required>
            </div>
            <div class="form-group">
                <label>Descrição (O que é o evento?):</label>
                <textarea name="event_description" placeholder="Escreva uma breve descrição sobre a importância deste torneio..." required></textarea>
            </div>

            <hr style="border: 0; border-top: 1px dashed #333; margin: 1.5rem 0;">
            <p style="color: #d32f2f; font-weight: bold; margin-bottom: 10px;"> Dica: Nas caixas abaixo, pressione ENTER para criar um novo tópico/linha!</p>

            <div class="form-group">
                <label>Regras do Torneio:</label>
                <textarea name="rules" rows="5" placeholder="Proibido chaves de calcanhar&#10;Lutas de 10 minutos&#10;Pontuação por finalização..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Categorias de Peso:</label>
                    <textarea name="weight_classes" rows="5" placeholder="- 65 kg&#10;- 76 kg&#10;- 87 kg&#10;Absoluto"></textarea>
                </div>
                <div class="form-group">
                    <label>Graduações Permitidas:</label>
                    <textarea name="belt_ranks" rows="5" placeholder="Faixa Azul&#10;Faixa Roxa&#10;Faixa Marrom&#10;Faixa Preta"></textarea>
                </div>
            </div>

            <button type="submit" name="add_event" class="btn-admin" style="background: #ff9800;">Registrar Evento</button>
        </form>

        <h3>Lista de Eventos Registrados</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome do Evento</th>
                        <th>Estilo</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($event = $events->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($event['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($event['martial_art_type']); ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <button type="submit" name="delete_event" class="btn-text-danger" onclick="return confirm('Apagar este torneio definitivamente?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-section section-gallery" style="border-top: 5px solid #28a745;">
        <h2>Gerenciar Galeria de Eventos </h2>
        <h3>Adicionar Foto a um Evento</h3>
        <form method="POST" class="admin-form-inner">
            <div class="form-group">
                <label>Selecione o Evento:</label>
                <select name="gallery_event_id" required>
                    <option value="">-- Escolha um Evento --</option>
                    <?php
                    $res_ev = $db->query("SELECT id, name FROM events ORDER BY id DESC");
                    while ($ev = $res_ev->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $ev['id']; ?>"><?php echo htmlspecialchars($ev['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>URL da Imagem:</label>
                <input type="text" name="gallery_image_url" placeholder="https://..." required>
            </div>
            <div class="form-group">
                <label>Descrição (Opcional):</label>
                <textarea name="gallery_description" rows="2" placeholder="Descreva a foto..."></textarea>
            </div>
            <button type="submit" name="add_event_gallery" class="btn-admin" style="background: #28a745;">Adicionar Foto</button>
        </form>
    </div>

    <div class="admin-section section-academies">
        <h2>Gerenciar Academias</h2>

        <h3>Adicionar Nova Academia</h3>
        <form method="POST" class="admin-form-inner">
            <div class="form-group">
                <label>Nome:</label>
                <input type="text" name="name" required minlength="3" maxlength="100">
            </div>
            <div class="form-group">
                <label>Endereço:</label>
                <input type="text" name="address" required>
            </div>
            <div class="form-group">
                <label>URL do Avatar da Academia (Opcional):</label>
                <input type="text" name="academy_avatar" placeholder="https://...">
            </div>
            <div class="form-group">
                <label>Descrição:</label>
                <textarea name="description" required></textarea>
            </div>
            <div class="form-flex">
                <input type="hidden" name="num_students" value="0">
                <input type="number" name="num_titles" placeholder="Nº de Títulos" required>
            </div>
            <button type="submit" name="add_academy" class="btn-admin">Salvar Academia</button>
        </form>

        <h3>Lista de Academias</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Endereço</th>
                        <th>Alunos / Títulos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($academy = $academies->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($academy['name']); ?></td>
                            <td><?php echo htmlspecialchars($academy['address']); ?></td>
                            <td><?php echo $academy['num_students'] . ' / ' . $academy['num_titles']; ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="academy_id" value="<?php echo $academy['id']; ?>">
                                    <button type="submit" name="delete_academy" class="btn-text-danger" onclick="return confirm('Isso apagará a academia e todos os seus dados. Continuar?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-section section-instructors">
        <h2>Gerenciar Instrutores</h2>

        <h3>Cadastrar Instrutor e Login</h3>
        <form method="POST" class="admin-form-inner">
            <div class="form-group">
                <label>Nome do Instrutor:</label>
                <input type="text" name="instructor_name" required minlength="3" maxlength="100">
            </div>

            <div class="form-group">
                <label>URL do Avatar do Instrutor (Opcional):</label>
                <input type="text" name="instructor_avatar" placeholder="https://...">
            </div>

            <div class="form-group">
                <label>Vincular à Academia:</label>
                <select name="academy_id" required>
                    <option value="">-- Selecione uma Academia --</option>
                    <?php
                    $res_ac = $db->query("SELECT * FROM academies ORDER BY name");
                    while ($ac = $res_ac->fetchArray(SQLITE3_ASSOC)): ?>
                        <option value="<?php echo $ac['id']; ?>"><?php echo htmlspecialchars($ac['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Biografia:</label>
                <textarea name="bio" required></textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid #333; margin: 1.5rem 0;">

            <h4 style="margin-bottom: 1rem; color: #fff;">Dados de Acesso (Login)</h4>
            <div class="form-grid-3">
                <input type="text" name="username" placeholder="Usuário" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Senha" required minlength="6">
            </div>

            <button type="submit" name="add_instructor" class="btn-admin btn-admin-alt">Criar Instrutor</button>
        </form>

        <h3>Equipe de Instrutores</h3>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Academia Filiada</th>
                        <th>Email</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $instructors = $db->query("SELECT instructors.*, academies.name as academy_name, users.email
                                               FROM instructors
                                               LEFT JOIN academies ON instructors.academy_id = academies.id
                                               LEFT JOIN users ON users.instructor_id = instructors.id
                                               ORDER BY instructors.name");
                    while ($inst = $instructors->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($inst['name']); ?></td>
                            <td><strong><?php echo htmlspecialchars($inst['academy_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($inst['email']); ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="instructor_id" value="<?php echo $inst['id']; ?>">
                                    <button type="submit" name="delete_instructor" class="btn-text-danger" onclick="return confirm('Tem certeza? Isso apaga o login e as aulas dele.')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-section section-schedules">
        <h2>Supervisão de Horários</h2>
        <p style="color: #999; margin-bottom: 1.5rem;"><em>Nota: Novos horários devem ser adicionados pelos próprios instrutores em seus painéis.</em></p>

        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Academia</th>
                        <th>Instrutor</th>
                        <th>Dia / Hora</th>
                        <th>Aula</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $schedules = $db->query("SELECT schedules.*, academies.name as academy_name, instructors.name as instructor_name
                                             FROM schedules
                                             JOIN academies ON schedules.academy_id = academies.id
                                             LEFT JOIN instructors ON schedules.instructor_id = instructors.id
                                             ORDER BY schedules.day, schedules.time");
                    while ($sch = $schedules->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sch['academy_name']); ?></td>
                            <td><?php echo htmlspecialchars($sch['instructor_name'] ?? 'N/A'); ?></td>
                            <td><?php echo $sch['day'] . ' - ' . $sch['time']; ?></td>
                            <td><?php echo htmlspecialchars($sch['class_type']); ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="schedule_id" value="<?php echo $sch['id']; ?>">
                                    <button type="submit" name="delete_schedule" class="btn-text-danger" onclick="return confirm('Remover esta aula da grade?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="admin-section section-users">
        <h2>Gerenciar Utilizadores</h2>
        <div style="overflow-x: auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome de Utilizador</th>
                        <th>Email</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users = $db->query("SELECT id, username, email FROM users WHERE role = 'user' ORDER BY username");
                    while ($user = $users->fetchArray(SQLITE3_ASSOC)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <form method="POST" class="form-inline">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="btn-text-danger" onclick="return confirm('Tem certeza que deseja apagar este utilizador e todos os seus dados?')">Excluir</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php
$db->close();
include 'footer.php';
?>
