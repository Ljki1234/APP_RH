<?php
$pageTitle = 'Employés';
$hideRightSidebar = true;
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();
$canEdit = hasFullAccess();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

// DG = accès lecture seule : bloquer les actions
if (!$canEdit && in_array($action, ['add', 'edit', 'delete'], true)) {
    header('Location: employes.php');
    exit();
}
if (!$canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: employes.php');
    exit();
}

// Suppression
if ($action === 'delete' && $id) {
    try {
        $stmt = $db->prepare("DELETE FROM employes WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) { /* ignore */ }
    header('Location: employes.php?success=1');
    exit();
}

// Enregistrement (ajout / modification)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    $matricule = trim($_POST['matricule'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $date_naissance = !empty($_POST['date_naissance']) ? $_POST['date_naissance'] : null;
    $date_embauche = trim($_POST['date_embauche'] ?? '');
    $poste = trim($_POST['poste'] ?? '');
    $departement_id = !empty($_POST['departement_id']) ? (int) $_POST['departement_id'] : null;
    $salaire_base = isset($_POST['salaire_base']) ? (float) str_replace(',', '.', $_POST['salaire_base']) : 0;
    $statut = in_array($_POST['statut'] ?? '', ['actif', 'inactif', 'congé', 'démission']) ? $_POST['statut'] : 'actif';

    if ($matricule && $nom && $prenom && $email && $date_embauche && $poste) {
        try {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO employes (matricule, nom, prenom, email, telephone, adresse, date_naissance, date_embauche, poste, departement_id, salaire_base, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$matricule, $nom, $prenom, $email, $telephone ?: null, $adresse ?: null, $date_naissance, $date_embauche, $poste, $departement_id, $salaire_base, $statut]);
                header('Location: employes.php?success=1');
            } else {
                $stmt = $db->prepare("UPDATE employes SET matricule=?, nom=?, prenom=?, email=?, telephone=?, adresse=?, date_naissance=?, date_embauche=?, poste=?, departement_id=?, salaire_base=?, statut=? WHERE id=?");
                $stmt->execute([$matricule, $nom, $prenom, $email, $telephone ?: null, $adresse ?: null, $date_naissance, $date_embauche, $poste, $departement_id, $salaire_base, $statut, $id]);
                header('Location: employes.php?success=1');
            }
            exit();
        } catch (PDOException $e) {
            $formError = 'Erreur: ' . $e->getMessage();
        }
    } else {
        $formError = 'Veuillez remplir les champs obligatoires (matricule, nom, prénom, email, date d\'embauche, poste).';
    }
}

// Formulaire add/edit : charger l'employé en édition
$employe = null;
if ($action === 'edit' && $id) {
    $stmt = $db->prepare("SELECT e.*, d.nom as departement_nom FROM employes e LEFT JOIN departements d ON e.departement_id = d.id WHERE e.id = ?");
    $stmt->execute([$id]);
    $employe = $stmt->fetch();
    if (!$employe) {
        header('Location: employes.php');
        exit();
    }
}

// Liste : recherche, tri, pagination
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'date_embauche';
$order = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$page = max(1, (int) ($_GET['p'] ?? 1));
$perPage = 25;

$allowedSort = ['date_embauche', 'nom', 'prenom', 'email', 'poste'];
if (!in_array($sort, $allowedSort)) {
    $sort = 'date_embauche';
}

