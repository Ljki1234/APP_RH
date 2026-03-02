<?php
require_once __DIR__ . '/../config/auth.php';
requireLogin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Gestion RH') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-page">
    <nav class="navbar navbar-expand-lg navbar-dark navbar-hr" style="background: #c41e3a;">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-people-fill"></i> Gestion RH
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-speedometer2"></i> Tableau de bord
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['PHP_SELF']) === 'employes.php' ? 'active' : '') ?>" href="employes.php">
                            <i class="bi bi-people"></i> Employés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="departements.php">
                            <i class="bi bi-building"></i> Départements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="conges.php">
                            <i class="bi bi-calendar-check"></i> Congés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="salaires.php">
                            <i class="bi bi-cash-coin"></i> Salaires
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="presences.php">
                            <i class="bi bi-clock-history"></i> Présences
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($currentUser['nom_utilisateur']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="bi bi-box-arrow-right"></i> Déconnexion
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container-fluid mt-4">
