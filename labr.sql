/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-11.8.8-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: labr
-- ------------------------------------------------------
-- Server version	11.8.8-MariaDB-1 from Debian

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `doctors`
--

DROP TABLE IF EXISTS `doctors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `doctors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `doctors`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `doctors` WRITE;
/*!40000 ALTER TABLE `doctors` DISABLE KEYS */;
INSERT INTO `doctors` VALUES
(1,'Self / Direct',NULL,'2026-09-06 05:44:56'),
(2,'Dr. Ahmad Khan',NULL,'2026-09-06 05:44:56'),
(3,'Dr. Ali Raza',NULL,'2026-09-06 05:44:56');
/*!40000 ALTER TABLE `doctors` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `lab_profile`
--

DROP TABLE IF EXISTS `lab_profile`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_profile` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_profile`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `lab_profile` WRITE;
/*!40000 ALTER TABLE `lab_profile` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_profile` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `mr_counters`
--

DROP TABLE IF EXISTS `mr_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `mr_counters` (
  `yr` char(2) NOT NULL,
  `last_seq` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`yr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mr_counters`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `mr_counters` WRITE;
/*!40000 ALTER TABLE `mr_counters` DISABLE KEYS */;
INSERT INTO `mr_counters` VALUES
('26',1);
/*!40000 ALTER TABLE `mr_counters` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `patients`
--

DROP TABLE IF EXISTS `patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `gender` varchar(15) DEFAULT 'Male',
  `phone` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT 0,
  `address` varchar(255) DEFAULT NULL,
  `mr_no` varchar(50) DEFAULT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `patients`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `patients` WRITE;
/*!40000 ALTER TABLE `patients` DISABLE KEYS */;
INSERT INTO `patients` VALUES
(1,'Muhammad Toufail','Male',NULL,12,'','26/000001','','2026-09-05 19:33:33'),
(2,'Muhammadtufail','Male',NULL,25,NULL,'MR-20260906-353','','2026-09-06 05:48:05'),
(12,'Muhammadtufail','Male',NULL,25,NULL,'MR-20260906-492','','2026-09-06 07:52:35'),
(13,'Muhammadtufail','Male',NULL,25,NULL,'MR-20260906-252','','2026-09-06 07:54:42'),
(14,'Muhammadtufail','Male',NULL,25,NULL,'MR-20260906-310','03419531374','2026-09-06 07:59:47'),
(15,'Bilal','Male',NULL,25,'','MR-20260906-861','','2026-09-06 08:17:10'),
(16,'Kalim','Male',NULL,25,NULL,'MR-20260906-887','','2026-09-06 08:59:11'),
(17,'Muhammadtufail','Male',NULL,25,NULL,'MR-20260906-294','','2026-09-06 09:03:58'),
(18,'Bilal','Male',NULL,25,NULL,'MR-20260906-563','','2026-09-06 09:09:27');
/*!40000 ALTER TABLE `patients` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `receipt_items`
--

DROP TABLE IF EXISTS `receipt_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipt_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_id` int(11) NOT NULL,
  `test_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `receipt_id` (`receipt_id`),
  KEY `test_id` (`test_id`),
  CONSTRAINT `receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `receipts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `receipt_items_ibfk_2` FOREIGN KEY (`test_id`) REFERENCES `tests` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipt_items`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `receipt_items` WRITE;
/*!40000 ALTER TABLE `receipt_items` DISABLE KEYS */;
INSERT INTO `receipt_items` VALUES
(1,4,67,100.00);
/*!40000 ALTER TABLE `receipt_items` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `receipts`
--

DROP TABLE IF EXISTS `receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `patient_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `patient_id` (`patient_id`),
  CONSTRAINT `receipts_ibfk_1` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipts`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `receipts` WRITE;
/*!40000 ALTER TABLE `receipts` DISABLE KEYS */;
INSERT INTO `receipts` VALUES
(4,15,3,100.00,0.00,100.00,0.00,'2026-09-06 09:38:49');
/*!40000 ALTER TABLE `receipts` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `test_categories`
--

DROP TABLE IF EXISTS `test_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `test_categories`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `test_categories` WRITE;
/*!40000 ALTER TABLE `test_categories` DISABLE KEYS */;
INSERT INTO `test_categories` VALUES
(1,'General Pathology'),
(2,'Hematology'),
(3,'Biochemistry'),
(4,'Microbiology'),
(5,'Radiology'),
(6,'Biochemistry Analysis'),
(7,'Semen Analysis'),
(8,'TORCH Profile'),
(9,'Immunology'),
(10,'Virology'),
(11,'General Analysis'),
(12,'Urine Analysis'),
(13,'Stool Analysis');
/*!40000 ALTER TABLE `test_categories` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `tests`
--

DROP TABLE IF EXISTS `tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `rate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `test_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=73 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tests`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `tests` WRITE;
/*!40000 ALTER TABLE `tests` DISABLE KEYS */;
INSERT INTO `tests` VALUES
(1,'CBC (Complete Blood Count)',500.00,NULL),
(2,'Blood Glucose Random',200.00,NULL),
(3,'CBC',200.00,2),
(4,'Blood Group',100.00,2),
(5,'PT INR',200.00,2),
(6,'Aptt',200.00,2),
(7,'FBS',50.00,3),
(8,'RBS',50.00,3),
(9,'Electrolyte',300.00,3),
(10,'LFT',250.00,3),
(11,'RFT',200.00,3),
(12,'LIPID PROFILE',500.00,3),
(13,'E.GFR',300.00,3),
(14,'BUN',300.00,3),
(15,'URIC ACID',100.00,3),
(16,'HBA1C',600.00,3),
(17,'CALCIUM',100.00,3),
(18,'Calcium ++',200.00,3),
(19,'Urine ACR',500.00,3),
(20,'Urine PCR',500.00,3),
(21,'Cardic enzymes',1000.00,3),
(22,'CPK',300.00,3),
(23,'S.Albumin',200.00,3),
(24,'CRP Q',400.00,11),
(25,'G6pd',700.00,2),
(26,'Urine  R/E',100.00,12),
(27,'Stool R/E',150.00,13),
(28,'Semen Analysis',300.00,7),
(29,'Troch profile',2400.00,8),
(30,'Hbs',200.00,10),
(31,'Hcv',200.00,10),
(32,'HBS hCV',300.00,10),
(33,'Hiv',300.00,10),
(34,'Hbs elisa',500.00,10),
(35,'Hcv elisa',500.00,10),
(36,'OGTT',600.00,3),
(37,'Ketone',300.00,3),
(38,'Dengue',450.00,9),
(39,'Widal',250.00,9),
(40,'Mp',200.00,9),
(41,'Typhidot',300.00,9),
(42,'Ict TB',300.00,9),
(43,'VDRL',300.00,9),
(44,'ASO',300.00,3),
(45,'RA Factor',300.00,9),
(46,'Sp.smer',400.00,2),
(47,'ESR',150.00,2),
(48,'Urine drugs tset',800.00,12),
(49,'Hbe Ag',500.00,3),
(50,'Hbe Ab',500.00,3),
(51,'Bt',200.00,2),
(52,'Ct',200.00,2),
(53,'BT CT',400.00,2),
(54,'FSH',500.00,11),
(55,'LH',500.00,11),
(56,'Prolactin',500.00,11),
(57,'Testosterone',500.00,11),
(58,'AMH',1500.00,11),
(59,'B.Hcg',500.00,11),
(60,'Cholesterol',100.00,3),
(61,'Triglycerides',100.00,3),
(62,'V.LDL',300.00,3),
(63,'HDL',250.00,3),
(64,'LDL',550.00,3),
(65,'D.Dimer',700.00,2),
(66,'FDPS',800.00,2),
(67,'Cp parint',100.00,2),
(68,'TOXO p',300.00,9),
(69,'Potassium',200.00,3),
(70,'Sputum AFB',300.00,3),
(71,'ANTI CCP',1500.00,6),
(72,'Folic acid',1500.00,6);
/*!40000 ALTER TABLE `tests` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(120) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Admin','admin@lab.com','$2y$12$AdVMhMhobNHsnGTxspLSIuQIEfiuN1u29BTPJoWOb.z96cwHYMYp2','2026-09-05 19:03:15');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-09-06 12:06:18
