<?php
// Receber dados do formulário
$nivelAcesso = $_POST['nivel_acesso'];
$estadoPermitido = ($nivelAcesso === 'estado') ? $_POST['estado_permitido'] : null;

// Inserir no banco
$sql = "INSERT INTO usuarios (nome, email, senha, nivel_acesso, estado_permitido) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $nome, $email, $senhaHash, $nivelAcesso, $estadoPermitido);
$stmt->execute();
?>
