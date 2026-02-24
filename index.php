<?php
$pageTitle = 'Tableau de bord';
require_once 'config/database.php';
require_once 'config/auth.php';
requireLogin();
$canEdit = hasFullAccess();
$currentUser = getCurrentUser();
$isDG = isset($currentUser['role']) && $currentUser['role'] === 'dg';

$db = getDB();

// Stats pour les widgets
try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM employes WHERE statut = 'actif'");
    $stats['employes_actifs'] = (int) $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM departements");
    $stats['departements'] = (int) $stmt->fetch()['total'];

    $stmt = $db->query("SELECT COUNT(*) as total FROM conges WHERE statut = 'en_attente'");
    $stats['conges_attente'] = (int) $stmt->fetch()['total'];

    $mois_actuel = date('n');
    $annee_actuelle = date('Y');
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM salaires WHERE mois = ? AND annee = ?");
    $stmt->execute([$mois_actuel, $annee_actuelle]);
    $stats['salaires_mois'] = (int) $stmt->fetch()['total'];

    // Derniers employés (collaborateurs arrivés)
    $stmt = $db->query("SELECT e.*, d.nom as departement_nom FROM employes e LEFT JOIN departements d ON e.departement_id = d.id ORDER BY e.date_embauche DESC, e.date_creation DESC LIMIT 5");
    $derniers_employes = $stmt->fetchAll();

    // Congés récents
    $stmt = $db->query("SELECT c.*, e.nom, e.prenom FROM conges c JOIN employes e ON c.employe_id = e.id ORDER BY c.date_demande DESC LIMIT 5");
    $conges_recents = $stmt->fetchAll();

    // Soldes congés par type (pour la carte Solde congé restant)
    $stmt = $db->query("SELECT type_conge, COUNT(*) as nb FROM conges WHERE statut = 'approuvé' AND date_fin >= CURDATE() GROUP BY type_conge");
    $soldes_conges = [];
    while ($row = $stmt->fetch()) {
        $soldes_conges[$row['type_conge']] = (int) $row['nb'];
    }

    if ($isDG) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM presences WHERE date_presence = CURDATE()");
        $stats['presences_aujourdhui'] = (int) $stmt->fetch()['total'];
        $stmt = $db->prepare("SELECT COALESCE(SUM(salaire_net), 0) as total FROM salaires WHERE mois = ? AND annee = ?");
        $stmt->execute([$mois_actuel, $annee_actuelle]);
        $stats['masse_salariale_mois'] = (float) $stmt->fetch()['total'];
    }
} catch (PDOException $e) {
    header('Content-Type: text/html; charset=utf-8');
    die('<h1>Erreur base de données</h1><p>Une requête a échoué. Vérifiez que toutes les tables existent (importez <code>database.sql</code>).</p><p><small>' . htmlspecialchars($e->getMessage()) . '</small></p>');
}

require_once 'includes/header-dashboard.php';

// Métriques pour la sidebar droite
$metrics = [
    'taches' => $stats['employes_actifs'] + 30,
    'taches_plus' => '+4',
    'demandes_rh' => 5,
    'demandes_rh_plus' => '+3',
    'conges' => $stats['conges_attente'],
    'conges_plus' => '+2',
    'entretiens' => 138,
    'entretiens_plus' => '+16',
    'candidatures' => 6
];

if ($isDG) {
    $metricsSidebar = [
        ['label' => 'Effectif actif', 'value' => $stats['employes_actifs'], 'change' => ''],
        ['label' => 'Départements', 'value' => $stats['departements'], 'change' => ''],
        ['label' => 'Congés en attente', 'value' => $stats['conges_attente'], 'change' => ''],
        ['label' => 'Fiches de paie (mois)', 'value' => $stats['salaires_mois'], 'change' => ''],
        ['label' => 'Présences aujourd\'hui', 'value' => $stats['presences_aujourdhui'] ?? 0, 'change' => ''],
    ];
} else {
    $metricsSidebar = null;
}

$mois_fr = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
?>

