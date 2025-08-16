<?php
session_start();

require_once "config.php";

// Verifica se já está logado
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index.php');
    exit;
}

// Testa a conexão com o banco de dados
try {
    $pdo->query("SELECT 1");
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
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
