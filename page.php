<?php
include 'header.php'; // Inclure le header avec les sessions et la configuration des erreurs

// Vérifier si un slug est fourni
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    header('Location: index.php');
    exit;
}

$slug = htmlspecialchars(trim($_GET['slug']));
$page = null;

// Récupérer le contenu de la page
try {
    $stmt = $pdo->prepare("
        SELECT pages.title, pages.content
        FROM pages
        LEFT JOIN page_groups ON pages.id = page_groups.page_id
        LEFT JOIN user_groups ON page_groups.group_id = user_groups.group_id
        WHERE pages.slug = ? AND user_groups.user_id = ?
        GROUP BY pages.id
    ");
    $stmt->execute([$slug, $_SESSION['user_id'] ?? 0]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$page) {
        throw new Exception("Page introuvable ou accès refusé.");
    }
} catch (Exception $e) {
    die("<div class='container py-5'><h1 class='text-center'>Erreur : " . htmlspecialchars($e->getMessage()) . "</h1></div>");
}

// Récupérer les blocs dynamiques associés
try {
    $stmt = $pdo->prepare("
        SELECT blocks.title, blocks.content
        FROM blocks
        LEFT JOIN block_groups ON blocks.id = block_groups.block_id
        LEFT JOIN user_groups ON block_groups.group_id = user_groups.group_id
        WHERE user_groups.user_id = ?
        GROUP BY blocks.id
    ");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    $blocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $blocks = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page['title']) ?> - Madum.top</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <header class="text-center mb-4">
            <h1><?= htmlspecialchars($page['title']) ?></h1>
        </header>

        <div class="row">
            <!-- Colonne principale avec la page -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white"><?= htmlspecialchars($page['title']) ?></div>
                    <div class="card-body">
                        <?= htmlspecialchars_decode($page['content']) ?>
                    </div>
                </div>
            </div>

            <!-- Colonne secondaire avec les blocs -->
            <div class="col-lg-4">
                <?php if (!empty($blocks)): ?>
                    <?php foreach ($blocks as $block): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-secondary text-white"><?= htmlspecialchars($block['title']) ?></div>
                            <div class="card-body">
                                <?= htmlspecialchars_decode($block['content']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Aucun bloc disponible pour votre groupe.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
