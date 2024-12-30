<?php
include 'header.php';
if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit;
}

$userId = $_GET['id'];
try {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    header('Location: admin.php');
    exit;
} catch (Exception $e) {
    die("Erreur lors de la suppression : " . $e->getMessage());
}
?>
