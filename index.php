<?php
session_start();
include 'header.php';

$dias_semana = [
    1 => 'Segunda', 2 => 'Terça', 3 => 'Quarta',
    4 => 'Quinta',  5 => 'Sexta', 6 => 'Sábado', 7 => 'Domingo'
];
$hoje_num  = date('N');
$hoje_nome = $dias_semana[$hoje_num];

$db = new SQLite3('academies.db');

// Stats para a landing page (visitante)
$stat_atletas   = $db->querySingle("SELECT COUNT(*) FROM users WHERE role='user'") ?: 0;
$stat_academias = $db->querySingle("SELECT COUNT(*) FROM academies") ?: 0;
$stat_grads     = $db->querySingle("SELECT COUNT(*) FROM graduations") ?: 0;
$stat_eventos   = $db->querySingle("SELECT COUNT(*) FROM events") ?: 0;
?>

<?php if (!isset($_SESSION['user_id'])): ?>
<!-- VISITANTE — Landing Page -->
<main class="fp-main">

    <!-- HERO -->
    <section class="fp-hero">
        <p class="fp-hero-eyebrow">Zona de Combate · Est. 2026</p>
        <h1>FIGHT <span>PUB</span></h1>
        <p class="fp-hero-sub">A plataforma oficial para gerir academias, atletas e eventos de artes marciais. Entre no tatame.</p>
        <div class="fp-hero-cta">
            <a href="register.php" class="fp-btn-primary">Iniciar Matrícula</a>
            <a href="academies.php" class="fp-btn-ghost">Ver Academias</a>
        </div>
    </section>

    <!-- BARRA DE ESTATÍSTICAS ANIMADAS -->
    <section class="fp-stats-bar">
        <div class="fp-stats-inner">
            <div class="fp-stat-item">
                <span class="fp-stat-num" data-target="<?php echo $stat_atletas; ?>">0</span>
                <span class="fp-stat-plus">+</span>
                <span class="fp-stat-label">Atletas Registados</span>
            </div>
            <div class="fp-stat-divider"></div>
            <div class="fp-stat-item">
                <span class="fp-stat-num" data-target="<?php echo $stat_academias; ?>">0</span>
                <span class="fp-stat-plus">+</span>
                <span class="fp-stat-label">Academias Filiadas</span>
            </div>
            <div class="fp-stat-divider"></div>
            <div class="fp-stat-item">
                <span class="fp-stat-num" data-target="<?php echo $stat_grads; ?>">0</span>
                <span class="fp-stat-plus">+</span>
                <span class="fp-stat-label">Graduações Emitidas</span>
            </div>
            <div class="fp-stat-divider"></div>
            <div class="fp-stat-item">
                <span class="fp-stat-num" data-target="<?php echo $stat_eventos; ?>">0</span>
                <span class="fp-stat-plus">+</span>
                <span class="fp-stat-label">Eventos Registados</span>
            </div>
        </div>
    </section>

    <!-- CARROSSEL DE FUNCIONALIDADES -->
    <section class="fp-carousel-section">
        <div class="fp-carousel-header">
            <h2>Tudo o que precisas, num só lugar</h2>
            <p>Gestão completa para academias, instrutores e atletas de artes marciais.</p>
        </div>

        <div class="fp-carousel-wrapper">
            <div class="fp-carousel-track" id="carouselTrack">

                <!-- SLIDE 1 — imagem esquerda, texto direita -->
                <div class="fp-carousel-slide">
                    <div class="fp-cs-image">
                        <img src="https://pt.quizur.com/_image?href=https%3A%2F%2Fimg.quizur.com%2Ff%2Fimg647d693a3e9cb1.28506085.jpg%3FlastEdited%3D1685940544&w=600&h=600&f=webp" alt="Academias">
                        <div class="fp-cs-img-overlay"></div>
                    </div>
                    <div class="fp-cs-content fp-cs-right">
                        <span class="fp-cs-icon">🏟️</span>
                        <span class="fp-cs-tag">01 / 06</span>
                        <h3>Academias</h3>
                        <p>Consulta as academias filiadas, os seus instrutores e a grade completa de aulas semanais. Encontra o teu dojo perfeito.</p>
                        <a href="academies.php" class="fp-cs-link">Explorar academias →</a>
                    </div>
                </div>

                <!-- SLIDE 2 — texto esquerda, imagem direita -->
                <div class="fp-carousel-slide fp-slide-reverse">
                    <div class="fp-cs-content fp-cs-left">
                        <span class="fp-cs-icon">🥋</span>
                        <span class="fp-cs-tag">02 / 06</span>
                        <h3>Graduações</h3>
                        <p>Registo oficial de faixas e graus, emitido pelo instrutor diretamente na plataforma. O teu progresso fica registado para sempre.</p>
                        <a href="graduation.php" class="fp-cs-link">Ver graduações →</a>
                    </div>
                    <div class="fp-cs-image">
                        <img src="https://images.unsplash.com/photo-1555597673-b21d5c935865?w=800&auto=format&fit=crop&q=80" alt="Graduações">
                        <div class="fp-cs-img-overlay"></div>
                    </div>
                </div>

                <!-- SLIDE 3 — imagem esquerda, texto direita -->
                <div class="fp-carousel-slide">
                    <div class="fp-cs-image">
                        <img src="https://imgnike-a.akamaihd.net/strapi/nike/artes_marciais_interna_desktop_mobile_cc9d97b3e6/artes_marciais_interna_desktop_mobile_cc9d97b3e6.jpg" alt="Presenças">
                        <div class="fp-cs-img-overlay"></div>
                    </div>
                    <div class="fp-cs-content fp-cs-right">
                        <span class="fp-cs-icon">📋</span>
                        <span class="fp-cs-tag">03 / 06</span>
                        <h3>Presenças</h3>
                        <p>Controlo de assiduidade por aula, com histórico completo para cada atleta. O instrutor faz a chamada e o registo fica guardado.</p>
                        <a href="login.php" class="fp-cs-link">Aceder ao painel →</a>
                    </div>
                </div>

                <!-- SLIDE 4 — texto esquerda, imagem direita -->
                <div class="fp-carousel-slide fp-slide-reverse">
                    <div class="fp-cs-content fp-cs-left">
                        <span class="fp-cs-icon">🏆</span>
                        <span class="fp-cs-tag">04 / 06</span>
                        <h3>Eventos</h3>
                        <p>Calendário de torneios e campeonatos com regras detalhadas, categorias de peso e graduações permitidas. Prepara-te para competir.</p>
                        <a href="events.php" class="fp-cs-link">Ver eventos →</a>
                    </div>
                    <div class="fp-cs-image">
                        <img src="https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?w=800&auto=format&fit=crop&q=80" alt="Eventos">
                        <div class="fp-cs-img-overlay"></div>
                    </div>
                </div>

                <!-- SLIDE 5 — imagem esquerda, texto direita -->
                <div class="fp-carousel-slide">
                    <div class="fp-cs-image">
                        <img src="https://st3.depositphotos.com/5311026/34640/i/450/depositphotos_346408696-stock-photo-woman-exercising-with-trainer-at.jpg" alt="Convites">
                        <div class="fp-cs-img-overlay"></div>
                    </div>
                    <div class="fp-cs-content fp-cs-right">
                        <span class="fp-cs-icon">🔔</span>
                        <span class="fp-cs-tag">05 / 06</span>
                        <h3>Convites</h3>
                        <p>Sistema de convites seguro — o instrutor valida o Cartão de Cidadão do atleta antes de o admitir na academia.</p>
                        <a href="register.php" class="fp-cs-link">Registar-me →</a>
                    </div>
                </div>

                <!-- SLIDE 6 — texto esquerda, imagem direita -->
                <div class="fp-carousel-slide fp-slide-reverse">
                    <div class="fp-cs-content fp-cs-left">
                        <span class="fp-cs-icon">👤</span>
                        <span class="fp-cs-tag">06 / 06</span>
                        <h3>Perfil Pessoal</h3>
                        <p>Cada atleta tem o seu perfil com dados pessoais, foto de perfil, histórico completo de treinos e todas as graduações obtidas.</p>
                        <a href="register.php" class="fp-cs-link">Criar perfil →</a>
                    </div>
                    <div class="fp-cs-image">
                        <img src="https://img.magnific.com/fotos-gratis/lutador-de-boxe-posando-em-posicao-defensiva-confiante-com-as-maos-em-bandagens_158595-4831.jpg?semt=ais_hybrid&w=740&q=80" alt="Perfil">
                        <div class="fp-cs-img-overlay"></div>
                    </div>
                </div>

            </div><!-- /track -->

            <!-- Setas de navegação -->
            <button class="fp-cs-arrow fp-cs-arrow-prev" onclick="carouselPrev()" aria-label="Anterior">&#8592;</button>
            <button class="fp-cs-arrow fp-cs-arrow-next" onclick="carouselNext()" aria-label="Próximo">&#8594;</button>

            <!-- Dots -->
            <div class="fp-cs-dots" id="carouselDots">
                <button class="fp-cs-dot active" onclick="carouselGoTo(0)"></button>
                <button class="fp-cs-dot" onclick="carouselGoTo(1)"></button>
                <button class="fp-cs-dot" onclick="carouselGoTo(2)"></button>
                <button class="fp-cs-dot" onclick="carouselGoTo(3)"></button>
                <button class="fp-cs-dot" onclick="carouselGoTo(4)"></button>
                <button class="fp-cs-dot" onclick="carouselGoTo(5)"></button>
            </div>
        </div>

        <style>
        /* ====== CARROSSEL ====== */
        .fp-carousel-section {
            background: #0d0d0d;
            padding: 0;
            margin: 0;
            width: 100%;
        }
        .fp-carousel-header {
            text-align: center;
            padding: 5rem 2rem 3rem;
            background: #111;
            border-bottom: 1px solid #1e1e1e;
        }
        .fp-carousel-header h2 {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(2rem, 4vw, 3rem);
            text-transform: uppercase;
            color: #fff;
            border: none;
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
        }
        .fp-carousel-header p {
            color: #666;
            font-size: 1rem;
            margin: 0;
        }

        /* Wrapper com altura fixa para o ecrã */
        .fp-carousel-wrapper {
            position: relative;
            overflow: hidden;
            width: 100%;
            height: 85vh;
            min-height: 500px;
            max-height: 850px;
        }

        /* Track ocupa 100% da altura do wrapper */
        .fp-carousel-track {
            display: flex;
            height: 100%;
            transition: transform 0.65s cubic-bezier(0.4, 0, 0.2, 1);
            will-change: transform;
        }

        /* Cada slide ocupa 100% da largura E altura */
        .fp-carousel-slide {
            min-width: 100%;
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            flex-shrink: 0;
        }

        /* Metade da imagem — preenche completamente */
        .fp-cs-image {
            position: relative;
            overflow: hidden;
            height: 100%;
            width: 100%;
        }
        .fp-cs-image img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            display: block;
            transition: transform 0.8s ease;
        }
        .fp-carousel-slide:hover .fp-cs-image img {
            transform: scale(1.04);
        }
        .fp-cs-img-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(211,47,47,0.12) 0%, rgba(0,0,0,0.45) 100%);
            z-index: 1;
        }

        /* Metade do texto — preenche completamente */
        .fp-cs-content {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 4rem 5rem;
            height: 100%;
            box-sizing: border-box;
        }
        .fp-cs-left  { background: #111; }
        .fp-cs-right { background: #0d0d0d; }

        .fp-cs-icon {
            font-size: 2.8rem;
            margin-bottom: 0.75rem;
            display: block;
        }
        .fp-cs-tag {
            font-family: 'Oswald', sans-serif;
            font-size: 0.75rem;
            letter-spacing: 4px;
            color: #d32f2f;
            text-transform: uppercase;
            margin-bottom: 1.2rem;
            display: block;
        }
        .fp-cs-content h3 {
            font-family: 'Oswald', sans-serif;
            font-size: clamp(2.2rem, 3.5vw, 3.2rem);
            color: #fff;
            text-transform: uppercase;
            border: none;
            margin: 0 0 1.2rem;
            line-height: 1;
            letter-spacing: 1px;
        }
        .fp-cs-content p {
            color: #888;
            font-size: 1.05rem;
            line-height: 1.85;
            margin-bottom: 2.5rem;
            max-width: 420px;
        }
        .fp-cs-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Oswald', sans-serif;
            font-size: 0.9rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #d32f2f;
            text-decoration: none;
            border-bottom: 2px solid #d32f2f;
            padding-bottom: 4px;
            transition: color 0.25s, border-color 0.25s;
            align-self: flex-start;
        }
        .fp-cs-link:hover { color: #fff; border-color: #fff; }

        /* Setas de navegação */
        .fp-cs-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(211,47,47,0.9);
            border: none;
            color: #fff;
            font-size: 1.4rem;
            width: 54px;
            height: 54px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 20;
            transition: background 0.25s, transform 0.25s;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
        }
        .fp-cs-arrow:hover { background: #d32f2f; transform: translateY(-50%) scale(1.1); }
        .fp-cs-arrow-prev { left: 2rem; }
        .fp-cs-arrow-next { right: 2rem; }

        /* Dots */
        .fp-cs-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 20;
        }
        .fp-cs-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            border: 2px solid #555;
            background: transparent;
            cursor: pointer;
            transition: all 0.3s;
            padding: 0;
        }
        .fp-cs-dot.active { background: #d32f2f; border-color: #d32f2f; transform: scale(1.3); }

        /* Mobile */
        @media (max-width: 768px) {
            .fp-carousel-wrapper { height: auto; max-height: none; }
            .fp-carousel-track { height: auto; }
            .fp-carousel-slide {
                grid-template-columns: 1fr;
                height: auto;
            }
            .fp-cs-image {
                position: relative;
                height: 260px;
                order: -1;
            }
            .fp-cs-image img { position: absolute; }
            .fp-slide-reverse .fp-cs-image { order: -1; }
            .fp-cs-content { padding: 2.5rem 1.5rem; height: auto; }
            .fp-cs-arrow { display: none; }
            .fp-cs-dots { bottom: 1rem; }
        }
        </style>

        <script>
        (function() {
            var current = 0;
            var total = 6;
            var track = document.getElementById('carouselTrack');
            var dots = document.querySelectorAll('.fp-cs-dot');
            var autoTimer;

            function goTo(n) {
                current = (n + total) % total;
                track.style.transform = 'translateX(-' + (current * 100) + '%)';
                dots.forEach(function(d, i) {
                    d.classList.toggle('active', i === current);
                });
            }

            function startAuto() {
                autoTimer = setInterval(function() { goTo(current + 1); }, 5000);
            }

            function resetAuto() {
                clearInterval(autoTimer);
                startAuto();
            }

            window.carouselNext = function() { goTo(current + 1); resetAuto(); };
            window.carouselPrev = function() { goTo(current - 1); resetAuto(); };
            window.carouselGoTo = function(n) { goTo(n); resetAuto(); };

            // Suporte a swipe (mobile)
            var startX = 0;
            track.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, {passive:true});
            track.addEventListener('touchend', function(e) {
                var dx = e.changedTouches[0].clientX - startX;
                if (Math.abs(dx) > 50) { dx < 0 ? carouselNext() : carouselPrev(); }
            }, {passive:true});

            startAuto();
        })();
        </script>
    </section>

    <!-- COMO FUNCIONA -->
    <section class="fp-how-section">
        <div class="fp-how-inner">
            <div class="fp-how-header">
                <span class="fp-how-eyebrow">Processo simples</span>
                <h2>Como funciona</h2>
            </div>
            <div class="fp-how-steps">
                <div class="fp-how-step">
                    <div class="fp-how-num">01</div>
                    <div class="fp-how-icon">📝</div>
                    <h3>Cria a tua conta</h3>
                    <p>Regista-te gratuitamente com o teu email e escolhe o teu username. Demora menos de 1 minuto.</p>
                </div>
                <div class="fp-how-connector"></div>
                <div class="fp-how-step">
                    <div class="fp-how-num">02</div>
                    <div class="fp-how-icon">🏟️</div>
                    <h3>Junta-te a uma academia</h3>
                    <p>O teu instrutor valida o teu Cartão de Cidadão e aceita-te oficialmente na academia.</p>
                </div>
                <div class="fp-how-connector"></div>
                <div class="fp-how-step">
                    <div class="fp-how-num">03</div>
                    <div class="fp-how-icon">🥋</div>
                    <h3>Começa a treinar</h3>
                    <p>Acede à grade de aulas, acumula presenças, recebe graduações e acompanha toda a tua evolução.</p>
                </div>
            </div>
            <div style="text-align:center; margin-top:3.5rem;">
                <a href="register.php" class="fp-btn-primary">Começar agora →</a>
            </div>
        </div>
    </section>

    <!-- FRASE MANIFESTO -->
    <section class="fp-manifesto">
        <div class="fp-manifesto-inner">
            <span class="fp-manifesto-quote-mark">"</span>
            <blockquote class="fp-manifesto-text">
                A disciplina começa no tatame<br>e nunca mais te larga.
            </blockquote>
            <span class="fp-manifesto-author">— Filosofia Fight Pub</span>
        </div>
    </section>

    <!-- PRÉ-VISUALIZAÇÃO DE ACADEMIAS -->
    <section class="fp-preview">
        <div class="fp-preview-inner">
            <h2 class="fp-preview-title">Academias em Destaque</h2>
            <p class="fp-preview-sub">Regista-te para acederes a todos os detalhes e aulas disponíveis.</p>
            <div class="fp-academy-preview-grid">
                <?php
                $res_ac = $db->query("SELECT * FROM academies ORDER BY name LIMIT 3");
                $has_ac = false;
                while ($ac = $res_ac->fetchArray(SQLITE3_ASSOC)):
                    $has_ac = true;
                    $num_inst = $db->querySingle("SELECT COUNT(*) FROM instructors WHERE academy_id = " . (int)$ac['id']);
                ?>
                <div class="fp-academy-card">
                    <h3><?php echo htmlspecialchars($ac['name']); ?></h3>
                    <p><?php echo htmlspecialchars(mb_substr($ac['description'] ?? '', 0, 80)) . (mb_strlen($ac['description'] ?? '') > 80 ? '...' : ''); ?></p>
                    <div class="fp-academy-stats">
                        <span class="fp-academy-stat">🥊 <span><?php echo $num_inst; ?></span> Instrutores</span>
                        <span class="fp-academy-stat">🏆 <span><?php echo $ac['num_titles'] ?? 0; ?></span> Títulos</span>
                    </div>
                    <div class="fp-lock-overlay">
                        <p>Entra para ver a grade de aulas e muito mais.</p>
                        <a href="register.php">Registar Agora</a>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if (!$has_ac): ?>
                <div style="color:#555; text-align:center; padding:2rem; grid-column:1/-1;">Nenhuma academia registada ainda.</div>
                <?php endif; ?>
            </div>
            <div style="text-align:center; margin-top:1rem;">
                <a href="academies.php" class="fp-btn-ghost">Ver todas as academias →</a>
            </div>
        </div>
    </section>

    <!-- CALL TO ACTION FINAL -->
    <section class="fp-cta">
        <div class="fp-cta-inner">
            <span class="fp-cta-eyebrow">Junta-te à comunidade</span>
            <h2>Pronto para entrar<br>no tatame?</h2>
            <p>Mais de <?php echo max($stat_atletas, 1); ?> atletas já fazem parte da Fight Pub.<br>A tua jornada começa com um clique.</p>
            <div class="fp-cta-buttons">
                <a href="register.php" class="fp-cta-btn-main">Fazer Matrícula Gratuita</a>
                <a href="academies.php" class="fp-cta-btn-ghost">Ver Academias</a>
            </div>
        </div>
    </section>

    <!-- JS: Contador animado com IntersectionObserver -->
    <script>
    (function() {
        function animateCount(el, target, duration) {
            var start = 0;
            var startTime = null;
            var step = function(timestamp) {
                if (!startTime) startTime = timestamp;
                var progress = Math.min((timestamp - startTime) / duration, 1);
                var ease = 1 - Math.pow(1 - progress, 3);
                el.textContent = Math.floor(ease * target);
                if (progress < 1) requestAnimationFrame(step);
                else el.textContent = target;
            };
            requestAnimationFrame(step);
        }

        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    var nums = entry.target.querySelectorAll('.fp-stat-num');
                    nums.forEach(function(num) {
                        var target = parseInt(num.getAttribute('data-target'), 10);
                        animateCount(num, target, 1800);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.3 });

        var statsBar = document.querySelector('.fp-stats-bar');
        if (statsBar) observer.observe(statsBar);
    })();
    </script>

