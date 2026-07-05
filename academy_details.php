<?php
session_start();

if (!isset($_GET['id'])) {
    header('Location: academies.php');
    exit;
}

$academy_id = (int)$_GET['id'];
$db = new SQLite3('academies.db');

$db->exec("CREATE TABLE IF NOT EXISTS registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    schedule_id INTEGER NOT NULL,
    registration_date DATE DEFAULT CURRENT_DATE,
    UNIQUE(user_id, schedule_id)
)");

$db->exec("CREATE TABLE IF NOT EXISTS reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    academy_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(academy_id, user_id)
)");

// POST: Inscrever-se numa aula
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
    $schedule_id = (int)$_POST['schedule_id'];
    $user_id     = (int)$_SESSION['user_id'];
    $check = $db->querySingle("SELECT id FROM registrations WHERE user_id=$user_id AND schedule_id=$schedule_id");
    if (!$check) {
        $s = $db->prepare("INSERT INTO registrations (user_id,schedule_id) VALUES (:u,:s)");
        $s->bindValue(':u', $user_id,     SQLITE3_INTEGER);
        $s->bindValue(':s', $schedule_id, SQLITE3_INTEGER);
        $s->execute();
    }
    header("Location: academy_details.php?id=$academy_id"); exit;
}

// POST: Instrutor adiciona horário
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_schedule_instructor'])) {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') { header('Location: login.php'); exit; }
    $day        = SQLite3::escapeString($_POST['day']);
    $time       = SQLite3::escapeString($_POST['time']);
    $class_type = SQLite3::escapeString($_POST['class_type']);
    $iuid       = (int)$_SESSION['user_id'];
    $rc = $db->query("SELECT instructors.id as rid, instructors.academy_id FROM users JOIN instructors ON users.instructor_id=instructors.id WHERE users.id=$iuid");
    $rw = $rc->fetchArray(SQLITE3_ASSOC);
    if ($rw && $rw['academy_id'] == $academy_id) {
        $rid = $rw['rid'];
        $db->exec("INSERT INTO schedules (academy_id,instructor_id,day,time,class_type) VALUES ($academy_id,$rid,'$day','$time','$class_type')");
        header("Location: academy_details.php?id=$academy_id"); exit;
    }
}

// POST: Atualizar Avatar da Academia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_academy_avatar'])) {
    if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'instructor') {
        $iuid = (int)$_SESSION['user_id'];
        $rc = $db->query("SELECT instructors.academy_id FROM users JOIN instructors ON users.instructor_id=instructors.id WHERE users.id=$iuid");
        $rw = $rc->fetchArray(SQLITE3_ASSOC);
        if ($rw && $rw['academy_id'] == $academy_id) {
            $avatar_url = trim($_POST['academy_avatar_url']);
            $sa = $db->prepare("UPDATE academies SET avatar = :av WHERE id = :aid");
            $sa->bindValue('av', $avatar_url, SQLITE3_TEXT);
            $sa->bindValue('aid', $academy_id, SQLITE3_INTEGER);
            $sa->execute();
            header("Location: academy_details.php?id=$academy_id");
            exit;
        }
    }
}

// POST: Avaliar academia
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_review'])) {
    if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user') {
        $rating  = min(5, max(1, (int)($_POST['rating'] ?? 5)));
        $comment = trim($_POST['comment'] ?? '');
        $uid     = (int)$_SESSION['user_id'];
        $s = $db->prepare("INSERT OR REPLACE INTO reviews (academy_id,user_id,rating,comment,created_at) VALUES (:a,:u,:r,:c,CURRENT_TIMESTAMP)");
        $s->bindValue(':a', $academy_id, SQLITE3_INTEGER);
        $s->bindValue(':u', $uid,        SQLITE3_INTEGER);
        $s->bindValue(':r', $rating,     SQLITE3_INTEGER);
        $s->bindValue(':c', $comment,    SQLITE3_TEXT);
        $s->execute();
    }
    header("Location: academy_details.php?id=$academy_id#avaliacoes"); exit;
}

