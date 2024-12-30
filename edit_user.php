<?php
include 'header.php';
if (!isset($_GET['id'])) {
    header('Location: admin.php');
    exit;
}

$userId = $_GET['id'];
try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("Utilisateur non trouvé.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = htmlspecialchars(trim($_POST['username']));
        $email = htmlspecialchars(trim($_POST['email']));

        if (!empty($username) && !empty($email)) {
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
            $stmt->execute([$username, $email, $userId]);
            header('Location: admin.php');
            exit;
        } else {
            $errorMessage = "Tous les champs sont obligatoires.";
        }
    }
} catch (Exception $e) {
    $errorMessage = "Erreur : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier Utilisateur</title>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Modifier Utilisateur</h1>
        <?php if (isset($errorMessage)): ?>
            <div class="alert alert-danger"><?= $errorMessage ?></div>
        <?php endif; ?>
        <form method="POST" class="card mx-auto" style="max-width: 600px;">
            <div class="card-body">
                <div class="mb-3">
                    <label for="username" class="form-label">Nom d'utilisateur :</label>
                    <input type="text" id="username" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email :</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Mettre à jour</button>
            </div>
        </form>
    </div>
</body>
</html>
