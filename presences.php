<?php
$pageTitle = 'Présences';
$hideRightSidebar = true;
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();
$canEdit = hasFullAccess();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$canEdit && in_array($action, ['add', 'edit', 'delete'], true)) {
    header('Location: presences.php');
    exit();
}
if (!$canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: presences.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        if (!csrf_validate()) {
            header('Location: presences.php?error=csrf');
            exit();
        }
        $employe_id = (int) ($_POST['employe_id'] ?? 0);
        $date_presence = $_POST['date_presence'] ?? '';
        $heure_arrivee = !empty($_POST['heure_arrivee']) ? $_POST['heure_arrivee'] : null;
        $heure_depart = !empty($_POST['heure_depart']) ? $_POST['heure_depart'] : null;
        $statut = in_array($_POST['statut'] ?? '', ['présent', 'absent', 'retard', 'congé']) ? $_POST['statut'] : 'présent';
        $remarques = trim($_POST['remarques'] ?? '');

        $heures_travaillees = 0;
        if ($heure_arrivee && $heure_depart) {
            $arrivee = new DateTime($heure_arrivee);
            $depart = new DateTime($heure_depart);
            $diff = $arrivee->diff($depart);
            $heures_travaillees = $diff->h + ($diff->i / 60) + ($diff->s / 3600);
        }

        if ($employe_id && $date_presence) {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO presences (employe_id, date_presence, heure_arrivee, heure_depart, heures_travaillees, statut, remarques) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$employe_id, $date_presence, $heure_arrivee, $heure_depart, $heures_travaillees, $statut, $remarques ?: null]);
                    // Audit: création de présence
                    $newData = [
                        'employe_id'        => $employe_id,
                        'date_presence'     => $date_presence,
                        'heure_arrivee'     => $heure_arrivee,
                        'heure_depart'      => $heure_depart,
                        'heures_travaillees'=> $heures_travaillees,
                        'statut'            => $statut,
                        'remarques'         => $remarques ?: null,
                    ];
                    $row = $db->run('SELECT LAST_INSERT_ID() AS id')->fetch();
                    $newId = isset($row['id']) ? (int) $row['id'] : null;
                    logActivity($db, 'CREATE', 'presences', $newId, null, $newData);
                } else {
                    // Charger l'ancienne présence avant mise à jour
                    $oldStmt = $db->prepare("SELECT * FROM presences WHERE id = ?");
                    $oldStmt->execute([$id]);
                    $old = $oldStmt->fetch() ?: null;

                    $stmt = $db->prepare("UPDATE presences SET employe_id=?, date_presence=?, heure_arrivee=?, heure_depart=?, heures_travaillees=?, statut=?, remarques=? WHERE id=?");
                    $stmt->execute([$employe_id, $date_presence, $heure_arrivee, $heure_depart, $heures_travaillees, $statut, $remarques ?: null, $id]);
                    $new = [
                        'id'                => $id,
                        'employe_id'        => $employe_id,
                        'date_presence'     => $date_presence,
                        'heure_arrivee'     => $heure_arrivee,
                        'heure_depart'      => $heure_depart,
                        'heures_travaillees'=> $heures_travaillees,
                        'statut'            => $statut,
                        'remarques'         => $remarques ?: null,
                    ];
                    logActivity($db, 'UPDATE', 'presences', $id, $old, $new);
                }
                header('Location: presences.php?success=1');
                exit();
            } catch (PDOException $e) { /* duplicate or error */ }
        }
    }
}

