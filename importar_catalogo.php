<?php
// importar_catalogo.php
include 'admin_auth.php';
include 'database.php';
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    
    $archivo_tmp = $_FILES['archivo_excel']['tmp_name'];
    $nombre_archivo = $_FILES['archivo_excel']['name'];
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

    if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
        // Redirigir con error si el formato es incorrecto
        header("Location: admin_inventario.php?msg=error_excel");
        exit;
    } 

    try {
        if ($extension === 'csv') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
            $reader->setDelimiter(','); 
            $reader->setInputEncoding('UTF-8');
        } elseif ($extension === 'xls') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }

        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($archivo_tmp);
        $worksheet = $spreadsheet->getActiveSheet();
        $filas = $worksheet->toArray(); 
        
        $autos_insertados = 0;
        $marcas_creadas = 0;
        $categorias_creadas = 0;

        foreach ($filas as $indice => $datos) {
            // 1. Obtener Marca
            $marca_nombre_crudo = trim($datos[0] ?? '');
            $marca_nombre = strtoupper($marca_nombre_crudo);

            if (empty($marca_nombre) || 
                strpos($marca_nombre, 'MARCA') !== false || 
                strpos($marca_nombre, 'FLUXOCARS') !== false || 
                strpos($marca_nombre, 'USO INTERNO') !== false ||
                strpos($marca_nombre, 'STOCK PREMIUM') !== false) {
                continue; 
            }

            // 2. Gestión de Marca
            $marca_id = NULL;
            $stmt_marca = $conn->prepare("SELECT id FROM marcas WHERE UPPER(nombre) = ? LIMIT 1");
            $stmt_marca->bind_param("s", $marca_nombre);
            $stmt_marca->execute();
            $res_marca = $stmt_marca->get_result();

            if ($row = $res_marca->fetch_assoc()) {
                $marca_id = $row['id'];
            } else {
                $ins_marca = $conn->prepare("INSERT INTO marcas (nombre) VALUES (?)");
                $ins_marca->bind_param("s", $marca_nombre_crudo);
                if ($ins_marca->execute()) {
                    $marca_id = $ins_marca->insert_id;
                    $marcas_creadas++;
                } else {
                    continue; 
                }
            }

            // 3. Obtener el resto de los datos
            $modelo_original = trim($datos[1] ?? ''); 
            
            if(empty($modelo_original)) {
                $modelo = "$marca_nombre - Modelo Desconocido";
            } else {
                $modelo = $modelo_original;
            }

            $categoria_nombre = trim($datos[2] ?? 'General'); 
            $combustible = trim($datos[3] ?? 'No especificado'); 
            $motor_autonomia = trim($datos[4] ?? ''); 
            
            $precio_texto = trim($datos[5] ?? '0');
            $precio = (float)str_replace(['$', ','], '', $precio_texto);
            
            $entrega_texto = trim($datos[6] ?? '');
            $entrega_dias = (int)preg_replace('/[^0-9]/', '', $entrega_texto);
            if(empty($entrega_dias)) $entrega_dias = 0; 
            
            $descripcion = trim($datos[7] ?? ''); 
            
            $estado_crudo = trim($datos[8] ?? 'Disponible');
            $estado = empty($estado_crudo) ? 'Disponible' : $estado_crudo;

            // Gestión de Categoría
            $categoria_id = NULL;
            $cat_nombre_upper = strtoupper($categoria_nombre);
            $stmt_cat = $conn->prepare("SELECT id FROM categorias WHERE UPPER(nombre) = ? LIMIT 1");
            $stmt_cat->bind_param("s", $cat_nombre_upper);
            $stmt_cat->execute();
            $res_cat = $stmt_cat->get_result();
            
            if ($row = $res_cat->fetch_assoc()) {
                $categoria_id = $row['id'];
            } else {
                $ins_cat = $conn->prepare("INSERT INTO categorias (nombre) VALUES (?)");
                $ins_cat->bind_param("s", $categoria_nombre);
                if ($ins_cat->execute()) {
                    $categoria_id = $ins_cat->insert_id;
                    $categorias_creadas++;
                } else {
                    continue; 
                }
            }

            // --- INSERTAR AUTO DEFINITIVO ---
            $sql_auto = "INSERT INTO autos (marca_id, categoria_id, modelo, motor_autonomia, precio, combustible, entrega_dias, estado, descripcion) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_auto = $conn->prepare($sql_auto);
            
            $stmt_auto->bind_param("iissdsiss", 
                $marca_id, $categoria_id, $modelo, $motor_autonomia, 
                $precio, $combustible, $entrega_dias, $estado, $descripcion
            );
            
            if ($stmt_auto->execute()) {
                $autos_insertados++;
            }
        }
        
        // Redirigir a admin_inventario.php activando la alerta de éxito
        header("Location: admin_inventario.php?msg=exito_excel");
        exit;

    } catch (\Exception $e) {
        // Redirigir a admin_inventario.php activando la alerta de error
        header("Location: admin_inventario.php?msg=error_excel");
        exit;
    }
} else {
    header("Location: admin_inventario.php");
    exit;
}
?>