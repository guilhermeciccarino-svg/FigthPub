<?php
// Inicia a sessão apenas se ainda não tiver sido iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$db = new SQLite3('academies.db');

include 'header.php';
?>

<main style="max-width: 1000px; margin: 0 auto; padding: 20px;">

    <div class="graduation-header" style="text-align: center; margin-bottom: 30px;">
        <h1>🥋 Dia da Graduação</h1>
        <p>Parabéns aos alunos que estão se preparando para o exame de faixa! Este é um momento especial para celebrar seu progresso, suor e conquistas no tatame.</p>
    </div>
    
    <?php if (isset($_SESSION['msg_sucesso'])): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid #c3e6cb;">
            <strong>Sucesso!</strong> <?php echo $_SESSION['msg_sucesso']; ?>
        </div>
        <?php unset($_SESSION['msg_sucesso']); // Limpa a mensagem depois de mostrar ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['msg_erro'])): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 5px; text-align: center; border: 1px solid #f5c6cb;">
            <strong>Erro:</strong> <?php echo $_SESSION['msg_erro']; ?>
        </div>
        <?php unset($_SESSION['msg_erro']); // Limpa a mensagem depois de mostrar ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'instructor'): ?>
        
        <div class="graduation-card" style="border-top-color: #4CAF50;"> 
            <h2>Painel de Graduação (Área do Instrutor)</h2>
            <p>Preencha os dados abaixo para registar a nova graduação do seu aluno.</p>
            
            <form action="process_graduation.php" method="POST" class="graduation-form">
                
                <div class="form-group">
                    <label for="student_id">Selecione o Aluno:</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">Escolha um aluno...</option>
                        
                        <?php
                        // Junta a tabela users com a tabela students para obter o full_name
                        $sql = "SELECT users.id, students.full_name 
                                FROM users 
                                JOIN students ON users.id = students.user_id 
                                WHERE users.role = 'user' 
                                ORDER BY students.full_name ASC";
                        
                        // CORREÇÃO: Alterado de $conn para $db
                        $result = $db->query($sql); 

                        if ($result) {
                            $tem_alunos = false;
                            while($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                $tem_alunos = true;
                                echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['full_name']) . "</option>";
                            }
                            if (!$tem_alunos) {
                                echo "<option value=''>Nenhum aluno registado no sistema</option>";
                            }
                        } else {
                            echo "<option value=''>Erro ao carregar alunos</option>";
                        }
                        ?>
                        
                    </select>
                </div>

                <div class="form-group">
                    <label for="martial_art">Arte Marcial:</label>
                    <select name="martial_art" id="martial_art" required>
                        <option value="">Selecione a modalidade...</option>
                        <option value="Jiu-Jitsu">Jiu-Jitsu</option>
                        <option value="Muay Thai">Muay-Thai</option>
                        <option value="Karatê">Karatê</option>
                        <option value="Boxe">Boxe</option>
                        <option value="Aikido">Aikido</option>
                        <option value="Judo">Judo</option>
                        <option value="Krav-magá">Krav-magá</option>
                        <option value="Taekwondo">Taekwondo</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="belt_rank">Nova Faixa / Prajied / Grau:</label>
                    <input type="text" name="belt_rank" id="belt_rank" placeholder="Ex: Faixa Azul" required>
                </div>

                <div class="form-group">
                    <label for="graduation_date">Data da Graduação:</label>
                    <input type="date" name="graduation_date" id="graduation_date" required>
                </div>

                <button type="submit" class="btn-submit">Confirmar Graduação</button>
            </form>
        </div>

   <?php else: ?>
        
        <div class="announcement-banner" style="background: #e9ecef; border-left: 5px solid #d32f2f; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
            <strong>📢 Próximas Graduações:</strong> As datas dos próximos exames de faixa serão anunciadas pelo seu Mestre. Fique atento ao mural da academia!
        </div>

        <div class="graduation-card" style="border-top-color: #ffd700;"> 
            <h3 style="text-align: center; color: #333;">🏆 Mural de Honra: Últimas Graduações</h3>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">Parabéns aos nossos atletas pelo suor e dedicação!</p>
            
            <div style="overflow-x: auto;">
                <table class="table-graduations" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background-color: #f4f4f4;">
                            <th style="padding: 10px; border-bottom: 2px solid #ddd;">Data</th>
                            <th style="padding: 10px; border-bottom: 2px solid #ddd;">Atleta</th>
                            <th style="padding: 10px; border-bottom: 2px solid #ddd;">Arte Marcial</th>
                            <th style="padding: 10px; border-bottom: 2px solid #ddd;">Nova Faixa/Grau</th>
                            <th style="padding: 10px; border-bottom: 2px solid #ddd;">Avaliador</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Prepara a query para buscar as últimas 10 graduações
                        $sql_mural = "SELECT 
                                        g.martial_art, 
                                        g.belt_rank, 
                                        strftime('%d/%m/%Y', g.graduation_date) as data_formatada, 
                                        s.full_name AS student_name, 
                                        inst.name AS instructor_name
                                      FROM graduations g
                                      JOIN students s ON g.student_id = s.user_id
                                      JOIN users u ON g.instructor_id = u.id
                                      JOIN instructors inst ON u.instructor_id = inst.id
                                      ORDER BY g.graduation_date DESC
                                      LIMIT 10";
                        
                        // Executa a query
                        $result_mural = $db->query($sql_mural); 
                        
                        $tem_graduacao = false;

                        if ($result_mural) {
                            while($row = $result_mural->fetchArray(SQLITE3_ASSOC)) {
                                $tem_graduacao = true;
                                echo "<tr>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>" . htmlspecialchars($row['data_formatada']) . "</td>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'><strong>" . htmlspecialchars($row['student_name']) . "</strong></td>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>" . htmlspecialchars($row['martial_art']) . "</td>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'><span class='badge-faixa'>" . htmlspecialchars($row['belt_rank']) . "</span></td>";
                                echo "<td style='padding: 10px; border-bottom: 1px solid #ddd; text-align: center;'>Prof. " . htmlspecialchars($row['instructor_name']) . "</td>";
                                echo "</tr>";
                            }
                        }

                        if (!$tem_graduacao) {
                            echo "<tr><td colspan='5' style='text-align: center; padding: 20px;'>Ainda não há graduações registadas neste semestre.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="graduation-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
            <div class="graduation-card">
                <h3>Como se Preparar</h3>
                <ul>
                    <li>Pratique regularmente suas técnicas base (Kihon/Drills)</li>
                    <li>Participe de todas as aulas de revisão</li>
                    <li>Mantenha o foco, respeito e a disciplina</li>
                </ul>
            </div>

            <div class="graduation-card" style="border-top-color: #d32f2f;">
                <h3>Requisitos Obrigatórios</h3>
                <ul>
                    <li>Presença mínima de 80% nas aulas do semestre</li>
                    <li>Aprovação prévia do seu instrutor direto</li>
                    <li>Aprovação nos testes práticos (técnica e combate)</li>
                </ul>
            </div>
        </div>
        
    <?php endif; ?>
</main>

<?php 
$db->close();
include 'footer.php'; 
?>