if ($action === 'delete' && $id) {
    // Charger l'ancienne présence avant suppression
    $oldStmt = $db->prepare("SELECT * FROM presences WHERE id = ?");
    $oldStmt->execute([$id]);
    $old = $oldStmt->fetch() ?: null;

    $stmt = $db->prepare("DELETE FROM presences WHERE id = ?");
    $stmt->execute([$id]);
    logActivity($db, 'DELETE', 'presences', $id, $old, null);
    header('Location: presences.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $presence = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM presences WHERE id = ?");
        $stmt->execute([$id]);
        $presence = $stmt->fetch();
        if (!$presence) {
            header('Location: presences.php');
            exit();
        }
    }

    $stmt = $db->prepare("SELECT id, matricule, nom, prenom FROM employes WHERE statut = 'actif' ORDER BY nom, prenom");
    $stmt->execute([]);
    $employes = $stmt->fetchAll();
    require_once 'includes/header-dashboard.php';
    ?>
    <div class="app-page-content">
        <h1 class="h3 mb-4"><?= $action === 'add' ? 'Enregistrer une présence' : 'Modifier une présence' ?></h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Employé *</label>
                        <select class="form-select" name="employe_id" required>
                            <option value="">Sélectionner un employé</option>
                            <?php foreach ($employes as $emp): ?>
                                <option value="<?= (int) $emp['id'] ?>" <?= (isset($presence['employe_id']) && (int) $presence['employe_id'] === (int) $emp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(trim($emp['prenom'] . ' ' . $emp['nom'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Date *</label>
                            <input type="date" class="form-control" name="date_presence" value="<?= htmlspecialchars($presence['date_presence'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut *</label>
                            <select class="form-select" name="statut" required>
                                <option value="présent" <?= ($presence['statut'] ?? '') === 'présent' ? 'selected' : '' ?>>Présent</option>
                                <option value="absent" <?= ($presence['statut'] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option>
                                <option value="retard" <?= ($presence['statut'] ?? '') === 'retard' ? 'selected' : '' ?>>Retard</option>
                                <option value="congé" <?= ($presence['statut'] ?? '') === 'congé' ? 'selected' : '' ?>>Congé</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Heure d'arrivée</label>
                            <input type="time" class="form-control" name="heure_arrivee" value="<?= htmlspecialchars($presence['heure_arrivee'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Heure de départ</label>
                            <input type="time" class="form-control" name="heure_depart" value="<?= htmlspecialchars($presence['heure_depart'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">Remarques</label>
                        <textarea class="form-control" name="remarques" rows="3"><?= htmlspecialchars($presence['remarques'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <a href="presences.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
    <?php
    require_once 'includes/footer-dashboard.php';
    exit();
}

$date_filter = $_GET['date'] ?? date('Y-m-d');
$stmt = $db->prepare("SELECT p.*, e.matricule, e.nom, e.prenom FROM presences p JOIN employes e ON p.employe_id = e.id WHERE p.date_presence = ? ORDER BY p.heure_arrivee");
$stmt->execute([$date_filter]);
$presences = $stmt->fetchAll();
$total = count($presences);

require_once 'includes/header-dashboard.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Opération effectuée avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (isset($_GET['error']) && $_GET['error'] === 'csrf'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        Session expirée ou formulaire invalide. Veuillez réessayer.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="app-page-content">
    <h1 class="h3 mb-4">Gestion des Présences</h1>

    <div class="employes-search-bar mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if ($canEdit): ?><a href="presences.php?action=add" class="btn btn-success"><i class="bi bi-plus-circle"></i> Enregistrer une présence</a><?php endif; ?>
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                <input type="date" class="form-control form-control-sm" name="date" value="<?= htmlspecialchars($date_filter) ?>" style="width: auto;">
                <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
            </form>
        </div>
    </div>

    <div class="employes-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted small"><?= $total ?> présence<?= $total !== 1 ? 's' : '' ?> pour le <?= date('d/m/Y', strtotime($date_filter)) ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover employes-table">
            <thead>
                <tr>
                    <th scope="col">Employé</th>
                    <th scope="col">Date</th>
                    <th scope="col">Heure arrivée</th>
                    <th scope="col">Heure départ</th>
                    <th scope="col">Heures travaillées</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Remarques</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($presences)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune présence pour cette date.</td></tr>
                <?php else: ?>
                    <?php
                    $statut_badge = ['présent' => 'pres-ok', 'absent' => 'pres-absent', 'retard' => 'pres-retard', 'congé' => 'pres-conge'];
                    foreach ($presences as $pres):
                        $st = $pres['statut'] ?? 'présent';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(trim($pres['prenom'] . ' ' . $pres['nom'])) ?></td>
                            <td><?= date('d/m/Y', strtotime($pres['date_presence'])) ?></td>
                            <td><?= $pres['heure_arrivee'] ? date('H:i', strtotime($pres['heure_arrivee'])) : '—' ?></td>
                            <td><?= $pres['heure_depart'] ? date('H:i', strtotime($pres['heure_depart'])) : '—' ?></td>
                            <td><?= number_format((float) $pres['heures_travaillees'], 2) ?> h</td>
                            <td><span class="badge app-badge-statut app-badge-<?= e($statut_badge[$st] ?? 'pres-ok') ?>"><?= e(ucfirst($st)) ?></span></td>
                            <td><?= htmlspecialchars($pres['remarques'] ?? '') ?: '—' ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <a href="presences.php?action=edit&id=<?= (int) $pres['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <a href="presences.php?action=delete&id=<?= (int) $pres['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer cette présence ?');"><i class="bi bi-trash"></i></a>
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
