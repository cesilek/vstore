# ************************************************************
# Sequel Pro SQL dump
# Version 3348
#
# http://www.sequelpro.com/
# http://code.google.com/p/sequel-pro/
#
# Host: 127.0.0.1 (MySQL 5.5.10-log)
# Database: vbuilder_cms
# Generation Time: 2011-08-16 14:25:02 +0200
# ************************************************************


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


# Dump of table redaction_doc_product
# ------------------------------------------------------------

DROP TABLE IF EXISTS `redaction_doc_product`;

CREATE TABLE `redaction_doc_product` (
  `contentId` int(11) unsigned NOT NULL,
  `lang` char(2) NOT NULL DEFAULT '',
  `pageId` int(11) NOT NULL,
  `title` varchar(256) NOT NULL DEFAULT '',
	`menuTitle` varchar(256) NOT NULL DEFAULT '',
  `perex` text NOT NULL,
  `content` text NOT NULL,
  `price` float unsigned NOT NULL DEFAULT '0',
	`usualPrice` float unsigned NOT NULL DEFAULT '0',
  `image` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`contentId`,`lang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


# Dump of table shop_addresses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `shop_addresses`;

CREATE TABLE `shop_addresses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `street` varchar(256) NOT NULL DEFAULT '',
  `houseNumber` varchar(64) NOT NULL DEFAULT '',
  `city` varchar(128) NOT NULL DEFAULT '',
  `zip` varchar(64) NOT NULL DEFAULT '',
  `country` char(2) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table shop_customers
# ------------------------------------------------------------

DROP TABLE IF EXISTS `shop_customers`;

CREATE TABLE `shop_customers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL DEFAULT '',
  `surname` varchar(256) NOT NULL DEFAULT '',
  `email` varchar(256) NOT NULL DEFAULT '',
  `phone` varchar(256) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table shop_orderItems
# ------------------------------------------------------------

DROP TABLE IF EXISTS `shop_orderItems`;

CREATE TABLE `shop_orderItems` (
  `orderId` int(11) unsigned NOT NULL,
  `productId` int(10) NOT NULL COMMENT 'Negative for special items',
  `name` varchar(256) NOT NULL DEFAULT '',
  `amount` smallint(5) unsigned NOT NULL DEFAULT '1',
  `price` float NOT NULL,
  `params` text COMMENT 'JSON encoded array',
  KEY `orderId` (`orderId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table shop_orders
# ------------------------------------------------------------

DROP TABLE IF EXISTS `shop_orders`;

CREATE TABLE `shop_orders` (
  `id` int(10) unsigned NOT NULL,
  `delivery` varchar(64) NOT NULL DEFAULT '',
  `payment` varchar(64) NOT NULL DEFAULT '',
  `customer` smallint(5) unsigned NOT NULL,
  `address` smallint(5) unsigned DEFAULT NULL,
  `note` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `state` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
