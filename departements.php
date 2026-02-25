<?php
$pageTitle = 'Départements';
$hideRightSidebar = true;
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();
$canEdit = hasFullAccess();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$canEdit && in_array($action, ['add', 'edit', 'delete'], true)) {
    header('Location: departements.php');
    exit();
}
if (!$canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: departements.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($nom !== '') {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO departements (nom, description) VALUES (?, ?)");
                $stmt->execute([$nom, $description ?: null]);
                header('Location: departements.php?success=1');
            } else {
                $stmt = $db->prepare("UPDATE departements SET nom=?, description=? WHERE id=?");
                $stmt->execute([$nom, $description ?: null, $id]);
                header('Location: departements.php?success=1');
            }
            exit();
        }
    }
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM departements WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: departements.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $departement = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM departements WHERE id = ?");
        $stmt->execute([$id]);
        $departement = $stmt->fetch();
        if (!$departement) {
            header('Location: departements.php');
            exit();
        }
    }
    require_once 'includes/header-dashboard.php';
    ?>
    <div class="app-page-content">
        <h1 class="h3 mb-4"><?= $action === 'add' ? 'Ajouter un département' : 'Modifier un département' ?></h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Nom du département *</label>
                        <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($departement['nom'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?= htmlspecialchars($departement['description'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <a href="departements.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
    <?php
    require_once 'includes/footer-dashboard.php';
    exit();
}

$stmt = $db->query("SELECT d.*, COUNT(e.id) as nb_employes FROM departements d LEFT JOIN employes e ON d.id = e.departement_id GROUP BY d.id ORDER BY d.nom");
$departements = $stmt->fetchAll();
$total = count($departements);

require_once 'includes/header-dashboard.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Opération effectuée avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="app-page-content">
    <h1 class="h3 mb-4">Départements</h1>

    <div class="employes-search-bar mb-3">
        <div class="d-flex gap-2 flex-wrap">
            <?php if ($canEdit): ?><a href="departements.php?action=add" class="btn btn-success"><i class="bi bi-plus-circle"></i> Ajouter un département</a><?php endif; ?>
        </div>
    </div>

    <div class="employes-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted small"><?= $total ?> département<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover employes-table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Nom</th>
                    <th scope="col">Description</th>
                    <th scope="col">Nombre d'employés</th>
                    <th scope="col">Date de création</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($departements)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun département.</td></tr>
                <?php else: ?>
                    <?php foreach ($departements as $dept): ?>
                        <tr>
                            <td><?= (int) $dept['id'] ?></td>
                            <td><strong><?= htmlspecialchars($dept['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($dept['description'] ?? '') ?: '—' ?></td>
                            <td><span class="badge app-badge-count"><?= (int) $dept['nb_employes'] ?></span></td>
                            <td><?= date('d/m/Y', strtotime($dept['date_creation'])) ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <a href="departements.php?action=edit&id=<?= (int) $dept['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <a href="departements.php?action=delete&id=<?= (int) $dept['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer ce département ?');"><i class="bi bi-trash"></i></a>
                                <?php else: ?><span class="text-muted small">Lecture seule</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer-dashboard.php'; ?>
