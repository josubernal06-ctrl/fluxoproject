<?php
// admin_catalogo.php
include 'admin_auth.php';
include 'database.php';

$mensaje = "";

// OBTENER DATOS PARA LOS SELECTORES
$marcas = $conn->query("SELECT id, nombre FROM marcas ORDER BY nombre");
$categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");

// --- LÓGICA DE SUBIDA MANUAL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subida_manual'])) {
    $marca_id = (int)$_POST['marca_id'];
    $categoria_id = (int)$_POST['categoria_id'];
    $modelo = trim($_POST['modelo']);
    $anio = (int)$_POST['anio'];
    $precio = (float)$_POST['precio'];
    $transmision = $_POST['transmision'];
    $combustible = $_POST['combustible'];
    $descripcion = trim($_POST['descripcion']);
    
    $imagen_principal = "placeholder_car.png";
    $pdf_ficha_url = NULL; 

    // 1. PROCESAR IMAGEN
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
        $img_ext = strtolower(pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION));
        if (in_array($img_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nuevo_nombre_img = uniqid('auto_') . '.' . $img_ext;
            if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], 'uploads/autos_images/' . $nuevo_nombre_img)) {
                $imagen_principal = $nuevo_nombre_img;
            }
        }
    }

    // 2. PROCESAR PDF
    if (isset($_FILES['pdf_ficha']) && $_FILES['pdf_ficha']['error'] === UPLOAD_ERR_OK) {
        $pdf_ext = strtolower(pathinfo($_FILES['pdf_ficha']['name'], PATHINFO_EXTENSION));
        if ($pdf_ext === 'pdf') {
            $nuevo_nombre_pdf = uniqid('ficha_') . '.pdf';
            if (move_uploaded_file($_FILES['pdf_ficha']['tmp_name'], 'uploads/autos_pdfs/' . $nuevo_nombre_pdf)) {
                $pdf_ficha_url = $nuevo_nombre_pdf;
            }
        }
    }

    $sql = "INSERT INTO autos (marca_id, categoria_id, modelo, anio, precio, transmision, combustible, descripcion, imagen_principal, pdf_ficha_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisidsssss", $marca_id, $categoria_id, $modelo, $anio, $precio, $transmision, $combustible, $descripcion, $imagen_principal, $pdf_ficha_url);
    
    if ($stmt->execute()) {
        $mensaje = "<div class='alert success'>✅ Vehículo guardado correctamente.</div>";
    } else {
        $mensaje = "<div class='alert error'>❌ Error: " . $stmt->error . "</div>";
    }
}

// OBTENER LA LISTA DE AUTOS PARA LA TABLA DEL INVENTARIO
$query_autos = "SELECT a.id, m.nombre AS marca, c.nombre AS categoria, a.modelo, a.anio, a.precio, a.estado 
                FROM autos a 
                JOIN marcas m ON a.marca_id = m.id 
                JOIN categorias c ON a.categoria_id = c.id 
                ORDER BY a.id DESC";
