<?php
/**
 * Crée des fiches de paie pour le mois en cours pour tous les employés actifs
 * qui n'en ont pas encore. À exécuter une fois pour voir des données sur la page Salaires.
 * Supprimez ce fichier après utilisation.
 */
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');

$mois = (int) date('n');
$annee = (int) date('Y');
$mois_noms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

try {
    $db = getDB();
    $stmt = $db->query("SELECT id, nom, prenom, salaire_base FROM employes WHERE statut = 'actif'");
    $employes = $stmt->fetchAll();
    if (empty($employes)) {
        echo '<h1>Aucun employé actif</h1><p>Ajoutez d\'abord des employés avec un salaire de base.</p><p><a href="login.php">Connexion</a></p>';
        exit;
    }

    $created = 0;
    foreach ($employes as $emp) {
        $stmt = $db->prepare("SELECT id FROM salaires WHERE employe_id = ? AND mois = ? AND annee = ?");
        $stmt->execute([$emp['id'], $mois, $annee]);
        if ($stmt->fetch()) continue; // déjà une fiche pour ce mois

        $salaire_base = (float) ($emp['salaire_base'] ?? 0);
        if ($salaire_base <= 0) $salaire_base = 150000;
        $prime = 0;
        $heures_sup = 0;
        $montant_heures_sup = 0;
        $retenues = 0;
        $salaire_net = $salaire_base;

        $stmt = $db->prepare("INSERT INTO salaires (employe_id, mois, annee, salaire_base, prime, heures_supplementaires, montant_heures_sup, retenues, salaire_net, statut) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'en_attente')");
        $stmt->execute([$emp['id'], $mois, $annee, $salaire_base, $prime, $heures_sup, $montant_heures_sup, $retenues, $salaire_net]);
        $created++;
    }

    echo '<h1>Fiches de paie créées</h1>';
    echo '<p><strong>' . $created . '</strong> fiche(s) de paie créée(s) pour ' . $mois_noms[$mois] . ' ' . $annee . '.</p>';
    echo '<p><a href="salaires.php">Voir la page Salaires</a></p>';
    echo '<p style="color:#666; margin-top:2rem;">Vous pouvez supprimer ce fichier <code>install_salaires_sample.php</code> après utilisation.</p>';
} catch (PDOException $e) {
    echo '<h1>Erreur</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
}
