# Application Intelligente de Gestion des Réclamations Clients (Tunisie Telecom)

Cette application web moderne et responsive est développée sur mesure pour **Tunisie Telecom**. Elle permet de gérer de bout en bout le processus de réclamation client, de la soumission intelligente avec auto-catégorisation en temps réel à la résolution, avec timelines de suivi et prévisions statistiques administratives.

---

## 1. Prérequis

* **Serveur Web Local** : [XAMPP](https://www.apachefriends.org/) (recommandé) ou WampServer.
* **PHP** : Version **8.0** ou supérieure (extensions `pdo_mysql` et `mbstring` activées).
* **MySQL** : Version **5.7** ou supérieure.
* Un navigateur web moderne (Chrome, Firefox, Edge, Safari).

---

## 2. Installation XAMPP

1. Téléchargez et installez XAMPP pour Windows/macOS/Linux.
2. Assurez-vous d'activer l'extension **PDO MySQL** (elle est activée par défaut sur la quasi-totalité des installations XAMPP).

---

## 3. Installation du projet

1. Copiez le dossier du projet `reclamation-tt` (ou le contenu de ce workspace) et collez-le dans le répertoire racine de votre serveur XAMPP :
   - Sur Windows : `C:\xampp\htdocs\reclamation-tt\`
   - Sur macOS : `/Applications/XAMPP/xamppfiles/htdocs/reclamation-tt/`
2. Si vous utilisez un autre répertoire ou nom de dossier, l'URL de base s'adaptera automatiquement grâce à la configuration dynamique.

---

## 4. Création de la base de données MySQL

1. Lancez le panneau de contrôle de XAMPP.
2. Démarrez les modules **Apache** et **MySQL**.
3. Ouvrez votre navigateur et accédez à **phpMyAdmin** via l'adresse : `http://localhost/phpmyadmin/`.
4. Cliquez sur **Nouvelle base de données** dans la barre latérale gauche.
5. Nommez la base de données : `reclamation_tt`.
6. Choisissez l'interclassement : `utf8mb4_unicode_ci` puis cliquez sur **Créer**.

---

## 5. Import de database.sql

1. Dans phpMyAdmin, sélectionnez la base de données `reclamation_tt` fraîchement créée.
2. Cliquez sur l'onglet **Importer** dans le menu supérieur.
3. Cliquez sur **Choisir un fichier** et sélectionnez le fichier SQL situé dans le projet :
   `reclamation-tt/database/database.sql`
4. Laissez les options par défaut et cliquez sur **Importer** (ou **Exécuter**) en bas de page.
5. Cette action va générer l'ensemble des tables (users, categories, reclamations, etc.) et importer les **données de démonstration** nécessaires.

---

## 6. Configuration de database.php

Le fichier de configuration de la base de données se trouve dans :
`config/database.php`

Par défaut, il est configuré pour s'adapter à une installation XAMPP standard (sans mot de passe) :
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'reclamation_tt');
```
Si vous utilisez un mot de passe MySQL personnalisé, mettez simplement à jour le paramètre `DB_PASS`.

---

## 7. Comptes de démonstration

La base de données est livrée avec des profils préconfigurés pour tester directement les 3 espaces :

| Rôle | Adresse Email | Mot de passe | Description |
| :--- | :--- | :--- | :--- |
| **ADMIN** | `admin@tunisietelecom.tn` | `Admin123!` | Dashboard global, KPI système, CRUD utilisateurs/catégories, logs audit, graphiques prédictifs. |
| **AGENT** | `agent1@tunisietelecom.tn` | `Agent123!` | Dashboard agent, traitement des tickets assignés, modifications de statuts, timeline. |
| **AGENT** | `agent2@tunisietelecom.tn` | `Agent123!` | Profil agent 2. |
| **CLIENT** | `client1@gmail.com` | `Client123!` | Dashboard client, dépôt de dossiers avec assistant intelligent, timeline de suivi, chat support. |
| **CLIENT** | `client2@gmail.com` | `Client123!` | Profil client 2. |

---

## 8. URL de l'application

Une fois Apache et MySQL démarrés dans XAMPP, visitez :
👉 **[http://localhost/reclamation-tt/index.php](http://localhost/reclamation-tt/index.php)**

---

## 9. Architecture du projet

Le projet respecte une séparation stricte des rôles et des composants (MVC léger) :
```text
reclamation-tt/
│
├── index.php                 # Landing page Tunisie Telecom
├── login.php                 # Formulaire de connexion sécurisé
├── register.php              # Formulaire d'inscription clients
├── logout.php                # Destruction de session et logs de déconnexion
│
├── config/
│   └── database.php          # Connexion PDO MySQL
│
├── includes/
│   ├── auth.php              # Protection des sessions et redirection des rôles
│   ├── header.php            # Balises head communes et imports (fonts, icons, css)
│   ├── footer.php            # Scripts JS communs et fermeture des balises
│   ├── navbar.php            # Navigation principale dynamique
│   └── functions.php         # Sécurité XSS, logs d'activité, auto-catégorisation et uploads
│
├── assets/
│   ├── css/
│   │   ├── style.css         # Charte graphique TT (Boutons, badges, toasts, tables)
│   │   ├── auth.css          # Design spécifique login/register
│   │   ├── dashboard.css     # Grille KPI, sidebar et timeline
│   │   └── responsive.css    # Support tablette et mobile 360px - 1920px
│   │
│   └── js/
│       ├── app.js            # Notifications Toast, Mobile Hamburger menu, Loader
│       ├── auth.js           # Validations de formulaires côté client
│       ├── reclamations.js   # Module de suggestion de catégorie en temps réel (fetch debounced)
│       └── dashboard.js      # Initialisation des graphiques via Chart.js
│
├── client/
│   ├── dashboard.php         # Espace client avec KPIs et graphiques
│   ├── reclamation-create.php# Formulaire intelligent de création
│   ├── reclamation-details.php# Timeline et chat avec l'agent assigné
│   ├── reclamations.php      # Liste filtrable et paginée des dossiers
│   └── profile.php           # Edition profil client
│
├── agent/
│   ├── dashboard.php         # Dashboard agent support
│   ├── reclamations.php      # Liste des tickets assignés
│   ├── reclamation-details.php# Consultation, chat et modification de statut
│   └── profile.php           # Profil agent
│
├── admin/
│   ├── dashboard.php         # KPI globaux, graphiques et dashboard de prévisions
│   ├── users.php             # CRUD complet des utilisateurs (Modales AJAX)
│   ├── reclamations.php      # Assignation en direct des agents aux tickets
│   ├── categories.php        # CRUD des catégories d'incidents
│   ├── statistics.php        # Indicateurs de performance des agents et impression
│   ├── activity-logs.php     # Journal d'audit complet (IP, Date, Action, User)
│   └── profile.php           # Profil administrateur
│
├── api/
│   ├── auth.php              # AJAX session checker
│   ├── reclamations.php      # Traitement AJAX des dossiers, statuts, comments et suggestions
│   ├── users.php             # CRUD AJAX Utilisateurs (pour l'admin)
│   ├── categories.php        # CRUD AJAX Catégories (pour l'admin)
│   └── dashboard.php         # API de statistiques (KPIs, graphiques, linéaire prédictif)
│
└── database/
    └── database.sql          # Schémas SQL et 20+ données de tests complètes
```

---

## 10. Sécurité implémentée

* **Protection contre les injections SQL** : Utilisation systématique de requêtes préparées PDO.
* **Protection contre les failles XSS** : Échappement des caractères via `htmlspecialchars` encapsulé dans la fonction `sanitize()`.
* **Cryptographie** : Mots de passe hachés avec `password_hash()` (Bcrypt) et comparés via `password_verify()`.
* **Sécurité des Uploads** : Vérification stricte des extensions autorisées (`png`, `jpg`, `jpeg`, `pdf`, `docx`, `doc`) et blocage des fichiers volumineux (> 5 Mo) côté serveur.
* **Contrôle d'accès** : Protection sessionologique sur chaque page et chaque API endpoint en fonction du rôle (`require_role()`).
* **Journaux d'activité** : Chaque action critique (connexion, changement de statut, création, modification, suppression) est tracée avec date, ID utilisateur et adresse IP physique.
