<?php
session_start();
require_once "config.php";
require_once "includes/functions.php";

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
$csrfToken = gerarTokenCSRF();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido!';
    } else {
        $username = sanitizeInput($_POST['username']);
        $password = trim($_POST['password']);
        
        if (empty($username) || empty($password)) {
            $error = 'Preencha todos os campos!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, password, estado_permitido, nivel_acesso FROM usuarios WHERE LOWER(username) = LOWER(?)");
                $stmt->execute([$username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user && password_verify($password, $user['password'])) {
                    // Regenerate session ID for security
                    session_regenerate_id(true);
                    
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['estado_permitido'] = $user['estado_permitido'];
                    $_SESSION['nivel_acesso'] = $user['nivel_acesso'];
                    
                    // Log successful login
                    logAtividade($pdo, $user['id'], 'login', 'Login realizado com sucesso');
                    
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Credenciais inválidas!';
                    // Log failed attempt
                    error_log("Failed login attempt for username: $username from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Erro interno do sistema!';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Spacecom</title>
    <meta name="description" content="Acesso ao sistema de monitoramento Spacecom">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?= gerarHashCache('css/login.css') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Spacecom</h1>
            <p>Sistema de Monitoramento</p>
        </div>
        
        <form method="POST" action="login.php" id="loginForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
            
            <div class="input-group">
                <label for="username">Usuário</label>
                <i class="fas fa-user input-icon" aria-hidden="true"></i>
                <input type="text" 
                       id="username" 
                       name="username" 
                       placeholder="Digite seu usuário" 
                       required 
                       autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            
            <div class="input-group">
                <label for="password">Senha</label>
                <i class="fas fa-lock input-icon" aria-hidden="true"></i>
                <input type="password" 
                       id="password" 
                       name="password" 
                       placeholder="Digite sua senha" 
                       required 
                       autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login" id="loginBtn">
                <span>Entrar</span>
            </button>
            
            <div class="error-message" id="errorMessage"><?= $error ?></div>
        </form>
        
        <div class="footer">
            Spacecom Monitoramento S/A © <?= date('Y') ?>
        </div>
    </div>

    <script defer>
        // Form validation and enhancement
        const form = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const inputs = form.querySelectorAll('input[required]');
        
        // Real-time validation
        inputs.forEach(input => {
            input.addEventListener('blur', validateField);
            input.addEventListener('input', clearErrors);
        });
        
        function validateField(e) {
            const field = e.target;
            const group = field.closest('.input-group');
            
            if (!field.value.trim()) {
                group.classList.add('error');
                group.classList.remove('success');
            } else {
                group.classList.remove('error');
                group.classList.add('success');
            }
        }
        
        function clearErrors(e) {
            const group = e.target.closest('.input-group');
            group.classList.remove('error');
            document.getElementById('errorMessage').textContent = '';
        }
        
        // Form submission with loading state
        form.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                document.getElementById('errorMessage').textContent = 'Preencha todos os campos!';
                return;
            }
            
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Entrando...';
        });
        
        // Auto-focus on first input
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>

    
    try {
        // Buscar usuário (case-insensitive)
        $stmt = $pdo->prepare("SELECT id, username, password, estado_permitido FROM usuarios WHERE LOWER(username) = LOWER(?)");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Verificar senha - compatível com senhas antigas e novas
            if (password_verify($password, $user['password']) || $password === $user['password']) {
                // Se a senha estava em texto plano, converte para hash
                if (!password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $user['id']]);
                }
                
                // Autenticação bem sucedida
                $_SESSION['loggedin'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['estado_permitido'] = $user['estado_permitido']; // Armazena o estado permitido
                
                header('Location: index.php');
                exit;
            } else {
                error_log("Tentativa de login falhou: Senha incorreta para usuário '$username'");
                $error = 'Senha incorreta!';
            }
        } else {
            error_log("Tentativa de login falhou: Usuário '$username' não encontrado");
            $error = 'Usuário não encontrado!';
        }
    } catch (PDOException $e) {
        error_log("Erro no sistema durante login: " . $e->getMessage());
        $error = 'Erro no sistema: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login - Spacecom</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Spacecom</h1>
            <p>Monitoramento de Links</p>
        </div>
        <form method="POST" action="login.php">
            <div class="input-group">
                <label for="username">Usuário</label>
                <i class="fas fa-user input-icon"></i>
                <input type="text" id="username" name="username" placeholder="Digite seu usuário" required>
            </div>
            <div class="input-group">
                <label for="password">Senha</label>
                <i class="fas fa-lock input-icon"></i>
                <input type="password" id="password" name="password" placeholder="Digite sua senha" required>
            </div>
            <button type="submit" class="btn-login">Entrar</button>
            <div class="error-message"><?php echo $error; ?></div>
        </form>
        <div class="footer">
            Spacecom Monitoramento S/A © 2025
        </div>
    </div>
</body>
</html>