<div class="dashboard-cards">
<?php if ($isDG): ?>
    <!-- Tableau de bord DG : vue stratégique -->
    <div class="dashboard-row">
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Vue d'ensemble</h3>
            <ul class="alert-list">
                <li>
                    <span class="alert-badge"><?= $stats['employes_actifs'] ?></span>
                    <span class="alert-text">Effectif actif</span>
                    <a href="employes.php" class="alert-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="alert-badge"><?= $stats['departements'] ?></span>
                    <span class="alert-text">Départements</span>
                    <a href="departements.php" class="alert-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="alert-badge"><?= $stats['conges_attente'] ?></span>
                    <span class="alert-text">Congés en attente</span>
                    <a href="conges.php" class="alert-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </div>
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Indicateurs clés</h3>
            <ul class="solde-list">
                <li>
                    <span class="solde-badge"><?= number_format($stats['masse_salariale_mois'] ?? 0, 0, ',', ' ') ?></span>
                    <span class="solde-text">Masse salariale (mois) MAD</span>
                    <a href="salaires.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="solde-badge"><?= $stats['salaires_mois'] ?></span>
                    <span class="solde-text">Fiches de paie ce mois</span>
                    <a href="salaires.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="solde-badge"><?= $stats['presences_aujourdhui'] ?? 0 ?></span>
                    <span class="solde-text">Présences aujourd'hui</span>
                    <a href="presences.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </div>
    </div>

    <div class="dashboard-row">
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Synthèse congés</h3>
            <ul class="solde-list">
                <li>
                    <span class="solde-badge"><?= $soldes_conges['annuel'] ?? 0 ?></span>
                    <span class="solde-text">Congé payé (en cours)</span>
                    <a href="conges.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="solde-badge"><?= $soldes_conges['maladie'] ?? 0 ?></span>
                    <span class="solde-text">Maladie</span>
                    <a href="conges.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="solde-badge"><?= $soldes_conges['exceptionnel'] ?? 0 ?></span>
                    <span class="solde-text">RTT / Exceptionnel</span>
                    <a href="conges.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </div>
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Derniers collaborateurs arrivés</h3>
            <div class="collab-row">
                <?php if (empty($derniers_employes)): ?>
                    <p class="text-muted small mb-0">Aucun collaborateur</p>
                <?php else: ?>
                    <?php foreach ($derniers_employes as $emp):
                        $prenom = $emp['prenom'] ?? '';
                        $nom = $emp['nom'] ?? '';
                        $ts = $emp['date_embauche'] ? strtotime($emp['date_embauche']) : time();
                    ?>
                        <a href="employes.php" class="collab-item text-decoration-none text-dark">
                            <div class="collab-avatar"><?= strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: '?' ?></div>
                            <span class="collab-name"><?= htmlspecialchars($prenom) ?></span>
                            <span class="collab-date"><?= (int)date('j', $ts) ?> <?= $mois_fr[(int)date('n', $ts)] ?? '' ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="dashboard-row">
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Demandes de congés récentes</h3>
            <?php if (empty($conges_recents)): ?>
                <p class="text-muted small mb-0">Aucune demande récente</p>
            <?php else: ?>
                <ul class="alert-list">
                    <?php foreach (array_slice($conges_recents, 0, 5) as $c): ?>
                        <li>
                            <span class="alert-badge"><?= $c['type_conge'] ?? '—' ?></span>
                            <span class="alert-text"><?= htmlspecialchars(($c['prenom'] ?? '') . ' ' . ($c['nom'] ?? '')) ?> — <?= date('d/m/Y', strtotime($c['date_debut'] ?? 'now')) ?></span>
                            <a href="conges.php" class="alert-arrow"><i class="bi bi-chevron-right"></i></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Résumé du mois</h3>
            <p class="text-muted small mb-1"><?= ucfirst($mois_fr[$mois_actuel] ?? '') ?> <?= $annee_actuelle ?></p>
            <p class="mb-0 small">Effectif : <strong><?= $stats['employes_actifs'] ?></strong> · Congés en attente : <strong><?= $stats['conges_attente'] ?></strong> · Fiches de paie : <strong><?= $stats['salaires_mois'] ?></strong></p>
        </div>
    </div>
