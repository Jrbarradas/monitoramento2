<?php
date_default_timezone_set('America/Sao_Paulo');

/**
 * Monitor de Links com Alertas para Google Chat
 * Monitora links e envia alertas quando ficam offline por 3 minutos consecutivos
 */

class LinkMonitor {
    
    private $webhookUrl;
    private $pdo;
    private $statusFile;
    private $logFile;
    private $checkInterval = 30; // segundos
    private $offlineThreshold = 180; // 3 minutos em segundos
    
    public function __construct($webhookUrl, $dbConfig) {
        $this->webhookUrl = $webhookUrl;
        $this->statusFile = __DIR__ . '/links_status.json';
        $this->logFile = __DIR__ . '/monitor.log';
        
        // Conecta ao banco de dados
        $this->connectDatabase($dbConfig);
        
        $this->initializeStatus();
    }
    
    /**
     * Conecta ao banco de dados
     */
    private function connectDatabase($dbConfig) {
        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $this->writeLog("Conexão com banco de dados estabelecida com sucesso");
        } catch (PDOException $e) {
            $this->writeLog("Erro ao conectar com banco de dados: " . $e->getMessage());
            throw new Exception("Falha na conexão com banco de dados: " . $e->getMessage());
        }
    }
    
    /**
     * Carrega links do banco de dados
     */
    private function loadLinksFromDatabase() {
        try {
            // Query ajustada para sua estrutura de tabela
            $sql = "SELECT 
                        id,
                        nome,
                        ip,
                        endereco,
                        cidade,
                        uf,
                        contato,
                        updated_at
                    FROM links 
                    WHERE ip IS NOT NULL 
                    AND ip != '' 
                    ORDER BY nome";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            $links = $stmt->fetchAll();
            
            $this->writeLog("Carregados " . count($links) . " links do banco de dados");
            return $links;
            
        } catch (PDOException $e) {
            $this->writeLog("Erro ao carregar links do banco: " . $e->getMessage());
            throw new Exception("Erro ao carregar links: " . $e->getMessage());
        }
    }
    
    /**
     * Inicializa o arquivo de status dos links
     */
    private function initializeStatus() {
        $linksData = $this->loadLinksFromDatabase();
        
        if (!file_exists($this->statusFile)) {
            $initialStatus = [];
            foreach ($linksData as $link) {
                $linkId = $link['id'] . '_' . $link['ip'];
                $initialStatus[$linkId] = [
                    'online' => true,
                    'last_check' => time(),
                    'offline_since' => null,
                    'alert_sent' => false,
                    'recovery_sent' => false,
                    'data' => $link
                ];
            }
            file_put_contents($this->statusFile, json_encode($initialStatus, JSON_PRETTY_PRINT));
        } else {
            // Atualiza status existente com novos links do banco
            $this->updateStatusWithNewLinks();
        }
    }
    
    /**
     * Atualiza arquivo de status com novos links do banco
     */
    private function updateStatusWithNewLinks() {
        $currentStatus = $this->loadStatus();
        $linksFromDb = $this->loadLinksFromDatabase();
        $updated = false;
        
        foreach ($linksFromDb as $link) {
            $linkId = $link['id'] . '_' . $link['ip'];
            if (!isset($currentStatus[$linkId])) {
                $currentStatus[$linkId] = [
                    'online' => true,
                    'last_check' => time(),
                    'offline_since' => null,
                    'alert_sent' => false,
                    'recovery_sent' => false,
                    'data' => $link
                ];
                $updated = true;
                $this->writeLog("Novo link adicionado ao monitoramento: {$link['nome']}");
            } else {
                // Atualiza dados do link caso tenham mudado no banco
                $currentStatus[$linkId]['data'] = $link;
            }
        }
        
        if ($updated) {
            $this->saveStatus($currentStatus);
        }
    }
    
    /**
     * Carrega o status atual dos links
     */
    private function loadStatus() {
        if (file_exists($this->statusFile)) {
            $content = file_get_contents($this->statusFile);
            return json_decode($content, true);
        }
        return [];
    }
    
    /**
     * Salva o status dos links
     */
    private function saveStatus($status) {
        file_put_contents($this->statusFile, json_encode($status, JSON_PRETTY_PRINT));
    }
    
    /**
     * Escreve log
     */
    private function writeLog($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        echo $logMessage;
    }
    
    /**
     * Verifica conectividade com ping - versão melhorada
     */
    private function pingHost($ip) {
        $this->writeLog("Verificando conectividade para $ip");
        
        // Limpa o IP de espaços em branco
        $ip = trim($ip);
        
        // Verifica se é um IP válido
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->writeLog("IP inválido: $ip");
            return false;
        }
        
        // Comando ping para diferentes sistemas operacionais
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $command = "ping -n 1 -w 3000 $ip 2>nul";
        } else {
            $command = "ping -c 1 -W 3 $ip 2>/dev/null";
        }
        
        // Executa o comando
        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);
        
        $isOnline = ($returnCode === 0);
        $this->writeLog("Ping para $ip: " . ($isOnline ? 'SUCESSO' : 'FALHOU') . " (código: $returnCode)");
        
        return $isOnline;
    }
    
    /**
     * Envia mensagem para Google Chat - versão melhorada
     */
    private function sendGoogleChatMessage($message) {
        $this->writeLog("Tentando enviar mensagem para Google Chat");
        $this->writeLog("URL: " . $this->webhookUrl);
        
        $payload = json_encode(['text' => $message], JSON_UNESCAPED_UNICODE);
        $this->writeLog("Payload: " . $payload);
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $this->webhookUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'LinkMonitor/1.0',
            CURLOPT_VERBOSE => false
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        
        $this->writeLog("HTTP Code: $httpCode");
        $this->writeLog("Response: " . $response);
        
        if ($error) {
            $this->writeLog("Erro cURL: $error");
            return false;
        }
        
        if ($httpCode >= 200 && $httpCode < 300) {
            $this->writeLog("Mensagem enviada com sucesso para Google Chat");
            return true;
        } else {
            $this->writeLog("Erro ao enviar mensagem: HTTP $httpCode - Response: $response");
            return false;
        }
    }
    
    /**
     * Formata mensagem de link offline
     */
    private function formatOfflineMessage($linkData) {
        $timestamp = date('d/m/Y H:i:s');
        
        return "🔴 *ALERTA - LINK OFFLINE*\n\n" .
               "📡 *Nome:* {$linkData['nome']}\n" .
               "🌐 *IP:* {$linkData['ip']}\n" .
               "📍 *Endereço:* {$linkData['endereco']}\n" .
               "🏙️ *Cidade:* {$linkData['cidade']}\n" .
               "📍 *UF:* {$linkData['uf']}\n" .
               "📧 *Contato:* {$linkData['contato']}\n\n" .
               "⚠️ *Status:* Link está OFFLINE há mais de 3 minutos\n" .
               "🕐 *Horário:* $timestamp\n\n" .
               "Por favor, verifique a conectividade imediatamente!";
    }
    
    /**
     * Formata mensagem de link online
     */
    private function formatOnlineMessage($linkData) {
        $timestamp = date('d/m/Y H:i:s');
        
        return "✅ *CONEXÃO RESTABELECIDA*\n\n" .
               "📡 *Nome:* {$linkData['nome']}\n" .
               "🌐 *IP:* {$linkData['ip']}\n" .
               "📍 *Endereço:* {$linkData['endereco']}\n" .
               "🏙️ *Cidade:* {$linkData['cidade']}\n" .
               "📍 *UF:* {$linkData['uf']}\n" .
               "📧 *Contato:* {$linkData['contato']}\n\n" .
               "✅ *Status:* Conexão RESTABELECIDA\n" .
               "🕐 *Horário:* $timestamp\n\n" .
               "O link voltou ao funcionamento normal.";
    }
    
    /**
     * Verifica um único link
     */
    private function checkSingleLink($linkData, &$status) {
        $linkId = $linkData['id'] . '_' . $linkData['ip'];
        $currentTime = time();
        
        // Verifica conectividade
        $isOnline = $this->pingHost($linkData['ip']);
        
        if ($isOnline) {
            // Link está online
            if (!$status[$linkId]['online']) {
                // Link voltou a ficar online - enviar alerta de recuperação
                $this->writeLog("Link {$linkData['nome']} voltou a ficar online");
                $message = $this->formatOnlineMessage($linkData);
                $this->sendGoogleChatMessage($message);
                $status[$linkId]['recovery_sent'] = true;
            }
            
            // Atualiza status
            $status[$linkId]['online'] = true;
            $status[$linkId]['last_check'] = $currentTime;
            $status[$linkId]['offline_since'] = null;
            $status[$linkId]['alert_sent'] = false;
            
        } else {
            // Link está offline
            if ($status[$linkId]['online']) {
                // Link acabou de ficar offline
                $this->writeLog("Link {$linkData['nome']} ficou offline - iniciando contagem");
                $status[$linkId]['online'] = false;
                $status[$linkId]['offline_since'] = $currentTime;
                $status[$linkId]['alert_sent'] = false;
                $status[$linkId]['recovery_sent'] = false;
            }
            
            // Verifica se deve enviar alerta (offline há mais de 3 minutos)
            $offlineTime = $currentTime - $status[$linkId]['offline_since'];
            if ($offlineTime >= $this->offlineThreshold && !$status[$linkId]['alert_sent']) {
                $this->writeLog("Enviando alerta de offline para {$linkData['nome']} (offline há " . round($offlineTime/60, 1) . " minutos)");
                $message = $this->formatOfflineMessage($linkData);
                $result = $this->sendGoogleChatMessage($message);
                if ($result) {
                    $status[$linkId]['alert_sent'] = true;
                }
            }
            
            $status[$linkId]['last_check'] = $currentTime;
        }
    }
    
    /**
     * Verifica todos os links
     */
    public function checkAllLinks() {
        // Recarrega links do banco a cada verificação para pegar atualizações
        $linksData = $this->loadLinksFromDatabase();
        $this->writeLog("Iniciando verificação de " . count($linksData) . " links");
        
        $status = $this->loadStatus();
        
        // Atualiza status com novos links se houver
        $this->updateStatusWithNewLinks();
        $status = $this->loadStatus(); // Recarrega após possível atualização
        
        foreach ($linksData as $linkData) {
            try {
                $this->checkSingleLink($linkData, $status);
            } catch (Exception $e) {
                $this->writeLog("Erro ao verificar link {$linkData['nome']}: " . $e->getMessage());
            }
        }
        
        $this->saveStatus($status);
        $this->writeLog("Verificação concluída");
    }
    
    /**
     * Testa envio de mensagem
     */
    public function testMessage() {
        $testMessage = "🧪 *TESTE DO MONITOR DE LINKS*\n\n" .
                      "Esta é uma mensagem de teste para verificar se o webhook está funcionando.\n\n" .
                      "🕐 Enviado em: " . date('d/m/Y H:i:s');
        
        $this->writeLog("Enviando mensagem de teste");
        $result = $this->sendGoogleChatMessage($testMessage);
        
        if ($result) {
            $this->writeLog("Teste concluído com sucesso!");
            echo "✅ Mensagem de teste enviada com sucesso!\n";
        } else {
            $this->writeLog("Falha no teste de envio");
            echo "❌ Falha ao enviar mensagem de teste\n";
        }
        
        return $result;
    }
    
    /**
     * Inicia monitoramento contínuo
     */
    public function startMonitoring() {
        $this->writeLog("Iniciando monitoramento de links...");
        $this->writeLog("Intervalo de verificação: {$this->checkInterval} segundos");
        $this->writeLog("Threshold offline: " . ($this->offlineThreshold/60) . " minutos");
        
        // Conta links iniciais do banco
        $initialLinks = $this->loadLinksFromDatabase();
        $linkCount = count($initialLinks);
        
        // Envia mensagem inicial
        $timestamp = date('d/m/Y H:i:s');
        $initialMessage = "🚀 *MONITOR DE LINKS INICIADO*\n\n" .
                         "🔍 Monitorando $linkCount links do banco de dados\n" .
                         "⏱️ Verificação a cada {$this->checkInterval} segundos\n" .
                         "⚠️ Alerta após " . ($this->offlineThreshold/60) . " minutos offline\n\n" .
                         "Sistema ativo desde: $timestamp";
        
        $this->sendGoogleChatMessage($initialMessage);
        
        // Loop principal de monitoramento
        try {
            while (true) {
                $this->checkAllLinks();
                sleep($this->checkInterval);
            }
        } catch (Exception $e) {
            $this->writeLog("Erro no monitoramento: " . $e->getMessage());
            
            // Envia mensagem de erro
            $errorMessage = "❌ *ERRO NO MONITOR DE LINKS*\n\n" .
                           "Erro: " . $e->getMessage() . "\n" .
                           "Horário: " . date('d/m/Y H:i:s');
            $this->sendGoogleChatMessage($errorMessage);
        }
    }
    
    /**
     * Gera relatório de status atual
     */
    public function getStatusReport() {
        $status = $this->loadStatus();
        $report = "📊 *RELATÓRIO DE STATUS DOS LINKS*\n\n";
        
        $totalLinks = count($status);
        $onlineLinks = 0;
        $offlineLinks = 0;
        
        foreach ($status as $linkId => $linkStatus) {
            $linkData = $linkStatus['data'];
            $statusIcon = $linkStatus['online'] ? "✅" : "🔴";
            $statusText = $linkStatus['online'] ? "ONLINE" : "OFFLINE";
            
            if ($linkStatus['online']) {
                $onlineLinks++;
            } else {
                $offlineLinks++;
            }
            
            $report .= "{$statusIcon} *{$linkData['nome']}* ({$linkData['ip']})\n";
            $report .= "   Status: $statusText\n";
            $report .= "   Última verificação: " . date('H:i:s', $linkStatus['last_check']) . "\n";
            
            if (!$linkStatus['online'] && $linkStatus['offline_since']) {
                $offlineMinutes = round((time() - $linkStatus['offline_since']) / 60, 1);
                $report .= "   Offline há: {$offlineMinutes} minutos\n";
            }
            
            $report .= "\n";
        }
        
        $report .= "📈 *RESUMO:*\n";
        $report .= "Total: $totalLinks | Online: $onlineLinks | Offline: $offlineLinks\n\n";
        $report .= "🕐 Relatório gerado em: " . date('d/m/Y H:i:s');
        
        return $report;
    }
}

