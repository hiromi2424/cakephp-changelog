# changelog_columns
# ------------------------------------------------------------

DROP TABLE IF EXISTS `changelog_columns`;

CREATE TABLE `changelog_columns` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `changelog_id` int(11) NOT NULL,
  `column` char(50) NOT NULL DEFAULT '',
  `before` longtext,
  `after` longtext,
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `changelog_id` (`changelog_id`)
) ENGINE=InnoDB;



# changelogs
# ------------------------------------------------------------

DROP TABLE IF EXISTS `changelogs`;

CREATE TABLE `changelogs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `model` char(30) NOT NULL DEFAULT '',
  `foreign_key` char(36) NOT NULL DEFAULT '',
  `is_new` tinyint(1) NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `model_foreign_key` (`model`,`foreign_key`)
) ENGINE=InnoDB;