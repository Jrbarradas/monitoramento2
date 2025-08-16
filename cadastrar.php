<?php
session_start();
require_once "config.php";
require_once "includes/functions.php";

verificarLogin();

$userInfo = verificarAdmin($pdo, $_SESSION['id']);
$is_admin = $userInfo['is_admin'];
$estado_usuario = $userInfo['estado_permitido'];

$csrfToken = gerarTokenCSRF();
$pageTitle = 'Cadastrar Link';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!verificarTokenCSRF($_POST['csrf_token'] ?? '')) {
        $error = "Token de segurança inválido!";
    } else {
        try {
            $nome = sanitizeInput($_POST["nome"]);
            $ip = sanitizeInput($_POST["ip"]);
            $endereco = sanitizeInput($_POST["endereco"]);
            $cidade = sanitizeInput($_POST["cidade"]);
            $uf = strtoupper(sanitizeInput($_POST["uf"]));
            $contato = sanitizeInput($_POST["contato"]);
            $lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
            $lon = filter_input(INPUT_POST, 'lon', FILTER_VALIDATE_FLOAT);

            // Validations
            if (!validarIP($ip)) {
                throw new Exception("Endereço IP inválido!");
            }

            if (!$is_admin && $uf !== $estado_usuario) {
                throw new Exception("Você só pode cadastrar links no seu estado ($estado_usuario)");
            }

            // Check if IP already exists
            $stmt = $pdo->prepare("SELECT id FROM links WHERE ip = ?");
            $stmt->execute([$ip]);
            if ($stmt->rowCount() > 0) {
                throw new Exception("Este IP já está cadastrado!");
            }

            $sql = "INSERT INTO links (nome, ip, endereco, cidade, uf, contato, lat, lon) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $ip, $endereco, $cidade, $uf, $contato, $lat, $lon]);

            logAtividade($pdo, $_SESSION['id'], 'cadastrar_link', "Link: $nome ($ip)");
            $success = "Link cadastrado com sucesso!";
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Spacecom</title>
    <meta name="description" content="Cadastro de novos links para monitoramento">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <style>
        <?= comprimirCSS(file_get_contents('css/index.css')) ?>

        .card {
            max-width: 900px;
            margin: 0 auto;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            width: 100%;
        }

        .form-row > .form-group {
            flex: 1;
            min-width: 0;
        }

        /* Include common styles */
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="content">
        <div class="card">
            <?php if(isset($success)): ?>
                <div class="alert success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if(isset($error)): ?>
                <div class="alert error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="form-container" id="cadastroForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label for="nome">Nome do Link *</label>
                    <i class="fas fa-link input-icon"></i>
                    <input class="input-field" 
                           type="text" 
                           id="nome"
                           name="nome" 
                           required 
                           maxlength="255"
                           placeholder="Ex: Link Matriz-SP"
                           value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ip">Endereço IP *</label>
                        <i class="fas fa-network-wired input-icon"></i>
                        <input class="input-field" 
                               type="text" 
                               id="ip"
                               name="ip" 
                               required 
                               pattern="^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$"
                               placeholder="192.168.1.1"
                               value="<?= htmlspecialchars($_POST['ip'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="contato">Contato Responsável *</label>
                        <i class="fas fa-user input-icon"></i>
                        <input class="input-field" 
                               type="text" 
                               id="contato"
                               name="contato" 
                               required 
                               maxlength="100"
                               placeholder="João Silva"
                               value="<?= htmlspecialchars($_POST['contato'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="endereco">Endereço Físico *</label>
                    <i class="fas fa-map-marker-alt input-icon"></i>
                    <input class="input-field" 
                           type="text" 
                           id="endereco"
                           name="endereco" 
                           required 
                           maxlength="255"
                           placeholder="Rua Principal, 123"
                           value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="cidade">Cidade *</label>
                        <i class="fas fa-city input-icon"></i>
                        <input class="input-field" 
                               type="text" 
                               id="cidade"
                               name="cidade" 
                               required 
                               maxlength="100"
                               placeholder="São Paulo"
                               value="<?= htmlspecialchars($_POST['cidade'] ?? '') ?>">
                    </div>

                    <div class="form-group" style="max-width: 150px">
                        <label for="uf">UF *</label>
                        <i class="fas fa-flag input-icon"></i>
                        <?php if ($is_admin): ?>
                            <select class="input-field" id="uf" name="uf" required>
                                <option value="">Selecione</option>
                                <?php
                                $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                foreach ($estados as $uf) {
                                    $selected = ($_POST['uf'] ?? '') === $uf ? 'selected' : '';
                                    echo "<option value='$uf' $selected>$uf</option>";
                                }
                                ?>
                            </select>
                        <?php else: ?>
                            <input class="input-field" 
                                   type="text" 
                                   id="uf"
                                   name="uf" 
                                   value="<?= $estado_usuario ?>" 
                                   readonly>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="lat">Latitude *</label>
                        <i class="fas fa-globe-americas input-icon"></i>
                        <input class="input-field" 
                               type="number" 
                               id="lat"
                               step="any" 
                               name="lat" 
                               required 
                               placeholder="-23.5506507"
                               value="<?= htmlspecialchars($_POST['lat'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="lon">Longitude *</label>
                        <i class="fas fa-globe-americas input-icon"></i>
                        <input class="input-field" 
                               type="number" 
                               id="lon"
                               step="any" 
                               name="lon" 
                               required 
                               placeholder="-46.6333824"
                               value="<?= htmlspecialchars($_POST['lon'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button primary" id="submitBtn">
                        <i class="fas fa-save"></i> Cadastrar Link
                    </button>
                    <a href="index.php" class="button secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="js/common.js?v=<?= gerarHashCache('js/common.js') ?>" defer></script>
    <script defer>
        // Form validation
        const form = document.getElementById('cadastroForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Real-time validation
        form.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
                validateField(this);
            });
        });
        
        function validateField(field) {
            const value = field.value.trim();
            const group = field.closest('.form-group');
            
            // Remove previous validation classes
            group.classList.remove('error', 'success');
            
            if (field.required && !value) {
                group.classList.add('error');
                return false;
            }
            
            // IP validation
            if (field.name === 'ip' && value && !utils.isValidIP(value)) {
                group.classList.add('error');
                return false;
            }
            
            if (value) {
                group.classList.add('success');
            }
            
            return true;
        }
        
        // Form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate all required fields
            form.querySelectorAll('[required]').forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                notifications.show('Por favor, corrija os erros no formulário', 'error');
                return;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Cadastrando...';
            submitBtn.disabled = true;
        });
        
        // Auto-focus first field
        document.addEventListener('DOMContentLoaded', () => {
            document.getElementById('nome').focus();
        });
    </script>
</body>
</html>