$where = "1=1";
$params = [];
if ($search !== '') {
    $where .= " AND (e.nom LIKE ? OR e.prenom LIKE ? OR e.matricule LIKE ? OR e.email LIKE ? OR e.poste LIKE ?)";
    $term = '%' . $search . '%';
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

$countSql = "SELECT COUNT(*) FROM employes e WHERE $where";
$stmt = $db->prepare($countSql);
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$orderCol = $sort === 'date_embauche' ? 'e.date_embauche' : 'e.' . $sort;
$sql = "SELECT e.*, d.nom as departement_nom FROM employes e LEFT JOIN departements d ON e.departement_id = d.id WHERE $where ORDER BY $orderCol $order LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$employes = $stmt->fetchAll();

// Départements pour les formulaires
$stmt = $db->query("SELECT id, nom FROM departements ORDER BY nom");
$departements = $stmt->fetchAll();

require_once 'includes/header-dashboard.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Opération effectuée avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <div class="app-page-content">
        <h1 class="h3 mb-4">
            <i class="bi bi-<?= $action === 'add' ? 'person-plus' : 'pencil' ?>"></i>
            <?= $action === 'add' ? 'Ajouter un employé' : 'Modifier l\'employé' ?>
        </h1>
        <?php if (!empty($formError)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($formError) ?></div>
        <?php endif; ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Matricule *</label>
                            <input type="text" class="form-control" name="matricule" value="<?= htmlspecialchars($employe['matricule'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" value="<?= htmlspecialchars($employe['nom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" value="<?= htmlspecialchars($employe['prenom'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($employe['email'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" class="form-control" name="telephone" value="<?= htmlspecialchars($employe['telephone'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Adresse</label>
                            <textarea class="form-control" name="adresse" rows="2"><?= htmlspecialchars($employe['adresse'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date de naissance</label>
                            <input type="date" class="form-control" name="date_naissance" value="<?= htmlspecialchars($employe['date_naissance'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Date d'embauche *</label>
                            <input type="date" class="form-control" name="date_embauche" value="<?= htmlspecialchars($employe['date_embauche'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Poste *</label>
                            <input type="text" class="form-control" name="poste" value="<?= htmlspecialchars($employe['poste'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Département</label>
                            <select class="form-select" name="departement_id">
                                <option value="">— Aucun —</option>
                                <?php foreach ($departements as $d): ?>
                                    <option value="<?= (int)$d['id'] ?>" <?= (isset($employe['departement_id']) && (int)$employe['departement_id'] === (int)$d['id']) ? 'selected' : '' ?>><?= htmlspecialchars($d['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Salaire base</label>
                            <input type="text" class="form-control" name="salaire_base" value="<?= htmlspecialchars($employe['salaire_base'] ?? '0') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="statut">
                                <option value="actif" <?= ($employe['statut'] ?? '') === 'actif' ? 'selected' : '' ?>>Actif</option>
                                <option value="inactif" <?= ($employe['statut'] ?? '') === 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                <option value="congé" <?= ($employe['statut'] ?? '') === 'congé' ? 'selected' : '' ?>>Congé</option>
                                <option value="démission" <?= ($employe['statut'] ?? '') === 'démission' ? 'selected' : '' ?>>Démission</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                            <a href="employes.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="app-page-content">
        <h1 class="h3 mb-4"><i class="bi bi-people"></i> Employés</h1>

        <div class="employes-search-bar mb-3">
            <form method="GET" action="employes.php" class="d-flex gap-2 flex-wrap">
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>">
                <input type="search" class="form-control" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher (nom, matricule, email, poste…)" style="max-width: 400px;">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
                <?php if ($canEdit): ?><a href="employes.php?action=add" class="btn btn-success"><i class="bi bi-person-plus"></i> Ajouter</a><?php endif; ?>
            </form>
        </div>

        <div class="employes-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <span class="text-muted small"><?= $total === 0 ? '0' : ($offset + 1) ?> – <?= min($offset + $perPage, $total) ?> sur <?= $total ?></span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover employes-table">
                <colgroup>
                    <col style="width: 40px;">
                    <col style="width: 100px;">
                    <col style="min-width: 180px;">
                    <col style="min-width: 180px;">
                    <col style="min-width: 140px;">
                    <col style="min-width: 140px;">
                    <col style="min-width: 120px;">
                    <col style="width: 120px;">
                </colgroup>
                <thead>
                    <tr>
                        <th scope="col"><input type="checkbox" class="form-check-input" aria-label="Tout sélectionner"></th>
                        <th scope="col">Statut</th>
                        <th scope="col">Employé</th>
                        <th scope="col">Email</th>
                        <th scope="col">Poste</th>
                        <th scope="col">Département</th>
                        <th scope="col">
                            <a href="employes.php?<?= http_build_query(array_merge($_GET, ['sort' => 'date_embauche', 'order' => $sort === 'date_embauche' && $order === 'DESC' ? 'asc' : 'desc'])) ?>" class="employes-sort-link-th text-decoration-none">
                                Date d'embauche <?= $sort === 'date_embauche' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($employes)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">Aucun employé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($employes as $emp): 
                            $prenom = $emp['prenom'] ?? '';
                            $nom = $emp['nom'] ?? '';
                            $initiales = strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: '?';
                            $statutLabel = ['actif' => 'Actif', 'inactif' => 'Inactif', 'congé' => 'Congé', 'démission' => 'Démission'];
                            $s = $emp['statut'] ?? 'actif';
                            $posteDisplay = trim($emp['poste'] ?? '') !== '' ? $emp['poste'] : '—';
                        ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input" value="<?= (int)$emp['id'] ?>" aria-label="Sélectionner"></td>
                                <td>
                                    <span class="status-dot status-<?= htmlspecialchars($s) ?>"></span>
                                    <?= htmlspecialchars($statutLabel[$s] ?? $s) ?>
                                </td>
                                <td class="employee-cell">
                                    <span class="employee-avatar"><?= $initiales ?></span>
                                    <span class="employee-name"><?= htmlspecialchars($prenom . ' ' . $nom) ?></span>
                                </td>
                                <td><?= htmlspecialchars($emp['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($posteDisplay) ?></td>
                                <td><?= htmlspecialchars($emp['departement_nom'] ?? '—') ?></td>
                                <td><?= !empty($emp['date_embauche']) ? date('d/m/Y', strtotime($emp['date_embauche'])) : '—' ?></td>
                                <td>
                                    <?php if ($canEdit): ?>
                                    <a href="employes.php?action=edit&id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                    <a href="employes.php?action=delete&id=<?= (int)$emp['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer cet employé ?');"><i class="bi bi-trash"></i></a>
                                    <?php else: ?><span class="text-muted small">Lecture seule</span><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-3" aria-label="Pagination">
                <ul class="pagination pagination-sm flex-wrap">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page <= 1 ? '#' : 'employes.php?' . http_build_query(array_merge($_GET, ['p' => $page - 1])) ?>">Précédent</a>
                    </li>
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="employes.php?<?= http_build_query(array_merge($_GET, ['p' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= $page >= $totalPages ? '#' : 'employes.php?' . http_build_query(array_merge($_GET, ['p' => $page + 1])) ?>">Suivant</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer-dashboard.php'; ?>
