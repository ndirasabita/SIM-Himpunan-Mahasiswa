<?php
session_start();
$_SESSION = array(); // Menghapus semua variabel sesi
session_destroy(); // Menghancurkan sesi
header("Location: index.php");
exit();
?>
