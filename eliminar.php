<?php
session_start();

$autenticado = (
    isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true &&
    isset($_SESSION['authenticated_from_login']) && $_SESSION['authenticated_from_login'] === true &&
    isset($_SESSION['session_token']) &&
    isset($_SESSION['ip_address']) && $_SESSION['ip_address'] === $_SERVER['REMOTE_ADDR']
);

if (!$autenticado) {
    header("Location: Loginti.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hola</title>
</head>
<body>
    <h1>Hola</h1>
</body>
</html>
