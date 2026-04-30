<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'admin_auth.php';
include 'database.php';

$mensaje = "";

// MANEJO DE ALERTAS MODERNAS
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'exito_masivo') {
        $mensaje = "<div class='alert success'>✅ Vehículos actualizados correctamente.</div>";
    } elseif ($_GET['msg'] === 'exito_excel') {
        $mensaje = "<div class='alert success'>✅ Catálogo importado con éxito.</div>";
    } elseif ($_GET['msg'] === 'error_excel') {
        $mensaje = "<div class='alert error'>❌ Hubo un error al leer el Excel.</div>";
    } elseif ($_GET['msg'] === 'exito_manual') {
        $mensaje = "<div class='alert success'>✅ Vehículo guardado correctamente.</div>";
    } elseif ($_GET['msg'] === 'error_manual') {
        $mensaje = "<div class='alert error'>❌ Hubo un error al guardar el vehículo.</div>";
    }
}

// --- LÓGICA DE SUBIDA MANUAL ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['subida_manual'])) {
    
    // --- LÓGICA PARA MARCA NUEVA ---
    if (isset($_POST['marca_id']) && $_POST['marca_id'] === 'nueva' && !empty($_POST['nueva_marca'])) {
        $nueva_marca = trim($_POST['nueva_marca']);
        
        $stmt_m = $conn->prepare("SELECT id FROM marcas WHERE UPPER(nombre) = UPPER(?) LIMIT 1");
        $stmt_m->bind_param("s", $nueva_marca);
        $stmt_m->execute();
        $res_m = $stmt_m->get_result();
        
        if ($row_m = $res_m->fetch_assoc()) {
            $marca_id = $row_m['id']; 
        } else {
            $ins_m = $conn->prepare("INSERT INTO marcas (nombre) VALUES (?)");
            $ins_m->bind_param("s", $nueva_marca);
            $ins_m->execute();
            $marca_id = $ins_m->insert_id;
        }
    } else {
        $marca_id = (int)$_POST['marca_id'];
    }

    $categoria_id = (int)$_POST['categoria_id'];
    $modelo = trim($_POST['modelo']);
    $motor_autonomia = trim($_POST['motor_autonomia'] ?? '');
    $entrega_dias = (int)($_POST['entrega_dias'] ?? 0);
    $precio = (float)$_POST['precio'];
    $combustible = $_POST['combustible'];
    $descripcion = trim($_POST['descripcion']);
    $estado = "En transito"; 
    $imagen_principal = "placeholder_car.png";
    $pdf_ficha_url = NULL; 

    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
        $img_ext = strtolower(pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION));
        if (in_array($img_ext, ['jpg', 'jpeg', 'png', 'webp'])) {
            $nuevo_nombre_img = uniqid('auto_') . '.' . $img_ext;
            if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], 'uploads/autos_images/' . $nuevo_nombre_img)) {
                $imagen_principal = $nuevo_nombre_img;
            }
        }
    }

    if (isset($_FILES['pdf_ficha']) && $_FILES['pdf_ficha']['error'] === UPLOAD_ERR_OK) {
        $pdf_ext = strtolower(pathinfo($_FILES['pdf_ficha']['name'], PATHINFO_EXTENSION));
        if ($pdf_ext === 'pdf') {
            $nuevo_nombre_pdf = uniqid('ficha_') . '.pdf';
            if (move_uploaded_file($_FILES['pdf_ficha']['tmp_name'], 'uploads/autos_pdfs/' . $nuevo_nombre_pdf)) {
                $pdf_ficha_url = $nuevo_nombre_pdf;
            }
        }
    }

    $sql = "INSERT INTO autos (marca_id, categoria_id, modelo, motor_autonomia, precio, combustible, entrega_dias, estado, descripcion, imagen_principal, pdf_ficha_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdsissss", $marca_id, $categoria_id, $modelo, $motor_autonomia, $precio, $combustible, $entrega_dias, $estado, $descripcion, $imagen_principal, $pdf_ficha_url);
    
    // REDIRECCIÓN LIMPIA EN VEZ DE MOSTRAR EL MENSAJE DIRECTO
    if ($stmt->execute()) {
        header("Location: admin_inventario.php?msg=exito_manual");
        exit;
    } else {
        header("Location: admin_inventario.php?msg=error_manual");
        exit;
    }
}

