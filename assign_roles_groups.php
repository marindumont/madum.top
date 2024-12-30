<?php
include 'header.php'; // Inclure le header avec affichage des erreurs et connexion DB

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
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

    if (strpos($roles, 'Admin') === false) {
        header('Location: index.php');
        exit;
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Variables pour stocker les valeurs sélectionnées
$selectedUserId = '';
$selectedRoleId = '';
$selectedGroupIds = [];

// Si un ID utilisateur est passé via l'URL, récupérer ses informations existantes
if (isset($_GET['id'])) {
    $selectedUserId = htmlspecialchars(trim($_GET['id']));
    try {
        // Récupérer les rôles actuels de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT role_id FROM user_roles WHERE user_id = ?
        ");
        $stmt->execute([$selectedUserId]);
        $selectedRoleId = $stmt->fetchColumn();

        // Récupérer les groupes actuels de l'utilisateur
        $stmt = $pdo->prepare("
            SELECT group_id FROM user_groups WHERE user_id = ?
        ");
        $stmt->execute([$selectedUserId]);
        $selectedGroupIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        $errorMessage = "Erreur lors de la récupération des données : " . $e->getMessage();
    }
}

// Gestion du formulaire
$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $selectedUserId = htmlspecialchars(trim($_POST['user_id']));
    $selectedRoleId = htmlspecialchars(trim($_POST['role_id']));
    $selectedGroupIds = $_POST['group_ids'] ?? [];

    try {
        // Assigner ou mettre à jour le rôle
        if (!empty($selectedRoleId)) {
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE role_id = VALUES(role_id)");
            $stmt->execute([$selectedUserId, $selectedRoleId]);
        }

        // Supprimer les groupes existants de l'utilisateur
        $stmt = $pdo->prepare("DELETE FROM user_groups WHERE user_id = ?");
        $stmt->execute([$selectedUserId]);

        // Assigner les nouveaux groupes
        foreach ($selectedGroupIds as $groupId) {
            $stmt = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
            $stmt->execute([$selectedUserId, $groupId]);
        }

        $successMessage = "Attributions mises à jour avec succès.";
    } catch (Exception $e) {
        $errorMessage = "Erreur lors de la mise à jour : " . $e->getMessage();
    }
}

// Récupérer les utilisateurs, rôles et groupes
try {
    $users = $pdo->query("SELECT id, username FROM users ORDER BY username ASC")->fetchAll();
    $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();
    $groups = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigner Rôles et Groupes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Assigner Rôles et Groupes</h1>

        <!-- Messages d'erreur et de succès -->
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php endif; ?>

        <!-- Formulaire d'association -->
        <form method="POST" class="card mx-auto" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">
                Associer un utilisateur
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="user_id" class="form-label">Utilisateur :</label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">-- Sélectionnez un utilisateur --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= htmlspecialchars($user['id']) ?>" <?= $user['id'] == $selectedUserId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="role_id" class="form-label">Rôle :</label>
                    <select name="role_id" id="role_id" class="form-select">
                        <option value="">-- Sélectionnez un rôle (optionnel) --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['id']) ?>" <?= $role['id'] == $selectedRoleId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="group_ids" class="form-label">Groupes :</label>
                    <select name="group_ids[]" id="group_ids" class="form-select" multiple>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['id']) ?>" <?= in_array($group['id'], $selectedGroupIds) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($group['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs groupes.</small>
                </div>
                <button type="submit" name="assign" class="btn btn-success w-100">Mettre à jour</button>
            </div>
        </form>
    </div>
</body>
</html>
