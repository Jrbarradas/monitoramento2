<?php
/**
 * Funções auxiliares do sistema de monitoramento
 */

/**
 * Verifica se o usuário está logado
 */
function verificarLogin() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Verifica se o usuário é administrador
 */
function verificarAdmin($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("SELECT nivel_acesso, estado_permitido FROM usuarios WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'is_admin' => ($user && $user['nivel_acesso'] === 'admin'),
            'estado_permitido' => $user['estado_permitido'] ?? ''
        ];
    } catch (PDOException $e) {
        error_log("Erro ao verificar permissões: " . $e->getMessage());
        return ['is_admin' => false, 'estado_permitido' => ''];
    }
}

/**
 * Sanitiza entrada de dados
 */
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida endereço IP
 */
function validarIP($ip) {
    return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}

/**
 * Gera token CSRF
 */
function gerarTokenCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verificarTokenCSRF($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formata data para exibição
 */
function formatarData($data, $formato = 'd/m/Y H:i') {
    if (!$data) return '-';
    return date($formato, strtotime($data));
}

/**
 * Obtém nome completo do estado
 */
function getNomeEstado($uf) {
    $estados = [
        'AC' => 'Acre',
        'AL' => 'Alagoas',
        'AP' => 'Amapá',
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'CE' => 'Ceará',
        'DF' => 'Distrito Federal',
        'ES' => 'Espírito Santo',
        'GO' => 'Goiás',
        'MA' => 'Maranhão',
        'MT' => 'Mato Grosso',
        'MS' => 'Mato Grosso do Sul',
        'MG' => 'Minas Gerais',
        'PA' => 'Pará',
        'PB' => 'Paraíba',
        'PR' => 'Paraná',
        'PE' => 'Pernambuco',
        'PI' => 'Piauí',
        'RJ' => 'Rio de Janeiro',
        'RN' => 'Rio Grande do Norte',
        'RS' => 'Rio Grande do Sul',
        'RO' => 'Rondônia',
        'RR' => 'Roraima',
        'SC' => 'Santa Catarina',
        'SP' => 'São Paulo',
        'SPC'=> 'Spacecom',
        'SE' => 'Sergipe',
        'TO' => 'Tocantins'
    ];
    return $estados[strtoupper($uf)] ?? 'Estado Desconhecido';
}

/**
 * Gera menu de navegação
 */
function gerarMenuNavegacao($isAdmin, $paginaAtual = '') {
    $menu = [
        'index.php' => ['icon' => 'home', 'text' => 'Dashboard', 'admin' => false],
        'cadastrar.php' => ['icon' => 'plus-circle', 'text' => 'Adicionar Link', 'admin' => true],
        'list_links.php' => ['icon' => 'list', 'text' => 'Gerenciar Links', 'admin' => true],
        'teste_ping.php' => ['icon' => 'network-wired', 'text' => 'Teste de Ping', 'admin' => false],
        'mapa.php' => ['icon' => 'map-marked-alt', 'text' => 'Mapa da Rede', 'admin' => true],
        'historico.php' => ['icon' => 'history', 'text' => 'Histórico', 'admin' => true],
        'usuarios.php' => ['icon' => 'users', 'text' => 'Usuários', 'admin' => true],
        'logout.php' => ['icon' => 'sign-out-alt', 'text' => 'Logout', 'admin' => false]
    ];
    
    $html = '';
    foreach ($menu as $url => $item) {
        if ($item['admin'] && !$isAdmin) continue;
        
        $active = (basename($_SERVER['PHP_SELF']) === $url) ? 'active' : '';
        $html .= '<div class="nav-item">
            <a href="' . $url . '" class="nav-link ' . $active . '">
                <div class="nav-icon"><i class="fas fa-' . $item['icon'] . '"></i></div>
                <div class="nav-text">' . $item['text'] . '</div>
            </a>
        </div>';
    }
    
    return $html;
}

/**
 * Log de atividades do sistema
 */
function logAtividade($pdo, $userId, $acao, $detalhes = '') {
    try {
        $stmt = $pdo->prepare("INSERT INTO log_atividades (user_id, acao, detalhes, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userId, $acao, $detalhes]);
    } catch (PDOException $e) {
        error_log("Erro ao registrar log: " . $e->getMessage());
    }
}

/**
 * Comprime CSS inline para melhor performance
 */
function comprimirCSS($css) {
    $css = preg_replace('/\/\*.*?\*\//s', '', $css);
    $css = preg_replace('/\s+/', ' ', $css);
    $css = str_replace(['; ', ' {', '{ ', ' }', '} ', ': '], [';', '{', '{', '}', '}', ':'], $css);
    return trim($css);
}

/**
 * Gera hash para cache busting
 */
function gerarHashCache($arquivo) {
    if (file_exists($arquivo)) {
        return substr(md5_file($arquivo), 0, 8);
    }
    return time();
}
?>