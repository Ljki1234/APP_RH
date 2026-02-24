<?php
$pageTitle = 'Congés';
$hideRightSidebar = true;
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();
$canEdit = hasFullAccess();

$db = getDB();
$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

if (!$canEdit && in_array($action, ['add', 'edit', 'delete', 'traiter'], true)) {
    header('Location: conges.php');
    exit();
}
if (!$canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: conges.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $employe_id = (int) ($_POST['employe_id'] ?? 0);
        $type_conge = $_POST['type_conge'] ?? '';
        $date_debut = $_POST['date_debut'] ?? '';
        $date_fin = $_POST['date_fin'] ?? '';
        $motif = trim($_POST['motif'] ?? '');

        $valid_types = ['annuel', 'maladie', 'maternité', 'paternité', 'exceptionnel', 'sans_solde'];
        if ($employe_id && in_array($type_conge, $valid_types) && $date_debut && $date_fin) {
            $date1 = new DateTime($date_debut);
            $date2 = new DateTime($date_fin);
            $nombre_jours = $date1->diff($date2)->days + 1;

            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO conges (employe_id, type_conge, date_debut, date_fin, nombre_jours, motif) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$employe_id, $type_conge, $date_debut, $date_fin, $nombre_jours, $motif ?: null]);
            } else {
                $stmt = $db->prepare("UPDATE conges SET employe_id=?, type_conge=?, date_debut=?, date_fin=?, nombre_jours=?, motif=? WHERE id=?");
                $stmt->execute([$employe_id, $type_conge, $date_debut, $date_fin, $nombre_jours, $motif ?: null, $id]);
            }
            header('Location: conges.php?success=1');
            exit();
        }
    }
}

if ($action === 'traiter' && $id) {
    $statut = $_GET['statut'] ?? '';
    if (in_array($statut, ['approuvé', 'refusé'])) {
        $stmt = $db->prepare("SELECT c.*, e.email as employe_email, e.prenom, e.nom FROM conges c JOIN employes e ON c.employe_id = e.id WHERE c.id = ?");
        $stmt->execute([$id]);
        $conge = $stmt->fetch();
        $user_id = $_SESSION['user_id'] ?? null;
        $stmt = $db->prepare("UPDATE conges SET statut=?, date_traitement=NOW(), traite_par=? WHERE id=?");
        $stmt->execute([$statut, $user_id, $id]);

        if ($conge && !empty($conge['employe_email'])) {
            require_once __DIR__ . '/config/mail.php';
            $prenom = $conge['prenom'] ?? '';
            $type = $conge['type_conge'] ?? '';
            $debut = $conge['date_debut'] ?? '';
            $fin = $conge['date_fin'] ?? '';
            if ($statut === 'approuvé') {
                $sujet = 'Votre demande de congé a été approuvée';
                $corps = '<p>Bonjour ' . htmlspecialchars($prenom) . ',</p><p>Votre demande de congé (<strong>' . htmlspecialchars($type) . '</strong>) du ' . htmlspecialchars($debut) . ' au ' . htmlspecialchars($fin) . ' a été <strong>approuvée</strong>.</p><p>Cordialement,<br>L\'équipe RH</p>';
            } else {
                $sujet = 'Votre demande de congé n\'a pas été retenue';
                $corps = '<p>Bonjour ' . htmlspecialchars($prenom) . ',</p><p>Votre demande de congé (<strong>' . htmlspecialchars($type) . '</strong>) du ' . htmlspecialchars($debut) . ' au ' . htmlspecialchars($fin) . ' a été <strong>refusée</strong>.</p><p>Pour toute question, contactez le service RH.</p><p>Cordialement,<br>L\'équipe RH</p>';
            }
            sendMail($conge['employe_email'], $sujet, $corps);
        }

        header('Location: conges.php?success=1');
        exit();
    }
}