$lista_autos = $conn->query($query_autos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css">
    <link rel="icon" type="image/png" href="favicon.png">
    <style>
        body { margin: 0; background-color: var(--bg-dark); overflow: hidden; }
        .dashboard-layout { display: grid; grid-template-columns: 260px 1fr; height: 100vh; }
        .sidebar { background-color: #151515; border-right: 1px solid #333; padding: 30px 20px; display: flex; flex-direction: column; }
        .sidebar-logo { text-align: center; margin-bottom: 50px; }
        .sidebar-logo img { width: 100%; max-width: 180px; }
        .sidebar-menu { list-style: none; padding: 0; display: flex; flex-direction: column; gap: 10px; }
        .sidebar-menu a { display: flex; align-items: center; gap: 12px; padding: 12px 15px; color: #aaa; text-decoration: none; border-radius: 8px; transition: 0.3s; font-weight: 500; }
        .sidebar-menu a:hover { background-color: #2a2a2a; color: #00d2ff; }
        .sidebar-menu a.active { background: var(--fluxo-gradient); color: #fff; }
        .main-content { padding: 40px 60px; overflow-y: auto; background-color: #1e1e1e; }
        .header-title { margin-bottom: 30px; font-size: 2rem; color: #fff; }
        .card { background: #2a2a2a; padding: 30px; border-radius: 12px; border: 1px solid #333; margin-bottom: 30px; }
        
        /* ESTILOS DEL ACORDEÓN (DESPLEGABLES) */
        .accordion {
            background-color: #1a1a1a; color: #fff; cursor: pointer; padding: 18px 20px; width: 100%;
            border: 1px solid #333; border-radius: 8px; text-align: left; outline: none; font-size: 1.1rem;
            transition: 0.3s; display: flex; justify-content: space-between; align-items: center;
        }
        .accordion.active, .accordion:hover { border-color: #00d2ff; background-color: #222; }
        .accordion:after { content: '\25BC'; font-size: 0.8rem; color: #00d2ff; transition: 0.3s; }
        .accordion.active:after { transform: rotate(-180deg); }
        .panel { padding: 0 20px; background-color: #2a2a2a; max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; border-left: 2px solid #00d2ff; margin-bottom: 15px; }
        
       /* SUBA-ACORDEONES */
        .sub-accordion { background-color: #222; font-size: 1rem; margin-top: 15px; border-left: 2px solid #3ebd60; }
        .sub-panel { background-color: #1a1a1a; padding: 0 15px; /* Aquí quitamos el padding vertical */ max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; margin-bottom: 15px; }
        .sub-panel-inner { padding: 15px 0; /* Este nuevo div interno maneja el espacio */ }

        /* FORMULARIOS Y BOTONES FILE */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px; }
        .form-group label { display: block; margin-bottom: 8px; color: #aaa; font-size: 0.85rem; }
        .form-group select, .form-group input, .form-group textarea { width: 100%; padding: 10px; background: #111; border: 1px solid #444; color: white; border-radius: 6px; }
        
        input[type="file"] { color: #888; background: #111; padding: 8px; border: 1px dashed #444; border-radius: 6px; width: 100%; }
        input[type="file"]::file-selector-button { background: #2a2a2a; color: #00d2ff; border: 1px solid #00d2ff; padding: 6px 12px; border-radius: 4px; margin-right: 10px; cursor: pointer; transition: 0.3s; }
        input[type="file"]::file-selector-button:hover { background: #00d2ff; color: #000; }

        /* TABLA DE INVENTARIO */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.95rem; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #00d2ff; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #1a1a1a; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .badge.disp { background: rgba(62, 189, 96, 0.1); color: #3ebd60; }
        .badge.trans { background: rgba(0, 210, 255, 0.1); color: #00d2ff; }
        
        /* BOTONES DE ACCIÓN */
        .action-btns { display: flex; gap: 10px; }
        .btn-icon { background: none; border: none; cursor: pointer; padding: 5px; border-radius: 4px; transition: 0.3s; }
        .btn-edit { color: #aaa; } .btn-edit:hover { color: #fff; background: #333; }
        .btn-del { color: #ff4d4d; } .btn-del:hover { color: #fff; background: #ff4d4d; }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-logo"><img src="src/logos/Logo.png" alt="FluxoCars Logo"></div>
            <ul class="sidebar-menu">
                <li><a href="index.php">Volver a la Tienda</a></li>
                <li><a href="catalogo.php">Catálogo</a></li>
                <li><a href="admin_inventario.php" class="active">Inventario</a></li>
                <li><a href="#contacto">Cotizaciones</a></li>
                <li><a href="#nosotros">Nosotros</a></li>
                <li><a href="#contacto">Contáctanos</a></li>
            </ul>
        </aside>
        
        <main class="main-content">
            <h1 class="header-title">Inventario de Vehículos</h1>
            <?php if($mensaje) echo $mensaje; ?>

            <button class="accordion">➕ Agregar Nuevos Vehículos al Inventario</button>
            <div class="panel" id="panel-principal">
                
                <button class="accordion sub-accordion">Opción 1: Carga Masiva (Excel / CSV)</button>
                <div class="sub-panel">
                    <div class="sub-panel-inner">
                        <p style="color:#aaa; font-size: 0.9rem; margin-bottom: 15px;">Sube un archivo .csv con el formato correcto. Las imágenes se añaden después.</p>
                        <img src="src/tutoriales/ejemplo_excel.png" alt="Ejemplo" style="width:100%; border:1px solid #444; border-radius:6px; margin-bottom:15px; max-width: 400px; display: block;">
                        <form action="importar_catalogo.php" method="POST" enctype="multipart/form-data" style="display:flex; gap:10px;">
                            <input type="file" name="archivo_csv" accept=".csv" required>
                            <button type="submit" class="btn-primary">Importar</button>
                        </form>
                    </div>
                </div>

                <button class="accordion sub-accordion">Opción 2: Carga Manual Individual</button>
                <div class="sub-panel">
                    <div class="sub-panel-inner">
                        <form method="POST" enctype="multipart/form-data" class="form-grid">
                            <div class="form-group">
                                <label>Marca</label>
                                <select name="marca_id" required>
                                    <option value="">Seleccionar...</option>
                                    <?php while($m = $marcas->fetch_assoc()) echo "<option value='{$m['id']}'>{$m['nombre']}</option>"; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Categoría</label>
                                <select name="categoria_id" required>
                                    <option value="">Seleccionar...</option>
                                    <?php mysqli_data_seek($categorias, 0); while($c = $categorias->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Modelo</label><input type="text" name="modelo" required></div>
                            <div class="form-group"><label>Año</label><input type="number" name="anio" required></div>
                            <div class="form-group"><label>Precio ($US)</label><input type="number" step="0.01" name="precio" required></div>
                            <div class="form-group">
                                <label>Transmisión</label>
                                <select name="transmision" required><option value="Manual">Manual</option><option value="Automatica">Automática</option></select>
                            </div>
                            <div class="form-group">
                                <label>Combustible</label>
                                <select name="combustible" required><option value="Gasolina">Gasolina</option><option value="Diesel">Diésel</option><option value="Hibrido">Híbrido</option><option value="Electrico">Eléctrico</option></select>
                            </div>
                            <div class="form-group" style="grid-column: span 2;"><label>Descripción</label><textarea name="descripcion" rows="2"></textarea></div>
                            <div class="form-group"><label>Foto Principal (Opcional)</label><input type="file" name="imagen_principal" accept="image/*"></div>
                            <div class="form-group"><label>Ficha PDF (Opcional)</label><input type="file" name="pdf_ficha" accept=".pdf"></div>
                            <button type="submit" name="subida_manual" class="btn-primary" style="grid-column: span 2; margin-top:10px;">Guardar Vehículo</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card" style="margin-top: 30px;">
                <h3 style="color: #fff; border: none; padding: 0; margin-bottom: 20px;">Listado de Vehículos</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Marca / Modelo</th>
                                <th>Cat.</th>
                                <th>Año</th>
                                <th>Precio</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($lista_autos && $lista_autos->num_rows > 0): ?>
                                <?php while($auto = $lista_autos->fetch_assoc()): 
                                    $clase_estado = ($auto['estado'] == 'Disponible') ? 'disp' : 'trans';
                                ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($auto['marca'] . " " . $auto['modelo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($auto['categoria']); ?></td>
                                    <td><?php echo $auto['anio']; ?></td>
                                    <td>$<?php echo number_format($auto['precio'], 2); ?></td>
                                    <td><span class="badge <?php echo $clase_estado; ?>"><?php echo $auto['estado']; ?></span></td>
                                    <td class="action-btns">
                                        <a href="editar_auto.php?id=<?php echo $auto['id']; ?>" class="btn-icon btn-edit" title="Editar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                        </a>
                                        <a href="eliminar_auto.php?id=<?php echo $auto['id']; ?>" onclick="return confirm('¿Seguro que deseas eliminar este auto del inventario?');" class="btn-icon btn-del" title="Eliminar">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" style="text-align:center; color:#888;">El inventario está vacío.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var accordions = document.querySelectorAll(".accordion");
            
            accordions.forEach(function(acc) {
                acc.addEventListener("click", function() {
                    this.classList.toggle("active");
                    var panel = this.nextElementSibling;
                    
                    if (panel.style.maxHeight) {
                        panel.style.maxHeight = null;
                    } else {
                        // Calcula el tamaño necesario
                        panel.style.maxHeight = panel.scrollHeight + "px";
                        
                        // Si es un sub-acordeón, asegúrate de expandir el padre para que quepa
                        var parentPanel = this.closest('.panel#panel-principal');
                        if(parentPanel && parentPanel !== panel) {
                            parentPanel.style.maxHeight = (parentPanel.scrollHeight + panel.scrollHeight + 50) + "px";
                        }
                    } 
                });
            });
        });
    </script>
</body>
</html>