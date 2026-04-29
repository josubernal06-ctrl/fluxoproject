<?php
include 'admin_auth.php';
include 'database.php';

$mensaje = "";
$resumen = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_csv'])) {
    
    $archivo_tmp = $_FILES['archivo_csv']['tmp_name'];
    $nombre_archivo = $_FILES['archivo_csv']['name'];
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

    if ($extension !== 'csv') {
        $mensaje = "<div class='alert error'>❌ Formato incorrecto. Por favor, sube un archivo con extensión .csv (valores separados por comas).</div>";
    } else {
        $file = fopen($archivo_tmp, 'r');
        $fila = 0;
        $autos_insertados = 0;
        $marcas_creadas = 0;
        $categorias_creadas = 0;

        while (($datos = fgetcsv($file, 1000, ",")) !== FALSE) {
            $fila++;
            if ($fila === 1) continue; // Saltar cabeceras

            // Limpieza de datos
            $marca_nombre    = trim($datos[0]); 
            $categoria_nombre= trim($datos[1]);
            $modelo          = trim($datos[2]);
            $anio            = (int)trim($datos[3]);
            $precio          = (float)str_replace(['$', ','], '', trim($datos[4]));
            $transmision     = trim($datos[5]);
            $combustible     = trim($datos[6]);
            
            // Validar que la fila no esté vacía por accidente
            if(empty($marca_nombre) || empty($modelo)) continue;

            // 1. GESTIÓN DE MARCA
            $stmt_marca = $conn->prepare("SELECT id FROM marcas WHERE nombre = ?");
            $stmt_marca->bind_param("s", $marca_nombre);
            $stmt_marca->execute();
            $res_marca = $stmt_marca->get_result();
            if ($res_marca->num_rows > 0) {
                $marca_id = $res_marca->fetch_assoc()['id'];
            } else {
                $ins_marca = $conn->prepare("INSERT INTO marcas (nombre) VALUES (?)");
                $ins_marca->bind_param("s", $marca_nombre);
                $ins_marca->execute();
                $marca_id = $ins_marca->insert_id;
                $marcas_creadas++;
            }

            // 2. GESTIÓN DE CATEGORÍA
            $stmt_cat = $conn->prepare("SELECT id FROM categorias WHERE nombre = ?");
            $stmt_cat->bind_param("s", $categoria_nombre);
            $stmt_cat->execute();
            $res_cat = $stmt_cat->get_result();
            if ($res_cat->num_rows > 0) {
                $categoria_id = $res_cat->fetch_assoc()['id'];
            } else {
                $ins_cat = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                $ins_cat->bind_param("s", $categoria_nombre);
                $ins_cat->execute();
                $categoria_id = $ins_cat->insert_id;
                $categorias_creadas++;
            }

            // 3. INSERTAR AUTO (En tránsito, sin foto ni PDF)
            $estado = "En transito";
            $sql_auto = "INSERT INTO autos (marca_id, categoria_id, modelo, anio, precio, transmision, combustible, estado) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_auto = $conn->prepare($sql_auto);
            $stmt_auto->bind_param("iisidsss", $marca_id, $categoria_id, $modelo, $anio, $precio, $transmision, $combustible, $estado);
            
            if ($stmt_auto->execute()) {
                $autos_insertados++;
            }
        }
        fclose($file);
        
        $mensaje = "<div class='alert success'>✅ ¡Importación Finalizada!</div>";
        $resumen = "<div class='card' style='margin-top: 20px;'>
                        <h3>Resumen del Proceso</h3>
                        <ul>
                            <li>Autos importados: <strong>$autos_insertados</strong></li>
                            <li>Marcas nuevas creadas: <strong>$marcas_creadas</strong></li>
                            <li>Categorías nuevas creadas: <strong>$categorias_creadas</strong></li>
                        </ul>
                        <a href='admin_catalogo.php' class='btn-primary' style='display:inline-block; margin-top:15px;'>Volver al Catálogo</a>
                    </div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Procesador CSV | FluxoCars</title>
    <link rel="stylesheet" href="styles1.css">
</head>
<body style="background-color: var(--bg-dark); padding: 50px; color: white; display: flex; justify-content: center; align-items: center; min-height: 100vh;">
    <div style="max-width: 600px; width: 100%;">
        <?php echo $mensaje; ?>
        <?php echo $resumen; ?>
        
        <?php if(empty($resumen)): ?>
        <div class="card">
            <h2 style="color: #00d2ff; margin-bottom: 20px;">Subir Catálogo CSV</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="archivo_csv" accept=".csv" required style="margin-bottom: 20px; width: 100%; padding: 15px; background: #1a1a1a; border: 1px dashed #444; border-radius: 8px;">
                <button type="submit" class="btn-primary" style="width: 100%;">Procesar e Importar</button>
            </form>
            <a href="admin_catalogo.php" style="display: block; text-align: center; margin-top: 20px; color: #888;">Cancelar y volver</a>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>