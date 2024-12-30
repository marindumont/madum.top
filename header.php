<?php
session_start(); // Démarrer la session pour suivre l'état de connexion

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db_connection.php'; // Inclure la connexion à la base de données

// Vérifier si l'utilisateur est connecté
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;

// Récupérer les pages accessibles pour l'utilisateur connecté (groupes/roles)
$accessiblePages = [];
if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.title, p.slug
            FROM pages p
            LEFT JOIN page_groups pg ON p.id = pg.page_id
            LEFT JOIN groups g ON pg.group_id = g.id
            LEFT JOIN user_groups ug ON g.id = ug.group_id
            LEFT JOIN page_roles pr ON p.id = pr.page_id
            LEFT JOIN roles r ON pr.role_id = r.id
            LEFT JOIN user_roles ur ON r.id = ur.role_id
            WHERE (ug.user_id = ? OR ur.user_id = ?) AND p.is_home = 0
            ORDER BY p.title ASC
        ");
        $stmt->execute([$userId, $userId]);
        $accessiblePages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Gérer les erreurs SQL
        die("Erreur lors du chargement des pages accessibles : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Madum.top</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<header>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">Madum.top</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="pagesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Pages accessibles
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="pagesDropdown">
                            <?php if (!empty($accessiblePages)): ?>
                                <?php foreach ($accessiblePages as $page): ?>
                                    <li><a class="dropdown-item" href="page.php?slug=<?= htmlspecialchars($page['slug']) ?>"><?= htmlspecialchars($page['title']) ?></a></li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li><span class="dropdown-item-text">Aucune page disponible</span></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php if ($isLoggedIn): ?>
                        <!-- Menu pour les utilisateurs connectés -->
                        <li class="nav-item">
                            <a class="nav-link" href="espace_membre.php">Espace Membre</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Déconnexion</a>
                        </li>
                    <?php else: ?>
                        <!-- Menu pour les visiteurs -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="enregistrement.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
