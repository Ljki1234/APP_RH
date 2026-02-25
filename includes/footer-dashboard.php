<?php
$currentUser = $currentUser ?? getCurrentUser();
$showRightSidebar = empty($hideRightSidebar);
$userName = ($currentUser && isset($currentUser['nom_utilisateur'])) ? trim($currentUser['nom_utilisateur']) : '';
$parts = $userName !== '' ? explode(' ', $userName, 2) : [''];
$last = isset($parts[1]) ? strtoupper(htmlspecialchars($parts[1])) : strtoupper(htmlspecialchars($parts[0]));
$first = isset($parts[1]) ? ucfirst(htmlspecialchars($parts[0])) : '';
$roleLabels = ['dg' => 'Directeur Général', 'admin' => 'Administrateur', 'rh' => 'Ressources Humaines', 'it' => 'Informatique'];
$roleLabel = isset($currentUser['role']) && isset($roleLabels[$currentUser['role']]) ? $roleLabels[$currentUser['role']] : 'Directeur gestion des Ressources Humaines';
?>
        </div>
    </main>
    <?php if ($showRightSidebar): ?>
    <aside class="dashboard-sidebar-right">
        <div class="right-profile-card">
            <div class="right-profile-avatar"><i class="bi bi-person-fill"></i></div>
            <div class="right-profile-name"><?= $last ?> <?= $first ?></div>
            <div class="right-profile-role"><?= htmlspecialchars($roleLabel) ?></div>
        </div>
        <ul class="right-metrics">
            <?php if (!empty($metricsSidebar) && is_array($metricsSidebar)): ?>
                <?php foreach ($metricsSidebar as $m): ?>
                <li>
                    <span><span class="metric-num"><?= (int) ($m['value'] ?? 0) ?></span> <?= htmlspecialchars($m['label'] ?? '') ?> <?= !empty($m['change']) ? '<span class="metric-change">(' . htmlspecialchars($m['change']) . ')</span>' : '' ?></span>
                    <span class="metric-arrow"><i class="bi bi-chevron-right"></i></span>
                </li>
                <?php endforeach; ?>
            <?php else: ?>
            <li>
                <span><span class="metric-num"><?= $metrics['taches'] ?? 37 ?></span> Tâches <span class="metric-change">(<?= $metrics['taches_plus'] ?? '+4' ?>)</span></span>
                <span class="metric-arrow"><i class="bi bi-chevron-right"></i></span>
            </li>
            <li>
                <span><span class="metric-num"><?= $metrics['demandes_rh'] ?? 5 ?></span> Demandes RH <span class="metric-change">(<?= $metrics['demandes_rh_plus'] ?? '+3' ?>)</span></span>
                <span class="metric-arrow"><i class="bi bi-chevron-right"></i></span>
            </li>
            <li>
                <span><span class="metric-num"><?= $metrics['conges'] ?? 5 ?></span> Demande(s) de congés <span class="metric-change">(<?= $metrics['conges_plus'] ?? '+2' ?>)</span></span>
                <span class="metric-arrow"><i class="bi bi-chevron-right"></i></span>
            </li>
            <li>
                <span><span class="metric-num"><?= $metrics['entretiens'] ?? 138 ?></span> Entretien(s) <span class="metric-change">(<?= $metrics['entretiens_plus'] ?? '+16' ?>)</span></span>
                <span class="metric-arrow"><i class="bi bi-chevron-right"></i></span>
            </li>
            <li>
                <span><span class="metric-num"><?= $metrics['candidatures'] ?? 6 ?></span> Candidature(s) en attente</span>
                <span class="metric-arrow"><i class="bi bi-chevron-right"></i></span>
            </li>
            <?php endif; ?>
        </ul>
        <?php
        $mois_fr = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
        $d1 = strtotime('first day of this month');
        $d2 = strtotime('last day of this month');
        $label_timeline = date('d', $d1) . ' ' . $mois_fr[(int)date('n', $d1)] . ' - ' . date('d', $d2) . ' ' . $mois_fr[(int)date('n', $d2)] . ' ' . date('Y', $d2);
        ?>
        <div class="timeline-title"><?= $label_timeline ?></div>
        <div class="timeline-days">
            <?php for ($i = 1; $i <= 7; $i++): ?>
                <span><?= ['L', 'M', 'M', 'J', 'V', 'S', 'D'][($i - 1) % 7] ?><?= $i ?></span>
            <?php endfor; ?>
        </div>
        <div class="timeline-bars">
            <div class="timeline-bar-row eval" style="width: 100%;"></div>
            <div class="timeline-bar-row quiz" style="width: 60%;"></div>
            <div class="timeline-bar-row formation" style="width: 80%;"></div>
            <div class="timeline-bar-row absence" style="width: 20%;"></div>
            <div class="timeline-bar-row action" style="width: 45%;"></div>
            <div class="timeline-bar-row session" style="width: 90%;"></div>
            <div class="timeline-bar-row encours" style="width: 70%;"></div>
        </div>
        <div class="timeline-legend">
            <span><span class="dot" style="background:#5bc0de"></span> Évaluations</span>
            <span><span class="dot" style="background:#f0ad4e"></span> Quiz / enquêtes</span>
            <span><span class="dot" style="background:#e8a0a8"></span> Formations</span>
            <span><span class="dot" style="background:#333"></span> Absences</span>
            <span><span class="dot" style="background:#a8d8ea"></span> Action individuelle</span>
            <span><span class="dot" style="background:#5cb85c"></span> Session d'encadrement</span>
            <span><span class="dot" style="background:#f0e68c"></span> En cours</span>
        </div>
    </aside>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
