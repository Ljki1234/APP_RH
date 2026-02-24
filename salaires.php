<?php
$pageTitle = 'Salaires';
$hideRightSidebar = true;
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();
$canEdit = hasFullAccess();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$mois_noms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

if (!$canEdit && in_array($action, ['add', 'edit', 'delete'], true)) {
    header('Location: salaires.php');
    exit();
}
if (!$canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: salaires.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $employe_id = (int) ($_POST['employe_id'] ?? 0);
        $mois = (int) ($_POST['mois'] ?? 0);
        $annee = (int) ($_POST['annee'] ?? date('Y'));
        $salaire_base = (float) str_replace(',', '.', $_POST['salaire_base'] ?? 0);
        $prime = (float) str_replace(',', '.', $_POST['prime'] ?? 0);
        $heures_supplementaires = (float) str_replace(',', '.', $_POST['heures_supplementaires'] ?? 0);
        $montant_heures_sup = (float) str_replace(',', '.', $_POST['montant_heures_sup'] ?? 0);
        $retenues = (float) str_replace(',', '.', $_POST['retenues'] ?? 0);
        $date_paiement = !empty($_POST['date_paiement']) ? $_POST['date_paiement'] : null;
        $statut = in_array($_POST['statut'] ?? '', ['en_attente', 'payé', 'annulé']) ? $_POST['statut'] : 'en_attente';
        $salaire_net = $salaire_base + $prime + $montant_heures_sup - $retenues;

        if ($employe_id && $mois >= 1 && $mois <= 12 && $annee >= 2020) {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO salaires (employe_id, mois, annee, salaire_base, prime, heures_supplementaires, montant_heures_sup, retenues, salaire_net, date_paiement, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$employe_id, $mois, $annee, $salaire_base, $prime, $heures_supplementaires, $montant_heures_sup, $retenues, $salaire_net, $date_paiement, $statut]);
                } else {
                    $stmt = $db->prepare("UPDATE salaires SET employe_id=?, mois=?, annee=?, salaire_base=?, prime=?, heures_supplementaires=?, montant_heures_sup=?, retenues=?, salaire_net=?, date_paiement=?, statut=? WHERE id=?");
                    $stmt->execute([$employe_id, $mois, $annee, $salaire_base, $prime, $heures_supplementaires, $montant_heures_sup, $retenues, $salaire_net, $date_paiement, $statut, $id]);
                }
                header('Location: salaires.php?success=1');
                exit();
            } catch (PDOException $e) { /* duplicate or error */ }
        }
    }
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM salaires WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: salaires.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $salaire = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM salaires WHERE id = ?");
        $stmt->execute([$id]);
        $salaire = $stmt->fetch();
        if (!$salaire) {
            header('Location: salaires.php');
            exit();
        }
    }

    $stmt = $db->query("SELECT id, matricule, nom, prenom, salaire_base FROM employes WHERE statut = 'actif' ORDER BY nom, prenom");
    $employes = $stmt->fetchAll();
    require_once 'includes/header-dashboard.php';
    ?>
    <div class="app-page-content">
        <h1 class="h3 mb-4">
            <i class="bi bi-<?= $action === 'add' ? 'cash-stack' : 'pencil' ?>"></i>
            <?= $action === 'add' ? 'Ajouter un salaire' : 'Modifier un salaire' ?>
        </h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="" id="salaireForm">
                    <div class="mb-3">
                        <label class="form-label">Employé *</label>
                        <select class="form-select" name="employe_id" id="employe_id" required>
                            <option value="">Sélectionner un employé</option>
                            <?php foreach ($employes as $emp): ?>
                                <option value="<?= (int) $emp['id'] ?>" data-salaire="<?= (float) ($emp['salaire_base'] ?? 0) ?>" <?= (isset($salaire['employe_id']) && (int) $salaire['employe_id'] === (int) $emp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(trim($emp['prenom'] . ' ' . $emp['nom'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Mois *</label>
                            <select class="form-select" name="mois" required>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <option value="<?= $i ?>" <?= (isset($salaire['mois']) && (int) $salaire['mois'] === $i) ? 'selected' : '' ?>><?= $mois_noms[$i] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Année *</label>
                            <input type="number" class="form-control" name="annee" value="<?= (int) ($salaire['annee'] ?? date('Y')) ?>" min="2020" max="2100" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="statut">
                                <option value="en_attente" <?= ($salaire['statut'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="payé" <?= ($salaire['statut'] ?? '') === 'payé' ? 'selected' : '' ?>>Payé</option>
                                <option value="annulé" <?= ($salaire['statut'] ?? '') === 'annulé' ? 'selected' : '' ?>>Annulé</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <h5 class="mb-3">Détails du salaire</h5>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Salaire de base *</label>
                            <input type="number" step="0.01" class="form-control" name="salaire_base" id="salaire_base" value="<?= (float) ($salaire['salaire_base'] ?? 0) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prime</label>
                            <input type="number" step="0.01" class="form-control" name="prime" id="prime" value="<?= (float) ($salaire['prime'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Heures supplémentaires</label>
                            <input type="number" step="0.01" class="form-control" name="heures_supplementaires" id="heures_supplementaires" value="<?= (float) ($salaire['heures_supplementaires'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Montant heures sup</label>
                            <input type="number" step="0.01" class="form-control" name="montant_heures_sup" id="montant_heures_sup" value="<?= (float) ($salaire['montant_heures_sup'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Retenues</label>
                            <input type="number" step="0.01" class="form-control" name="retenues" id="retenues" value="<?= (float) ($salaire['retenues'] ?? 0) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Salaire net</label>
                            <input type="number" step="0.01" class="form-control" name="salaire_net" id="salaire_net" value="<?= (float) ($salaire['salaire_net'] ?? 0) ?>" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date de paiement</label>
                            <input type="date" class="form-control" name="date_paiement" value="<?= htmlspecialchars($salaire['date_paiement'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                        <a href="salaires.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php
    require_once 'includes/footer-dashboard.php';
    exit();
}

$mois_filter = isset($_GET['mois']) && $_GET['mois'] !== '' ? (int) $_GET['mois'] : null;
$annee_filter = isset($_GET['annee']) && $_GET['annee'] !== '' ? (int) $_GET['annee'] : null;
if ($mois_filter !== null && ($mois_filter < 1 || $mois_filter > 12)) $mois_filter = null;
if ($annee_filter !== null && ($annee_filter < 2020 || $annee_filter > 2100)) $annee_filter = null;

if ($mois_filter !== null && $annee_filter !== null) {
    $stmt = $db->prepare("SELECT s.*, e.matricule, e.nom, e.prenom FROM salaires s JOIN employes e ON s.employe_id = e.id WHERE s.mois = ? AND s.annee = ? ORDER BY s.annee DESC, s.mois DESC, s.date_creation DESC");
    $stmt->execute([$mois_filter, $annee_filter]);
} else {
    $stmt = $db->query("SELECT s.*, e.matricule, e.nom, e.prenom FROM salaires s JOIN employes e ON s.employe_id = e.id ORDER BY s.annee DESC, s.mois DESC, s.date_creation DESC");
}
$salaires = $stmt->fetchAll();
$total_net = array_sum(array_column($salaires, 'salaire_net'));
$total = count($salaires);

require_once 'includes/header-dashboard.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Opération effectuée avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="app-page-content">
    <h1 class="h3 mb-4"><i class="bi bi-cash-coin"></i> Gestion des Salaires</h1>

    <div class="employes-search-bar mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if ($canEdit): ?><a href="salaires.php?action=add" class="btn btn-success"><i class="bi bi-cash-stack"></i> Ajouter un salaire</a><?php endif; ?>
            <form method="GET" class="salaires-filter-form d-flex flex-wrap align-items-center">
                <select class="form-select form-select-sm salaires-filter-select" name="mois">
                    <option value="" <?= $mois_filter === null ? 'selected' : '' ?>>Toutes les périodes</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?= $i ?>" <?= $mois_filter === $i ? 'selected' : '' ?>><?= $mois_noms[$i] ?></option>
                    <?php endfor; ?>
                </select>
                <input type="number" class="form-control form-control-sm salaires-filter-year" name="annee" value="<?= $annee_filter ?? date('Y') ?>" min="2020" max="2100" placeholder="Année">
                <button type="submit" class="btn btn-primary btn-sm salaires-filter-btn"><i class="bi bi-funnel"></i> Filtrer</button>
            </form>
        </div>
    </div>

    <div class="employes-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted small"><?= $total ?> salaire<?= $total !== 1 ? 's' : '' ?> — Total nets: <strong><?= number_format($total_net, 2, ',', ' ') ?> MAD</strong></span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover employes-table">
            <thead>
                <tr>
                    <th scope="col">Employé</th>
                    <th scope="col">Période</th>
                    <th scope="col">Salaire base</th>
                    <th scope="col">Prime</th>
                    <th scope="col">Heures sup</th>
                    <th scope="col">Retenues</th>
                    <th scope="col">Salaire net</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($salaires)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4"><?= $mois_filter === null ? 'Aucun salaire enregistré.' : 'Aucun salaire pour cette période.' ?></td></tr>
                <?php else: ?>
                    <?php
                    $statut_badge = ['en_attente' => 'salaire-attente', 'payé' => 'salaire-ok', 'annulé' => 'salaire-annule'];
                    foreach ($salaires as $sal):
                        $st = $sal['statut'] ?? 'en_attente';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(trim($sal['prenom'] . ' ' . $sal['nom'])) ?></td>
                            <td><?= $mois_noms[(int) $sal['mois']] ?> <?= (int) $sal['annee'] ?></td>
                            <td><?= number_format((float) $sal['salaire_base'], 2, ',', ' ') ?> MAD</td>
                            <td><?= number_format((float) $sal['prime'], 2, ',', ' ') ?> MAD</td>
                            <td><?= number_format((float) $sal['montant_heures_sup'], 2, ',', ' ') ?> MAD</td>
                            <td><?= number_format((float) $sal['retenues'], 2, ',', ' ') ?> MAD</td>
                            <td><strong><?= number_format((float) $sal['salaire_net'], 2, ',', ' ') ?> MAD</strong></td>
                            <td><span class="badge app-badge-statut app-badge-<?= $statut_badge[$st] ?? 'salaire-attente' ?>"><?= ucfirst($st) ?></span></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <a href="salaires.php?action=edit&id=<?= (int) $sal['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <a href="salaires.php?action=delete&id=<?= (int) $sal['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer ce salaire ?');"><i class="bi bi-trash"></i></a>
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
