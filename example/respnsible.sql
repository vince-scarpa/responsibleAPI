# ************************************************************
# Sequel Pro SQL dump
# Version 4541
#
# http://www.sequelpro.com/
# https://github.com/sequelpro/sequelpro
#
# Host: 127.0.0.1 (MySQL 5.6.44)
# Database: responsible
# Generation Time: 2019-06-10 05:48:30 +0000
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table responsible_api_users
# ------------------------------------------------------------

DROP TABLE IF EXISTS `responsible_api_users`;

CREATE TABLE `responsible_api_users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `account_id` bigint(8) NOT NULL DEFAULT '0',
  `name` varchar(60) NOT NULL DEFAULT '',
  `mail` varchar(254) DEFAULT '',
  `created` int(11) NOT NULL DEFAULT '0',
  `access` int(11) NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '0',
  `secret` varchar(32) NOT NULL DEFAULT '',
  PRIMARY KEY (`uid`),
  UNIQUE KEY `name` (`name`),
  KEY `access` (`access`),
  KEY `created` (`created`),
  KEY `mail` (`mail`),
  KEY `account_id` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores user data.';

LOCK TABLES `responsible_api_users` WRITE;
/*!40000 ALTER TABLE `responsible_api_users` DISABLE KEYS */;

INSERT INTO `responsible_api_users` (`uid`, `account_id`, `name`, `mail`, `created`, `access`, `status`, `secret`)
VALUES
	(0,42150011,'admin','admin@',1560128836,1560128836,1,'MASTER_SECRET'),
	(1,29159987,'example-user','example@example.com',1560145601,1560145601,1,')ikSv!aPVj1G$o98C^Dm@V1]NjpBN9Xr');

/*!40000 ALTER TABLE `responsible_api_users` ENABLE KEYS */;
UNLOCK TABLES;


# Dump of table responsible_token_bucket
# ------------------------------------------------------------

DROP TABLE IF EXISTS `responsible_token_bucket`;

CREATE TABLE `responsible_token_bucket` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `bucket` varchar(128) NOT NULL DEFAULT '',
  `account_id` bigint(8) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `Account ID Constraint` (`account_id`),
  CONSTRAINT `Account ID Constraint` FOREIGN KEY (`account_id`) REFERENCES `responsible_api_users` (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

LOCK TABLES `responsible_token_bucket` WRITE;
/*!40000 ALTER TABLE `responsible_token_bucket` DISABLE KEYS */;

INSERT INTO `responsible_token_bucket` (`id`, `bucket`, `account_id`)
VALUES
	(0,'',42150011),
	(1,'',29159987);

/*!40000 ALTER TABLE `responsible_token_bucket` ENABLE KEYS */;
UNLOCK TABLES;



/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
