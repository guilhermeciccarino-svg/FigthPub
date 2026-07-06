<?php
session_start();
include 'header.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new SQLite3('academies.db');
$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'];

// Cleanup old group chat messages (older than 1 hour)
$db->exec("DELETE FROM academy_chat WHERE created_at < datetime('now', '-1 hour')");

// Determine Academy ID for the user
$academy_id = null;
if ($role == 'instructor') {
    $stmt = $db->prepare("SELECT academy_id FROM instructors JOIN users ON users.instructor_id = instructors.id WHERE users.id = :uid");
    $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($res) {
        $academy_id = $res['academy_id'];
    }
} elseif ($role == 'user') {
    $stmt = $db->prepare("SELECT academy_id FROM students WHERE user_id = :uid AND status = 'active'");
    $stmt->bindValue(':uid', $uid, SQLITE3_INTEGER);
    $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    if ($res) {
        $academy_id = $res['academy_id'];
    }
}

if (!$academy_id && $role != 'admin') {
    echo "<main><div style='padding: 2rem;'><p style='color: #fff;'>Acesso negado ou não estás filiado a nenhuma academia.</p></div></main>";
    include 'footer.php';
    exit;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['send_group_msg']) && !empty(trim($_POST['message']))) {
        $stmt = $db->prepare("INSERT INTO academy_chat (academy_id, sender_id, message) VALUES (:aid, :sid, :msg)");
        $stmt->bindValue(':aid', $academy_id, SQLITE3_INTEGER);
        $stmt->bindValue(':sid', $uid, SQLITE3_INTEGER);
        $stmt->bindValue(':msg', trim($_POST['message']), SQLITE3_TEXT);
        $stmt->execute();
        header("Location: chat.php?tab=group");
        exit;
    }
    elseif (isset($_POST['delete_group_msg']) && $role == 'instructor') {
        $stmt = $db->prepare("DELETE FROM academy_chat WHERE id = :msg_id AND academy_id = :aid");
        $stmt->bindValue(':msg_id', (int)$_POST['msg_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':aid', $academy_id, SQLITE3_INTEGER);
        $stmt->execute();
        header("Location: chat.php?tab=group");
        exit;
    }
    elseif (isset($_POST['send_private_msg']) && !empty(trim($_POST['message'])) && !empty($_POST['receiver_id'])) {
        $stmt = $db->prepare("INSERT INTO private_messages (sender_id, receiver_id, message) VALUES (:sid, :rid, :msg)");
        $stmt->bindValue(':sid', $uid, SQLITE3_INTEGER);
        $stmt->bindValue(':rid', (int)$_POST['receiver_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':msg', trim($_POST['message']), SQLITE3_TEXT);
        $stmt->execute();
        $rid = (int)$_POST['receiver_id'];
        header("Location: chat.php?tab=private&user=$rid");
        exit;
    }
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'group';
?>

<main>
    <div style="background: #111; padding: 2rem; border-radius: 8px; border-left: 5px solid #d32f2f;">
        <h2 style="color: #fff; font-family: 'Oswald', sans-serif; text-transform: uppercase;">Chat da Academia</h2>
        <div style="margin-bottom: 2rem;">
            <a href="?tab=group" class="btn-admin <?php echo $tab == 'group' ? 'btn-admin-alt' : ''; ?>" style="margin-right: 10px;">Chat de Grupo</a>
            <a href="?tab=private" class="btn-admin <?php echo $tab == 'private' ? 'btn-admin-alt' : ''; ?>">Mensagens Privadas</a>
        </div>

        <?php if ($tab == 'group'): ?>
            <div style="background: #1a1a1a; border-radius: 8px; padding: 1rem; height: 400px; overflow-y: auto; margin-bottom: 1rem;">
                <?php
                if ($academy_id) {
                    $msgs = $db->prepare("SELECT c.id, c.message, c.created_at, u.username, u.role, u.avatar
                                          FROM academy_chat c
                                          JOIN users u ON c.sender_id = u.id
                                          WHERE c.academy_id = :aid
                                          ORDER BY c.created_at ASC");
                    $msgs->bindValue(':aid', $academy_id, SQLITE3_INTEGER);
                    $res = $msgs->execute();
                    $has_messages = false;
                    while ($msg = $res->fetchArray(SQLITE3_ASSOC)) {
                        $has_messages = true;
                        $is_mine = ($msg['username'] == $_SESSION['username']);
                        $align = $is_mine ? 'right' : 'left';
                        $bg = $is_mine ? '#d32f2f' : '#333';
                        $color = '#fff';
                        $av = !empty($msg['avatar']) ? $msg['avatar'] : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

                        echo "<div style='text-align: $align; margin-bottom: 1rem;'>";
                        echo "<div style='display: inline-block; text-align: left; max-width: 70%;'>";
                        echo "<div style='display: flex; align-items: center; justify-content: " . ($is_mine ? 'flex-end' : 'flex-start') . "; margin-bottom: 5px;'>";
                        if (!$is_mine) echo "<img src='".htmlspecialchars($av)."' style='width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-right: 10px;'>";
                        echo "<small style='color: #888;'><b>".htmlspecialchars($msg['username'])."</b> - ".date('H:i', strtotime($msg['created_at']))."</small>";
                        if ($is_mine) echo "<img src='".htmlspecialchars($av)."' style='width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-left: 10px;'>";
                        echo "</div>";

                        echo "<div style='background: $bg; color: $color; padding: 10px 15px; border-radius: 8px; display: inline-block; word-wrap: break-word;'>";
                        echo htmlspecialchars($msg['message']);
                        echo "</div>";

                        if ($role == 'instructor' && !$is_mine) {
                            echo "<form method='POST' style='display:inline-block; margin-left: 10px;'>";
                            echo "<input type='hidden' name='msg_id' value='".$msg['id']."'>";
                            echo "<button type='submit' name='delete_group_msg' style='background: none; border: none; color: #ff4444; cursor: pointer; font-size: 0.8rem;'>[Apagar]</button>";
                            echo "</form>";
                        }

                        echo "</div></div>";
                    }
                    if (!$has_messages) {
                        echo "<p style='color: #888; text-align: center;'>Sem mensagens recentes.</p>";
                    }
                }
                ?>
            </div>
            <form method="POST" style="display: flex; gap: 10px;">
                <input type="text" name="message" placeholder="Escreve uma mensagem..." style="flex: 1; padding: 10px; border-radius: 4px; border: 1px solid #444; background: #222; color: #fff;" required autocomplete="off">
                <button type="submit" name="send_group_msg" class="btn-admin">Enviar</button>
            </form>

        <?php elseif ($tab == 'private'): ?>
            <div style="display: flex; gap: 2rem; height: 500px;">
                <!-- User List -->
                <div style="flex: 1; background: #1a1a1a; border-radius: 8px; padding: 1rem; overflow-y: auto;">
                    <h3 style="color: #fff; margin-bottom: 1rem;">Membros</h3>
                    <?php
                    if ($academy_id) {
                        // Fetch instructors and students in this academy
                        $members_query = "
                            SELECT u.id, u.username, u.role, u.avatar
                            FROM users u
                            JOIN instructors i ON u.instructor_id = i.id
                            WHERE i.academy_id = :aid AND u.id != :uid
                            UNION
                            SELECT u.id, u.username, u.role, u.avatar
                            FROM users u
                            JOIN students s ON s.user_id = u.id
                            WHERE s.academy_id = :aid AND u.id != :uid
                        ";
                        $stmt_members = $db->prepare($members_query);
                        $stmt_members->bindValue(':aid', $academy_id, SQLITE3_INTEGER);
                        $stmt_members->bindValue(':uid', $uid, SQLITE3_INTEGER);
                        $members = $stmt_members->execute();

                        while ($m = $members->fetchArray(SQLITE3_ASSOC)) {
                            $m_av = !empty($m['avatar']) ? $m['avatar'] : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';
                            $selected = (isset($_GET['user']) && $_GET['user'] == $m['id']) ? 'background: #333;' : '';
                            echo "<a href='?tab=private&user=".$m['id']."' style='display: flex; align-items: center; padding: 10px; border-radius: 4px; color: #fff; text-decoration: none; margin-bottom: 5px; $selected'>";
                            echo "<img src='".htmlspecialchars($m_av)."' style='width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-right: 10px;'>";
                            echo htmlspecialchars($m['username']);
                            if ($m['role'] == 'instructor') echo " <small style='color: #d32f2f; margin-left: 5px;'>(Instrutor)</small>";
                            echo "</a>";
                        }
                    }
                    ?>
                </div>

                <!-- Chat View -->
                <div style="flex: 2; display: flex; flex-direction: column;">
                    <?php if (isset($_GET['user'])):
                        $chat_user_id = (int)$_GET['user'];
                    ?>
                        <div style="flex: 1; background: #1a1a1a; border-radius: 8px; padding: 1rem; overflow-y: auto; margin-bottom: 1rem;">
                            <?php
                            $pmsgs = $db->prepare("SELECT p.*, u.username, u.avatar
                                                   FROM private_messages p
                                                   JOIN users u ON p.sender_id = u.id
                                                   WHERE (p.sender_id = :uid AND p.receiver_id = :chat_user)
                                                      OR (p.sender_id = :chat_user AND p.receiver_id = :uid)
                                                   ORDER BY p.created_at ASC");
                            $pmsgs->bindValue(':uid', $uid, SQLITE3_INTEGER);
                            $pmsgs->bindValue(':chat_user', $chat_user_id, SQLITE3_INTEGER);
                            $pres = $pmsgs->execute();
                            $has_pm = false;
                            while ($pm = $pres->fetchArray(SQLITE3_ASSOC)) {
                                $has_pm = true;
                                $is_mine = ($pm['sender_id'] == $uid);
                                $align = $is_mine ? 'right' : 'left';
                                $bg = $is_mine ? '#2c5282' : '#333';
                                $color = '#fff';
                                $av = !empty($pm['avatar']) ? $pm['avatar'] : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';

                                echo "<div style='text-align: $align; margin-bottom: 1rem;'>";
                                echo "<div style='display: inline-block; text-align: left; max-width: 70%;'>";
                                echo "<div style='display: flex; align-items: center; justify-content: " . ($is_mine ? 'flex-end' : 'flex-start') . "; margin-bottom: 5px;'>";
                                if (!$is_mine) echo "<img src='".htmlspecialchars($av)."' style='width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-right: 10px;'>";
                                echo "<small style='color: #888;'>".date('H:i', strtotime($pm['created_at']))."</small>";
                                if ($is_mine) echo "<img src='".htmlspecialchars($av)."' style='width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-left: 10px;'>";
                                echo "</div>";

                                echo "<div style='background: $bg; color: $color; padding: 10px 15px; border-radius: 8px; display: inline-block; word-wrap: break-word;'>";
                                echo htmlspecialchars($pm['message']);
                                echo "</div>";
                                echo "</div></div>";
                            }
                            if (!$has_pm) {
                                echo "<p style='color: #888; text-align: center;'>Sem mensagens.</p>";
                            }
                            ?>
                        </div>
                        <form method="POST" style="display: flex; gap: 10px;">
                            <input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
                            <input type="text" name="message" placeholder="Mensagem privada..." style="flex: 1; padding: 10px; border-radius: 4px; border: 1px solid #444; background: #222; color: #fff;" required autocomplete="off">
                            <button type="submit" name="send_private_msg" class="btn-admin" style="background: #2c5282;">Enviar</button>
                        </form>
                    <?php else: ?>
                        <div style="flex: 1; background: #1a1a1a; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <p style="color: #888;">Selecione um membro para iniciar conversa.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php
$db->close();
include 'footer.php';
?>
