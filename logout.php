<?php
session_start();
session_destroy(); // Détruire la session
header('Location: index.php'); // Rediriger après déconnexion
exit;
?>
