# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: localhost (MySQL 5.5.42)
# Database: ffmvc_test_db
# Generation Time: 2016-08-20 20:22:59 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table audit
# ------------------------------------------------------------

DROP TABLE IF EXISTS `audit`;

CREATE TABLE `audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL COMMENT 'UUID',
  `users_uuid` char(36) DEFAULT NULL COMMENT 'User UUID',
  `ip` varchar(16) DEFAULT NULL COMMENT 'IP-Address',
  `agent` varchar(255) DEFAULT NULL COMMENT 'User-Agent',
  `created` datetime NOT NULL COMMENT 'Created',
  `actor` varchar(128) DEFAULT NULL COMMENT 'Actor',
  `event` varchar(128) DEFAULT NULL COMMENT 'Event',
  `description` varchar(255) DEFAULT NULL COMMENT 'Description',
  `old` text COMMENT 'Old Value',
  `new` text COMMENT 'New Value',
  `debug` text COMMENT 'Debug Information',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table config_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `config_data`;

CREATE TABLE `config_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL COMMENT 'UUID',
  `description` text COMMENT 'Description',
  `key` varchar(255) NOT NULL COMMENT 'Key',
  `value` text COMMENT 'Value',
  `type` varchar(32) DEFAULT NULL COMMENT 'Type',
  `options` text COMMENT 'Options',
  `rank` int(11) DEFAULT '9999' COMMENT 'Rank',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `config_data` WRITE;
/*!40000 ALTER TABLE `config_data` DISABLE KEYS */;

INSERT INTO `config_data` (`id`, `uuid`, `description`, `key`, `value`, `type`, `options`, `rank`)
VALUES
	(1,'35db8273','Contact form email','contact-email','vijay@yoyo.org','email','',0),
	(2,'8fdf6122','Github URL','github-url','https://github.com/vijinho','url','',0),
	(3,'73aeed16','Twitter URL','twitter-url','http://twitter.com/vijinh0','url','',0),
	(4,'25cb0566','EyeEm URL','eyem-url','http://eyeem.com/u/vijinho','url','',0),
	(5,'df06116c','Instagram URL','instagram-url','http://instagram.com/vijinho','url','',0),
	(6,'c9bbfd21','Website Author URL','author-url','http://about.me/vijay.mahrra','url','',0);

/*!40000 ALTER TABLE `config_data` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table oauth2_apps
# ------------------------------------------------------------

DROP TABLE IF EXISTS `oauth2_apps`;

CREATE TABLE `oauth2_apps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created` datetime NOT NULL COMMENT 'Created',
  `users_uuid` char(36) NOT NULL COMMENT 'User UUID',
  `client_id` char(36) NOT NULL COMMENT 'Client Id',
  `client_secret` char(36) NOT NULL COMMENT 'Client Secret',
  `name` varchar(255) NOT NULL COMMENT 'Application Name',
  `logo_url` tinytext COMMENT 'Logo Image URL',
  `description` text COMMENT 'Description',
  `scope` text COMMENT 'Allowed Scopes',
  `callback_uri` text COMMENT 'Callback URI',
  `redirect_uris` text COMMENT 'Redirect URIs',
  `status` varchar(16) NOT NULL DEFAULT 'NEW' COMMENT 'Status',
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `client_id` (`client_id`),
  UNIQUE KEY `client_secret` (`client_secret`),
  UNIQUE KEY `client_id_secret` (`client_id`,`client_secret`),
  KEY `users_uuid` (`users_uuid`),
  CONSTRAINT `oauth2_apps_fk_uuid` FOREIGN KEY (`users_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `oauth2_apps` WRITE;
/*!40000 ALTER TABLE `oauth2_apps` DISABLE KEYS */;

