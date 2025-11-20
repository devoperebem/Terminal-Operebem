<!DOCTYPE html>
<?php
// Detectar tema do usuário
$userTheme = $_SESSION['user_theme'] ?? 'light';
?>
<html lang="pt-BR" class="<?= htmlspecialchars($userTheme, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relógio de Mercados Globais - Terminal Operebem</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Light Theme */
        html.light body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        html.light .container {
            width: 100%;
            max-width: 800px;
            background: #ffffff;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        html.light h1 {
            text-align: center;
            color: #1f2937;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 800;
        }
        
        html.light .subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }
        
        html.light .footer {
            text-align: center;
            margin-top: 30px;
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        /* Dark Blue Theme */
        html.dark-blue body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #1e2a3a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        html.dark-blue .container {
            width: 100%;
            max-width: 800px;
            background: rgba(26, 37, 47, 0.95);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        html.dark-blue h1 {
            text-align: center;
            color: #ffffff;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 800;
        }
        
        html.dark-blue .subtitle {
            text-align: center;
            color: #9ca3af;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }
        
        html.dark-blue .footer {
            text-align: center;
            margin-top: 30px;
            color: #9ca3af;
            font-size: 0.85rem;
        }
        
        /* All Black Theme */
        html.all-black body {
            font-family: 'Inter', sans-serif;
            background: #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        html.all-black .container {
            width: 100%;
            max-width: 800px;
            background: rgba(17, 17, 17, 0.95);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        html.all-black h1 {
            text-align: center;
            color: #ffffff;
            font-size: 2rem;
            margin-bottom: 10px;
            font-weight: 800;
        }
        
        html.all-black .subtitle {
            text-align: center;
            color: #9ca3af;
            font-size: 0.9rem;
            margin-bottom: 30px;
        }
        
        html.all-black .footer {
            text-align: center;
            margin-top: 30px;
            color: #9ca3af;
            font-size: 0.85rem;
        }
        
        .clock-wrapper {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Override widget styles for fullscreen */
        .market-clock-widget {
            max-width: 100% !important;
        }
        
        /* Links */
        .footer a {
            color: #0d6efd;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        html.dark-blue .footer a,
        html.all-black .footer a {
            color: #60a5fa;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🌍 Mercados Globais 24h</h1>
        <p class="subtitle">Acompanhe os horários de funcionamento das principais bolsas de valores do mundo em tempo real</p>
        
        <div class="clock-wrapper">
            <?php
            $clockPath = __DIR__ . '/../../../widgets/market-clock.php';
            if (is_file($clockPath)) {
                include $clockPath;
            } else {
                echo '<div style="text-align:center;color:#ef4444;">Widget de Market Clock indisponível.</div>';
            }
            ?>
        </div>
        
        <div class="footer">
            <p>Horários em BRT (UTC-3) | Atualização em tempo real</p>
            <p><a href="https://terminal.operebem.com.br" target="_blank">Terminal Operebem</a></p>
        </div>
    </div>
</body>
</html>
