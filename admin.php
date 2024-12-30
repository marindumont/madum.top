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

// Récupérer la liste des utilisateurs
try {
    $stmt = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    die("Erreur lors de la récupération des utilisateurs : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Gestion des utilisateurs</h1>

        <!-- Liste des utilisateurs -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                Liste des utilisateurs
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom d'utilisateur</th>
                            <th>Email</th>
                            <th>Date d'inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars(date("d/m/Y", strtotime($user['created_at']))) ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-warning btn-sm">Modifier</a>
                                <a href="assign_roles_groups.php?id=<?= $user['id'] ?>" class="btn btn-info btn-sm">Changer Rôle/Groupes</a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">Supprimer</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