/*!40000 ALTER TABLE `oauth2_apps` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table oauth2_tokens
# ------------------------------------------------------------

DROP TABLE IF EXISTS `oauth2_tokens`;

CREATE TABLE `oauth2_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL COMMENT 'UUID',
  `created` datetime NOT NULL COMMENT 'Created',
  `expires` datetime DEFAULT NULL COMMENT 'Expires',
  `users_uuid` char(36) NOT NULL COMMENT 'User UUID',
  `client_id` char(36) NOT NULL COMMENT 'Client Id',
  `token` char(36) NOT NULL COMMENT 'Token Value',
  `type` char(16) NOT NULL COMMENT 'Token Type',
  `scope` char(255) DEFAULT NULL COMMENT 'Allowed Scopes',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `client_id` (`client_id`,`users_uuid`,`type`),
  KEY `users_uuid` (`users_uuid`),
  CONSTRAINT `oauth2_tokens_fk_client_id` FOREIGN KEY (`client_id`) REFERENCES `oauth2_apps` (`client_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `oauth2_tokens_fk_uuid` FOREIGN KEY (`users_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table reports
# ------------------------------------------------------------

DROP TABLE IF EXISTS `reports`;

CREATE TABLE `reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL COMMENT 'UUID',
  `users_uuid` char(36) NOT NULL COMMENT 'User UUID',
  `scopes` varchar(64) NOT NULL DEFAULT 'user' COMMENT 'Account Scopes',
  `key` varchar(255) NOT NULL COMMENT 'Key',
  `name` varchar(255) NOT NULL COMMENT 'Name',
  `description` text COMMENT 'Description',
  `query` text COMMENT 'Query',
  `options` text COMMENT 'Extra Options',
  `created` datetime NOT NULL COMMENT 'Created',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `users_uuid` (`users_uuid`),
  CONSTRAINT `reports_fk_uuid` FOREIGN KEY (`users_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL COMMENT 'UUID',
  `password` char(16) NOT NULL COMMENT 'Password',
  `email` varchar(255) NOT NULL COMMENT 'Email',
  `firstname` varchar(128) NOT NULL COMMENT 'First Name(s)',
  `lastname` varchar(128) NOT NULL COMMENT 'Last Name(s)',
  `scopes` varchar(64) NULL DEFAULT 'user' COMMENT 'Account Scopes',
  `status` varchar(32) NOT NULL DEFAULT 'NEW' COMMENT 'Account Status',
  `password_question` varchar(255) NOT NULL COMMENT 'Password Hint Question',
  `password_answer` varchar(255) NOT NULL COMMENT 'Password Hint Answer',
  `created` datetime NOT NULL COMMENT 'Created',
  `login_count` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'Login Count',
  `login_last` datetime DEFAULT NULL COMMENT 'Last Login',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;

/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table users_data
# ------------------------------------------------------------

DROP TABLE IF EXISTS `users_data`;

CREATE TABLE `users_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL COMMENT 'UUID',
  `users_uuid` char(36) NOT NULL COMMENT 'User UUID',
  `key` varchar(255) NOT NULL COMMENT 'Key',
  `value` text COMMENT 'Value',
  `type` varchar(32) DEFAULT NULL COMMENT 'Type',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `users_uuid` (`users_uuid`,`key`),
  CONSTRAINT `users_data_fk_uuid` FOREIGN KEY (`users_uuid`) REFERENCES `users` (`uuid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table assets
# ------------------------------------------------------------

DROP TABLE IF EXISTS `assets`;

CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL COMMENT 'UUID',
  `users_uuid` varchar(36) NOT NULL COMMENT 'User UUID',
  `key` varchar(255) DEFAULT NULL COMMENT 'Key',
  `name` varchar(255) DEFAULT NULL COMMENT 'Name',
  `description` text COMMENT 'Description',
  `filename` text NOT NULL COMMENT 'Filename',
  `size` int(11) NOT NULL COMMENT 'File Size',
  `type` varchar(255) DEFAULT NULL COMMENT 'Mime Type',
  `categories` text COMMENT 'Categories',
  `tags` text COMMENT 'Tags',
  `metadata` text COMMENT 'File Metadata',
  `url` text COMMENT 'URL',
  `created` datetime NOT NULL COMMENT 'Created',
  `updated` datetime NOT NULL COMMENT 'Updated',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `key` (`key`),
  KEY `type` (`type`),
  KEY `users_uuid` (`users_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
