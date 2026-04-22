<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_rol'] !== 'admin' && $_SESSION['user_rol'] !== 'sup-admin')) {
    header("Location: index.php");
    exit();
}
?>