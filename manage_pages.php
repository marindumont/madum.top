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

// Traitement pour définir une page comme page d'accueil
if (isset($_GET['set_home_id'])) {
    $homePageId = intval($_GET['set_home_id']);
    try {
        $pdo->query("UPDATE pages SET is_home = 0 WHERE is_home = 1"); // Désactiver la page actuelle
        $stmt = $pdo->prepare("UPDATE pages SET is_home = 1 WHERE id = ?");
        $stmt->execute([$homePageId]);
        header('Location: manage_pages.php?home_updated=1');
        exit;
    } catch (Exception $e) {
        die("Erreur lors de la définition de la page d'accueil : " . $e->getMessage());
    }
}

// Préparer les données pour les rôles, groupes et blocs
try {
    $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();
    $groups = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC")->fetchAll();
    $blocks = $pdo->query("SELECT id, title FROM blocks ORDER BY title ASC")->fetchAll();
} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// Pré-remplissage en cas de modification
$pageToEdit = null;
if (isset($_GET['edit_id'])) {
    $editId = intval($_GET['edit_id']);
    try {
        $stmt = $pdo->prepare("
            SELECT pages.id, pages.title, pages.slug, pages.content, pages.is_home,
                   GROUP_CONCAT(DISTINCT page_roles.role_id) AS role_ids,
                   GROUP_CONCAT(DISTINCT page_groups.group_id) AS group_ids,
                   GROUP_CONCAT(DISTINCT page_blocks.block_id) AS block_ids
            FROM pages
            LEFT JOIN page_roles ON pages.id = page_roles.page_id
            LEFT JOIN page_groups ON pages.id = page_groups.page_id
            LEFT JOIN page_blocks ON pages.id = page_blocks.page_id
            WHERE pages.id = ?
            GROUP BY pages.id
        ");
        $stmt->execute([$editId]);
        $pageToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        die("Erreur lors de la récupération de la page : " . $e->getMessage());
    }
}

// Sauvegarde ou modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = htmlspecialchars(trim($_POST['title']));
    $slug = htmlspecialchars(trim($_POST['slug']));
    $content = htmlspecialchars(trim($_POST['content']));
    $isHome = isset($_POST['is_home']) ? 1 : 0;
    $roleIds = $_POST['role_ids'] ?? [];
    $groupIds = $_POST['group_ids'] ?? [];
    $blockIds = $_POST['block_ids'] ?? [];
    $pageId = isset($_POST['page_id']) ? intval($_POST['page_id']) : null;

    if (!empty($title) && !empty($slug) && !empty($content)) {
        try {
            if ($isHome) {
                // Désactiver la page d'accueil actuelle
                $pdo->query("UPDATE pages SET is_home = 0 WHERE is_home = 1");
            }

            if ($pageId) {
                // Modifier une page existante
                $stmt = $pdo->prepare("UPDATE pages SET title = ?, slug = ?, content = ?, is_home = ? WHERE id = ?");
                $stmt->execute([$title, $slug, $content, $isHome, $pageId]);

                // Supprimer les relations existantes
                $pdo->prepare("DELETE FROM page_roles WHERE page_id = ?")->execute([$pageId]);
                $pdo->prepare("DELETE FROM page_groups WHERE page_id = ?")->execute([$pageId]);
                $pdo->prepare("DELETE FROM page_blocks WHERE page_id = ?")->execute([$pageId]);

                // Ajouter les nouvelles relations
                foreach ($roleIds as $roleId) {
                    $stmt = $pdo->prepare("INSERT INTO page_roles (page_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$pageId, $roleId]);
                }
                foreach ($groupIds as $groupId) {
                    $stmt = $pdo->prepare("INSERT INTO page_groups (page_id, group_id) VALUES (?, ?)");
                    $stmt->execute([$pageId, $groupId]);
                }
                foreach ($blockIds as $blockId) {
                    $stmt = $pdo->prepare("INSERT INTO page_blocks (page_id, block_id) VALUES (?, ?)");
                    $stmt->execute([$pageId, $blockId]);
                }
            } else {
                // Ajouter une nouvelle page
                $stmt = $pdo->prepare("INSERT INTO pages (title, slug, content, is_home) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $slug, $content, $isHome]);
                $pageId = $pdo->lastInsertId();

                // Ajouter les relations
                foreach ($roleIds as $roleId) {
                    $stmt = $pdo->prepare("INSERT INTO page_roles (page_id, role_id) VALUES (?, ?)");
                    $stmt->execute([$pageId, $roleId]);
                }
                foreach ($groupIds as $groupId) {
                    $stmt = $pdo->prepare("INSERT INTO page_groups (page_id, group_id) VALUES (?, ?)");
                    $stmt->execute([$pageId, $groupId]);
                }
                foreach ($blockIds as $blockId) {
                    $stmt = $pdo->prepare("INSERT INTO page_blocks (page_id, block_id) VALUES (?, ?)");
                    $stmt->execute([$pageId, $blockId]);
                }
            }
            $successMessage = "Page enregistrée avec succès.";
        } catch (Exception $e) {
            $errorMessage = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    } else {
        $errorMessage = "Tous les champs doivent être remplis.";
    }
}

// Récupération des pages existantes
try {
    $pages = $pdo->query("
        SELECT pages.id, pages.title, pages.slug, pages.is_home,
               GROUP_CONCAT(DISTINCT roles.name SEPARATOR ', ') AS role_names,
               GROUP_CONCAT(DISTINCT groups.name SEPARATOR ', ') AS group_names,
               GROUP_CONCAT(DISTINCT blocks.title SEPARATOR ', ') AS block_names
        FROM pages
        LEFT JOIN page_roles ON pages.id = page_roles.page_id
        LEFT JOIN roles ON page_roles.role_id = roles.id
        LEFT JOIN page_groups ON pages.id = page_groups.page_id
        LEFT JOIN groups ON page_groups.group_id = groups.id
        LEFT JOIN page_blocks ON pages.id = page_blocks.page_id
        LEFT JOIN blocks ON page_blocks.block_id = blocks.id
        GROUP BY pages.id
        ORDER BY pages.is_home DESC, pages.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $pages = [];
    die("Erreur lors de la récupération des pages : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les Pages</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Gérer les Pages</h1>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST" class="card mx-auto" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">Créer ou Modifier une Page</div>
            <div class="card-body">
                <input type="hidden" name="page_id" value="<?= $pageToEdit['id'] ?? '' ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Titre :</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($pageToEdit['title'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="slug" class="form-label">Slug (URL) :</label>
                    <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($pageToEdit['slug'] ?? '') ?>" required>
                </div>
                <div class="mb-3">
                    <label for="content" class="form-label">Contenu :</label>
                    <textarea id="content" name="content" class="form-control" rows="5" required><?= htmlspecialchars($pageToEdit['content'] ?? '') ?></textarea>
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input" id="is_home" name="is_home" <?= isset($pageToEdit['is_home']) && $pageToEdit['is_home'] ? 'checked' : '' ?>>
                    <label for="is_home" class="form-check-label">Définir comme page d'accueil</label>
                </div>
                <div class="mb-3">
                    <label for="role_ids" class="form-label">Associer aux Rôles :</label>
                    <select name="role_ids[]" id="role_ids" class="form-select" multiple>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['id'] ?>" <?= isset($pageToEdit['role_ids']) && in_array($role['id'], explode(',', $pageToEdit['role_ids'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="group_ids" class="form-label">Associer aux Groupes :</label>
                    <select name="group_ids[]" id="group_ids" class="form-select" multiple>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= $group['id'] ?>" <?= isset($pageToEdit['group_ids']) && in_array($group['id'], explode(',', $pageToEdit['group_ids'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="block_ids" class="form-label">Associer des Blocs :</label>
                    <select name="block_ids[]" id="block_ids" class="form-select" multiple>
                        <?php foreach ($blocks as $block): ?>
                            <option value="<?= $block['id'] ?>" <?= isset($pageToEdit['block_ids']) && in_array($block['id'], explode(',', $pageToEdit['block_ids'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($block['title']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success w-100">Enregistrer</button>
            </div>
        </form>

        <!-- Liste des pages -->
        <div class="card mt-5">
            <div class="card-header bg-secondary text-white">Pages Existantes</div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Titre</th>
                            <th>Slug</th>
                            <th>Page d'accueil</th>
                            <th>Rôles Associés</th>
                            <th>Groupes Associés</th>
                            <th>Blocs Associés</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pages)): ?>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td><?= $page['id'] ?></td>
                                    <td><?= htmlspecialchars($page['title']) ?></td>
                                    <td><?= htmlspecialchars($page['slug']) ?></td>
                                    <td><?= $page['is_home'] ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-danger">Non</span>' ?></td>
                                    <td><?= htmlspecialchars($page['role_names'] ?? 'Aucun') ?></td>
                                    <td><?= htmlspecialchars($page['group_names'] ?? 'Aucun') ?></td>
                                    <td><?= htmlspecialchars($page['block_names'] ?? 'Aucun') ?></td>
                                    <td>
                                        <a href="manage_pages.php?edit_id=<?= $page['id'] ?>" class="btn btn-warning btn-sm">Modifier</a>
                                        <a href="manage_pages.php?delete_id=<?= $page['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette page ?');">Supprimer</a>
                                        <?php if (!$page['is_home']): ?>
                                            <a href="manage_pages.php?set_home_id=<?= $page['id'] ?>" class="btn btn-success btn-sm">Définir comme page d'accueil</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Aucune page disponible.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
