# Audit anti–injection SQL – Gestion RH

**Date:** 2025-03-02  
**Périmètre:** Projet PHP (hors `vendor/`)

## 1. Résumé

- **Requêtes avec variables dynamiques :** aucune interpolation directe de variables utilisateur dans les chaînes SQL. Les parties dynamiques (filtres, recherche, pagination) utilisent des requêtes préparées et des paramètres liés.
- **`$db->query()` :** toutes les utilisations concernaient des requêtes **statiques** (aucune variable dans la chaîne). Elles ont été converties en `prepare()->execute([])` pour uniformiser l’usage et limiter les risques en cas d’évolution du code.
- **ORDER BY / LIMIT / OFFSET :** contrôlés par liste blanche ou cast explicite.

## 2. Patterns recherchés

| Pattern | Résultat |
|--------|----------|
| `$db->query()` avec variables dans la chaîne | Aucun (chaînes statiques uniquement) |
| Interpolation `"… $var …"` dans du SQL | Aucune (corrigée précédemment) |
| Concatenation de SQL avec entrée utilisateur | Aucune ; seules des constantes ou identifiants whitelistés sont concaténés |
| Noms de tables/colonnes dynamiques | Aucun |
| ORDER BY non validé | Aucun ; `employes.php` utilise une whitelist `$allowedSort` et `ASC`/`DESC` forcé |
| LIMIT/OFFSET non castés | Aucun ; `employes.php` utilise `(int) $perPage` et `(int) $offset` |

## 3. Fichiers audités

### 3.1 `config/database.php`
- Connexion PDO avec `ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `ATTR_EMULATE_PREPARES => false`.
- **Multi-statement désactivé :** `PDO::MYSQL_ATTR_MULTI_STATEMENTS => false` (si disponible) pour interdire l’exécution de plusieurs requêtes en un seul appel.
- **Wrapper centralisé (SafeDB) :** `getDB()` retourne un wrapper qui n’expose que `prepare()`, `run()`, `exec()` (DDL uniquement), et les méthodes de transaction. La méthode `query()` n’existe pas, ce qui impose l’usage de requêtes préparées.
- Aucun fragment SQL brut ne doit être construit à partir d’entrées utilisateur ; les commentaires du wrapper le rappellent.

### 3.2 `index.php`
- **Avant :** plusieurs `$db->query()` avec SQL entièrement statique.
- **Après :** remplacés par `$db->prepare($sql)->execute([])`.
- Requêtes avec paramètres (mois/année) déjà en `prepare()` + `execute([...])`.

### 3.3 `employes.php`
- **WHERE :** clause construite en chaîne fixe `"1=1"` ou `"1=1 AND (e.nom LIKE ? ...)"` ; la recherche est passée uniquement via `$params` (liée aux `?`). **Sûr.**
- **ORDER BY :** colonne = `$orderCol` dérivé de `$sort` après `in_array($sort, $allowedSort)` ; sens = `ASC` ou `DESC` forcé. **Sûr.**
- **LIMIT / OFFSET :** `(int) $perPage`, `(int) $offset`. **Sûr.**
- Liste des départements : ancien `query()` statique → converti en `prepare()->execute([])`.

### 3.4 `conges.php`
- Filtre statut : `$whereSql` et `$params` avec `?` ; pas d’interpolation de valeur utilisateur dans la chaîne. **Sûr.**
- Liste employés (formulaires) : ancien `query()` statique → converti en `prepare()->execute([])`.

### 3.5 `departements.php`
- Toutes les requêtes avec données utilisateur utilisent déjà `prepare()` + paramètres liés.
- Liste principale : ancien `query()` statique → converti en `prepare()->execute([])`.

### 3.6 `presences.php`, `salaires.php`, `utilisateurs.php`
- Données utilisateur : uniquement via `prepare()` et paramètres liés.
- Listes / listes pour formulaires en `query()` statique → converties en `prepare()->execute([])`.

### 3.7 `login.php`, `forgot_password.php`, `install_dg_user.php`
- Requêtes avec entrées utilisateur : toutes en `prepare()` + `execute([...])`. **Sûr.**

### 3.8 `forgot_password.php` – `$db->exec("CREATE TABLE ...")`
- DDL fixe, aucune entrée utilisateur. **Sûr.**

### 3.9 `install_salaires_sample.php`
- Ancien `query()` statique pour les employés actifs → converti en `prepare()->execute([])`.

## 4. Recommandations appliquées

1. **Aucune requête ne concatène d’entrée utilisateur** dans la chaîne SQL ; les valeurs passent par des paramètres liés.
2. **ORDER BY** : uniquement via colonnes whitelistées et direction forcée (ASC/DESC).
3. **LIMIT / OFFSET** : cast `(int)` avant concaténation dans la requête.
4. **Uniformisation** : tout accès lecture/écriture en base passe par `prepare()` puis `execute()`, y compris pour les requêtes statiques.

## 5. Conclusion

Aucune vulnérabilité d’injection SQL exploitable n’a été constatée. Les requêtes dynamiques utilisent des requêtes préparées et des paramètres liés ; les requêtes auparavant en `query()` statique ont été converties en `prepare()->execute([])` pour cohérence et maintenance.
