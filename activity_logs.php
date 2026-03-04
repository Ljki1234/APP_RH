<?php
$pageTitle = 'Journal d\'activité';
$hideRightSidebar = true;
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();

// Admin + IT uniquement (même règle que canManageUsers)
if (!canManageUsers()) {
    header('Location: index.php');
    exit();
}

$db = getDB();

// Suppression d'une entrée du journal (admin/IT uniquement)
if (isset($_GET['do']) && $_GET['do'] === 'delete' && isset($_GET['log_id'])) {
    $logId = (int) $_GET['log_id'];
    if ($logId > 0) {
        $db->run('DELETE FROM activity_logs WHERE id = ?', [$logId]);
    }
    $redirectParams = [];
    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') $redirectParams['user_id'] = $_GET['user_id'];
    if (isset($_GET['action']) && trim($_GET['action']) !== '') $redirectParams['action'] = trim($_GET['action']);
    if (isset($_GET['table']) && trim($_GET['table']) !== '') $redirectParams['table'] = trim($_GET['table']);
    header('Location: activity_logs.php?' . http_build_query($redirectParams + ['deleted' => '1']));
    exit();
}

// Filtres simples
$actionFilter = trim($_GET['action'] ?? '');
$tableFilter  = trim($_GET['table'] ?? '');
$userFilter   = isset($_GET['user_id']) && $_GET['user_id'] !== '' ? (int) $_GET['user_id'] : null;

$where = [];
$params = [];

