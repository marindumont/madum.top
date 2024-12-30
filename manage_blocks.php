<?php
include 'header.php'; // Inclure le header avec affichage des erreurs et connexion DB

// Vérification de l'administrateur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$isAdmin = false;
try {
    $stmt = $pdo->prepare("
        SELECT GROUP_CONCAT(roles.name SEPARATOR ', ') AS roles
        FROM users
        LEFT JOIN user_roles ON users.id = user_roles.user_id
        LEFT JOIN roles ON user_roles.role_id = roles.id
        WHERE users.id = ?
    ");
    $stmt->execute([$userId]);
    $roles = $stmt->fetchColumn();
    $isAdmin = strpos($roles, 'Admin') !== false;

    if (!$isAdmin) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Préparer les données pour les pages
try {
    $pages = $pdo->query("SELECT id, title FROM pages ORDER BY title ASC")->fetchAll();
} catch (Exception $e) {
    die("Erreur lors de la récupération des pages : " . $e->getMessage());
}

// Pré-remplissage en cas de modification
$blockToEdit = null;
if (isset($_GET['edit_id'])) {
    $editId = intval($_GET['edit_id']);
    try {
        $stmt = $pdo->prepare("
            SELECT blocks.id, blocks.title, blocks.content, blocks.is_public, blocks.position,
                   GROUP_CONCAT(block_pages.page_id) AS page_ids
            FROM blocks
            LEFT JOIN block_pages ON blocks.id = block_pages.block_id
            WHERE blocks.id = ?
            GROUP BY blocks.id
        ");
        $stmt->execute([$editId]);
        $blockToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Erreur lors de la récupération du bloc : " . $e->getMessage());
    }
}

// Sauvegarde ou modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $content = htmlspecialchars(trim($_POST['content']));
    $isPublic = isset($_POST['is_public']) ? 1 : 0;
    $position = intval($_POST['position']);
    $pageIds = $_POST['page_ids'] ?? [];
    $blockId = isset($_POST['block_id']) ? intval($_POST['block_id']) : null;

    if (!empty($title) && !empty($content)) {
        try {
            if ($blockId) {
                // Modifier un bloc existant
                $stmt = $pdo->prepare("UPDATE blocks SET title = ?, content = ?, is_public = ?, position = ? WHERE id = ?");
                $stmt->execute([$title, $content, $isPublic, $position, $blockId]);

                $stmt = $pdo->prepare("DELETE FROM block_pages WHERE block_id = ?");
                $stmt->execute([$blockId]);

                foreach ($pageIds as $pageId) {
                    $stmt = $pdo->prepare("INSERT INTO block_pages (block_id, page_id) VALUES (?, ?)");
                    $stmt->execute([$blockId, $pageId]);
                }
            } else {
                // Ajouter un nouveau bloc
                $stmt = $pdo->prepare("INSERT INTO blocks (title, content, is_public, position) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $isPublic, $position]);
                $blockId = $pdo->lastInsertId();

                foreach ($pageIds as $pageId) {
                    $stmt = $pdo->prepare("INSERT INTO block_pages (block_id, page_id) VALUES (?, ?)");
                    $stmt->execute([$blockId, $pageId]);
                }
            }
            $successMessage = "Bloc enregistré avec succès.";
        } catch (Exception $e) {
            $errorMessage = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    } else {
        $errorMessage = "Tous les champs doivent être remplis.";
    }
}

// Récupération des blocs existants
try {
    $blocks = $pdo->query("
        SELECT blocks.id, blocks.title, blocks.is_public, blocks.position,
               GROUP_CONCAT(DISTINCT pages.title SEPARATOR ', ') AS page_names
        FROM blocks
        LEFT JOIN block_pages ON blocks.id = block_pages.block_id
        LEFT JOIN pages ON block_pages.page_id = pages.id
        GROUP BY blocks.id
        ORDER BY blocks.position ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $blocks = [];
    die("Erreur lors de la récupération des blocs : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Blocs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Gérer les Blocs</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST" class="card mx-auto" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">Créer ou Modifier un Bloc</div>
            <div class="card-body">
                <input type="hidden" name="block_id" value="<?= $blockToEdit['id'] ?? '' ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Titre :</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($blockToEdit['title'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Contenu :</label>
                    <textarea id="content" name="content" class="form-control" rows="5" required><?= htmlspecialchars($blockToEdit['content'] ?? '') ?></textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="is_public" name="is_public" <?= isset($blockToEdit['is_public']) && $blockToEdit['is_public'] ? 'checked' : '' ?>>
                    <label for="is_public" class="form-check-label">Bloc Public</label>
                </div>
                <div class="mb-3">
                    <label for="position" class="form-label">Position :</label>
                    <input type="number" id="position" name="position" class="form-control" value="<?= htmlspecialchars($blockToEdit['position'] ?? 0) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="page_ids" class="form-label">Associer aux Pages :</label>
                    <select name="page_ids[]" id="page_ids" class="form-select" multiple>
                        <?php foreach ($pages as $page): ?>
                            <option value="<?= $page['id'] ?>" <?= isset($blockToEdit['page_ids']) && in_array($page['id'], explode(',', $blockToEdit['page_ids'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($page['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-100">Enregistrer</button>
            </div>
        </form>

        <!-- Liste des blocs -->
        <div class="card mt-5">
            <div class="card-header bg-secondary text-white">Blocs Existants</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Public</th>
                            <th>Position</th>
                            <th>Pages Associées</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($blocks)): ?>
                            <?php foreach ($blocks as $block): ?>
                                <tr>
                                    <td><?= $block['id'] ?></td>
                                    <td><?= htmlspecialchars($block['title']) ?></td>
                                    <td><?= $block['is_public'] ? 'Oui' : 'Non' ?></td>
                                    <td><?= $block['position'] ?></td>
                                    <td><?= htmlspecialchars($block['page_names'] ?? 'Aucune') ?></td>
                                    <td>
                                        <a href="manage_blocks.php?edit_id=<?= $block['id'] ?>" class="btn btn-warning btn-sm">Modifier</a>
                                        <a href="manage_blocks.php?delete_id=<?= $block['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce bloc ?');">Supprimer</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center">Aucun bloc disponible.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
