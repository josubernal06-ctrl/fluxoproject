<?php
include 'admin_auth.php';
include 'database.php';

if ($_SESSION['user_rol'] !== 'sup-admin') {
    header("Location: panel_admin.php"); 
    exit();
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_admin'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO usuarios (nombre, apellido, email, password, rol) VALUES (?, ?, ?, ?, 'admin')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $nombre, $apellido, $email, $pass);
    
    if ($stmt->execute()) {
        $mensaje = "✅ Administrador creado con éxito.";
    } else {
        $mensaje = "❌ Error: El correo ya podría estar en uso.";
    }
}

$admins = $conn->query("SELECT id, nombre, apellido, email, telefono, ciudad FROM usuarios WHERE rol = 'admin'");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Admins | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css">
    <link rel="icon" type="image/png" href="src/logos/mini-logo.jpg">
    <style>
        .admin-container { max-width: 1000px; margin: 100px auto; padding: 20px; }
        .card { background: #2a2a2a; padding: 25px; border-radius: 12px; border: 1px solid #333; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #00d2ff; }
        .btn-delete { background: none; border: none; color: #ff4d4d; cursor: pointer; transition: 0.3s; }
        .btn-delete:hover { color: #ff7373; transform: scale(1.1); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .form-grid input { padding: 10px; background: #1e1e1e; border: 1px solid #444; color: white; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="admin-container">
        <h1>Panel de Control: Super Admin</h1>
        
        <?php if($mensaje): ?> <p style="color: #3ebd60;"><?php echo $mensaje; ?></p> <?php endif; ?>

        <div class="card">
            <h3>Agregar Nuevo Administrador</h3>
            <form method="POST" class="form-grid">
                <input type="text" name="nombre" placeholder="Nombre" required>
                <input type="text" name="apellido" placeholder="Apellido" required>
                <input type="email" name="email" placeholder="Correo" required>
                <input type="password" name="password" placeholder="Contraseña Temporal" required>
                <button type="submit" name="agregar_admin" class="btn-primary" style="grid-column: span 2;">Crear Cuenta Administrativa</button>
            </form>
        </div>

        <div class="card">
            <h3>Administradores Actuales</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Ciudad</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $admins->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['nombre'] . " " . $row['apellido']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['ciudad'] ?: 'No definida'; ?></td>
                        <td>
                            <form action="eliminar_admin.php" method="POST" onsubmit="return confirm('¿Seguro que quieres eliminar a este admin?');">
                                <input type="hidden" name="id_admin" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn-delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>