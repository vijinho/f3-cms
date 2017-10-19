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

CREATE TABLE IF NOT EXISTS `audit` (
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

CREATE TABLE IF NOT EXISTS `config_data` (
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
	(6,'c9bbfd21','Website Author URL','author-url','http://www.urunu.com','url','',0);

/*!40000 ALTER TABLE `config_data` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table oauth2_apps
# ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `oauth2_apps` (
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

# Dump of table oauth2_tokens
# ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `oauth2_tokens` (
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

CREATE TABLE IF NOT EXISTS `reports` (
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

CREATE TABLE IF NOT EXISTS `users` (
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


# Dump of table users_data
# ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users_data` (
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

CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL COMMENT 'UUID',
  `users_uuid` varchar(36) DEFAULT NULL COMMENT 'User UUID',
  `key` varchar(255) DEFAULT NULL COMMENT 'Key',
  `groups` varchar(255) DEFAULT NULL COMMENT 'Groups',
  `name` varchar(255) DEFAULT NULL COMMENT 'Name',
  `description` text COMMENT 'Description',
  `filename` text NOT NULL COMMENT 'Filename',
  `size` int(11) NOT NULL DEFAULT '0' COMMENT 'File Size',
  `type` varchar(255) DEFAULT NULL COMMENT 'Mime Type',
  `categories` text COMMENT 'Categories',
  `tags` text COMMENT 'Tags',
  `created` datetime NOT NULL COMMENT 'Created',
  `updated` datetime DEFAULT NULL COMMENT 'Updated',
  `url` text COMMENT 'URL',
  `metadata` text COMMENT 'Additional Metadata',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `users_uuid` (`users_uuid`,`key`),
  KEY `type` (`type`),
  KEY `users_uuid_2` (`users_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table pages
# ------------------------------------------------------------

CREATE TABLE `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `uuid` varchar(36) NOT NULL COMMENT 'UUID',
  `users_uuid` varchar(36) DEFAULT NULL COMMENT 'User UUID',
  `key` varchar(255) DEFAULT NULL COMMENT 'Key',
  `author` varchar(255) DEFAULT NULL COMMENT 'Author',
  `language` varchar(5) DEFAULT 'en' COMMENT 'Language',
  `status` varchar(255) DEFAULT NULL COMMENT 'Publish Status',
  `slug` varchar(255) DEFAULT NULL COMMENT 'Slug',
  `path` text COMMENT 'URL Path',
  `keywords` text COMMENT 'Meta Keywords',
  `description` text COMMENT 'Meta Description',
  `robots` tinyint(1) DEFAULT '1' COMMENT 'Allow Robots?',
  `title` varchar(255) DEFAULT NULL COMMENT 'Page Title',
  `summary` text COMMENT 'Summary',
  `body` text COMMENT 'Body Content',
  `scopes` varchar(255) DEFAULT NULL COMMENT 'Scopes',
  `category` varchar(255) DEFAULT 'page' COMMENT 'Category',
  `tags` text COMMENT 'Tags',
  `metadata` text COMMENT 'Additional Metadata',
  `created` datetime NOT NULL COMMENT 'Date Created',
  `published` datetime DEFAULT NULL COMMENT 'Date Published',
  `expires` datetime DEFAULT NULL COMMENT 'Date Expires',
  `updated` datetime DEFAULT NULL COMMENT 'Last Updated',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`),
  UNIQUE KEY `slug` (`slug`),
  KEY `language` (`language`),
  KEY `status` (`status`),
  KEY `users_uuid` (`users_uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `pages` (`id`, `uuid`, `users_uuid`, `key`, `author`, `language`, `status`, `slug`, `path`, `keywords`, `description`, `robots`, `title`, `summary`, `body`, `scopes`, `category`, `tags`, `metadata`, `created`, `published`, `expires`, `updated`)
VALUES
	(1, '171daf63', 'acb84996', 'terms-and-conditions', 'Website Owner', 'en', 'published', 'terms-and-conditions', '/', 'term of use,terms and conditions,terms', 'Terms and Conditions of the website', 1, 'Terms and Conditions', 'This page and any pages it links to explains the website terms of use. You must agree to these to use the website.', 'This page and any pages it links to explains the website terms of use. You must agree to these to use the website.\r\n\r\n## Who we are\r\n\r\nThe website is managed by --- on behalf of --. The website will be referred to as ‘we’ from now on.\r\n\r\n## Using the website\r\n\r\nYou agree to use the website only for lawful purposes. You must also use it in a way that doesn’t infringe the rights of, or restrict or inhibit the use and enjoyment of, this site by anyone else.\r\n\r\nWe update the website all the time. We can change or remove content at any time without notice.\r\n\r\n## Services and transactions\r\n\r\nSome services have their own terms and conditions which also apply - read these before you use the service.\r\n\r\n## Linking to the website\r\n\r\nWe welcome and encourage other websites to link to the website.\r\n\r\nYou must contact us for permission if you want to either:\r\n\r\n- charge your website’s users to click on a link to any page on the website\r\n- say your website is associated with or endorsed by the website\r\n\r\n## Linking from the website\r\n\r\nThe website links to websites that are managed by other organisations. We don’t have any control over the content on these websites.\r\n\r\nWe’re not responsible for:\r\n\r\n- the protection of any information you give to these websites\r\nany loss or damage that may come from your use of these websites, or any other websites they link to\r\n- You agree to release us from any claims or disputes that may come from using these websites.\r\n\r\nYou should read all terms and conditions, privacy policies and end user licences that relate to these websites before you use them.\r\n\r\n## Using website content\r\n\r\nWe make most of the content on the website available through feeds for other websites and applications to use. The websites and applications that use our feeds aren’t our products, and they might use versions of our content that have been edited and stored for later use (‘cached’).\r\n\r\nWe don’t give any guarantees, conditions or warranties about the accuracy or completeness of any content used by these products. We’re not liable for any loss or damage that may come from your use of these products.\r\n\r\nThe most up to date version of our content will always be on the website.\r\n\r\n## Disclaimer\r\n\r\nWhile we make every effort to keep the website up to date, we don’t provide any guarantees, conditions or warranties that the information will be:\r\n\r\n- current\r\n- secure\r\n- accurate\r\n- complete\r\n- free from bugs or viruses\r\n\r\nWe don’t publish advice on the website. You should get professional or specialist advice before doing anything on the basis of the content.\r\n\r\nWe’re not liable for any loss or damage that may come from using the website. This includes:\r\n\r\n- any direct, indirect or consequential losses\r\n- any loss or damage caused by civil wrongs (‘tort’, including negligence), breach of contract or otherwise\r\n- the use of the website  and any websites that are linked to or from it\r\n- the inability to use the website and any websites that are linked to or from it\r\n\r\nThis applies if the loss or damage was foreseeable, arose in the normal course of things or you advised us that it might happen.\r\n\r\nThis includes (but isn’t limited to) the loss of your:\r\n\r\n- income or revenue\r\n- salary, benefits or other payments\r\n- business\r\n- profits or contracts\r\n- opportunity\r\n- anticipated savings\r\n- data\r\n- goodwill or reputation\r\n- tangible property\r\n- intangible property, including loss, corruption or damage to data or - - any computer system\r\n- wasted management or office time\r\n\r\nWe may still be liable for:\r\n\r\n- death or personal injury arising from our negligence\r\nfraudulent misrepresentation\r\n- any other liability which cannot be excluded or limited under applicable law\r\n\r\n## Requests to remove content\r\n\r\nYou can ask for content to be removed from the website. We’ll only do this in certain cases, eg if it breaches copyright laws, contains sensitive personal data or material that may be considered obscene or defamatory.\r\n\r\nContact us to ask for content to be removed. You’ll need to send us the web address (URL) of the content and explain why you think it should be removed. We’ll reply to let you know whether we’ll remove it.\r\n\r\nWe remove content at our discretion in discussion with the department responsible for it. You can still request information under the Freedom of Information Act and the Data Protection Act.\r\n\r\n## Information about you and your visits to the website\r\n\r\nWe collect information about you in accordance with our privacy policy and our cookie policy. By using the website, you agree to us collecting this information and confirm that any data you provide is accurate.\r\n\r\n## Virus protection\r\n\r\nWe make every effort to check and test the website for viruses at every stage of production. You must make sure that the way you use website doesn’t expose you to the risk of viruses, malicious computer code or other forms of interference which can damage your computer system.\r\n\r\nWe’re not responsible for any loss, disruption or damage to your data or computer system that might happen when you use the website.\r\n\r\n## Viruses, hacking and other offences\r\n\r\nWhen using the website, you must not introduce viruses, trojans, worms, logic bombs or any other material that’s malicious or technologically harmful.\r\n\r\nYou must not try to gain unauthorised access to the website, the server on which it’s stored or any server, computer or database connected to it.\r\n\r\nYou must not attack the website in any way. This includes denial-of-service attacks.\r\n\r\nWe’ll report any attacks or attempts to gain unauthorised access to the website to the relevant law enforcement authorities and share information about you with them.\r\n\r\n## Governing law\r\n\r\nThese terms and conditions are governed by and construed in accordance with the laws of England and Wales.\r\n\r\nAny dispute you have which relates to these terms and conditions, or your use of the website (whether it be contractual or non-contractual), will be subject to the exclusive jurisdiction of the courts of England and Wales.\r\n\r\n## General\r\n\r\nThere may be legal notices elsewhere on the website that relate to how you use the site.\r\n\r\nWe’re not liable if we fail to comply with these terms and conditions because of circumstances beyond our reasonable control.\r\n\r\nWe might decide not to exercise or enforce any right available to us under these terms and conditions. We can always decide to exercise or enforce that right at a later date.\r\n\r\nDoing this once won’t mean we automatically waive the right on any other occasion.\r\n\r\nIf any of these terms and conditions are held to be invalid, unenforceable or illegal for any reason, the remaining terms and conditions will still apply.\r\n\r\n## Changes to these terms and conditions\r\n\r\nPlease check these terms and conditions regularly. We can update them at any time without notice.\r\n\r\nYou’ll agree to any changes if you continue to use the website after the terms and conditions have been updated.', 'public', 'page', 'terms,conditions', '', '2016-09-03 15:06:24', '2016-09-02 14:53:21', NULL, '0000-00-00 00:00:00'),
	(2, 'e9b807cc', 'acb84996', 'privacy-policy', 'Website Owner', 'en', 'published', 'privacy-policy', '/', '', '', 1, 'Privacy Policy', 'We collect certain information or data about you when you use the website. This is our policy.', 'We collect certain information or data about you when you use the website.\r\n\r\nThis includes:\r\n\r\n- questions, queries or feedback you leave, including your email address if you send an email to the website\r\n- your IP address, and details of which version of web browser you used\r\n- information on how you use the site, using cookies and page tagging techniques to help us improve the website\r\n- details to allow you to access government services and transactions, eg an email address (you’ll always be told when this information is being collected, and it will only be used for the purpose you provide it for)\r\n\r\nThis helps us to:\r\n\r\n- improve the site by monitoring how you use it\r\n- respond to any feedback you send us, if you’ve asked us to\r\n- provide you with information about services if you want it\r\n\r\n**We can’t personally identify you using your data.**\r\n\r\n## Where your data is stored\r\n\r\nWe store your data on our secure servers in the UK.\r\n\r\nIt may also be stored outside of Europe, where it could be viewed by our staff or suppliers.\r\n\r\n**By submitting your personal data, you agree to this.**\r\n\r\n## Keeping your data secure\r\n\r\nTransmitting information over the internet is generally not completely secure, and we can’t guarantee the security of your data.\r\n\r\n**Any data you transmit is at your own risk.**\r\n\r\nWe have procedures and security features in place to try and keep your data secure once we receive it.\r\n\r\nWe won’t share your information with any other organisations for marketing, market research or commercial purposes, and we don’t pass on your details to other websites.\r\n\r\n**Payment transactions are always encrypted.**\r\n\r\n## Disclosing your information\r\n\r\nWe may pass on your personal information if we have a legal obligation to do so, or if we have to enforce or apply our terms of use and other agreements. This includes exchanging information for legal reasons.\r\n\r\n## Your rights\r\n\r\nYou can find out what information we hold about you, and ask us not to use any of the information we collect.\r\n\r\n## Links to other websites\r\n\r\nThe website contains links to and from other websites.\r\n\r\nThis privacy policy only applies to this website, and doesn’t cover other government services and transactions that we link to.\r\n\r\n## Following a link to another website\r\n\r\nIf you go to another website from this one, read the privacy policy on that website to find out what it does with your information.\r\n\r\n## Following a link to the website from another website\r\n\r\nIf you come to the website from another website, we may receive personal information about you from the other website. You should read the privacy policy of the website you came from to find out more about this.', 'public', 'page', '', '', '2016-09-03 15:39:21', '2016-09-03 15:34:11', NULL, '0000-00-00 00:00:00'),
	(3, 'cc721c75', 'acb84996', 'cookies', 'Author', 'en', 'published', 'cookies', '/', '', '', 1, 'Cookies', 'The website puts small files (known as ‘cookies’) onto your computer to collect information about how you browse the site.', 'The website puts small files (known as ‘cookies’) onto your computer to collect information about how you browse the site.\r\n\r\nCookies are used to:\r\n\r\n- measure how you use the website so it can be updated and improved based on your needs\r\n- remember the notifications you’ve seen so that we don’t show them to you again\r\n- The website cookies aren’t used to identify you personally.\r\n\r\nYou’ll normally see a message on the site before we store a cookie on your computer.', 'public', 'page', '', '', '2016-09-03 15:48:02', '2016-08-22 15:48:02', NULL, '0000-00-00 00:00:00'),
	(4, 'e06d10d0', 'acb84996', 'about', 'Author', 'en', 'published', 'about', '/', 'about us', 'About Us', 1, 'About', 'About', 'About', 'public', 'page', 'about,about us', '', '2016-09-03 16:45:41', '2016-08-22 16:45:41', NULL, '0000-00-00 00:00:00'),
	(5, 'cbc5e88f', 'acb84996', 'contact', 'Author', 'en', 'published', 'contact', '/', 'contact,contact', 'Contact Us', 1, 'Contact', 'Contact Us', 'Contact Us', 'public', 'page', 'contact', '', '2016-09-03 16:46:34', '0000-00-00 00:00:00', NULL, '0000-00-00 00:00:00');

/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
