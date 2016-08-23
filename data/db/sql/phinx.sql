# Dump of table phinxlog
# ------------------------------------------------------------

DROP TABLE IF EXISTS `phinxlog`;

CREATE TABLE `phinxlog` (
  `version` bigint(20) NOT NULL,
  `migration_name` varchar(100) DEFAULT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `end_time` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `breakpoint` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `phinxlog` WRITE;
/*!40000 ALTER TABLE `phinxlog` DISABLE KEYS */;

INSERT INTO `phinxlog` (`version`, `migration_name`, `start_time`, `end_time`, `breakpoint`)
VALUES
    (20160713214419,'Users','2016-08-14 19:49:14','2016-08-14 19:49:14',0),
    (20160714152505,'UsersData','2016-08-14 19:49:14','2016-08-14 19:49:14',0),
    (20160715174200,'Audit','2016-08-14 19:49:14','2016-08-14 19:49:14',0),
    (20160717113500,'ConfigData','2016-08-14 19:49:14','2016-08-14 19:49:14',0),
    (20160719215200,'OAuth2Apps','2016-08-14 19:49:14','2016-08-14 19:49:14',0),
    (20160719222400,'OAuth2Tokens','2016-08-14 19:49:14','2016-08-14 19:49:14',0),
    (20160805134100,'Reports','2016-08-14 19:49:14','2016-08-14 19:49:14',0);

/*!40000 ALTER TABLE `phinxlog` ENABLE KEYS */;
UNLOCK TABLES;
