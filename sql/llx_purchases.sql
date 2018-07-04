CREATE TABLE `llx_purchases` (
  `rowid` int(11) NOT NULL auto_increment,
  `ts_create` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fk_user_author` int(11) DEFAULT NULL,
  `label` varchar(128) COLLATE utf8_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8_unicode_ci,
  `fk_project` int(11) DEFAULT NULL,
  `status` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `s_products` text COLLATE utf8_unicode_ci NULL,
  `n_products` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY  (`rowid`)
) ENGINE=innodb AUTO_INCREMENT=1 ;