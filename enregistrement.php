<?php
include 'header.php'; // Inclure le header avec affichage des erreurs et connexion DB

// Fonction pour valider les données entrantes
function sanitizeInput($data) {
    return htmlspecialchars(trim($data));
}

// Initialisation des messages d'erreur et de succès
$errorMessage = '';
$successMessage = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = sanitizeInput($_POST['password']);
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';

    // Vérification de reCAPTCHA
    $recaptchaSecret = '6LeX8KkqAAAAADsgfP-5Xwdrfor-V0FC-0K2cqaP';
    $recaptchaVerifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
    $response = file_get_contents("$recaptchaVerifyUrl?secret=$recaptchaSecret&response=$recaptchaResponse");
    $responseKeys = json_decode($response, true);

    if (isset($responseKeys['success']) && $responseKeys['success']) {
        // Si reCAPTCHA est validé, continuer la vérification des champs
        if (empty($username) || empty($email) || empty($password)) {
            $errorMessage = "Tous les champs sont obligatoires.";
        } else {
            try {
                // Vérifier si l'utilisateur existe déjà
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetchColumn() > 0) {
                    $errorMessage = "Nom d'utilisateur ou email déjà utilisé.";
                } else {
                    // Hacher le mot de passe
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    // Insérer l'utilisateur dans la table `users`
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $hashedPassword]);
                    $userId = $pdo->lastInsertId();

                    // Récupérer et assigner le rôle par défaut
                    $stmt = $pdo->prepare("SELECT id FROM roles WHERE is_default = 1 LIMIT 1");
                    $stmt->execute();
                    $defaultRole = $stmt->fetch();
                    if ($defaultRole) {
                        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                        $stmt->execute([$userId, $defaultRole['id']]);
                    }

                    // Récupérer et assigner le groupe par défaut
                    $stmt = $pdo->prepare("SELECT id FROM groups WHERE is_default = 1 LIMIT 1");
                    $stmt->execute();
                    $defaultGroup = $stmt->fetch();
                    if ($defaultGroup) {
                        $stmt = $pdo->prepare("INSERT INTO user_groups (user_id, group_id) VALUES (?, ?)");
                        $stmt->execute([$userId, $defaultGroup['id']]);
                    }

                    // Afficher un message de succès
                    $successMessage = "Inscription réussie. Bienvenue dans notre communauté, $username !";
                }
            } catch (Exception $e) {
                $errorMessage = "Erreur lors de l'inscription : " . $e->getMessage();
            }
        }
    } else {
        $errorMessage = "Échec de la vérification reCAPTCHA. Veuillez réessayer.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Inscription</h1>

        <!-- Affichage des messages -->
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

        <!-- Formulaire d'inscription -->
        <form action="" method="POST" class="mx-auto" style="max-width: 400px;">
            <div class="mb-3">
                <label for="username" class="form-label">Nom d'utilisateur :</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email :</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe :</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="g-recaptcha mb-3" data-sitekey="6LeX8KkqAAAAAEle0Pahp2EtHnGCrdX_ohg0_Uef"></div>
            <button type="submit" class="btn btn-primary w-100">S'inscrire</button>
        </form>
    </div>
</body>
</html>
