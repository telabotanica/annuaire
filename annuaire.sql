CREATE TABLE `users` (
  `ID` bigint(20) UNSIGNED NOT NULL,
  `user_login` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_pass` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_nicename` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_url` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_registered` datetime NULL,
  `user_activation_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `user_status` int(11) NOT NULL DEFAULT '0',
  `display_name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usermeta` (
 `umeta_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 `user_id` bigint(20) unsigned NOT NULL DEFAULT '0',
 `meta_key` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `meta_value` longtext COLLATE utf8mb4_unicode_ci,
 PRIMARY KEY (`umeta_id`),
 KEY `user_id` (`user_id`),
 KEY `meta_key` (`meta_key`(191))
) ENGINE=InnoDB AUTO_INCREMENT=891313 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`ID`, `user_login`, `user_pass`, `user_nicename`, `user_email`, `user_url`, `user_registered`, `user_activation_key`, `user_status`, `display_name`) VALUES
(1, 'demo-user@example.org', '78838781849b8b78bc97403dfa2a327a', 'demo-user', 'demo-user@example.org', '', '2016-10-17 12:30:45', '1507884852:$P$B8nQgST869NUKyTjxiC9P1sokhi3MY0', 0, 'demo-user');