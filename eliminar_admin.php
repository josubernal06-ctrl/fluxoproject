<?php
include 'admin_auth.php';
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id_admin']) && $_SESSION['user_rol'] === 'sup-admin') {
    $id = $_POST['id_admin'];
    
    $sql = "DELETE FROM usuarios WHERE id = ? AND rol = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        header("Location: gestion_admins.php?success=1");
    } else {
        header("Location: gestion_admins.php?error=1");
    }
}
?>