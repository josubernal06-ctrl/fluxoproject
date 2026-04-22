<?php
include 'admin_auth.php';
include 'database.php';

// Verificación de seguridad para tu rango 'sup-admin'
if ($_SESSION['user_rol'] !== 'sup-admin') {
    header("Location: panel_admin.php");
    exit();
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agregar_admin'])) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $telefono = $_POST['telefono'];
    $ciudad = $_POST['ciudad'];
    $email = $_POST['email'];
    $pass = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    $sql = "INSERT INTO usuarios (nombre, apellido, telefono, ciudad, email, password, rol) VALUES (?, ?, ?, ?, ?, ?, 'admin')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $nombre, $apellido, $telefono, $ciudad, $email, $pass);
    
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
    <title>Dashboard | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css"> 
    <link rel="icon" type="image/png" href="src/logos/mini-logo.jpg">
    <style>
        body { margin: 0; background-color: var(--bg-dark); overflow-x: hidden; }
        .dashboard-layout { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        
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
        .sidebar-menu a.active { background: var(--fluxo-gradient); color: #fff; box-shadow: 0 4px 15px rgba(0, 210, 255, 0.2); }
        
        .main-content { padding: 40px 50px; background-color: #1e1e1e; overflow-y: auto; }
        .header-title { margin-bottom: 40px; font-size: 2.2rem; color: #fff; }
        
        .card { 
            background: #2a2a2a; 
            padding: 30px; 
            border-radius: 12px; 
            border: 1px solid #333; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.2); 
        }
        
        .card h3 { margin-bottom: 25px; color: #00d2ff; border-bottom: 1px solid #333; padding-bottom: 15px; font-size: 1.3rem; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-grid input { 
            padding: 14px; 
            background: #151515; 
            border: 1px solid #444; 
            color: white; 
            border-radius: 8px; 
            font-family: 'Poppins', sans-serif; 
        }

        .admin-list { display: flex; flex-direction: column; gap: 15px; }
        
        /* Modificado para quitar la expansión (transform) */
        .admin-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            background: #1a1a1a; 
            padding: 15px 25px; 
            border-radius: 8px; 
            border: 1px solid #333; 
            transition: border-color 0.3s ease; 
        }
        
        .admin-item:hover { border-color: #00d2ff; }

        .admin-info h4 { margin: 0 0 5px 0; color: #fff; font-size: 1.1rem; }
        .admin-info p { margin: 0; color: #888; font-size: 0.9rem; }
        
        /* Botón de eliminar con estilo vectorial */
        .btn-delete { 
            background: rgba(255, 77, 77, 0.1); 
            border: 1px solid #ff4d4d; 
            color: #ff4d4d; 
            padding: 10px; 
            border-radius: 8px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
        }
        
        .btn-delete:hover { background: #ff4d4d; color: #fff; }
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
                    <a href="gestion_admins.php" class="active">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        Gestión de Admins
                    </a>
                </li>
            </ul>
        </aside>

        <main class="main-content">
            <h1 class="header-title">Panel de Control: <?php echo $_SESSION['user_name']; ?></h1>
            
            <?php if($mensaje): ?> 
                <div style="background: rgba(62, 189, 96, 0.1); border-left: 4px solid #3ebd60; padding: 15px; border-radius: 4px; margin-bottom: 25px; color: #3ebd60;">
                    <?php echo $mensaje; ?>
                </div> 
            <?php endif; ?>

            <div class="card">
                <h3>+ Agregar Nuevo Administrador</h3>
                <form method="POST" class="form-grid">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                    <input type="text" name="apellido" placeholder="Apellido" required>
                    <input type="text" name="telefono" placeholder="Teléfono" required>
                    <input type="text" name="ciudad" placeholder="Ciudad" required>
                    <input type="email" name="email" placeholder="Correo Electrónico" required>
                    <input type="password" name="password" placeholder="Contraseña Temporal" required>
                    <button type="submit" name="agregar_admin" class="btn-primary" style="grid-column: span 2;">Crear Cuenta Administrativa</button>
                </form>
            </div>

            <div class="card">
                <h3>Administradores Actuales</h3>
                <div class="admin-list">
                    <?php while($row = $admins->fetch_assoc()): ?>
                    <div class="admin-item">
                        <div class="admin-info">
                            <h4><?php echo htmlspecialchars($row['nombre'] . " " . $row['apellido']); ?></h4>
                            <p><?php echo htmlspecialchars($row['email']); ?> &nbsp;•&nbsp; <?php echo htmlspecialchars($row['telefono']); ?> &nbsp;•&nbsp; <?php echo htmlspecialchars($row['ciudad']); ?></p>
                        </div>
                        <form action="eliminar_admin.php" method="POST" onsubmit="return confirm('¿Seguro que quieres revocar el acceso a este administrador?');">
                            <input type="hidden" name="id_admin" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="btn-delete" title="Eliminar Admin">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                            </button>
                        </form>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>