// Dados da academia
$academy = $db->querySingle("SELECT * FROM academies WHERE id=$academy_id", true);
if (!$academy) {
    include 'header.php';
    echo "<main style='text-align:center;padding:5rem 2rem;'><h1 style='color:#fff'>Academia não encontrada</h1><a href='academies.php' class='btn'>← Voltar</a></main>";
    include 'footer.php'; exit;
}

$instructors    = $db->query("SELECT * FROM instructors WHERE academy_id=$academy_id");
$schedules_res  = $db->query("SELECT schedules.*, instructors.name as instructor_name FROM schedules LEFT JOIN instructors ON schedules.instructor_id=instructors.id WHERE schedules.academy_id=$academy_id ORDER BY schedules.day, schedules.time");

$days_of_week  = ['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];
$calendar_data = array_fill_keys($days_of_week, []);
while ($row = $schedules_res->fetchArray(SQLITE3_ASSOC)) {
    if (in_array($row['day'], $days_of_week)) $calendar_data[$row['day']][] = $row;
}

$minhas_aulas_ids = [];
if (isset($_SESSION['user_id'])) {
    $uid  = (int)$_SESSION['user_id'];
    $regs = $db->query("SELECT schedule_id FROM registrations WHERE user_id=$uid");
    if ($regs) while ($r = $regs->fetchArray(SQLITE3_ASSOC)) $minhas_aulas_ids[] = $r['schedule_id'];
}

// Reviews
$avg_raw      = $db->querySingle("SELECT AVG(CAST(rating AS FLOAT)) FROM reviews WHERE academy_id=$academy_id");
$avg_rating   = round((float)$avg_raw, 1);
$total_reviews= $db->querySingle("SELECT COUNT(*) FROM reviews WHERE academy_id=$academy_id") ?: 0;
$reviews_res  = $db->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id=u.id WHERE r.academy_id=$academy_id ORDER BY r.created_at DESC");

$my_review = null;
if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user') {
    $uid = (int)$_SESSION['user_id'];
    $my_review = $db->querySingle("SELECT rating, comment FROM reviews WHERE academy_id=$academy_id AND user_id=$uid", true);
}

function renderStars($rating, $max = 5) {
    $html = '<span class="fp-stars">';
    for ($i = 1; $i <= $max; $i++) {
        $html .= '<span class="fp-star ' . ($i <= round($rating) ? 'on' : '') . '">★</span>';
    }
    $html .= '</span>';
    return $html;
}

include 'header.php';
?>

