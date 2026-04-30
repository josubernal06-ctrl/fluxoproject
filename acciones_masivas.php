<?php
// acciones_masivas.php
require_once 'database.php'; // Tu archivo de conexión

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['autos_ids'])) {
    
    $ids = $_POST['autos_ids']; // Array con los IDs seleccionados
    $accion = $_POST['accion']; // 'eliminar' o 'estado'
    
    // 1. Limpieza de seguridad: Forzar que todos los IDs sean números enteros
    $ids_limpios = array_map('intval', $ids);
    
    // Crear interrogantes para la consulta preparada (ej: ?, ?, ?)
    $placeholders = implode(',', array_fill(0, count($ids_limpios), '?'));
    
    if ($accion === 'eliminar') {
        // Eliminar masivo
        $sql = "DELETE FROM autos WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        // Bind dinámico de n variables
        $stmt->execute($ids_limpios);
        
    } elseif ($accion === 'estado' && !empty($_POST['nuevo_estado'])) {
        // Cambio de estado masivo
        $nuevo_estado = $_POST['nuevo_estado'];
        
        $sql = "UPDATE autos SET estado = ? WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        
        // Juntamos el nuevo estado con los IDs para el bind
        $params = array_merge([$nuevo_estado], $ids_limpios);
        $stmt->execute($params);
    }
    
    // Redirigir de vuelta al inventario con un mensaje de éxito
    header("Location: admin_inventario.php?msg=exito_masivo");
    exit;
} else {
    // Si entró sin seleccionar nada, lo devolvemos
    header("Location: admin_inventario.php");
    exit;
}
?>