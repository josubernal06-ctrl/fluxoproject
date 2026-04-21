<?php
include 'database.php';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $ciudad = $_POST['ciudad'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Verificar si el email ya existe
    $check_email = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result = $check_email->get_result();

    if ($result->num_rows > 0) {
        $message = "El correo electrónico ya está registrado.";
    } else {
        $sql = "INSERT INTO usuarios (nombre, apellido, telefono, ciudad, email, password) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $nombre, $apellido, $telefono, $ciudad, $email, $password);

        if ($stmt->execute()) {
            $message = "Registro exitoso. <a href='login.php'>Inicia sesión aquí</a>";
        } else {
            $message = "Hubo un error al registrarte.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registro | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css">
    <style>
        .auth-container { max-width: 400px; margin: 100px auto; padding: 30px; background: #2a2a2a; border-radius: 10px; border: 1px solid #333; }
        .auth-container h2 { margin-bottom: 20px; text-align: center; }
        .auth-form input, .auth-form select { width: 100%; padding: 12px; margin-bottom: 15px; background: #1e1e1e; border: 1px solid #444; color: white; border-radius: 5px; }
        .msg { margin-bottom: 15px; color: #3ebd60; text-align: center; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Crear Cuenta</h2>
        <?php if($message): ?> <p class="msg"><?php echo $message; ?></p> <?php endif; ?>
        <form action="signup.php" method="POST" class="auth-form">
            <input type="text" name="nombre" placeholder="Nombre" required>
            <input type="text" name="apellido" placeholder="Apellido" required>
            <input type="text" name="telefono" placeholder="Número de teléfono" required>
            <input type="text" name="ciudad" placeholder="Ciudad" required>
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" class="btn-primary" style="width: 100%;">Registrarse</button>
        </form>
        <p style="margin-top: 15px; text-align: center;">¿Ya tienes cuenta? <a href="login.php" style="color: #00d2ff;">Inicia sesión</a></p>
    </div>
</body>
</html>