if ($actionFilter !== '') {
    $where[] = 'l.action = ?';
    $params[] = $actionFilter;
}
if ($tableFilter !== '') {
    $where[] = 'l.table_name = ?';
    $params[] = $tableFilter;
}
if ($userFilter !== null) {
    $where[] = 'l.user_id = ?';
    $params[] = $userFilter;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Derniers 200 logs
$sql = "SELECT l.*, u.nom_utilisateur, u.email 
        FROM activity_logs l 
        LEFT JOIN utilisateurs u ON l.user_id = u.id
        $whereSql
        ORDER BY l.created_at DESC, l.id DESC
        LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
$totalLogs = count($logs);

// Pour le filtre utilisateurs
$usersStmt = $db->prepare("SELECT id, nom_utilisateur, email FROM utilisateurs ORDER BY nom_utilisateur");
$usersStmt->execute([]);
$users = $usersStmt->fetchAll();

// Pour affichage lisible des employés (employe_id -> matricule + nom)
$empStmt = $db->prepare("SELECT id, matricule, nom, prenom FROM employes");
$empStmt->execute([]);
$employeesMap = [];
foreach ($empStmt->fetchAll() as $e) {
    $fullName = trim(($e['prenom'] ?? '') . ' ' . ($e['nom'] ?? ''));
    $employeesMap[(int)$e['id']] = [
        'matricule' => $e['matricule'] ?? '',
        'name'      => $fullName !== '' ? $fullName : ('ID ' . (int)$e['id'])
    ];
}

require_once 'includes/header-dashboard.php';
?>

<div class="app-page-content">
    <h1 class="h3 mb-4">Journal d'activité</h1>

    <div class="employes-search-bar mb-3">
        <form method="GET" action="activity_logs.php" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="user_id" class="form-select" style="max-width: 260px;">
                <option value="">Tous les utilisateurs</option>
                <?php foreach ($users as $u): ?>
                    <option value="<?= (int)$u['id'] ?>" <?= $userFilter === (int)$u['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nom_utilisateur'] . ' (' . $u['email'] . ')') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Rechercher</button>
            <a href="activity_logs.php" class="btn btn-outline-secondary">Réinitialiser</a>
        </form>
    </div>

    <div class="employes-toolbar d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <span class="text-muted small"><?= $totalLogs ?> activité<?= $totalLogs !== 1 ? 's' : '' ?></span>
    </div>

    <div class="dashboard-card logs-card">
        <div class="table-responsive">
        <table class="table table-hover employes-table logs-table">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Utilisateur</th>
                    <th scope="col">Action</th>
                    <th scope="col">Old</th>
                    <th scope="col">New</th>
                    <th scope="col">Agent</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune activité enregistrée.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                        $oldArr = null;
                        $newArr = null;
                        $action = strtoupper((string)($log['action'] ?? ''));
                        $tableName = (string)($log['table_name'] ?? '');

                        // Classe couleur du badge
                        $actionClass = 'bg-secondary';
                        if (in_array($action, ['CREATE', 'LOGIN', 'LOGIN_CHALLENGE'], true)) {
                            $actionClass = 'bg-success logs-action-create';
                        } elseif (in_array($action, ['UPDATE', 'ROLE_CHANGE'], true)) {
                            $actionClass = 'bg-info logs-action-update';
                        } elseif (in_array($action, ['DELETE', 'FAILED_LOGIN', 'PASSWORD_CHANGE'], true)) {
                            $actionClass = 'bg-danger logs-action-delete';
                        }

                        // Libellé lisible en français selon l'action + la table
                        $actionLabel = $action;
                        switch ($action) {
                            case 'CREATE':
                                switch ($tableName) {
                                    case 'employes':      $actionLabel = "Ajout d'un employé"; break;
                                    case 'departements':  $actionLabel = "Création d'un département"; break;
                                    case 'conges':        $actionLabel = "Création d'une demande de congé"; break;
                                    case 'presences':     $actionLabel = "Enregistrement d'une présence"; break;
                                    case 'salaires':      $actionLabel = "Création d'un salaire"; break;
                                    case 'utilisateurs':  $actionLabel = "Création d'un utilisateur"; break;
                                    default:              $actionLabel = "Création d'un enregistrement"; break;
                                }
                                break;
                            case 'UPDATE':
                                switch ($tableName) {
                                    case 'employes':      $actionLabel = "Mise à jour d'un employé"; break;
                                    case 'departements':  $actionLabel = "Mise à jour d'un département"; break;
                                    case 'conges':        $actionLabel = "Mise à jour d'un congé"; break;
                                    case 'presences':     $actionLabel = "Mise à jour d'une présence"; break;
                                    case 'salaires':      $actionLabel = "Mise à jour d'un salaire"; break;
                                    case 'utilisateurs':  $actionLabel = "Mise à jour d'un utilisateur"; break;
                                    default:              $actionLabel = "Mise à jour d'un enregistrement"; break;
                                }
                                break;
                            case 'DELETE':
                                switch ($tableName) {
                                    case 'employes':      $actionLabel = "Suppression d'un employé"; break;
                                    case 'departements':  $actionLabel = "Suppression d'un département"; break;
                                    case 'conges':        $actionLabel = "Suppression d'un congé"; break;
                                    case 'presences':     $actionLabel = "Suppression d'une présence"; break;
                                    case 'salaires':      $actionLabel = "Suppression d'un salaire"; break;
                                    case 'utilisateurs':  $actionLabel = "Suppression d'un utilisateur"; break;
                                    default:              $actionLabel = "Suppression d'un enregistrement"; break;
                                }
                                break;
                            case 'LOGIN':
                                $actionLabel = "Connexion réussie"; break;
                            case 'LOGIN_CHALLENGE':
                                $actionLabel = "Début de connexion (MFA)"; break;
                            case 'LOGOUT':
                                $actionLabel = "Déconnexion"; break;
                            case 'FAILED_LOGIN':
                                $actionLabel = "Tentative de connexion échouée"; break;
                            case 'ROLE_CHANGE':
                                $actionLabel = "Changement de rôle utilisateur"; break;
                            case 'PASSWORD_CHANGE':
                                $actionLabel = "Changement de mot de passe"; break;
                            default:
                                $actionLabel = $action !== '' ? ucfirst(strtolower($action)) : 'Action';
                                break;
                        }
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($log['created_at']) ?></td>
                            <td>
                                <?php if ($log['user_id']): ?>
                                    <?= htmlspecialchars($log['nom_utilisateur'] ?? '') ?><br>
                                    <small class="text-muted"><?= htmlspecialchars($log['email'] ?? '') ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Système / Anonyme</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $actionClass ?>" title="<?= htmlspecialchars($action) ?>">
                                    <?= htmlspecialchars($actionLabel) ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ($log['old_value'] !== null) {
                                    $oldArr = json_decode($log['old_value'], true);
                                }
                                ?>
                                <?php if (!empty($oldArr) && is_array($oldArr)): ?>
                                    <ul class="list-unstyled small mb-0 logs-diff-list">
                                        <?php
                                        $i = 0;
                                        foreach ($oldArr as $k => $v):
                                            if ($k === 'id') {
                                                continue;
                                            }
                                            $labelKey = $k;
                                            $display = $v;
                                            if ($k === 'employe_id') {
                                                $idVal = (int)$v;
                                                $empInfo = $employeesMap[$idVal] ?? ['matricule' => (string)$idVal, 'name' => ''];
                                                // Matricule
                                                $i++;
                                                if ($i > 6) {
                                                    echo '<li class="text-muted">…</li>';
                                                    break;
                                                }
                                        ?>
                                                <li><strong><?= e('matricule') ?>:</strong> <?= e((string)$empInfo['matricule']) ?></li>
                                        <?php
                                                // Employé (nom complet)
                                                $i++;
                                                if ($i > 6) {
                                                    echo '<li class="text-muted">…</li>';
                                                    break;
                                                }
                                        ?>
                                                <li><strong><?= e('employé') ?>:</strong> <?= e((string)$empInfo['name']) ?></li>
                                        <?php
                                                continue;
                                            } else {
                                                $display = (is_scalar($v) || $v === null) ? (string)$v : json_encode($v);
                                            }
                                            $i++;
                                            if ($i > 6) {
                                                echo '<li class="text-muted">…</li>';
                                                break;
                                            }
                                        ?>
                                            <li><strong><?= e((string)$labelKey) ?>:</strong> <?= e((string)$display) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                if ($log['new_value'] !== null) {
                                    $newArr = json_decode($log['new_value'], true);
                                }
                                ?>
                                <?php if (!empty($newArr) && is_array($newArr)): ?>
                                    <ul class="list-unstyled small mb-0 logs-diff-list">
                                        <?php
                                        $j = 0;
                                        foreach ($newArr as $k => $v):
                                            if ($k === 'id') {
                                                continue;
                                            }
                                            $labelKey = $k;
                                            $display = $v;
                                            if ($k === 'employe_id') {
                                                $idVal = (int)$v;
                                                $empInfo = $employeesMap[$idVal] ?? ['matricule' => (string)$idVal, 'name' => ''];
                                                // Matricule
                                                $j++;
                                                if ($j > 6) {
                                                    echo '<li class="text-muted">…</li>';
                                                    break;
                                                }
                                        ?>
                                                <li><strong><?= e('matricule') ?>:</strong> <?= e((string)$empInfo['matricule']) ?></li>
                                        <?php
                                                // Employé
                                                $j++;
                                                if ($j > 6) {
                                                    echo '<li class="text-muted">…</li>';
                                                    break;
                                                }
                                        ?>
                                                <li><strong><?= e('employé') ?>:</strong> <?= e((string)$empInfo['name']) ?></li>
                                        <?php
                                                continue;
                                            } else {
                                                $display = (is_scalar($v) || $v === null) ? (string)$v : json_encode($v);
                                            }
                                            $j++;
                                            if ($j > 6) {
                                                echo '<li class="text-muted">…</li>';
                                                break;
                                            }
                                        ?>
                                            <li><strong><?= e((string)$labelKey) ?>:</strong> <?= e((string)$display) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="small text-muted"><?= htmlspecialchars(mb_strimwidth($log['user_agent'], 0, 60, '…')) ?></span></td>
                            <td>
                                <a href="activity_logs.php?do=delete&log_id=<?= (int)$log['id'] ?><?= $userFilter !== null ? '&user_id=' . (int)$userFilter : '' ?><?= $actionFilter !== '' ? '&action=' . rawurlencode($actionFilter) : '' ?><?= $tableFilter !== '' ? '&table=' . rawurlencode($tableFilter) : '' ?>"
                                   class="btn btn-sm btn-outline-danger js-confirm-delete"
                                   title="Supprimer ce log"
                                   data-confirm="Supprimer cette entrée du journal ?">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div><!-- /.dashboard-card -->
</div>

<?php require_once 'includes/footer-dashboard.php'; ?>

