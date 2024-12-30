<?php
include 'header.php'; // Inclure le header avec les sessions et la configuration des erreurs

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Rediriger vers la page de connexion si non connecté
    exit;
}

// Récupérer les informations de l'utilisateur
$userId = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("
        SELECT users.username, users.email, users.created_at,
               GROUP_CONCAT(DISTINCT roles.name SEPARATOR ', ') AS roles,
               GROUP_CONCAT(DISTINCT groups.name SEPARATOR ', ') AS groups
        FROM users
        LEFT JOIN user_roles ON users.id = user_roles.user_id
        LEFT JOIN roles ON user_roles.role_id = roles.id
        LEFT JOIN user_groups ON users.id = user_groups.user_id
        LEFT JOIN groups ON user_groups.group_id = groups.id
        WHERE users.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Utilisateur non trouvé.");
    }

    // Vérifier si l'utilisateur est administrateur
    $isAdmin = strpos($user['roles'], 'Admin') !== false;

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Membre</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Espace Membre</h1>

        <!-- Informations utilisateur -->
        <div class="card mx-auto mb-4" style="max-width: 600px;">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Vos Informations</h4>
            </div>
            <div class="card-body">
                <p><strong>Nom d'utilisateur :</strong> <?= htmlspecialchars($user['username']) ?></p>
                <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Rôle(s) :</strong> <?= htmlspecialchars($user['roles'] ?: 'Aucun rôle attribué') ?></p>
                <p><strong>Groupe(s) :</strong> <?= htmlspecialchars($user['groups'] ?: 'Aucun groupe attribué') ?></p>
                <p><strong>Date d'inscription :</strong> <?= htmlspecialchars(date("d/m/Y", strtotime($user['created_at']))) ?></p>
                <a href="logout.php" class="btn btn-danger w-100">Se déconnecter</a>
            </div>
        </div>

        <!-- Bloc administrateur -->
        <?php if ($isAdmin): ?>
        <div class="card mx-auto" style="max-width: 600px;">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0">Options Administrateur</h4>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li><a href="admin.php" class="btn btn-link">Gestion des Utilisateurs</a></li>
                    <li><a href="manage_roles_groups.php" class="btn btn-link">Gérer les Rôles et Groupes</a></li>
                    <li><a href="add_member.php" class="btn btn-link">Ajouter un Utilisateur</a></li>
                    <li><a href="manage_pages.php" class="btn btn-link">Gérer les Pages</a></li>
                    <li><a href="manage_blocks.php" class="btn btn-link">Gérer les Blocs</a></li>
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
