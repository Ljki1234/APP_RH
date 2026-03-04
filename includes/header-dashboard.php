<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Tableau de bord') ?> - Gestion RH</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/theme-advanced.css">
</head>
<body class="dashboard-page <?= in_array($currentPage, ['employes.php', 'departements.php', 'conges.php', 'salaires.php', 'presences.php', 'utilisateurs.php', 'activity_logs.php']) ? 'app-page' : '' ?> <?= $currentPage === 'employes.php' ? 'page-employes' : '' ?> <?= $currentPage === 'departements.php' ? 'page-departements' : '' ?> <?= $currentPage === 'conges.php' ? 'page-conges' : '' ?> <?= $currentPage === 'salaires.php' ? 'page-salaires' : '' ?> <?= $currentPage === 'presences.php' ? 'page-presences' : '' ?> <?= $currentPage === 'utilisateurs.php' ? 'page-utilisateurs' : '' ?> <?= $currentPage === 'activity_logs.php' ? 'page-activity-logs' : '' ?> <?= !empty($hideRightSidebar) ? 'no-right-sidebar' : '' ?>">
<div class="dashboard-layout <?= !empty($hideRightSidebar) ? 'no-right-sidebar' : '' ?>">
    <aside class="dashboard-sidebar-left">
        <div class="dashboard-logo">HR MAPS</div>
        <nav class="dashboard-nav">
            <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>" title="Tableau de bord"><i class="bi bi-speedometer2"></i></a>
            <a href="employes.php" class="<?= $currentPage === 'employes.php' ? 'active' : '' ?>" title="Employés"><i class="bi bi-people"></i></a>
            <a href="departements.php" class="<?= $currentPage === 'departements.php' ? 'active' : '' ?>" title="Départements"><i class="bi bi-building"></i></a>
            <a href="conges.php" class="<?= $currentPage === 'conges.php' ? 'active' : '' ?>" title="Congés"><i class="bi bi-calendar-check"></i></a>
            <a href="salaires.php" class="<?= $currentPage === 'salaires.php' ? 'active' : '' ?>" title="Salaires"><i class="bi bi-cash-coin"></i></a>
            <a href="presences.php" class="<?= $currentPage === 'presences.php' ? 'active' : '' ?>" title="Présences"><i class="bi bi-clock-history"></i></a>
            <?php if (canManageUsers()): ?>
                <a href="utilisateurs.php" class="<?= $currentPage === 'utilisateurs.php' ? 'active' : '' ?>" title="Utilisateurs"><i class="bi bi-person-gear"></i></a>
                <a href="activity_logs.php" class="<?= $currentPage === 'activity_logs.php' ? 'active' : '' ?>" title="Journal d'activité"><i class="bi bi-activity"></i></a>
            <?php endif; ?>
            <a href="logout.php" class="dashboard-nav-logout" title="Déconnexion"><i class="bi bi-box-arrow-right"></i></a>
        </nav>
    </aside>
    <main class="dashboard-main">
        <div class="dashboard-banner"></div>
        <div class="dashboard-main-inner">