</main>

<?php else: ?>
<main class="dash">

<?php
$role = $_SESSION['role'];
$uid  = (int)$_SESSION['user_id'];
$badge_class = 'badge-user';   $badge_label = 'Atleta';         $welcome_icon = '🥋';
if ($role == 'admin')      { $badge_class = 'badge-admin';      $badge_label = 'Administrador'; $welcome_icon = '👑'; }
if ($role == 'instructor') { $badge_class = 'badge-instructor'; $badge_label = 'Instrutor';     $welcome_icon = '🥊'; }
?>
    <div class="dash-welcome">
        <div class="dash-welcome-text">
            <h1><?php echo $welcome_icon; ?> Olá, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
            <p>Hoje é <strong style="color:#fff"><?php echo $hoje_nome; ?>-feira</strong>.
            <?php
            if ($role == 'admin')          echo 'O tatame está sob o teu comando.';
            elseif ($role == 'instructor') echo 'Os teus alunos contam contigo.';
            else                           echo 'Bom treino — um dia de cada vez.';
            ?>
            </p>
        </div>
        <span class="dash-welcome-badge <?php echo $badge_class; ?>"><?php echo $badge_label; ?></span>
    </div>

    <?php /* ══════════════ ADMIN ══════════════ */ if ($role == 'admin'): ?>
    <?php
    $total_academias  = $db->querySingle("SELECT COUNT(*) FROM academies");
    $total_instrutores = $db->querySingle("SELECT COUNT(*) FROM instructors");
    $total_alunos     = $db->querySingle("SELECT COUNT(*) FROM users WHERE role='user'");
    $total_eventos    = $db->querySingle("SELECT COUNT(*) FROM events");
    ?>

    <div class="dash-stats">
        <div class="dash-stat-card red">
            <div class="dash-stat-num"><?php echo $total_academias; ?></div>
            <div class="dash-stat-label">🏟️ Academias</div>
        </div>
        <div class="dash-stat-card">
            <div class="dash-stat-num"><?php echo $total_instrutores; ?></div>
            <div class="dash-stat-label">🥊 Instrutores</div>
        </div>
        <div class="dash-stat-card green">
            <div class="dash-stat-num"><?php echo $total_alunos; ?></div>
            <div class="dash-stat-label">🥋 Atletas</div>
        </div>
        <div class="dash-stat-card gold">
            <div class="dash-stat-num"><?php echo $total_eventos; ?></div>
            <div class="dash-stat-label">🏆 Eventos</div>
        </div>
    </div>

    <div class="dash-grid">
        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>⚡ Atalhos de Administração</h2>
            </div>
            <div class="dash-panel-body">
                <div class="dash-shortcuts">
                    <a href="admin.php" class="dash-shortcut"><span>🛠️</span><small>Painel Admin</small></a>
                    <a href="admin_announcements.php" class="dash-shortcut"><span>📌</span><small>Gerir Avisos</small></a>
                    <a href="academies.php" class="dash-shortcut"><span>🏟️</span><small>Academias</small></a>
                    <a href="instructors.php" class="dash-shortcut"><span>🥊</span><small>Instrutores</small></a>
                    <a href="events.php" class="dash-shortcut"><span>🏆</span><small>Eventos</small></a>
                    <a href="graduation.php" class="dash-shortcut"><span>🥋</span><small>Graduações</small></a>
                </div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>📌 Últimos Avisos</h2>
                <a href="admin_announcements.php">Gerir →</a>
            </div>
            <div class="dash-panel-body">
                <?php
                $tem_aviso = false;
                $ra = $db->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
                while ($av = $ra->fetchArray(SQLITE3_ASSOC)) { $tem_aviso = true; ?>
                    <div class="aviso-item <?php echo htmlspecialchars($av['type']); ?>">
                        <strong><?php echo htmlspecialchars($av['title']); ?></strong>
                        <p><?php echo htmlspecialchars(mb_substr($av['message'], 0, 100)) . (mb_strlen($av['message']) > 100 ? '...' : ''); ?></p>
                        <small><?php echo date('d/m/Y', strtotime($av['created_at'])); ?></small>
                    </div>
                <?php }
                if (!$tem_aviso): ?>
                    <div class="dash-empty"><span>📭</span>Sem avisos publicados.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>🏟️ Academias Registadas</h2>
                <a href="admin.php">Gerir →</a>
            </div>
            <div class="dash-panel-body">
                <?php
                $ac_list = $db->query("SELECT name FROM academies ORDER BY name LIMIT 5");
                $has_ac = false;
                while ($ac = $ac_list->fetchArray(SQLITE3_ASSOC)) { $has_ac = true; ?>
                <div style="padding:0.6rem 0; border-bottom:1px solid #f5f5f5; font-size:0.95rem; color:#333;">
                    🏟️ <?php echo htmlspecialchars($ac['name']); ?>
                </div>
                <?php }
                if (!$has_ac): ?>
                    <div class="dash-empty"><span>🏗️</span>Nenhuma academia ainda.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php /* ══════════════ INSTRUTOR ══════════════ */ elseif ($role == 'instructor'): ?>
    <?php
    $stmt_profile = $db->prepare("SELECT i.id as iid, i.name as iname, i.academy_id, a.name as aname FROM users u JOIN instructors i ON u.instructor_id = i.id JOIN academies a ON i.academy_id = a.id WHERE u.id = :uid");
    $stmt_profile->bindValue(':uid', $uid, SQLITE3_INTEGER);
    $profile = $stmt_profile->execute()->fetchArray(SQLITE3_ASSOC);

    $total_aulas   = $profile ? $db->querySingle("SELECT COUNT(*) FROM schedules WHERE instructor_id = " . (int)$profile['iid']) : 0;
    $total_alunos  = $profile ? $db->querySingle("SELECT COUNT(*) FROM students WHERE academy_id = " . (int)$profile['academy_id']) : 0;
    $total_grads   = $profile ? $db->querySingle("SELECT COUNT(*) FROM graduations WHERE instructor_id = $uid") : 0;
    ?>

    <div class="dash-stats">
        <div class="dash-stat-card red">
            <div class="dash-stat-num"><?php echo $total_aulas; ?></div>
            <div class="dash-stat-label">📅 Aulas na Grade</div>
        </div>
        <div class="dash-stat-card green">
            <div class="dash-stat-num"><?php echo $total_alunos; ?></div>
            <div class="dash-stat-label">🥋 Alunos na Academia</div>
        </div>
        <div class="dash-stat-card gold">
            <div class="dash-stat-num"><?php echo $total_grads; ?></div>
            <div class="dash-stat-label">🏆 Graduações Realizadas</div>
        </div>
    </div>

    <?php if ($profile): ?>
    <div style="background:#111; color:#fff; border-radius:8px; padding:1rem 1.5rem; margin-bottom:1.5rem; font-size:0.9rem; border-left:4px solid #d32f2f;">
        📍 A gerir a academia: <strong><?php echo htmlspecialchars($profile['aname']); ?></strong>
    </div>
    <?php endif; ?>

    <div class="dash-grid">
        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>⚡ Atalhos Rápidos</h2>
            </div>
            <div class="dash-panel-body">
                <div class="dash-shortcuts">
                    <a href="painel_instrutor.php" class="dash-shortcut"><span>📋</span><small>Painel</small></a>
                    <a href="instructor_attendance.php" class="dash-shortcut"><span>✅</span><small>Presenças</small></a>
                    <a href="manage_students.php" class="dash-shortcut"><span>👥</span><small>Alunos</small></a>
                    <a href="graduation.php" class="dash-shortcut"><span>🥋</span><small>Graduações</small></a>
                    <a href="events.php" class="dash-shortcut"><span>🏆</span><small>Eventos</small></a>
                    <a href="academies.php" class="dash-shortcut"><span>🏟️</span><small>Academias</small></a>
                </div>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>📅 As Minhas Aulas</h2>
                <a href="painel_instrutor.php">Gerir grade →</a>
            </div>
            <div class="dash-panel-body">
                <?php if ($profile):
                    $aulas = $db->prepare("SELECT * FROM schedules WHERE instructor_id = :iid ORDER BY CASE day WHEN 'Segunda' THEN 1 WHEN 'Terça' THEN 2 WHEN 'Quarta' THEN 3 WHEN 'Quinta' THEN 4 WHEN 'Sexta' THEN 5 WHEN 'Sábado' THEN 6 WHEN 'Domingo' THEN 7 END, time LIMIT 5");
                    $aulas->bindValue(':iid', $profile['iid'], SQLITE3_INTEGER);
                    $res_aulas = $aulas->execute();
                    $has_aulas = false;
                    while ($aula = $res_aulas->fetchArray(SQLITE3_ASSOC)) { $has_aulas = true; ?>
                    <div class="aula-item">
                        <div class="aula-time"><?php echo htmlspecialchars($aula['time']); ?></div>
                        <div class="aula-info">
                            <strong><?php echo htmlspecialchars($aula['class_type']); ?></strong>
                            <span>📆 <?php echo htmlspecialchars($aula['day']); ?>-feira</span>
                        </div>
                    </div>
                    <?php }
                    if (!$has_aulas): ?>
                        <div class="dash-empty"><span>📭</span>Nenhuma aula na grade ainda.</div>
                    <?php endif;
                else: ?>
                    <div class="dash-empty"><span>⚠️</span>Perfil de instrutor não configurado.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header"><h2>📌 Avisos</h2></div>
            <div class="dash-panel-body">
                <?php
                $tem_aviso = false;
                $ra = $db->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
                while ($av = $ra->fetchArray(SQLITE3_ASSOC)) { $tem_aviso = true; ?>
                    <div class="aviso-item <?php echo htmlspecialchars($av['type']); ?>">
                        <strong><?php echo htmlspecialchars($av['title']); ?></strong>
                        <p><?php echo htmlspecialchars(mb_substr($av['message'], 0, 100)) . (mb_strlen($av['message']) > 100 ? '...' : ''); ?></p>
                        <small><?php echo date('d/m/Y', strtotime($av['created_at'])); ?></small>
                    </div>
                <?php }
                if (!$tem_aviso): ?>
                    <div class="dash-empty"><span>📭</span>Sem avisos. Bom treino!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php /* ══════════════ ALUNO ══════════════ */ else: ?>
    <?php
    $sa = $db->prepare("SELECT s.*, a.name as academy_name, a.id as academy_id FROM students s LEFT JOIN academies a ON s.academy_id = a.id WHERE s.user_id = :uid");
    $sa->bindValue(':uid', $uid, SQLITE3_INTEGER);
    $aluno = $sa->execute()->fetchArray(SQLITE3_ASSOC);

    $total_grads = $db->querySingle("SELECT COUNT(*) FROM graduations WHERE student_id = $uid");
    $total_pres  = $db->querySingle("SELECT COUNT(*) FROM attendance WHERE student_id = $uid");
    $tem_academia = !empty($aluno['academy_name']);

    $stmt_ug = $db->prepare("
        SELECT g.belt_rank, g.martial_art, strftime('%d/%m/%Y', g.graduation_date) as data_fmt, i.name as instructor_name
        FROM graduations g
        JOIN users u ON g.instructor_id = u.id
        JOIN instructors i ON u.instructor_id = i.id
        WHERE g.student_id = :uid
        ORDER BY g.graduation_date DESC
        LIMIT 1
    ");
    $stmt_ug->bindValue(':uid', $uid, SQLITE3_INTEGER);
    $ultima_grad = $stmt_ug->execute()->fetchArray(SQLITE3_ASSOC);

    $proxima_aula = null;
    if ($tem_academia) {
        $dias_ordem = ['Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo'];
        $idx_hoje = array_search($hoje_nome, $dias_ordem);
        $dias_reordenados = array_merge(
            array_slice($dias_ordem, $idx_hoje),
            array_slice($dias_ordem, 0, $idx_hoje)
        );
        $stmt_pa = $db->prepare("
            SELECT sc.*,
                   CASE sc.day
                       WHEN 'Segunda' THEN 1 WHEN 'Terça' THEN 2 WHEN 'Quarta' THEN 3
                       WHEN 'Quinta' THEN 4  WHEN 'Sexta' THEN 5 WHEN 'Sábado' THEN 6
                       WHEN 'Domingo' THEN 7 END as day_order
            FROM schedules sc
            WHERE sc.academy_id = :aid
            AND sc.id IN (SELECT schedule_id FROM registrations WHERE user_id = :uid)
            ORDER BY day_order, sc.time
            LIMIT 1
        ");
        $stmt_pa->bindValue(':aid', $aluno['academy_id'], SQLITE3_INTEGER);
        $stmt_pa->bindValue(':uid', $uid, SQLITE3_INTEGER);
        $proxima_aula = $stmt_pa->execute()->fetchArray(SQLITE3_ASSOC);
    }

    $stmt_up = $db->prepare("
        SELECT a.class_date, sc.class_type, sc.time, ac.name as academy_name
        FROM attendance a
        JOIN schedules sc ON a.schedule_id = sc.id
        JOIN academies ac ON sc.academy_id = ac.id
        WHERE a.student_id = :uid
        ORDER BY a.class_date DESC
        LIMIT 1
    ");
    $stmt_up->bindValue(':uid', $uid, SQLITE3_INTEGER);
    $ultima_pres = $stmt_up->execute()->fetchArray(SQLITE3_ASSOC);
    ?>

    <div class="dash-stats">
        <div class="dash-stat-card red">
            <div class="dash-stat-num"><?php echo $total_grads; ?></div>
            <div class="dash-stat-label">🏆 Graduações</div>
        </div>
        <div class="dash-stat-card green">
            <div class="dash-stat-num"><?php echo $total_pres; ?></div>
            <div class="dash-stat-label">📋 Treinos Marcados</div>
        </div>
        <div class="dash-stat-card <?php echo $tem_academia ? 'gold' : ''; ?>">
            <div class="dash-stat-num" style="font-size:1.4rem; <?php echo !$tem_academia ? 'color:#ccc;' : ''; ?>">
                <?php echo $tem_academia ? '✅' : '⏳'; ?>
            </div>
            <div class="dash-stat-label"><?php echo $tem_academia ? htmlspecialchars($aluno['academy_name']) : 'Sem Academia'; ?></div>
        </div>
    </div>

    <?php if (!$tem_academia): ?>
    <div style="background:#fffbeb; border:1px solid #fcd34d; border-left:4px solid #fbbf24; border-radius:8px; padding:1.2rem 1.5rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
        <span style="font-size:1.5rem;">⚠️</span>
        <div>
            <strong style="color:#92400e;">Ainda não tens academia!</strong>
            <p style="margin:0; color:#78350f; font-size:0.9rem;">Aguarda o convite de um instrutor ou explora as academias disponíveis.</p>
        </div>
        <a href="academies.php" style="margin-left:auto; background:#111; color:#fff; font-family:'Oswald',sans-serif; font-size:0.85rem; font-weight:700; letter-spacing:1px; text-transform:uppercase; text-decoration:none; padding:0.6rem 1.2rem; border-radius:4px; white-space:nowrap;">Ver Academias</a>
    </div>
    <?php endif; ?>

    <div class="dash-grid">

        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>🥋 Última Graduação</h2>
                <a href="user_profiles.php#historico">Ver histórico →</a>
            </div>
            <div class="dash-panel-body">
                <?php if ($ultima_grad): ?>
                <div style="display:flex; flex-direction:column; gap:0.6rem;">
                    <span class="badge-faixa" style="align-self:flex-start; font-size:1rem; padding:6px 16px;">
                        <?php echo htmlspecialchars($ultima_grad['belt_rank']); ?>
                    </span>
                    <p style="font-family:'Oswald',sans-serif; font-size:1.4rem; color:#111; margin:0; text-transform:uppercase;">
                        <?php echo htmlspecialchars($ultima_grad['martial_art']); ?>
                    </p>
                    <p style="color:#888; font-size:0.85rem; margin:0;">
                        Prof. <?php echo htmlspecialchars($ultima_grad['instructor_name']); ?> · <?php echo $ultima_grad['data_fmt']; ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="dash-empty"><span>💪</span>Ainda sem graduações. Continue treinando!</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>📅 Próxima Aula</h2>
                <?php if ($tem_academia): ?>
                <a href="academy_details.php?id=<?php echo $aluno['academy_id']; ?>">Ver grade →</a>
                <?php endif; ?>
            </div>
            <div class="dash-panel-body">
                <?php if ($proxima_aula): ?>
                <div class="aula-item" style="border:none; padding:0;">
                    <div class="aula-time"><?php echo htmlspecialchars($proxima_aula['time']); ?></div>
                    <div class="aula-info">
                        <strong><?php echo htmlspecialchars($proxima_aula['class_type']); ?></strong>
                        <span>📆 <?php echo htmlspecialchars($proxima_aula['day']); ?>-feira</span>
                    </div>
                </div>
                <?php elseif (!$tem_academia): ?>
                <div class="dash-empty"><span>🏟️</span>Sem academia associada.</div>
                <?php else: ?>
                <div class="dash-empty"><span>🛋️</span>Não estás inscrito em nenhuma aula.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header">
                <h2>📋 Último Treino</h2>
                <a href="user_profiles.php#presencas">Ver presenças →</a>
            </div>
            <div class="dash-panel-body">
                <?php if ($ultima_pres): ?>
                <div style="display:flex; flex-direction:column; gap:0.5rem;">
                    <p style="font-family:'Oswald',sans-serif; font-size:1.3rem; color:#111; margin:0; text-transform:uppercase;">
                        <?php echo htmlspecialchars($ultima_pres['class_type']); ?>
                    </p>
                    <p style="color:#d32f2f; font-weight:bold; font-size:0.95rem; margin:0;">
                        🕐 <?php echo htmlspecialchars($ultima_pres['time']); ?>
                    </p>
                    <p style="color:#888; font-size:0.85rem; margin:0;">
                        📍 <?php echo htmlspecialchars($ultima_pres['academy_name']); ?> · 
                        <?php echo date('d/m/Y', strtotime($ultima_pres['class_date'])); ?>
                    </p>
                </div>
                <?php else: ?>
                <div class="dash-empty"><span>📭</span>Nenhuma presença registada ainda.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header"><h2>📌 Mural de Avisos</h2></div>
            <div class="dash-panel-body">
                <?php
                $tem_aviso = false;
                $ra = $db->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
                while ($av = $ra->fetchArray(SQLITE3_ASSOC)) { $tem_aviso = true; ?>
                    <div class="aviso-item <?php echo htmlspecialchars($av['type']); ?>">
                        <strong><?php echo htmlspecialchars($av['title']); ?></strong>
                        <p><?php echo htmlspecialchars(mb_substr($av['message'], 0, 100)) . (mb_strlen($av['message']) > 100 ? '...' : ''); ?></p>
                        <small><?php echo date('d/m/Y', strtotime($av['created_at'])); ?></small>
                    </div>
                <?php }
                if (!$tem_aviso): ?>
                    <div class="dash-empty"><span>📭</span>Sem avisos. Bom treino!</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-header"><h2>⚡ Atalhos Rápidos</h2></div>
            <div class="dash-panel-body">
                <div class="dash-shortcuts">
                    <a href="user_profiles.php" class="dash-shortcut"><span>👤</span><small>Meu Perfil</small></a>
                    <a href="academies.php" class="dash-shortcut"><span>🏟️</span><small>Academias</small></a>
                    <a href="graduation.php" class="dash-shortcut"><span>🥋</span><small>Graduações</small></a>
                    <a href="events.php" class="dash-shortcut"><span>🏆</span><small>Eventos</small></a>
                    <a href="instructors.php" class="dash-shortcut"><span>🥊</span><small>Instrutores</small></a>
                    <?php if ($tem_academia): ?>
                    <a href="academy_details.php?id=<?php echo $aluno['academy_id']; ?>" class="dash-shortcut"><span>🏠</span><small>Minha Academia</small></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
    <?php endif; // fim roles ?>
</main>
<?php endif; // fim visitante vs autenticado ?>

<?php
$db->close();
include 'footer.php';
?>
