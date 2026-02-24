<?php
$pageTitle = 'Utilisateurs';
$hideRightSidebar = true;
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();
if (!isAdmin()) {
    header('Location: index.php');
    exit();
}

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$roles = ['admin' => 'Admin', 'rh' => 'RH', 'it' => 'IT', 'dg' => 'DG'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'rh';
        if (!array_key_exists($role, $roles)) $role = 'rh';

        if ($nom_utilisateur !== '' && $email !== '') {
            try {
                if ($action === 'add') {
                    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
                    if (strlen($mot_de_passe) < 4) {
                        $error = 'Le mot de passe doit contenir au moins 4 caractères.';
                    } else {
                        $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                        $stmt = $db->prepare("INSERT INTO utilisateurs (nom_utilisateur, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$nom_utilisateur, $email, $hash, $role]);
                        header('Location: utilisateurs.php?success=1');
                        exit();
                    }
                } else {
                    $stmt = $db->prepare("UPDATE utilisateurs SET nom_utilisateur=?, email=?, role=? WHERE id=?");
                    $stmt->execute([$nom_utilisateur, $email, $role, $id]);
                    $mot_de_passe = trim($_POST['mot_de_passe'] ?? '');
                    if ($mot_de_passe !== '') {
                        $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);
                        $up = $db->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?");
                        $up->execute([$hash, $id]);
                    }
                    header('Location: utilisateurs.php?success=1');
                    exit();
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) $error = 'Ce nom d\'utilisateur ou cet email est déjà utilisé.';
                else $error = 'Erreur lors de l\'enregistrement.';
            }
        } else {
            $error = 'Nom d\'utilisateur et email obligatoires.';
        }
    }
}

if ($action === 'delete' && $id) {
    $current_id = $_SESSION['user_id'] ?? 0;
    if ((int) $id !== (int) $current_id) {
        $stmt = $db->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id]);
    }
    header('Location: utilisateurs.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $utilisateur = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM utilisateurs WHERE id = ?");
        $stmt->execute([$id]);
        $utilisateur = $stmt->fetch();
        if (!$utilisateur) {
            header('Location: utilisateurs.php');
            exit();
        }
    }
    require_once 'includes/header-dashboard.php';
    ?>
    <div class="app-page-content">
        <h1 class="h3 mb-4">
            <i class="bi bi-<?= $action === 'add' ? 'person-plus' : 'pencil' ?>"></i>
            <?= $action === 'add' ? 'Ajouter un utilisateur' : 'Modifier un utilisateur' ?>
        </h1>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" name="nom_utilisateur" value="<?= htmlspecialchars($utilisateur['nom_utilisateur'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($utilisateur['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Mot de passe <?= $action === 'edit' ? '(laisser vide pour ne pas changer)' : '*' ?></label>
                        <input type="password" class="form-control" name="mot_de_passe" <?= $action === 'add' ? 'required' : '' ?> minlength="4" autocomplete="new-password">
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Rôle *</label>
                        <div class="d-flex flex-wrap gap-3">
                            <?php foreach ($roles as $val => $label): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="role_<?= $val ?>" value="<?= htmlspecialchars($val) ?>" <?= ($utilisateur['role'] ?? 'rh') === $val ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="role_<?= $val ?>"><?= htmlspecialchars($label) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <a href="utilisateurs.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
    <?php
    require_once 'includes/footer-dashboard.php';
    exit();
}

$stmt = $db->query("SELECT id, nom_utilisateur, email, role, date_creation FROM utilisateurs ORDER BY nom_utilisateur");
$utilisateurs = $stmt->fetchAll();
$total = count($utilisateurs);

require_once 'includes/header-dashboard.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Opération effectuée avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="app-page-content">
    <h1 class="h3 mb-4"><i class="bi bi-people"></i> Gestion des utilisateurs</h1>

    <div class="employes-search-bar mb-3">
        <div class="d-flex gap-2 flex-wrap">
            <a href="utilisateurs.php?action=add" class="btn btn-success"><i class="bi bi-person-plus"></i> Ajouter un utilisateur</a>
        </div>
    </div>

    <div class="employes-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted small"><?= $total ?> utilisateur<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover employes-table">
            <thead>
                <tr>
                    <th scope="col">ID</th>
                    <th scope="col">Nom d'utilisateur</th>
                    <th scope="col">Email</th>
                    <th scope="col">Rôle</th>
                    <th scope="col">Date de création</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($utilisateurs)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun utilisateur.</td></tr>
                <?php else: ?>
                    <?php foreach ($utilisateurs as $u): ?>
                        <tr>
                            <td><?= (int) $u['id'] ?></td>
                            <td><strong><?= htmlspecialchars($u['nom_utilisateur']) ?></strong></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($roles[$u['role']] ?? $u['role']) ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($u['date_creation'])) ?></td>
                            <td>
                                <a href="utilisateurs.php?action=edit&id=<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <?php if ((int) $u['id'] !== (int) ($_SESSION['user_id'] ?? 0)): ?>
                                <a href="utilisateurs.php?action=delete&id=<?= (int) $u['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer cet utilisateur ?');"><i class="bi bi-trash"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer-dashboard.php'; ?>
