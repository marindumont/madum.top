<?php
require 'db_connection.php'; // Inclure votre connexion à la base de données

// Fonction pour vérifier la structure d'une table
function checkTableStructure($pdo, $tableName) {
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $structure;
    } catch (Exception $e) {
        return "Erreur : " . $e->getMessage();
    }
}

// Fonction pour vérifier le contenu d'une table
function checkTableContent($pdo, $tableName) {
    try {
        $stmt = $pdo->query("SELECT * FROM $tableName LIMIT 20");
        $content = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $content;
    } catch (Exception $e) {
        return "Erreur : " . $e->getMessage();
    }
}

// Tables à vérifier
$tables = ['pages', 'blocks', 'page_blocks'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des Tables</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1 class="text-center mb-4">Vérification des Tables</h1>
        
        <?php foreach ($tables as $table): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    Table : <?= htmlspecialchars($table) ?>
                </div>
                <div class="card-body">
                    <h5>Structure</h5>
                    <?php
                    $structure = checkTableStructure($pdo, $table);
                    if (is_array($structure)): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Field</th>
                                    <th>Type</th>
                                    <th>Null</th>
                                    <th>Key</th>
                                    <th>Default</th>
                                    <th>Extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($structure as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['Field']) ?></td>
                                        <td><?= htmlspecialchars($row['Type']) ?></td>
                                        <td><?= htmlspecialchars($row['Null']) ?></td>
                                        <td><?= htmlspecialchars($row['Key']) ?></td>
                                        <td><?= htmlspecialchars($row['Default']) ?></td>
                                        <td><?= htmlspecialchars($row['Extra']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-danger"><?= $structure ?></p>
                    <?php endif; ?>

                    <h5>Contenu (20 premières lignes)</h5>
                    <?php
                    $content = checkTableContent($pdo, $table);
                    if (is_array($content) && !empty($content)): ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($content[0]) as $column): ?>
                                        <th><?= htmlspecialchars($column) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($content as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $cell): ?>
                                            <td><?= htmlspecialchars($cell) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php elseif (is_array($content) && empty($content)): ?>
                        <p>Aucune donnée trouvée dans cette table.</p>
                    <?php else: ?>
                        <p class="text-danger"><?= $content ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