<main class="academy-main-container">

    <!-- HERO DA ACADEMIA -->
    <div class="academy-hero" style="position: relative;">
        <?php
        $is_my_academy_instructor = false;
        if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'instructor') {
            $iuid_check = (int)$_SESSION['user_id'];
            $rc_check = $db->query("SELECT instructors.academy_id FROM users JOIN instructors ON users.instructor_id=instructors.id WHERE users.id=$iuid_check");
            $rw_check = $rc_check->fetchArray(SQLITE3_ASSOC);
            if ($rw_check && $rw_check['academy_id'] == $academy_id) {
                $is_my_academy_instructor = true;
            }
        }
        ?>

        <div style="display: flex; flex-direction: column; align-items: center; margin-bottom: 20px;">
            <div style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 4px solid var(--primary); background: #222; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
                <?php if (!empty($academy['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($academy['avatar']); ?>" alt="Avatar Academia" style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <span style="font-size: 3rem;">🏛️</span>
                <?php endif; ?>
            </div>

            <?php if ($is_my_academy_instructor): ?>
            <form method="POST" style="margin-top: 10px; display: flex; gap: 5px; align-items: center; background: rgba(0,0,0,0.5); padding: 5px 10px; border-radius: 5px;">
                <input type="text" name="academy_avatar_url" placeholder="URL da nova foto" style="padding: 5px; border-radius: 4px; border: none; font-size: 0.8rem; width: 150px;">
                <button type="submit" name="update_academy_avatar" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Atualizar</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="academy-hero-meta">
            <?php if ($total_reviews > 0): ?>
            <span class="academy-hero-rating">
                <?php echo renderStars($avg_rating); ?>
                <strong><?php echo number_format($avg_rating, 1); ?></strong>
                <span>(<?php echo $total_reviews; ?> avaliações)</span>
            </span>
            <?php endif; ?>
        </div>
        <h1><?php echo htmlspecialchars($academy['name']); ?></h1>
        <div class="address">📍 <?php echo htmlspecialchars($academy['address']); ?></div>
        <div class="description"><?php echo nl2br(htmlspecialchars($academy['description'])); ?></div>

        <?php if (!empty($academy['address'])): ?>
        <div style="margin-top: 2rem; width: 100%; max-width: 800px; border-radius: 10px; overflow: hidden; border: 2px solid #333; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
            <iframe
                width="100%"
                height="300"
                frameborder="0"
                scrolling="no"
                marginheight="0"
                marginwidth="0"
                src="https://maps.google.com/maps?q=<?php echo urlencode($academy['address']); ?>&t=&z=15&ie=UTF8&iwloc=&output=embed">
            </iframe>
        </div>
        <?php endif; ?>
    </div>

    <!-- INSTRUTORES -->
    <h2 class="section-title">Mestres & Instrutores</h2>
    <div class="instructor-grid">
        <?php
        $has_instructors = false;
        while ($inst = $instructors->fetchArray(SQLITE3_ASSOC)):
            $has_instructors = true;
        ?>
        <div class="instructor-card-pro">
            <span class="instructor-icon">🥋</span>
            <h3><?php echo htmlspecialchars($inst['name']); ?></h3>
            <p><?php echo htmlspecialchars($inst['bio']); ?></p>
        </div>
        <?php endwhile; ?>
        <?php if (!$has_instructors): ?>
        <div class="fp-empty-state" style="grid-column:1/-1">
            <span>🥋</span><p>Ainda sem instrutores cadastrados.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="fp-divider"></div>

    <!-- GRADE HORÁRIA -->
    <h2 class="section-title">Grade Horária</h2>

    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'instructor'):
        $iuid = (int)$_SESSION['user_id'];
        $my_academy = $db->querySingle("SELECT i.academy_id FROM users u JOIN instructors i ON u.instructor_id=i.id WHERE u.id=$iuid");
        if ($my_academy == $academy_id):
    ?>
    <div class="add-class-form-card">
        <h3>➕ Adicionar Nova Aula</h3>
        <form method="POST" class="add-class-form-inner">
            <div class="form-group">
                <label>Dia da Semana</label>
                <select name="day" required>
                    <?php foreach($days_of_week as $d): ?>
                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Horário</label>
                <input type="time" name="time" required>
            </div>
            <div class="form-group" style="flex:2">
                <label>Modalidade / Tipo</label>
                <input type="text" name="class_type" placeholder="Ex: Jiu Jitsu Iniciante" required>
            </div>
            <button type="submit" name="add_schedule_instructor" class="btn-save-class">Guardar Aula</button>
        </form>
    </div>
    <?php endif; endif; ?>

    <div class="schedule-grid">
        <?php foreach ($days_of_week as $day): ?>
        <div class="day-column-pro">
            <div class="day-header"><?php echo $day; ?></div>
            <?php if (empty($calendar_data[$day])): ?>
            <div class="day-empty">Sem aulas</div>
            <?php else: ?>
            <div style="padding:4px 0;">
                <?php foreach ($calendar_data[$day] as $aula):
                    $inscrito = in_array($aula['id'], $minhas_aulas_ids);
                ?>
                <div class="class-card-pro <?php echo $inscrito ? 'registered' : ''; ?>">
                    <?php if ($inscrito): ?>
                    <span class="class-confirmed">✓ CONFIRMADO</span>
                    <?php endif; ?>
                    <h4><?php echo htmlspecialchars($aula['class_type']); ?></h4>
                    <p class="class-time">🕒 <?php echo htmlspecialchars($aula['time']); ?></p>
                    <p class="class-instructor">👤 <?php echo htmlspecialchars($aula['instructor_name'] ?? 'Instrutor'); ?></p>
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user' && !$inscrito): ?>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="schedule_id" value="<?php echo $aula['id']; ?>">
                        <button type="submit" name="register" class="btn-register">Inscrever-se</button>
                    </form>
                    <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="btn-login-prompt">Faça login para agendar</a>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="fp-divider"></div>

    <!-- SECÇÃO DE AVALIAÇÕES -->
    <div id="avaliacoes">
        <div class="reviews-header">
            <h2 class="section-title" style="margin:0">Avaliações</h2>
            <?php if ($total_reviews > 0): ?>
            <div class="reviews-summary">
                <span class="reviews-avg-num"><?php echo number_format($avg_rating, 1); ?></span>
                <?php echo renderStars($avg_rating); ?>
                <span class="reviews-count"><?php echo $total_reviews; ?> avaliação<?php echo $total_reviews != 1 ? 'ões' : ''; ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- FORMULÁRIO DE AVALIAÇÃO (só para alunos) -->
        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user'): ?>
        <div class="review-form-card">
            <h3><?php echo $my_review ? '✏️ Atualizar a minha avaliação' : '⭐ Deixar avaliação'; ?></h3>
            <form method="POST" id="review-form">
                <input type="hidden" name="add_review" value="1">
                <div class="star-picker" id="starPicker">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star-pick <?php echo ($my_review && $my_review['rating'] >= $i) ? 'selected' : ''; ?>"
                          data-val="<?php echo $i; ?>" onclick="pickStar(<?php echo $i; ?>)">★</span>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?php echo $my_review['rating'] ?? 5; ?>">
                <div class="form-group" style="margin-top:1rem">
                    <label>Comentário (opcional)</label>
                    <textarea name="comment" rows="3" placeholder="Conta a tua experiência nesta academia..."><?php echo htmlspecialchars($my_review['comment'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-review-submit">
                    <?php echo $my_review ? 'Atualizar avaliação' : 'Publicar avaliação'; ?>
                </button>
            </form>
        </div>
        <?php elseif (!isset($_SESSION['user_id'])): ?>
        <div class="fp-review-login-cta">
            <p>Tens de estar inscrito para avaliar esta academia.</p>
            <a href="login.php" class="btn">Fazer Login →</a>
        </div>
        <?php endif; ?>

        <!-- LISTA DE AVALIAÇÕES -->
        <div class="reviews-list">
            <?php
            $has_reviews = false;
            while ($rev = $reviews_res->fetchArray(SQLITE3_ASSOC)):
                $has_reviews = true;
                $timeago = date('d/m/Y', strtotime($rev['created_at']));
            ?>
            <div class="review-card">
                <div class="review-card-top">
                    <div class="review-user">
                        <span class="review-avatar">👤</span>
                        <strong>@<?php echo htmlspecialchars($rev['username']); ?></strong>
                    </div>
                    <div class="review-meta">
                        <?php echo renderStars($rev['rating']); ?>
                        <span class="review-date"><?php echo $timeago; ?></span>
                    </div>
                </div>
                <?php if (!empty($rev['comment'])): ?>
                <p class="review-comment">"<?php echo htmlspecialchars($rev['comment']); ?>"</p>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            <?php if (!$has_reviews): ?>
            <div class="fp-empty-state">
                <span>💬</span>
                <p>Ainda sem avaliações. Sê o primeiro a avaliar!</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

</main>

<script>
function pickStar(val) {
    document.getElementById('ratingInput').value = val;
    document.querySelectorAll('.star-pick').forEach(function(s, i) {
        s.classList.toggle('selected', i < val);
    });
}
document.querySelectorAll('.star-pick').forEach(function(s) {
    s.addEventListener('mouseenter', function() {
        var val = parseInt(this.dataset.val);
        document.querySelectorAll('.star-pick').forEach(function(x, i) {
            x.classList.toggle('hover', i < val);
        });
    });
    s.addEventListener('mouseleave', function() {
        document.querySelectorAll('.star-pick').forEach(function(x) { x.classList.remove('hover'); });
    });
});
</script>

<?php $db->close(); include 'footer.php'; ?>
