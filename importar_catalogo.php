<?php
// importar_catalogo.php
include 'admin_auth.php';
include 'database.php';
require 'vendor/autoload.php';

// Limpieza para que las alertas no salgan solas al cargar
$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    
    $archivo_tmp = $_FILES['archivo_excel']['tmp_name'];
    $nombre_archivo = $_FILES['archivo_excel']['name'];
    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));

    if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
        echo "<script>alert('❌ Formato incorrecto. Sube un archivo .xlsx o .csv.'); window.location.href='admin_inventario.php';</script>";
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
            // 1. Obtener Marca (en mayúsculas para evitar TOYOTA vs toyota)
            $marca_nombre_crudo = trim($datos[0] ?? '');
            $marca_nombre = strtoupper($marca_nombre_crudo);

            // Filtro para saltar filas vacías o de cabeceras de FluxoCars
            if (empty($marca_nombre) || 
                strpos($marca_nombre, 'MARCA') !== false || 
                strpos($marca_nombre, 'FLUXOCARS') !== false || 
                strpos($marca_nombre, 'USO INTERNO') !== false ||
                strpos($marca_nombre, 'STOCK PREMIUM') !== false) {
                continue; 
            }

            // 2. Lógica Infalible de Gestión de Marca
            $marca_id = NULL;
            $stmt_marca = $conn->prepare("SELECT id FROM marcas WHERE UPPER(nombre) = ? LIMIT 1");
            $stmt_marca->bind_param("s", $marca_nombre);
            $stmt_marca->execute();
            $res_marca = $stmt_marca->get_result();

            if ($row = $res_marca->fetch_assoc()) {
                // La marca existe, usamos su ID
                $marca_id = $row['id'];
            } else {
                // La marca no existe, la creamos
                $ins_marca = $conn->prepare("INSERT INTO marcas (nombre) VALUES (?)");
                $ins_marca->bind_param("s", $marca_nombre_crudo);
                if ($ins_marca->execute()) {
                    $marca_id = $ins_marca->insert_id;
                    $marcas_creadas++;
                } else {
                    // Fallo al crear la marca, no podemos insertar el auto
                    continue; 
                }
            }

            // 3. Obtener el resto de los datos (Índices basados en tus Excel de prueba)
            $modelo_original = trim($datos[1] ?? ''); 
            
            // --- ARREGLO DEL CONTADOR (Modelo Vacío) ---
            if(empty($modelo_original)) {
                // Si el modelo está vacío, creamos uno genérico para no perder el auto
                $modelo = "$marca_nombre - Modelo Desconocido";
            } else {
                $modelo = $modelo_original;
            }

            $categoria_nombre = trim($datos[2] ?? 'General'); 
            $combustible = trim($datos[3] ?? 'No especificado'); 
            $motor_autonomia = trim($datos[4] ?? ''); 
            
            // Limpieza de Precio
            $precio_texto = trim($datos[5] ?? '0');
            $precio = (float)str_replace(['$', ','], '', $precio_texto);
            
            // Limpieza de Entrega
            $entrega_texto = trim($datos[6] ?? '');
            $entrega_dias = (int)preg_replace('/[^0-9]/', '', $entrega_texto);
            if(empty($entrega_dias)) $entrega_dias = 0; 
            
            $descripcion = trim($datos[7] ?? ''); 
            
            // --- ARREGLO DEL "EN TRANSITO" ---
            // Buscamos si hay una columna de estado en el Excel (supongamos que es la columna 8)
            // Si no existe, pondremos "Disponible" como valor más neutral
            $estado_crudo = trim($datos[8] ?? 'Disponible');
            $estado = empty($estado_crudo) ? 'Disponible' : $estado_crudo;

            // Gestión de Categoría (similar a la marca)
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
                    // Fallo al crear la categoría
                    continue; 
                }
            }

            // --- INSERTAR AUTO DEFINITIVO (SÓLO 9 PARÁMETROS, CON NUEVA ESTRUCTURA) ---
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
        
        // Magia: Te devuelve a admin_inventario.php con un mensaje de éxito sin que notes que saliste
        echo "<script>alert('✅ ¡Proceso completado! Se importaron $autos_insertados vehículos, $marcas_creadas marcas y $categorias_creadas categorías nuevas.'); window.location.href='admin_inventario.php';</script>";

    } catch (\Exception $e) {
        $error_limpio = addslashes($e->getMessage());
        echo "<script>alert('❌ Error al procesar el archivo: $error_limpio'); window.location.href='admin_inventario.php';</script>";
    }
} else {
    header("Location: admin_inventario.php");
    exit;
}
?>