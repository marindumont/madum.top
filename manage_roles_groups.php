<?php
include 'header.php'; // Inclure le header avec affichage des erreurs et connexion DB

// Vérifier si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Rediriger vers la page de connexion
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
        header('Location: index.php'); // Rediriger si non administrateur
        exit;
    }
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}

// Gestion des rôles et groupes
$errorMessage = '';
$successMessage = '';

// Ajout d'un rôle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_role'])) {
    $roleName = htmlspecialchars(trim($_POST['role_name']));
    if (!empty($roleName)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO roles (name, is_default) VALUES (?, 0)");
            $stmt->execute([$roleName]);
            $successMessage = "Rôle ajouté avec succès.";
        } catch (Exception $e) {
            $errorMessage = "Erreur lors de l'ajout du rôle : " . $e->getMessage();
        }
    } else {
        $errorMessage = "Le nom du rôle ne peut pas être vide.";
    }
}

// Ajout d'un groupe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_group'])) {
    $groupName = htmlspecialchars(trim($_POST['group_name']));
    if (!empty($groupName)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO groups (name, is_default) VALUES (?, 0)");
            $stmt->execute([$groupName]);
            $successMessage = "Groupe ajouté avec succès.";
        } catch (Exception $e) {
            $errorMessage = "Erreur lors de l'ajout du groupe : " . $e->getMessage();
        }
    } else {
        $errorMessage = "Le nom du groupe ne peut pas être vide.";
    }
}

// Récupérer les rôles et groupes existants
try {
    $roles = $pdo->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();
    $groups = $pdo->query("SELECT * FROM groups ORDER BY id ASC")->fetchAll();
} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Rôles et Groupes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Gestion des Rôles et Groupes</h1>

        <!-- Messages d'erreur et de succès -->
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger">
                <?= $errorMessage ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success">
                <?= $successMessage ?>
            </div>
        <?php endif; ?>

        <!-- Gestion des rôles -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Gestion des Rôles
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="role_name" class="form-control" placeholder="Nom du rôle" required>
                        <button type="submit" name="add_role" class="btn btn-success">Ajouter</button>
                    </div>
                </form>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom du rôle</th>
                            <th>Rôle par défaut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($roles as $role): ?>
                        <tr>
                            <td><?= htmlspecialchars($role['id']) ?></td>
                            <td><?= htmlspecialchars($role['name']) ?></td>
                            <td><?= $role['is_default'] ? 'Oui' : 'Non' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gestion des groupes -->
        <div class="card">
            <div class="card-header bg-warning text-dark">
                Gestion des Groupes
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="group_name" class="form-control" placeholder="Nom du groupe" required>
                        <button type="submit" name="add_group" class="btn btn-success">Ajouter</button>
                    </div>
                </form>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom du groupe</th>
                            <th>Groupe par défaut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groups as $group): ?>
                        <tr>
                            <td><?= htmlspecialchars($group['id']) ?></td>
                            <td><?= htmlspecialchars($group['name']) ?></td>
                            <td><?= $group['is_default'] ? 'Oui' : 'Non' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
