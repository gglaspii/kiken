SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE IF NOT EXISTS `account` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `solde` float NOT NULL,
  `user_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `best_tags` (
  `transaction_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  `tag_name` varchar(45) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  PRIMARY KEY (`transaction_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `category_tag` (
  `category_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`category_id`,`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`,`account_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tag_auto` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL DEFAULT '0',
  `regexp` varchar(100) NOT NULL DEFAULT '',
  `tag_id_1` int(11) NOT NULL DEFAULT '0',
  `tag_id_2` int(11) NOT NULL DEFAULT '0',
  `tag_id_3` int(11) NOT NULL DEFAULT '0',
  `tag_id_4` int(11) NOT NULL DEFAULT '0',
  `tag_id_5` int(11) NOT NULL DEFAULT '0',
  `tr_type` enum('Expense','Income') NOT NULL DEFAULT 'Expense',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 ;

CREATE TABLE IF NOT EXISTS `transaction` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tdate` date DEFAULT NULL,
  `tdatetime` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `currency` int(11) DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `type` enum('Expense','Income') DEFAULT NULL,
  `status` enum('Reconciled','Cleared') DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `split_parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `splitted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tdate` (`tdate`,`description`,`amount`),
  UNIQUE KEY `tdatetime` (`tdatetime`),
  KEY `fk_transaction_account` (`account_id`),
  KEY `split_parent_id` (`split_parent_id`),
  KEY `splitted` (`splitted`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `transaction_schedule` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) NOT NULL,
  `next_exec_date` date NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` float NOT NULL,
  `type` enum('Expense','Income') NOT NULL,
  `inc_day` tinyint(4) NOT NULL,
  `inc_month` tinyint(4) NOT NULL,
  `tags` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `transaction_tag` (
  `transaction_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`transaction_id`,`tag_id`),
  KEY `fk_transaction_has_tag_tag1` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(20) DEFAULT NULL,
  `passwd` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

