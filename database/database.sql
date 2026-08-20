-- ========================================================
-- SCHEMA DE LA BASE DE DONNEES POUR TUNISIE TELECOM (RECLAMATION TT)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `reclamation_tt` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `reclamation_tt`;

-- --------------------------------------------------------
-- 1. Table users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('CLIENT', 'AGENT', 'ADMIN') NOT NULL DEFAULT 'CLIENT',
  `status` TINYINT(1) DEFAULT 1, -- 1 = Actif, 0 = Suspendu
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2. Table categories
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3. Table reclamations
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reclamations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `agent_id` INT DEFAULT NULL,
  `category_id` INT DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `priority` ENUM('Faible', 'Moyenne', 'Haute', 'Urgente') NOT NULL DEFAULT 'Moyenne',
  `status` ENUM('Ouverte', 'En cours', 'Résolue', 'Clôturée') NOT NULL DEFAULT 'Ouverte',
  `ai_category` VARCHAR(100) DEFAULT NULL,
  `ai_confidence` FLOAT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` DATETIME NULL DEFAULT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 4. Table comments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reclamation_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reclamation_id`) REFERENCES `reclamations` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 5. Table status_history
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reclamation_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `old_status` ENUM('Ouverte', 'En cours', 'Résolue', 'Clôturée') DEFAULT NULL,
  `new_status` ENUM('Ouverte', 'En cours', 'Résolue', 'Clôturée') NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reclamation_id`) REFERENCES `reclamations` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 6. Table activity_logs
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 7. Table attachments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reclamation_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`reclamation_id`) REFERENCES `reclamations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- Indexations pour optimiser les performances
-- --------------------------------------------------------
CREATE INDEX `idx_users_email` ON `users` (`email`);
CREATE INDEX `idx_users_role` ON `users` (`role`);
CREATE INDEX `idx_users_status` ON `users` (`status`);
CREATE INDEX `idx_reclamations_status` ON `reclamations` (`status`);
CREATE INDEX `idx_reclamations_priority` ON `reclamations` (`priority`);
CREATE INDEX `idx_reclamations_created_at` ON `reclamations` (`created_at`);

-- ========================================================
-- INSERTION DES DONNEES DE DEMONSTRATION
-- ========================================================

-- 1. Catégories prédéfinies
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Internet', 'Problèmes de connexion web, coupures de ligne internet ou lenteurs générales.'),
(2, 'Téléphonie mobile', 'Problèmes liés au réseau mobile, appels, SMS ou forfaits cellulaires.'),
(3, 'Téléphonie fixe', 'Problèmes avec la ligne téléphonique fixe classique ou les équipements associés.'),
(4, 'Fibre optique', 'Dysfonctionnements spécifiques sur les offres et raccordements Fibre Optique (FTTH).'),
(5, 'ADSL', 'Problèmes sur la ligne ADSL / VDSL, désynchronisations de box ou débits insuffisants.'),
(6, 'Facturation', 'Contestation de facture, frais inattendus, double prélèvement ou erreurs de facturation.'),
(7, 'Paiement', 'Difficultés pour payer en ligne, rejets de paiement ou encaissement non enregistré.'),
(8, 'Recharge', 'Problèmes avec les recharges de cartes prépayées (tickets de recharge ou rechargement électronique).'),
(9, 'Service client', 'Plaintes concernant l''accueil en agence, le temps d''attente au téléphone ou la qualité du support.'),
(10, 'Autre', 'Toute autre réclamation ne correspondant pas aux catégories ci-dessus.');

