-- NOTE: If your WordPress table prefix is not 'wp_', please rename `wp_pincode_directory` below.
CREATE TABLE IF NOT EXISTS `wp_pincode_directory` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `pincode` varchar(10) NOT NULL,
  `officename` varchar(150) NOT NULL,
  `pincode_type` varchar(50) DEFAULT NULL,
  `deliverystatus` varchar(50) DEFAULT NULL,
  `divisionname` varchar(100) DEFAULT NULL,
  `regionname` varchar(100) DEFAULT NULL,
  `circlename` varchar(100) DEFAULT NULL,
  `taluk` varchar(100) DEFAULT NULL,
  `districtname` varchar(100) NOT NULL,
  `statename` varchar(100) NOT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `related_suboffice` varchar(100) DEFAULT NULL,
  `related_headoffice` varchar(100) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `slug` varchar(200) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pincode` (`pincode`),
  KEY `district_state` (`districtname`, `statename`),
  KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