//====================================================================
// CONFIGURAÇÃO DO BANCO DE DADOS
//====================================================================

// Configuração do banco de dados - ALTERE AQUI
$dbConfig = [
    'host' => 'localhost',             // Servidor do banco
    'database' => 'monitoramento',     // Nome do banco de dados
    'username' => 'app_user',          // Usuário do banco
    'password' => 'Kangoo.2010'        // Senha do banco
];

// URL do webhook do Google Chat
$webhookUrl = "https://chat.googleapis.com/v1/spaces/AAQAdSW-O0c/messages?key=AIzaSyDdI0hCZtE6vySjMm-WEfRq3CPzqKqqsHI&token=mvt-H38s6Y2ltBGyHNjV7OuQuaWGE1jM-hI5Iqn9j98";

//====================================================================
// EXECUÇÃO
//====================================================================

try {
    // Cria instância do monitor
    $monitor = new LinkMonitor($webhookUrl, $dbConfig);
    
    // Verifica argumentos da linha de comando
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'test':
                // Testa envio de mensagem
                echo "🧪 Testando envio de mensagem...\n";
                $monitor->testMessage();
                break;
                
            case 'report':
                // Gera apenas relatório
                echo "📊 Gerando relatório...\n";
                $report = $monitor->getStatusReport();
                echo $report . "\n";
                $monitor->sendGoogleChatMessage($report);
                break;
                
            case 'check':
                // Executa apenas uma verificação
                echo "🔍 Executando verificação única...\n";
                $monitor->checkAllLinks();
                break;
                
            default:
                echo "Uso: php link_monitor.php [test|report|check]\n";
                echo "  test   - Testa envio de mensagem\n";
                echo "  report - Gera relatório de status\n";
                echo "  check  - Executa uma verificação\n";
                echo "  (sem parâmetro) - Inicia monitoramento contínuo\n";
        }
    } else {
        // Inicia monitoramento contínuo
        echo "🚀 Iniciando monitoramento contínuo...\n";
        echo "Pressione Ctrl+C para parar\n\n";
        $monitor->startMonitoring();
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    error_log("Erro no monitor de links: " . $e->getMessage());
}

?>