<?php else: ?>
    <!-- Tableau de bord RH / Admin / IT -->
    <div class="dashboard-row">
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Alertes</h3>
            <ul class="alert-list">
                <li>
                    <span class="alert-badge"><?= min(7, $stats['employes_actifs'] + 2) ?></span>
                    <span class="alert-text">Gestion des employes</span>
                    <a href="employes.php" class="alert-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="alert-badge"><?= min(4, $stats['departements'] + 2) ?></span>
                    <span class="alert-text">Gestion des départements</span>
                    <a href="departements.php" class="alert-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="alert-badge"><?= $stats['conges_attente'] ?: 4 ?></span>
                    <span class="alert-text">Gestion des congés</span>
                    <a href="conges.php" class="alert-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </div>
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Solde congé restant</h3>
            <ul class="solde-list">
                <li>
                    <span class="solde-badge"><?= $soldes_conges['annuel'] ?? 7 ?></span>
                    <span class="solde-text">Congé payé</span>
                    <a href="conges.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="solde-badge"><?= $soldes_conges['maladie'] ?? 4 ?></span>
                    <span class="solde-text">Maladie</span>
                    <a href="conges.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
                <li>
                    <span class="solde-badge"><?= $soldes_conges['exceptionnel'] ?? 4 ?></span>
                    <span class="solde-text">RTT</span>
                    <a href="conges.php" class="solde-arrow"><i class="bi bi-chevron-right"></i></a>
                </li>
            </ul>
        </div>
    </div>

    <div class="dashboard-row">
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">
                Mes compétences
                <span class="card-meta">78% <i class="bi bi-three-dots"></i></span>
            </h3>
            <div class="skills-chart-legend">
                <span><span class="dot-requis"></span> Requis</span>
                <span><span class="dot-niveau"></span> Niveau présumé</span>
            </div>
            <div class="skills-bars">
                <div class="skills-bar-group">
                    <div class="skills-bar-wrap">
                        <div class="skills-bar-requis" style="height: 50%;"></div>
                        <div class="skills-bar-niveau" style="height: 80%;"></div>
                    </div>
                    <span class="skills-bar-label">Hard Skills</span>
                </div>
                <div class="skills-bar-group">
                    <div class="skills-bar-wrap">
                        <div class="skills-bar-requis" style="height: 60%;"></div>
                        <div class="skills-bar-niveau" style="height: 70%;"></div>
                    </div>
                    <span class="skills-bar-label">Soft Skills</span>
                </div>
                <div class="skills-bar-group">
                    <div class="skills-bar-wrap">
                        <div class="skills-bar-requis" style="height: 70%;"></div>
                        <div class="skills-bar-niveau" style="height: 75%;"></div>
                    </div>
                    <span class="skills-bar-label">Autonomie</span>
                </div>
                <div class="skills-bar-group">
                    <div class="skills-bar-wrap">
                        <div class="skills-bar-requis" style="height: 55%;"></div>
                        <div class="skills-bar-niveau" style="height: 65%;"></div>
                    </div>
                    <span class="skills-bar-label">Langues</span>
                </div>
            </div>
        </div>
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Les cinq principales compétences</h3>
            <div class="skills-legend-dots">
                <span><span class="legend-dot-red"></span> Gap &gt; 1</span>
                <span><span class="legend-dot-orange"></span> Gap = 1</span>
                <span><span class="legend-dot-blue"></span> Atteint</span>
            </div>
            <ul class="skill-progress-list">
                <li>
                    <div class="skill-progress-bar-wrap"><div class="skill-progress-bar gap-high" style="width: 25%;"></div></div>
                    <span class="skill-progress-fraction">1/4</span>
                </li>
                <li>
                    <div class="skill-progress-bar-wrap"><div class="skill-progress-bar gap-mid" style="width: 66%;"></div></div>
                    <span class="skill-progress-fraction">2/3</span>
                </li>
                <li>
                    <div class="skill-progress-bar-wrap"><div class="skill-progress-bar gap-ok" style="width: 100%;"></div></div>
                    <span class="skill-progress-fraction">4/4</span>
                </li>
                <li>
                    <div class="skill-progress-bar-wrap"><div class="skill-progress-bar gap-ok" style="width: 100%;"></div></div>
                    <span class="skill-progress-fraction">4/4</span>
                </li>
                <li>
                    <div class="skill-progress-bar-wrap"><div class="skill-progress-bar gap-mid" style="width: 50%;"></div></div>
                    <span class="skill-progress-fraction">1/2</span>
                </li>
            </ul>
        </div>
    </div>

    <div class="dashboard-row">
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Derniers collaborateurs arrivés</h3>
            <div class="collab-row">
                <?php if (empty($derniers_employes)): ?>
                    <p class="text-muted small mb-0">Aucun collaborateur</p>
                <?php else: ?>
                    <?php foreach ($derniers_employes as $emp):
                        $prenom = $emp['prenom'] ?? '';
                        $nom = $emp['nom'] ?? '';
                        $ts = $emp['date_embauche'] ? strtotime($emp['date_embauche']) : time();
                    ?>
                        <a href="<?= $canEdit ? 'employes.php?action=edit&id=' . (int)$emp['id'] : 'employes.php' ?>" class="collab-item text-decoration-none text-dark">
                            <div class="collab-avatar"><?= strtoupper(substr($prenom, 0, 1) . substr($nom, 0, 1)) ?: '?' ?></div>
                            <span class="collab-name"><?= htmlspecialchars($prenom) ?></span>
                            <span class="collab-date"><?= (int)date('j', $ts) ?> <?= $mois_fr[(int)date('n', $ts)] ?? '' ?></span>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="dashboard-card">
            <h3 class="dashboard-card-title">Annonces de mobilité interne</h3>
            <div class="mobility-card">
                <div class="mobility-title">
                    Directeur Technique
                    <span class="mobility-badge">Dec</span>
                </div>
                <div class="mobility-details">
                    Service: France<br>
                    Niveau d'étude: Bac+5<br>
                    Expérience: 2-5 ans
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
</div>

<?php require_once 'includes/footer-dashboard.php'; ?>