if ($action === 'delete' && $id) {
    $stmt = $db->prepare("DELETE FROM conges WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: conges.php?success=1');
    exit();
}

if ($action === 'add' || $action === 'edit') {
    $conge = null;
    if ($action === 'edit' && $id) {
        $stmt = $db->prepare("SELECT * FROM conges WHERE id = ?");
        $stmt->execute([$id]);
        $conge = $stmt->fetch();
        if (!$conge) {
            header('Location: conges.php');
            exit();
        }
    }

    $stmt = $db->query("SELECT id, matricule, nom, prenom FROM employes WHERE statut = 'actif' ORDER BY nom, prenom");
    $employes = $stmt->fetchAll();
    require_once 'includes/header-dashboard.php';
    ?>
    <div class="app-page-content">
        <h1 class="h3 mb-4">
            <i class="bi bi-<?= $action === 'add' ? 'calendar-plus' : 'pencil' ?>"></i>
            <?= $action === 'add' ? 'Demander un congé' : 'Modifier un congé' ?>
        </h1>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Employé *</label>
                        <select class="form-select" name="employe_id" required>
                            <option value="">Sélectionner un employé</option>
                            <?php foreach ($employes as $emp): ?>
                                <option value="<?= (int) $emp['id'] ?>" <?= (isset($conge['employe_id']) && (int) $conge['employe_id'] === (int) $emp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(trim($emp['prenom'] . ' ' . $emp['nom'])) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type de congé *</label>
                        <select class="form-select" name="type_conge" required>
                            <option value="">Sélectionner un type</option>
                            <option value="annuel" <?= ($conge['type_conge'] ?? '') === 'annuel' ? 'selected' : '' ?>>Annuel</option>
                            <option value="maladie" <?= ($conge['type_conge'] ?? '') === 'maladie' ? 'selected' : '' ?>>Maladie</option>
                            <option value="maternité" <?= ($conge['type_conge'] ?? '') === 'maternité' ? 'selected' : '' ?>>Maternité</option>
                            <option value="paternité" <?= ($conge['type_conge'] ?? '') === 'paternité' ? 'selected' : '' ?>>Paternité</option>
                            <option value="exceptionnel" <?= ($conge['type_conge'] ?? '') === 'exceptionnel' ? 'selected' : '' ?>>Exceptionnel</option>
                            <option value="sans_solde" <?= ($conge['type_conge'] ?? '') === 'sans_solde' ? 'selected' : '' ?>>Sans solde</option>
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Date de début *</label>
                            <input type="date" class="form-control" name="date_debut" value="<?= htmlspecialchars($conge['date_debut'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de fin *</label>
                            <input type="date" class="form-control" name="date_fin" value="<?= htmlspecialchars($conge['date_fin'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3 mt-2">
                        <label class="form-label">Motif</label>
                        <textarea class="form-control" name="motif" rows="3"><?= htmlspecialchars($conge['motif'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
                    <a href="conges.php" class="btn btn-secondary">Annuler</a>
                </form>
            </div>
        </div>
    </div>
    <?php
    require_once 'includes/footer-dashboard.php';
    exit();
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'en_attente') {
    $where = "WHERE c.statut = 'en_attente'";
} elseif ($filter === 'approuvé') {
    $where = "WHERE c.statut = 'approuvé'";
} elseif ($filter === 'refusé') {
    $where = "WHERE c.statut = 'refusé'";
}

$stmt = $db->query("SELECT c.*, e.matricule, e.nom, e.prenom FROM conges c JOIN employes e ON c.employe_id = e.id $where ORDER BY c.date_demande DESC");
$conges = $stmt->fetchAll();
$total = count($conges);

require_once 'includes/header-dashboard.php';
?>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Opération effectuée avec succès.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="app-page-content">
    <h1 class="h3 mb-4"><i class="bi bi-calendar-check"></i> Congés</h1>

    <div class="employes-search-bar mb-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <?php if ($canEdit): ?><a href="conges.php?action=add" class="btn btn-success"><i class="bi bi-calendar-plus"></i> Demander un congé</a><?php endif; ?>
            <div class="conges-filter-group btn-group" role="group">
                <a href="conges.php" class="btn btn-<?= $filter === 'all' ? 'primary' : 'outline-primary' ?> btn-sm">Tous</a>
                <a href="conges.php?filter=en_attente" class="btn btn-<?= $filter === 'en_attente' ? 'warning' : 'outline-warning' ?> btn-sm">En attente</a>
                <a href="conges.php?filter=approuvé" class="btn btn-<?= $filter === 'approuvé' ? 'success' : 'outline-success' ?> btn-sm">Approuvés</a>
                <a href="conges.php?filter=refusé" class="btn btn-<?= $filter === 'refusé' ? 'danger' : 'outline-danger' ?> btn-sm">Refusés</a>
            </div>
        </div>
    </div>

    <div class="employes-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted small"><?= $total ?> congé<?= $total !== 1 ? 's' : '' ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover employes-table">
            <thead>
                <tr>
                    <th scope="col">Employé</th>
                    <th scope="col">Type</th>
                    <th scope="col">Période</th>
                    <th scope="col">Jours</th>
                    <th scope="col">Statut</th>
                    <th scope="col">Date demande</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($conges)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun congé.</td></tr>
                <?php else: ?>
                    <?php
                    $statut_badge = ['en_attente' => 'conge-attente', 'approuvé' => 'conge-ok', 'refusé' => 'conge-refuse'];
                    $statut_text = ['en_attente' => 'En attente', 'approuvé' => 'Approuvé', 'refusé' => 'Refusé'];
                    foreach ($conges as $c):
                        $s = $c['statut'] ?? 'en_attente';
                    ?>
                        <tr>
                            <td><?= htmlspecialchars(trim($c['prenom'] . ' ' . $c['nom'])) ?></td>
                            <td><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $c['type_conge']))) ?></td>
                            <td><?= date('d/m/Y', strtotime($c['date_debut'])) ?> – <?= date('d/m/Y', strtotime($c['date_fin'])) ?></td>
                            <td><strong><?= (int) $c['nombre_jours'] ?></strong> jour(s)</td>
                            <td><span class="badge app-badge-statut app-badge-<?= $statut_badge[$s] ?? 'conge-attente' ?>"><?= $statut_text[$s] ?? $s ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($c['date_demande'])) ?></td>
                            <td>
                                <?php if ($canEdit): ?>
                                <?php if ($s === 'en_attente'): ?>
                                    <a href="conges.php?action=traiter&id=<?= (int) $c['id'] ?>&statut=approuvé" class="btn btn-sm btn-success" title="Approuver" onclick="return confirm('Approuver ce congé ?');"><i class="bi bi-check-lg"></i></a>
                                    <a href="conges.php?action=traiter&id=<?= (int) $c['id'] ?>&statut=refusé" class="btn btn-sm btn-danger" title="Refuser" onclick="return confirm('Refuser ce congé ?');"><i class="bi bi-x-lg"></i></a>
                                <?php endif; ?>
                                <a href="conges.php?action=edit&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="bi bi-pencil"></i></a>
                                <a href="conges.php?action=delete&id=<?= (int) $c['id'] ?>" class="btn btn-sm btn-outline-danger" title="Supprimer" onclick="return confirm('Supprimer ce congé ?');"><i class="bi bi-trash"></i></a>
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
