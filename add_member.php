<?php
include 'header.php'; // Inclure le header avec affichage des erreurs et connexion DB

// Vérifier si l'utilisateur est administrateur
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

// Récupérer les rôles et groupes disponibles
try {
    $roles = $pdo->query("SELECT id, name FROM roles ORDER BY name ASC")->fetchAll();
    $groups = $pdo->query("SELECT id, name FROM groups ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}

// Ajouter un utilisateur avec rôle et groupes multiples
$errorMessage = '';
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $roleId = htmlspecialchars(trim($_POST['role_id']));
    $groupIds = $_POST['group_ids'] ?? [];

    if (!empty($username) && !empty($email) && !empty($password)) {
        try {
            // Ajouter l'utilisateur
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $password]);
            $newUserId = $pdo->lastInsertId();

            // Assigner le rôle
            if (!empty($roleId)) {
                $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $stmt->execute([$newUserId, $roleId]);
            }

            // Assigner les groupes
            if (!empty($groupIds)) {
                foreach ($groupIds as $groupId) {
                    $stmt = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
                    $stmt->execute([$newUserId, $groupId]);
                }
            }

            $successMessage = "Utilisateur ajouté avec succès.";
        } catch (Exception $e) {
            $errorMessage = "Erreur lors de l'ajout de l'utilisateur : " . $e->getMessage();
        }
    } else {
        $errorMessage = "Tous les champs sont obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un membre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Ajouter un membre</h1>

        <!-- Messages -->
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>

        <?php if ($successMessage): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php endif; ?>

        <!-- Formulaire -->
        <form method="POST" class="card mx-auto" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">Ajouter un utilisateur</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email :</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mot de passe :</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="role_id" class="form-label">Rôle :</label>
                    <select name="role_id" id="role_id" class="form-select">
                        <option value="">-- Sélectionnez un rôle (optionnel) --</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= htmlspecialchars($role['id']) ?>"><?= htmlspecialchars($role['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="group_ids" class="form-label">Groupes :</label>
                    <select name="group_ids[]" id="group_ids" class="form-select" multiple>
                        <?php foreach ($groups as $group): ?>
                            <option value="<?= htmlspecialchars($group['id']) ?>"><?= htmlspecialchars($group['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Maintenez Ctrl (ou Cmd) pour sélectionner plusieurs groupes.</small>
                </div>
                <button type="submit" class="btn btn-success w-100">Ajouter</button>
            </div>
        </form>
    </div>
</body>
</html>
