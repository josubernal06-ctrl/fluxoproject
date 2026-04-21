<?php
session_start();
include 'database.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT id, nombre, password, rol FROM usuarios WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nombre'];
            $_SESSION['user_rol'] = $user['rol'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "El usuario no existe.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css">
    <style>
        .auth-container { max-width: 400px; margin: 100px auto; padding: 30px; background: #2a2a2a; border-radius: 10px; border: 1px solid #333; }
        .auth-container h2 { margin-bottom: 20px; text-align: center; }
        .auth-form input { width: 100%; padding: 12px; margin-bottom: 15px; background: #1e1e1e; border: 1px solid #444; color: white; border-radius: 5px; }
        .error-msg { color: #ff4d4d; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Iniciar Sesión</h2>
        <?php if($error): ?> <p class="error-msg"><?php echo $error; ?></p> <?php endif; ?>
        <form action="login.php" method="POST" class="auth-form">
            <input type="email" name="email" placeholder="Correo electrónico" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit" class="btn-primary" style="width: 100%;">Ingresar</button>
        </form>
        <p style="margin-top: 15px; text-align: center;">¿No tienes cuenta? <a href="signup.php" style="color: #3ebd60;">Regístrate</a></p>
    </div>
</body>
</html>