CREATE TABLE `0_emp_jobs` (
  `id` bigint(8) NOT NULL AUTO_INCREMENT,
  `employee_id` bigint(8) NOT NULL,
  `designation_id` smallint(2) NOT NULL,
  `department_id` smallint(2) NOT NULL,
  `commence_from` date NOT NULL,
  `week_offs` json DEFAULT NULL,
  `default_shift_id` int(4) DEFAULT NULL,
  `has_commission` tinyint(1) DEFAULT 1,
  `has_pension` tinyint(1) DEFAULT 0,
  `has_overtime` tinyint(1) DEFAULT 1,
  `require_attendance` tinyint(1) DEFAULT 1,
  `supervisor_id` bigint(8) DEFAULT NULL,
  `is_current` tinyint(1) NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `current_job` (`employee_id`,`is_current`),
  KEY `employee_id` (`employee_id`),
  KEY `designation_id` (`designation_id`),
  KEY `department_id` (`department_id`),
  KEY `supervisor_id` (`supervisor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;