<?php
session_start();
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db      = new SQLite3('academies.db');
$uid     = (int)$_SESSION['user_id'];

// 1. DADOS DO UTILIZADOR + STUDENT
$query = "SELECT u.*, s.full_name, s.birth_date, s.phone, s.emergency_contact, s.CC, a.name as academy_name, a.id as academy_id
          FROM users u
          LEFT JOIN students s ON u.id = s.user_id
          LEFT JOIN academies a ON s.academy_id = a.id
          WHERE u.id = $uid";
$user = $db->query($query)->fetchArray(SQLITE3_ASSOC);

// Role display
$role_display = 'Aluno';  $role_class = 'role-user';  $avatar_icon = '🥋';
if ($user['role'] == 'admin')      { $role_display = 'Administrador / Mestre'; $role_class = 'role-admin';      $avatar_icon = '👑'; }
if ($user['role'] == 'instructor') { $role_display = 'Instrutor Oficial';       $role_class = 'role-instructor'; $avatar_icon = '🥊'; }

// 2. TODAS AS GRADUAÇÕES do utilizador
$stmt_grads = $db->prepare("
    SELECT g.martial_art, g.belt_rank,
           strftime('%d/%m/%Y', g.graduation_date) as data_fmt,
           i.name AS instructor_name
    FROM graduations g
    JOIN users u ON g.instructor_id = u.id
    JOIN instructors i ON u.instructor_id = i.id
    WHERE g.student_id = :uid
    ORDER BY g.graduation_date DESC
");
$stmt_grads->bindValue(':uid', $uid, SQLITE3_INTEGER);
$res_grads = $stmt_grads->execute();

// 3. TODAS AS PRESENÇAS do utilizador
$stmt_pres = $db->prepare("
    SELECT a.class_date, sc.class_type, sc.time, sc.day, ac.name as academy_name
    FROM attendance a
    JOIN schedules sc ON a.schedule_id = sc.id
    JOIN academies ac ON sc.academy_id = ac.id
    WHERE a.student_id = :uid
    ORDER BY a.class_date DESC
");
$stmt_pres->bindValue(':uid', $uid, SQLITE3_INTEGER);
$res_pres = $stmt_pres->execute();

// Conta totais para os stats
$total_grads = $db->querySingle("SELECT COUNT(*) FROM graduations WHERE student_id = $uid");
$total_pres  = $db->querySingle("SELECT COUNT(*) FROM attendance WHERE student_id = $uid");
?>

<main class="profile-container" style="max-width:960px; margin:0 auto; padding:20px;">

    <?php if (isset($_GET['message'])): ?>
        <?php
        $msg = urldecode($_GET['message']);
        if (strpos($msg, 'erro:') === 0)
            echo "<div class='alert-danger'>" . htmlspecialchars(substr($msg, 5)) . "</div>";
        elseif (strpos($msg, 'sucesso:') === 0)
            echo "<div class='alert-success'>" . htmlspecialchars(substr($msg, 8)) . "</div>";
        else
            echo "<div class='alert-success'>" . htmlspecialchars($msg) . "</div>";
        ?>
    <?php endif; ?>

    <!-- ══ CARD DE PERFIL ══ -->
    <div class="profile-card" style="margin-bottom:2rem;">
        <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
            <div class="profile-avatar" onclick="abrirModalOpcoesAvatar()" title="Clique para alterar"
                 style="font-size:60px; width:100px; height:100px; cursor:pointer; transition:transform 0.2s, box-shadow 0.2s;"
                 onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 4px 14px rgba(211,47,47,0.35)';"
                 onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                <?php if (!empty($user['avatar'])): ?>
                    <img src="<?php echo htmlspecialchars($user['avatar']); ?>" alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                <?php else: ?>
                    <?php echo $avatar_icon; ?>
                <?php endif; ?>
            </div>
            <small style="color:#999; font-size:0.75rem; font-weight:bold; cursor:pointer;" onclick="abrirModalOpcoesAvatar()">ALTERAR FOTO</small>
        </div>
        <div class="profile-info">
            <h2 style="margin:0; font-size:2rem; border:none;">
                <?php echo !empty($user['full_name']) ? htmlspecialchars($user['full_name']) : htmlspecialchars($user['username']); ?>
            </h2>
            <p style="margin:5px 0; color:#999;">
                <strong style="color:#ccc;">Usuário:</strong> @<?php echo htmlspecialchars($user['username']); ?> &nbsp;|&nbsp;
                <strong style="color:#ccc;">Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
            </p>
            <span class="role-badge <?php echo $role_class; ?>" style="margin-top:8px;">
                <?php echo $role_display; ?>
            </span>
            <?php if (!empty($user['academy_name'])): ?>
            <span class="profile-academy-badge">
                🏟️ <?php echo htmlspecialchars($user['academy_name']); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ══ STATS RÁPIDOS ══ -->
    <div class="dash-stats" style="margin-bottom:2rem;">
        <div class="dash-stat-card gold">
            <div class="dash-stat-num"><?php echo $total_grads; ?></div>
            <div class="dash-stat-label">🏆 Graduações</div>
        </div>
        <div class="dash-stat-card green">
            <div class="dash-stat-num"><?php echo $total_pres; ?></div>
            <div class="dash-stat-label">📋 Presenças</div>
        </div>
    </div>

    <!-- ══ DADOS PESSOAIS (só para alunos) ══ -->
    <?php if ($user['role'] == 'user'): ?>
    <div class="admin-section" style="border-top: 5px solid #28a745;">
        <h3 style="margin-top:0;">📄 Dados Pessoais</h3>
        <div class="info-kv-grid">
            <p class="info-kv-item"><strong>Nº Documento (CC)</strong><?php echo htmlspecialchars($user['CC'] ?? 'N/A'); ?></p>
            <p class="info-kv-item"><strong>Data de Nascimento</strong><?php echo !empty($user['birth_date']) ? date('d/m/Y', strtotime($user['birth_date'])) : 'N/A'; ?></p>
            <p class="info-kv-item"><strong>Telemóvel</strong><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></p>
            <p class="info-kv-item"><strong>Contacto de Emergência</strong><?php echo htmlspecialchars($user['emergency_contact'] ?? 'N/A'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ══ HISTÓRICO DE GRADUAÇÕES ══ -->
    <div id="historico" class="dash-panel" style="margin-bottom:2rem; overflow:hidden;">
        <div class="dash-panel-header">
            <h2>🥋 Histórico de Graduações</h2>
            <span style="font-size:0.8rem; color:#888;"><?php echo $total_grads; ?> no total</span>
        </div>
        <?php
        $tem_grad = false;
        while ($g = $res_grads->fetchArray(SQLITE3_ASSOC)):
            $tem_grad = true;
        ?>
        <div style="display:flex; align-items:center; gap:1rem; padding:1rem 1.5rem; border-bottom:1px solid var(--card-border); flex-wrap:wrap;">
            <span class="badge-faixa"><?php echo htmlspecialchars($g['belt_rank']); ?></span>
            <strong style="color:#fff; font-size:0.95rem;"><?php echo htmlspecialchars($g['martial_art']); ?></strong>
            <span style="color:#888; font-size:0.85rem; margin-left:auto; white-space:nowrap;">
                Prof. <?php echo htmlspecialchars($g['instructor_name']); ?> &middot; <?php echo $g['data_fmt']; ?>
            </span>
        </div>
        <?php endwhile; ?>
        <?php if (!$tem_grad): ?>
        <div class="dash-empty" style="padding:2.5rem;"><span>💪</span>Ainda sem graduações. Continue treinando!</div>
        <?php endif; ?>
    </div>

    <!-- ══ HISTÓRICO DE PRESENÇAS ══ -->
    <div id="presencas" class="dash-panel" style="margin-bottom:2rem; overflow:hidden;">
        <div class="dash-panel-header">
            <h2>📋 Histórico de Presenças</h2>
            <span style="font-size:0.8rem; color:#888;"><?php echo $total_pres; ?> no total</span>
        </div>

        <?php if ($total_pres > 0): ?>
        <div style="overflow-x:auto;">
            <table class="table" style="width:100%; font-size:0.9rem;">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo de Aula</th>
                        <th>Dia</th>
                        <th>Hora</th>
                        <th>Academia</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $count = 0;
                while ($p = $res_pres->fetchArray(SQLITE3_ASSOC)):
                    $count++;
                    $hide = $count > 10 ? 'class="pres-extra" style="display:none;"' : '';
                ?>
                    <tr <?php echo $hide; ?>>
                        <td style="font-weight:bold; color:#fff;"><?php echo date('d/m/Y', strtotime($p['class_date'])); ?></td>
                        <td><?php echo htmlspecialchars($p['class_type']); ?></td>
                        <td><?php echo htmlspecialchars($p['day']); ?></td>
                        <td style="color:var(--primary); font-weight:bold;"><?php echo htmlspecialchars($p['time']); ?></td>
                        <td><?php echo htmlspecialchars($p['academy_name']); ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php if ($total_pres > 10): ?>
        <div style="text-align:center; padding:1rem; border-top:1px solid var(--card-border);">
            <button id="btn-ver-mais" onclick="verMaisPresencas()" class="btn-outline">
                Ver todas as <?php echo $total_pres; ?> presenças ↓
            </button>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="dash-empty" style="padding:2.5rem;"><span>📭</span>Nenhuma presença registada ainda.</div>
        <?php endif; ?>
    </div>

    <!-- ══ ATUALIZAR CONTA ══ -->
    <form method="POST" action="update_profile.php" class="admin-section" style="margin:0;">
        <h3 style="margin-top:0;">⚙️ Atualizar Dados de Acesso</h3>
        <div class="form-group">
            <label for="email">Seu Email Atual:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:20px; margin-top:10px;">
            <div class="form-group" style="margin:0;">
                <label for="current_password">Senha Atual (obrigatório para salvar):</label>
                <input type="password" id="current_password" name="current_password" required placeholder="Digite sua senha atual">
            </div>
            <div class="form-group" style="margin:0;">
                <label for="new_password">Nova Senha (opcional):</label>
                <input type="password" id="new_password" name="new_password" placeholder="Deixe em branco para não alterar">
            </div>
        </div>
        <button type="submit" class="btn-admin" style="margin-top:20px;">Salvar Alterações</button>
    </form>

</main>

<!-- ══ MODAIS DE AVATAR (sem alterações) ══ -->
<div id="opcoesAvatarModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9998; align-items:center; justify-content:center;">
    <div style="background:white; padding:25px; border-radius:10px; text-align:center; max-width:400px; width:90%;">
        <h3 style="margin-top:0; font-family:'Oswald',sans-serif; text-transform:uppercase; color:#111;">Mudar Foto de Perfil</h3>
        <p style="color:#666; font-size:0.9rem; margin-bottom:20px;">Escolha como deseja adicionar a sua nova foto:</p>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <button onclick="prepararCamera()" style="background:#111; color:white; padding:12px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; font-size:1rem;">📷 Tirar Foto na Hora</button>
            <button onclick="document.getElementById('fileInput').click();" style="background:#28a745; color:white; padding:12px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; font-size:1rem;">📁 Enviar do Dispositivo</button>
            <input type="file" id="fileInput" accept="image/*" style="display:none;" onchange="lidarComUpload(event)">
            <button onclick="mostrarAreaUrl()" style="background:#007bff; color:white; padding:12px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; font-size:1rem;">🔗 Usar Link (URL)</button>
        </div>
        <div id="areaUrl" style="display:none; margin-top:20px; text-align:left; background:#f8f9fa; padding:15px; border-radius:5px; border:1px solid #ddd;">
            <label style="font-size:0.9rem; font-weight:bold; color:#333;">Cole o link da imagem:</label>
            <input type="url" id="urlInput" placeholder="https://site.com/minha-foto.jpg" style="width:100%; padding:10px; margin-top:8px; border:1px solid #ccc; border-radius:5px; box-sizing:border-box; color:#111;">
            <button onclick="salvarFotoUrl()" style="margin-top:10px; width:100%; background:#28a745; color:white; padding:10px; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Salvar Link</button>
        </div>
        <button onclick="fecharModalOpcoesAvatar()" style="margin-top:25px; background:transparent; color:#dc3545; padding:10px 20px; border:2px solid #dc3545; border-radius:5px; cursor:pointer; font-weight:bold;">Cancelar</button>
    </div>
</div>

<div id="cameraModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:white; padding:20px; border-radius:10px; text-align:center; max-width:90%;">
        <h3 style="margin-top:0; font-family:'Oswald',sans-serif; color:#111;">Sorria! 📸</h3>
        <video id="videoElement" autoplay style="width:100%; max-width:400px; border-radius:8px; background:#000;"></video>
        <canvas id="canvasElement" style="display:none;"></canvas>
        <div style="margin-top:15px; display:flex; gap:10px; justify-content:center;">
            <button onclick="tirarFoto()" style="background:#28a745; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-weight:bold;">Capturar e Salvar</button>
            <button onclick="fecharCamera()" style="background:#dc3545; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer;">Cancelar</button>
        </div>
    </div>
</div>

<script>
// Modais de avatar (sem alterações)
const modalOpcoes = document.getElementById('opcoesAvatarModal');
const modalCamera = document.getElementById('cameraModal');
const areaUrl     = document.getElementById('areaUrl');

function abrirModalOpcoesAvatar() { modalOpcoes.style.display = 'flex'; areaUrl.style.display = 'none'; }
function fecharModalOpcoesAvatar() { modalOpcoes.style.display = 'none'; }
function mostrarAreaUrl() { areaUrl.style.display = areaUrl.style.display === 'none' ? 'block' : 'none'; }

function enviarParaServidor(dadosImagem) {
    fetch('upload_avatar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'imagem=' + encodeURIComponent(dadosImagem)
    })
    .then(r => r.text())
    .then(data => {
        const t = data.trim();
        if (t === 'sucesso') window.location.href = '?message=sucesso:Foto atualizada com sucesso!';
        else {
            const err = t.startsWith('erro:') ? t.substring(5) : 'Erro desconhecido.';
            window.location.href = '?message=erro:' + err;
        }
    })
    .catch(() => { window.location.href = '?message=erro:Erro de conexão com o servidor.'; });
}

function lidarComUpload(e) {
    const f = e.target.files[0];
    if (!f) return;
    if (!f.type.startsWith('image/')) { alert('Selecione apenas imagens.'); return; }
    const r = new FileReader();
    r.onload = ev => { fecharModalOpcoesAvatar(); enviarParaServidor(ev.target.result); };
    r.readAsDataURL(f);
}

function salvarFotoUrl() {
    const url = document.getElementById('urlInput').value.trim();
    if (url) { fecharModalOpcoesAvatar(); enviarParaServidor(url); }
    else alert('Insira um link válido.');
}

const video  = document.getElementById('videoElement');
const canvas = document.getElementById('canvasElement');
let stream   = null;

function prepararCamera() {
    fecharModalOpcoesAvatar();
    modalCamera.style.display = 'flex';
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(ms => { stream = ms; video.srcObject = ms; })
        .catch(() => { alert('Não foi possível acessar a câmera.'); fecharCamera(); });
}

function fecharCamera() {
    modalCamera.style.display = 'none';
    if (stream) stream.getTracks().forEach(t => t.stop());
}

function tirarFoto() {
    canvas.width = video.videoWidth; canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    fecharCamera();
    enviarParaServidor(canvas.toDataURL('image/jpeg'));
}

// Ver mais presenças
function verMaisPresencas() {
    document.querySelectorAll('.pres-extra').forEach(r => r.style.display = '');
    document.getElementById('btn-ver-mais').style.display = 'none';
}

// Scroll para anchor se vier do index
window.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (hash) {
        const el = document.querySelector(hash);
        if (el) setTimeout(() => el.scrollIntoView({ behavior: 'smooth', block: 'start' }), 300);
    }
});
</script>

<?php
$db->close();
include 'footer.php';
?>