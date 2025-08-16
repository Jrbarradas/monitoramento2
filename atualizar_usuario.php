<?php
// atualizar_usuario.php
session_start();
require 'conexao.php';

var_dump($_POST); // Verifique se os dados estão chegando

if(isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $nome = mysqli_real_escape_string($conn, $_POST['nome']);
    
    $sql = "UPDATE usuarios SET nome='$nome' WHERE id=$id";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['sucesso'] = "Usuário atualizado!";
    } else {
        $_SESSION['erro'] = "Erro: " . mysqli_error($conn);
    }
    header("Location: lista_usuarios.php");
    exit;
}
?>