// OBTENER DATOS PARA LOS SELECTORES 
$marcas = $conn->query("SELECT id, nombre FROM marcas ORDER BY nombre");
$categorias = $conn->query("SELECT id, nombre FROM categorias ORDER BY nombre");

// --- LÓGICA DE BUSCADOR Y PAGINACIÓN ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10; 
$offset = ($page - 1) * $limit;

$where_sql = "";
if (!empty($search)) {
    $search_esc = $conn->real_escape_string($search);
    $where_sql = " WHERE a.modelo LIKE '%$search_esc%' OR m.nombre LIKE '%$search_esc%' ";
}

$count_query = "SELECT COUNT(a.id) as total FROM autos a LEFT JOIN marcas m ON a.marca_id = m.id $where_sql";
$total_result = $conn->query($count_query);
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

$query_autos = "SELECT a.id, m.nombre AS marca, c.nombre AS categoria, a.modelo, a.motor_autonomia, a.precio, a.estado 
                FROM autos a 
                LEFT JOIN marcas m ON a.marca_id = m.id 
                LEFT JOIN categorias c ON a.categoria_id = c.id 
                $where_sql 
                ORDER BY a.id DESC 
                LIMIT $limit OFFSET $offset";
$lista_autos = $conn->query($query_autos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css">
    <link rel="icon" type="image/png" href="src/logos/mini-logo.png">
    
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
        
        .accordion { background-color: #1a1a1a; color: #fff; cursor: pointer; padding: 18px 20px; width: 100%; border: 1px solid #333; border-radius: 8px; text-align: left; outline: none; font-size: 1.1rem; transition: 0.3s; display: flex; justify-content: space-between; align-items: center; }
        .accordion.active, .accordion:hover { border-color: #00d2ff; background-color: #222; }
        .accordion:after { content: '\25BC'; font-size: 0.8rem; color: #00d2ff; transition: 0.3s; }
        .accordion.active:after { transform: rotate(-180deg); }
        .panel { padding: 0 20px; background-color: #2a2a2a; max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; border-left: 2px solid #00d2ff; margin-bottom: 15px; }
        
        .sub-accordion { background-color: #222; font-size: 1rem; margin-top: 15px; border-left: 2px solid #3ebd60; }
        .sub-panel { background-color: #1a1a1a; padding: 0 15px; max-height: 0; overflow: hidden; transition: max-height 0.4s ease-out; margin-bottom: 15px; }
        .sub-panel-inner { padding: 15px 0; }
        .excel-preview-img { width: 100%; max-width: 900px; border: 2px solid #333; border-radius: 8px; margin-bottom: 25px; display: block; box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4); background-color: #151515; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #e0e0e0; font-size: 0.95rem; font-weight: 500; letter-spacing: 0.3px; } 
        .form-group select, .form-group input, .form-group textarea { width: 100%; padding: 12px; background: #151515; border: 1px solid #444; color: white; border-radius: 6px; font-size: 1rem; transition: 0.3s; }
        .form-group select:focus, .form-group input:focus, .form-group textarea:focus { border-color: #00d2ff; outline: none; box-shadow: 0 0 8px rgba(0, 210, 255, 0.2); background: #1a1a1a; } 
        .form-group input::placeholder, .form-group textarea::placeholder { color: #666; font-style: italic; }
        
        input[type="file"] { color: #aaa; background: #151515; padding: 10px; border: 1px dashed #555; border-radius: 6px; width: 100%; }
        input[type="file"]::file-selector-button { background: #2a2a2a; color: #00d2ff; border: 1px solid #00d2ff; padding: 8px 15px; border-radius: 4px; margin-right: 15px; cursor: pointer; transition: 0.3s; font-weight: bold; }
        input[type="file"]::file-selector-button:hover { background: #00d2ff; color: #000; }
        
        .btn-primary { background: linear-gradient(90deg, #00d2ff 0%, #3a7bd5 100%); border: none; color: white; padding: 12px 25px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: 0.3s; font-size: 1.05rem; }
        .btn-primary:hover { opacity: 0.9; transform: scale(1.02); }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 0.95rem; color: #fff; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #333; }
        th { color: #00d2ff; text-transform: uppercase; font-size: 0.85rem; }
        tr:hover { background-color: #1a1a1a; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; font-weight: bold; }
        .badge.disp { background: rgba(62, 189, 96, 0.1); color: #3ebd60; }
        .badge.trans { background: rgba(0, 210, 255, 0.1); color: #00d2ff; }
        
        .action-btns { display: flex; gap: 10px; align-items: center; }
        .btn-icon { background: none; border: none; cursor: pointer; padding: 5px; border-radius: 4px; transition: 0.3s; display: flex; align-items: center; }
        .btn-edit { color: #aaa; } .btn-edit:hover { color: #fff; background: #333; }
        .btn-del { color: #ff4d4d; } .btn-del:hover { color: #fff; background: #ff4d4d; }

        .alert { padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-weight: bold; border-left: 5px solid; }
        .alert.success { background-color: rgba(62, 189, 96, 0.1); color: #3ebd60; border-color: #3ebd60; }
        .alert.error { background-color: rgba(255, 77, 77, 0.1); color: #ff4d4d; border-color: #ff4d4d; }

        input.check-item { width: 18px; height: 18px; cursor: pointer; accent-color: #00d2ff; }
        .barra-acciones { background-color: #1a1a1a; border-left: 4px solid #00d2ff; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        .barra-acciones.oculta { display: none; }
        .barra-acciones select { padding: 8px 12px; background: #222; color: white; border: 1px solid #444; border-radius: 6px; margin-right: 10px; outline: none; font-size: 0.9rem; }
        .btn-masivo { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.9rem; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-masivo.aplicar { background-color: #00d2ff; color: #000; margin-right: 10px; }
        .btn-masivo.aplicar:hover { background-color: #00b8e6; }
        .btn-masivo.eliminar { background-color: #ff4d4d; color: #fff; }
        .btn-masivo.eliminar:hover { background-color: #e60000; }
        .btn-masivo.seleccionar-todos { background: #333; color: #fff; margin-left: 15px; border: 1px solid #444; }

        .search-bar { display: flex; gap: 10px; margin-bottom: 20px; }
        .search-bar input { flex: 1; max-width: 400px; padding: 10px 15px; border-radius: 6px; border: 1px solid #444; background: #111; color: #fff; }
        .btn-masivo.limpiar { background-color: #444; color: #fff; }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 25px; }
        .page-link { padding: 8px 14px; background: #222; color: #fff; text-decoration: none; border-radius: 4px; border: 1px solid #333; transition: 0.3s; }
        .page-link:hover, .page-link.active { background: #00d2ff; color: #000; border-color: #00d2ff; font-weight: bold; }
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
            <?php if(!empty($mensaje)) echo $mensaje; ?>

            <button class="accordion">Agregar Nuevos Vehículos al Inventario</button>
            <div class="panel" id="panel-principal">
                
                <button class="accordion sub-accordion">Opción 1: Carga Masiva (Excel / CSV)</button>
                <div class="sub-panel">
                    <div class="sub-panel-inner">
                        <p style="color:#aaa; font-size: 0.9rem; margin-bottom: 20px;">Sube un archivo .csv, .xls o .xlsx con el formato oficial.</p>
                        <img src="src/tutoriales/excel.png" alt="Ejemplo Formato Excel" class="excel-preview-img">
                        <form action="importar_catalogo.php" method="POST" enctype="multipart/form-data" style="display:flex; gap:10px;">
                            <input type="file" name="archivo_excel" accept=".xlsx, .xls, .csv" required>
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
                                <select name="marca_id" id="marca-select" required>
                                    <option value="">Seleccionar...</option>
                                    <?php while($m = $marcas->fetch_assoc()) echo "<option value='{$m['id']}'>{$m['nombre']}</option>"; ?>
                                    <option value="nueva" style="font-weight: bold; color: #00d2ff;">+ Agregar Nueva Marca...</option>
                                </select>
                                <input type="text" name="nueva_marca" id="nueva-marca-input" placeholder="Escribe el nombre de la marca" style="display: none; margin-top: 10px; border-color: #00d2ff;">
                            </div>

                            <div class="form-group">
                                <label>Categoría</label>
                                <select name="categoria_id" required>
                                    <option value="">Seleccionar...</option>
                                    <?php mysqli_data_seek($categorias, 0); while($c = $categorias->fetch_assoc()) echo "<option value='{$c['id']}'>{$c['nombre']}</option>"; ?>
                                </select>
                            </div>
                            <div class="form-group"><label>Modelo</label><input type="text" name="modelo" required></div>
                            <div class="form-group"><label>Motor / Autonomía</label><input type="text" name="motor_autonomia" placeholder="Ej: 380 km / 1.5T" required></div>
                            <div class="form-group"><label>Tipo (Combustible)</label><input type="text" name="combustible" placeholder="Ej: Eléctrico, Híbrido" required></div>
                            <div class="form-group"><label>Días de Entrega (Aprox)</label><input type="number" name="entrega_dias" placeholder="Ej: 60" required></div>
                            <div class="form-group"><label>Precio ($US)</label><input type="number" step="0.01" name="precio" required></div>
                            
                            <div class="form-group" style="grid-column: span 2;"><label>Descripción / Observaciones</label><textarea name="descripcion" rows="3"></textarea></div>
                            
                            <div class="form-group"><label>Foto Principal (Opcional)</label><input type="file" name="imagen_principal" accept="image/*"></div>
                            <div class="form-group"><label>Ficha PDF (Opcional)</label><input type="file" name="pdf_ficha" accept=".pdf"></div>
                            
                            <div style="grid-column: span 2; display: flex; justify-content: flex-end; margin-top:10px;">
                                <button type="submit" name="subida_manual" class="btn-primary" style="width: 100%; max-width: 300px;">Guardar Vehículo</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="card" style="margin-top: 30px;">
                <h3 style="color: #fff; border: none; padding: 0; margin-bottom: 20px;">Listado de Vehículos</h3>
                
                <form method="GET" class="search-bar">
                    <input type="text" name="search" placeholder="Buscar por marca o modelo..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn-masivo aplicar">Buscar</button>
                    <?php if(!empty($search)): ?>
                        <a href="admin_inventario.php" class="btn-masivo limpiar">Limpiar</a>
                    <?php endif; ?>
                </form>

                <form action="acciones_masivas.php" method="POST" id="form-masivo">
                    <div id="barra-acciones" class="barra-acciones oculta">
                        <div>
                            <span id="contador-seleccionados" style="color: #fff; font-weight: bold;">0 seleccionados</span>
                            <button type="button" id="btn-select-all" class="btn-masivo seleccionar-todos">Marcar Todos</button>
                        </div>
                        <div>
                            <select name="nuevo_estado" form="form-masivo">
                                <option value="">Cambiar estado a...</option>
                                <option value="Disponible">Disponible</option>
                                <option value="Reservado">Reservado</option>
                                <option value="Vendido">Vendido</option>
                                <option value="En transito">En transito</option>
                            </select>
                            <button type="submit" name="accion" value="estado" form="form-masivo" class="btn-masivo aplicar">Aplicar</button>
                            <button type="submit" name="accion" value="eliminar" form="form-masivo" class="btn-masivo eliminar" onclick="return confirm('¿Seguro que deseas eliminar los autos seleccionados? Esto no se puede deshacer.');">Eliminar</button>
                        </div>
                    </div>

                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 40px; text-align: center;">#</th>
                                    <th>Marca / Modelo</th>
                                    <th>Cat.</th>
                                    <th>Motor / Aut.</th>
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
                                        <td style="text-align: center;">
                                            <input type="checkbox" class="check-item" name="autos_ids[]" value="<?php echo $auto['id']; ?>">
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($auto['marca'] . " " . $auto['modelo']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($auto['categoria']); ?></td>
                                        <td><?php echo htmlspecialchars($auto['motor_autonomia']); ?></td>
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
                                    <tr><td colspan="7" style="text-align:center; color:#888;">No se encontraron vehículos.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>" 
                           class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>

    <script>
        // ELIMINAR EL "?msg=" DE LA URL DESPUÉS DE CARGAR PARA QUE F5 NO LO REPITA
        if (window.history.replaceState) {
            const url = new URL(window.location);
            if (url.searchParams.has('msg')) {
                url.searchParams.delete('msg');
                window.history.replaceState({path:url.href}, '', url.href);
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const marcaSelect = document.getElementById('marca-select');
            const nuevaMarcaInput = document.getElementById('nueva-marca-input');
            
            marcaSelect.addEventListener('change', function() {
                if (this.value === 'nueva') {
                    nuevaMarcaInput.style.display = 'block';
                    nuevaMarcaInput.required = true;
                } else {
                    nuevaMarcaInput.style.display = 'none';
                    nuevaMarcaInput.required = false;
                }
            });

            var accordions = document.querySelectorAll(".accordion");
            accordions.forEach(function(acc) {
                acc.addEventListener("click", function() {
                    this.classList.toggle("active");
                    var panel = this.nextElementSibling;
                    if (panel.style.maxHeight) {
                        panel.style.maxHeight = null;
                    } else {
                        panel.style.maxHeight = panel.scrollHeight + 150 + "px";
                        var parentPanel = this.closest('.panel#panel-principal');
                        if(parentPanel && parentPanel !== panel) {
                            parentPanel.style.maxHeight = (parentPanel.scrollHeight + panel.scrollHeight + 200) + "px";
                        }
                    } 
                });
            });

            const checkItems = document.querySelectorAll('.check-item');
            const barraAcciones = document.getElementById('barra-acciones');
            const contadorText = document.getElementById('contador-seleccionados');
            const btnSelectAll = document.getElementById('btn-select-all');
            let todosMarcadosFlag = false;

            function actualizarBarra() {
                const seleccionados = document.querySelectorAll('.check-item:checked').length;
                if (seleccionados > 0) {
                    barraAcciones.classList.remove('oculta');
                    contadorText.textContent = seleccionados + (seleccionados === 1 ? ' auto seleccionado' : ' autos seleccionados');
                    if(seleccionados === checkItems.length) {
                        todosMarcadosFlag = true;
                        if(btnSelectAll) btnSelectAll.textContent = "Desmarcar Todos";
                    } else {
                        todosMarcadosFlag = false;
                        if(btnSelectAll) btnSelectAll.textContent = "Marcar Todos";
                    }
                } else {
                    barraAcciones.classList.add('oculta');
                    todosMarcadosFlag = false;
                    if(btnSelectAll) btnSelectAll.textContent = "Marcar Todos";
                }
            }

            checkItems.forEach(item => { item.addEventListener('change', actualizarBarra); });

            if(btnSelectAll) {
                btnSelectAll.addEventListener('click', function() {
                    todosMarcadosFlag = !todosMarcadosFlag;
                    checkItems.forEach(item => item.checked = todosMarcadosFlag);
                    actualizarBarra();
                });
            }
        });
    </script>
</body>
</html>