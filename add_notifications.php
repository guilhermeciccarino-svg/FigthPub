<?php
$db = new SQLite3('academies.db');
$db->exec('PRAGMA foreign_keys = ON;');

$query = "CREATE TABLE IF NOT EXISTS notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,          /* O ID do utilizador que RECEBE a notificação */
    sender_id INTEGER NOT NULL,        /* O ID de quem ENVIA (o Instrutor) */
    academy_id INTEGER,                /* A academia em questão (para o convite) */
    type TEXT NOT NULL,                /* Tipo: 'invite', 'alert', 'graduation' */
    message TEXT NOT NULL,             /* O texto da notificação */
    status TEXT DEFAULT 'pending',     /* Estados: 'pending', 'accepted', 'rejected', 'read' */
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (academy_id) REFERENCES academies(id) ON DELETE CASCADE
);";

if ($db->exec($query)) {
    echo "<h2 style='color: green;'>✅ Tabela de Notificações criada com sucesso!</h2>";
    echo "<p>Podes apagar este ficheiro.</p>";
} else {
    echo "<h2 style='color: red;'>Erro: " . $db->lastErrorMsg() . "</h2>";
}

$db->close();
?>