-- 2. Utilisateurs
-- Admin123! : $2y$10$hpEl5IkGnkjMb3Zv8SvSquQW29hQfgGwPV06w9i9qnqLep6XzHa8m
-- Agent123! : $2y$10$3/H/crP/HIeAfQWQXp7FIObZ4mfdUhDNWJeoLY9kKDPZDJ2/GfrhK
-- Client123! : $2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `role`, `status`, `created_at`) VALUES
-- Administrateur (1)
(1, 'Super', 'Admin', 'admin@tunisietelecom.tn', '71001100', '$2y$10$hpEl5IkGnkjMb3Zv8SvSquQW29hQfgGwPV06w9i9qnqLep6XzHa8m', 'ADMIN', 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
-- Agents (3)
(2, 'Ahmed', 'Ben Ali', 'agent1@tunisietelecom.tn', '98100200', '$2y$10$3/H/crP/HIeAfQWQXp7FIObZ4mfdUhDNWJeoLY9kKDPZDJ2/GfrhK', 'AGENT', 1, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(3, 'Amira', 'Trabelsi', 'agent2@tunisietelecom.tn', '98100300', '$2y$10$3/H/crP/HIeAfQWQXp7FIObZ4mfdUhDNWJeoLY9kKDPZDJ2/GfrhK', 'AGENT', 1, DATE_SUB(NOW(), INTERVAL 24 DAY)),
(4, 'Sami', 'Gharbi', 'agent3@tunisietelecom.tn', '98100400', '$2y$10$3/H/crP/HIeAfQWQXp7FIObZ4mfdUhDNWJeoLY9kKDPZDJ2/GfrhK', 'AGENT', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
-- Clients (10)
(5, 'Yassine', 'Ayari', 'client1@gmail.com', '50111222', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(6, 'Rim', 'Masmoudi', 'client2@gmail.com', '50222333', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(7, 'Mohamed', 'Karray', 'client3@gmail.com', '50333444', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 15 DAY)),
(8, 'Selma', 'Dridi', 'client4@gmail.com', '50444555', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 12 DAY)),
(9, 'Kais', 'Saidi', 'client5@gmail.com', '50555666', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 10 DAY)),
(10, 'Fatma', 'Chakroun', 'client6@gmail.com', '50666777', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
(11, 'Anis', 'Rekik', 'client7@gmail.com', '50777888', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 7 DAY)),
(12, 'Leila', 'Bouazizi', 'client8@gmail.com', '50888999', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(13, 'Hassen', 'Mejri', 'client9@gmail.com', '50999000', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(14, 'Meriem', 'Zouari', 'client10@gmail.com', '55123456', '$2y$10$AWPEuHDS0//INSFh7xfDWufjMCSZQ/8Wls9dIHXBkRF/eS7eB45x2', 'CLIENT', 1, DATE_SUB(NOW(), INTERVAL 3 DAY));

-- 3. Réclamations (23 réclamations réparties sur les 30 derniers jours)
INSERT INTO `reclamations` (`id`, `user_id`, `agent_id`, `category_id`, `subject`, `description`, `priority`, `status`, `ai_category`, `ai_confidence`, `created_at`, `resolved_at`) VALUES
-- Semaine 1
(1, 5, 2, 5, 'Coupures Internet fréquentes ADSL', 'Ma connexion Internet ADSL saute toutes les 10 minutes depuis hier soir. Le voyant DSL clignote en rouge sur le modem.', 'Haute', 'Résolue', 'ADSL', 0.95, DATE_SUB(NOW(), INTERVAL 28 DAY), DATE_SUB(NOW(), INTERVAL 26 DAY)),
(2, 6, 2, 6, 'Facture de Juillet erronée', 'J''ai reçu une facture de 95 DT ce mois-ci alors que mon abonnement mensuel habituel est de 45 DT. Je demande une rectification.', 'Moyenne', 'Clôturée', 'Facturation', 0.98, DATE_SUB(NOW(), INTERVAL 27 DAY), DATE_SUB(NOW(), INTERVAL 25 DAY)),
(3, 7, 3, 2, 'Réseau 4G indisponible au Lac 2', 'Il n''y a plus de signal 4G dans la zone du Lac 2 à Tunis. Les appels vocaux passent mais la connexion internet est inexistante.', 'Urgente', 'Résolue', 'Téléphonie mobile', 0.92, DATE_SUB(NOW(), INTERVAL 26 DAY), DATE_SUB(NOW(), INTERVAL 24 DAY)),
(4, 8, 4, 8, 'Crédit non reçu après recharge en ligne', 'J''ai effectué une recharge en ligne de 20 DT via l''application de ma banque, mais mon solde n''a pas été mis à jour. L''argent a été débité.', 'Moyenne', 'Résolue', 'Recharge', 0.94, DATE_SUB(NOW(), INTERVAL 25 DAY), DATE_SUB(NOW(), INTERVAL 24 DAY)),
-- Semaine 2
(5, 9, 3, 1, 'Lenteur extrême de connexion internet', 'Le débit de ma connexion internet est extrêmement lent. Un test de débit montre moins de 1 Mbps sur une offre souscrite de 20 Mbps.', 'Moyenne', 'En cours', 'Internet', 0.91, DATE_SUB(NOW(), INTERVAL 20 DAY), NULL),
(6, 10, NULL, 3, 'Ligne fixe en dérangement', 'Ma ligne téléphonique fixe ne fonctionne plus du tout. Aucun tonalité lors du décrochage.', 'Faible', 'Ouverte', 'Téléphonie fixe', 0.89, DATE_SUB(NOW(), INTERVAL 19 DAY), NULL),
(7, 5, 2, 6, 'Double prélèvement sur abonnement Smart ADSL', 'J''ai été débité deux fois pour le même mois de Juin (45DT le 5 et le 12). Merci de me rembourser le trop-perçu.', 'Moyenne', 'Résolue', 'Facturation', 0.96, DATE_SUB(NOW(), INTERVAL 18 DAY), DATE_SUB(NOW(), INTERVAL 16 DAY)),
(8, 11, 4, 4, 'Fibre optique coupée par des travaux', 'Des travaux de voirie devant mon domicile ont coupé le câble de la fibre optique. Je n''ai plus d''internet ni de téléphone.', 'Urgente', 'En cours', 'Fibre optique', 0.97, DATE_SUB(NOW(), INTERVAL 17 DAY), NULL),
-- Semaine 3
(9, 12, 3, 7, 'Échec de paiement de facture en ligne', 'Impossible de valider le paiement de ma facture sur le portail web. Le système affiche une erreur technique après la saisie du code OTP.', 'Moyenne', 'Résolue', 'Paiement', 0.93, DATE_SUB(NOW(), INTERVAL 14 DAY), DATE_SUB(NOW(), INTERVAL 13 DAY)),
(10, 13, 2, 9, 'Comportement irrespectueux en agence', 'Je me suis rendu à l''agence TT d''Ennasr et l''agent d''accueil a refusé de m''écouter et m''a parlé de façon impolie.', 'Faible', 'Résolue', 'Service client', 0.85, DATE_SUB(NOW(), INTERVAL 13 DAY), DATE_SUB(NOW(), INTERVAL 11 DAY)),
(11, 14, NULL, 2, 'Problème d''activation carte SIM 4G', 'J''ai acheté une nouvelle carte SIM hier, mais elle n''est toujours pas activée. Le téléphone affiche "Aucun service".', 'Moyenne', 'Ouverte', 'Téléphonie mobile', 0.90, DATE_SUB(NOW(), INTERVAL 12 DAY), NULL),
(12, 6, 3, 5, 'Box ADSL ne s''allume plus', 'Suite à un orage hier soir, mon modem ADSL ne s''allume plus du tout. Aucun voyant n''est allumé.', 'Haute', 'En cours', 'ADSL', 0.94, DATE_SUB(NOW(), INTERVAL 10 DAY), NULL),
-- Semaine 4
(13, 7, 2, 6, 'Abonnement facturé malgré résiliation', 'J''ai résilié mon abonnement fixe en Mai dernier, mais je continue de recevoir des factures mensuelles pour ce service.', 'Moyenne', 'En cours', 'Facturation', 0.97, DATE_SUB(NOW(), INTERVAL 8 DAY), NULL),
(14, 8, NULL, 4, 'Installation Fibre optique en retard', 'J''ai signé le contrat de raccordement fibre il y a un mois et j''attends toujours le passage des techniciens pour l''installation.', 'Moyenne', 'Ouverte', 'Fibre optique', 0.88, DATE_SUB(NOW(), INTERVAL 7 DAY), NULL),
(15, 9, 4, 8, 'Erreur code de recharge Mobirachid', 'J''ai acheté un ticket de recharge de 5 DT mais les chiffres du code secret sont partiellement effacés. Je ne peux pas recharger.', 'Faible', 'Résolue', 'Recharge', 0.87, DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_SUB(NOW(), INTERVAL 5 DAY)),
(16, 10, 3, 1, 'Problème DNS récurent sur Smart Box', 'Les pages web ne se chargent pas sauf si j''utilise une IP directe. Il semble y avoir un problème avec les serveurs DNS de Tunisie Telecom.', 'Moyenne', 'En cours', 'Internet', 0.93, DATE_SUB(NOW(), INTERVAL 5 DAY), NULL),
(17, 11, NULL, 3, 'Bruits et grésillements sur la ligne fixe', 'Il y a énormément de bruit et de friture sur ma ligne de téléphone fixe, ce qui rend les conversations impossibles.', 'Faible', 'Ouverte', 'Téléphonie fixe', 0.91, DATE_SUB(NOW(), INTERVAL 4 DAY), NULL),
(18, 12, 2, 10, 'Demande de déménagement de ligne', 'Je souhaite déménager ma ligne ADSL actuelle vers mon nouveau domicile situé à la Soukra.', 'Moyenne', 'En cours', 'Autre', 0.82, DATE_SUB(NOW(), INTERVAL 3 DAY), NULL),
(19, 13, NULL, 6, 'Erreur de tarification forfait optionnel', 'L''option internet mobile de 10 Go m''a été facturée 25 DT au lieu des 15 DT annoncés dans l''offre promotionnelle.', 'Faible', 'Ouverte', 'Facturation', 0.94, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
(20, 14, 4, 2, 'Réseau mobile indisponible en intérieur', 'Depuis quelques jours, je capte très mal à l''intérieur de mon bureau au centre-ville. Je dois sortir pour passer un appel.', 'Moyenne', 'En cours', 'Téléphonie mobile', 0.90, DATE_SUB(NOW(), INTERVAL 2 DAY), NULL),
(21, 5, NULL, 1, 'Perte totale de synchronisation', 'Mon routeur affiche un voyant Internet rouge fixe et je n''ai plus d''accès au réseau. J''ai déjà tenté de redémarrer le modem.', 'Haute', 'Ouverte', 'Internet', 0.95, DATE_SUB(NOW(), INTERVAL 1 DAY), NULL),
(22, 6, NULL, 7, 'Paiement effectué en agence non enregistré', 'J''ai payé ma facture directement en agence en espèces et j''ai reçu le reçu, mais mon compte client en ligne indique toujours que la facture est impayée.', 'Moyenne', 'Ouverte', 'Paiement', 0.93, DATE_SUB(NOW(), INTERVAL 12 HOUR), NULL),
(23, 7, NULL, 5, 'Débit ADSL divisé par 4 depuis ce matin', 'J''ai d''habitude un débit de 12 Mbps stables, mais depuis ce matin le débit plafonne à 3 Mbps avec un ping très élevé.', 'Moyenne', 'Ouverte', 'ADSL', 0.96, DATE_SUB(NOW(), INTERVAL 4 HOUR), NULL);

-- 4. Commentaires (Dialogue client - agent)
INSERT INTO `comments` (`id`, `reclamation_id`, `user_id`, `comment`, `created_at`) VALUES
(1, 1, 5, 'Bonjour, le problème persiste. J''ai vérifié mes branchements téléphoniques.', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(2, 1, 2, 'Bonjour Monsieur, nous avons détecté une instabilité sur votre ligne au niveau du répartiteur de zone. Un technicien va intervenir sous 24h.', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(3, 1, 5, 'Merci pour votre réactivité. La ligne semble stable à présent !', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(4, 1, 2, 'Parfait. Nous clôturons donc cette réclamation. Merci d''avoir choisi Tunisie Telecom.', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(5, 5, 9, 'Bonjour, j''aimerais savoir quand mon débit sera rétabli. C''est très handicapant pour mon télétravail.', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(6, 5, 3, 'Bonjour, un test d''atténuation est en cours sur votre boucle locale. Nous attendons les résultats d''ici ce soir.', DATE_SUB(NOW(), INTERVAL 14 DAY)),
(7, 3, 7, 'Le réseau est urgent pour mon commerce !', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(8, 3, 3, 'Nous avons identifié une panne de l''antenne relais relais locale. Nos équipes sont sur site.', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(9, 3, 3, 'L''antenne relais a été réparée. Le signal 4G a été rétabli. Pouvez-vous confirmer ?', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(10, 3, 7, 'Oui, tout fonctionne à nouveau correctement. Merci !', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(11, 8, 11, 'Les techniciens de voirie ont endommagé le boîtier de raccordement mural.', DATE_SUB(NOW(), INTERVAL 16 DAY)),
(12, 12, 3, 'Nous planifions l''envoi d''un nouveau modem ADSL de remplacement dans votre agence de rattachement.', DATE_SUB(NOW(), INTERVAL 9 DAY));

-- 5. Historique des statuts
INSERT INTO `status_history` (`id`, `reclamation_id`, `user_id`, `old_status`, `new_status`, `created_at`) VALUES
(1, 1, 5, NULL, 'Ouverte', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(2, 1, 2, 'Ouverte', 'En cours', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(3, 1, 2, 'En cours', 'Résolue', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(4, 1, 5, 'Résolue', 'Clôturée', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(5, 2, 6, NULL, 'Ouverte', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(6, 2, 2, 'Ouverte', 'En cours', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(7, 2, 2, 'En cours', 'Résolue', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(8, 2, 6, 'Résolue', 'Clôturée', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(9, 3, 7, NULL, 'Ouverte', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(10, 3, 3, 'Ouverte', 'En cours', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(11, 3, 3, 'En cours', 'Résolue', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(12, 5, 9, NULL, 'Ouverte', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(13, 5, 3, 'Ouverte', 'En cours', DATE_SUB(NOW(), INTERVAL 19 DAY)),
(14, 8, 11, NULL, 'Ouverte', DATE_SUB(NOW(), INTERVAL 17 DAY)),
(15, 8, 4, 'Ouverte', 'En cours', DATE_SUB(NOW(), INTERVAL 16 DAY)),
(16, 12, 6, NULL, 'Ouverte', DATE_SUB(NOW(), INTERVAL 10 DAY)),
(17, 12, 3, 'Ouverte', 'En cours', DATE_SUB(NOW(), INTERVAL 9 DAY));

-- 6. Journaux d'activité
INSERT INTO `activity_logs` (`id`, `user_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'CONNEXION', 'Administrateur s''est connecté au panneau d''administration.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 30 DAY)),
(2, 5, 'INSCRIPTION', 'Inscription du client Yassine Ayari.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(3, 5, 'CONNEXION', 'Connexion de Yassine Ayari.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(4, 5, 'CREATION_RECLAMATION', 'Création de la réclamation ID #1 "Coupures Internet fréquentes ADSL"', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(5, 2, 'CONNEXION', 'Connexion de l''agent Ahmed Ben Ali.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(6, 2, 'MODIFICATION_STATUT', 'Réclamation #1 changée de Ouverte à En cours.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 27 DAY)),
(7, 2, 'MODIFICATION_STATUT', 'Réclamation #1 changée de En cours à Résolue.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 26 DAY)),
(8, 1, 'CREATION_UTILISATEUR', 'Création de l''utilisateur agent3@tunisietelecom.tn par l''admin.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 20 DAY)),
(9, 1, 'CREATION_CATEGORIE', 'Ajout de la catégorie "Autre".', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 29 DAY)),
(10, 8, 'CREATION_RECLAMATION', 'Création de la réclamation ID #4 "Crédit non reçu après recharge en ligne"', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(11, 4, 'CONNEXION', 'Connexion de l''agent Sami Gharbi.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(12, 4, 'MODIFICATION_STATUT', 'Réclamation #4 changée de Ouverte à Résolue.', '127.0.0.1', DATE_SUB(NOW(), INTERVAL 24 DAY));
