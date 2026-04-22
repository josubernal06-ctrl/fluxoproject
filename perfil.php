<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$mensaje = "";
$tipo_mensaje = ""; 

$query = $conn->prepare("SELECT nombre, apellido, telefono, ciudad, email, password, rol FROM usuarios WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $ciudad = $_POST['ciudad'];

    $update = $conn->prepare("UPDATE usuarios SET nombre = ?, apellido = ?, telefono = ?, ciudad = ? WHERE id = ?");
    $update->bind_param("ssssi", $nombre, $apellido, $telefono, $ciudad, $user_id);
    
    if ($update->execute()) {
        $_SESSION['user_name'] = $nombre;
        $mensaje = "Datos actualizados correctamente.";
        $tipo_mensaje = "success";
        $user['nombre'] = $nombre; $user['apellido'] = $apellido;
        $user['telefono'] = $telefono; $user['ciudad'] = $ciudad;
    } else {
        $mensaje = "Error al actualizar los datos.";
        $tipo_mensaje = "error";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if (password_verify($current_pass, $user['password'])) {
        if ($new_pass === $confirm_pass) {
            $hashed_pass = password_hash($new_pass, PASSWORD_BCRYPT);
            $update_pass = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $update_pass->bind_param("si", $hashed_pass, $user_id);
            if ($update_pass->execute()) {
                $mensaje = "Contraseña actualizada con éxito.";
                $tipo_mensaje = "success";
            }
        } else {
            $mensaje = "Las nuevas contraseñas no coinciden.";
            $tipo_mensaje = "error";
        }
    } else {
        $mensaje = "La contraseña actual es incorrecta.";
        $tipo_mensaje = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css">
    <link rel="icon" type="image/png" href="src/logos/mini-logo.jpg">
    <style>
        body { margin: 0; background-color: var(--bg-dark); overflow: hidden; }
        
        .dashboard-layout {
            display: grid;
            grid-template-columns: 260px 1fr;
            height: 100vh;
        }

        /* BARRA LATERAL */
        .sidebar {
            background-color: #151515;
            border-right: 1px solid #333;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar-logo { text-align: center; margin-bottom: 50px; }
        .sidebar-logo img { width: 100%; max-width: 180px; }

        .sidebar-menu { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #aaa;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .sidebar-menu a:hover { background-color: #2a2a2a; color: #00d2ff; }
        .sidebar-menu a.active { background: var(--fluxo-gradient); color: #fff; }

        /* CONTENIDO */
        .main-content {
            padding: 40px 60px;
            overflow-y: auto;
            background-color: #1e1e1e;
        }

        .header-title { margin-bottom: 30px; font-size: 2rem; color: #fff; }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 1100px;
        }

        .card {
            background: #2a2a2a;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #333;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .card h3 { 
            margin-bottom: 25px; 
            color: #00d2ff; 
            font-size: 1.2rem;
            border-bottom: 1px solid #333;
            padding-bottom: 15px;
        }

        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 8px; color: #888; font-size: 0.85rem; }
        .form-group input { 
            width: 100%; 
            padding: 12px; 
            background: #151515; 
            border: 1px solid #444; 
            color: white; 
            border-radius: 8px; 
            font-family: 'Poppins', sans-serif;
        }

        .form-group input[readonly] { background: #0f0f0f; color: #555; border-color: #222; cursor: not-allowed; }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            max-width: 1100px;
            border-left: 4px solid;
        }
        .alert.success { background: rgba(62, 189, 96, 0.1); color: #3ebd60; border-color: #3ebd60; }
        .alert.error { background: rgba(255, 77, 77, 0.1); color: #ff4d4d; border-color: #ff4d4d; }

        @media (max-width: 1000px) { .profile-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo">
                <img src="src/logos/Logo.png" alt="FluxoCars Logo">
            </div>
            <ul class="sidebar-menu">
                <li>
                    <a href="index.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        Volver a la Tienda
                    </a>
                </li>
                <li>
                    <a href="perfil.php" class="active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        Mi Perfil
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <h1 class="header-title">Configuración de Perfil</h1>

            <?php if($mensaje): ?>
                <div class="alert <?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="profile-grid">
                <div class="card">
                    <h3>Información Personal</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input type="text" name="nombre" value="<?php echo htmlspecialchars($user['nombre']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Apellido</label>
                            <input type="text" name="apellido" value="<?php echo htmlspecialchars($user['apellido']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" value="<?php echo htmlspecialchars($user['telefono']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Ciudad</label>
                            <input type="text" name="ciudad" value="<?php echo htmlspecialchars($user['ciudad']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn-primary" style="width: 100%; margin-top: 10px;">Actualizar Datos</button>
                    </form>
                </div>

                <div class="card">
                    <h3>Seguridad y Contraseña</h3>
                    <form method="POST">
                        <div class="form-group">
                            <label>Contraseña Actual</label>
                            <input type="password" name="current_password" required>
                        </div>
                        <div style="height: 1px; background: #333; margin: 25px 0;"></div>
                        <div class="form-group">
                            <label>Nueva Contraseña</label>
                            <input type="password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label>Confirmar Nueva Contraseña</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn-primary" style="width: 100%; margin-top: 10px; background: var(--fluxo-gradient-hover);">Cambiar Contraseña</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

</body>
</html>