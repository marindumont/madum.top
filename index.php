<?php
include 'header.php'; // Inclure le header avec affichage des erreurs et connexion DB

// Récupérer le contenu de la page d'accueil
try {
    $stmt = $pdo->prepare("SELECT title, content FROM pages WHERE is_home = 1 LIMIT 1");
    $stmt->execute();
    $homePage = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Erreur lors du chargement de la page d'accueil : " . $e->getMessage());
}

// Charger les blocs publics
try {
    $publicBlocksStmt = $pdo->query("SELECT id, title, content FROM blocks WHERE is_public = 1 ORDER BY position ASC");
    $publicBlocks = $publicBlocksStmt->fetchAll(PDO::FETCH_ASSOC);
    $publicBlockIds = array_column($publicBlocks, 'id'); // Stocker les IDs des blocs publics
} catch (Exception $e) {
    die("Erreur lors du chargement des blocs publics : " . $e->getMessage());
}

// Charger les blocs personnalisés en fonction des groupes/rôles de l'utilisateur connecté
$customBlocks = [];
if (isset($_SESSION['user_id'])) {
    try {
        $userId = $_SESSION['user_id'];
        $stmt = $pdo->prepare("
            SELECT DISTINCT b.id, b.title, b.content
            FROM blocks b
            LEFT JOIN block_groups bg ON b.id = bg.block_id
            LEFT JOIN groups g ON bg.group_id = g.id
            LEFT JOIN user_groups ug ON g.id = ug.group_id
            LEFT JOIN block_roles br ON b.id = br.block_id
            LEFT JOIN roles r ON br.role_id = r.id
            LEFT JOIN user_roles ur ON r.id = ur.role_id
            WHERE (ug.user_id = ? OR ur.user_id = ?) AND b.id NOT IN (" . implode(',', $publicBlockIds) . ")
            ORDER BY b.position ASC
        ");
        $stmt->execute([$userId, $userId]);
        $customBlocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Erreur lors du chargement des blocs personnalisés : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($homePage['title']) ? htmlspecialchars($homePage['title']) : 'Accueil' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <!-- Section d'en-tête -->
        <header class="text-center mb-4">
            <h1 class="display-4"><?= isset($homePage['title']) ? htmlspecialchars($homePage['title']) : 'Bienvenue sur notre site' ?></h1>
            <p class="lead">Explorez nos fonctionnalités et contenus exclusifs.</p>
        </header>

        <!-- Contenu principal -->
        <div class="row">
            <!-- Contenu principal -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h2><?= isset($homePage['title']) ? htmlspecialchars($homePage['title']) : 'Contenu principal' ?></h2>
                    </div>
                    <div class="card-body">
                        <?= isset($homePage['content']) ? htmlspecialchars_decode($homePage['content']) : '<p>Aucune page d\'accueil définie.</p>' ?>
                    </div>
                </div>
            </div>

            <!-- Blocs dynamiques dans la section de droite -->
            <div class="col-lg-4">
                <!-- Blocs publics -->
                <?php if (!empty($publicBlocks)): ?>
                    <?php foreach ($publicBlocks as $block): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                <h3><?= htmlspecialchars($block['title']) ?></h3>
                            </div>
                            <div class="card-body">
                                <?= htmlspecialchars_decode($block['content']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Blocs personnalisés (connectés uniquement) -->
                <?php if (!empty($customBlocks)): ?>
                    <?php foreach ($customBlocks as $block): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-secondary text-white">
                                <h3><?= htmlspecialchars($block['title']) ?></h3>
                            </div>
                            <div class="card-body">
                                <?= htmlspecialchars_decode($block['content']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 mt-5 bg-light">
        <p class="mb-0">© <?= date('Y') ?> Votre entreprise. Tous droits réservés.</p>
    </footer>
</body>
</html>
