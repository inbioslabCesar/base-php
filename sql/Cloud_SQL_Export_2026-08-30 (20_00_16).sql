-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: 127.0.0.1    Database: tarifario_inbioslab
-- ------------------------------------------------------
-- Server version	8.0.45-google

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `tarifario_inbioslab`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `tarifario_inbioslab` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `tarifario_inbioslab`;

--
-- Table structure for table `app_settings`
--

DROP TABLE IF EXISTS `app_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `app_settings` (
  `setting_key` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` varchar(600) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `app_settings`
--

LOCK TABLES `app_settings` WRITE;
/*!40000 ALTER TABLE `app_settings` DISABLE KEYS */;
INSERT INTO `app_settings` VALUES ('whatsapp_quote_number','945241682','2026-05-31 19:05:15','2026-05-31 19:05:15');
/*!40000 ALTER TABLE `app_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams_audit_log`
--

DROP TABLE IF EXISTS `exams_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `exams_audit_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exam_id` int DEFAULT NULL,
  `actor` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_role` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_exams_audit_log_exam_id` (`exam_id`),
  KEY `idx_exams_audit_log_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams_audit_log`
--

LOCK TABLES `exams_audit_log` WRITE;
/*!40000 ALTER TABLE `exams_audit_log` DISABLE KEYS */;
INSERT INTO `exams_audit_log` VALUES (1,'UPDATE_EXAM',1,'role:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS\"}','2026-05-31 06:39:04'),(2,'UPDATE_EXAM',1,'role:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS REAL\"}','2026-05-31 06:39:28'),(3,'UPDATE_EXAM',1,'role:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS\"}','2026-05-31 06:53:28'),(4,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS ee\"}','2026-05-31 19:21:11'),(5,'UPDATE_EXAM',2,'admin:admin','admin','{\"name\": \"acido folico b9\"}','2026-05-31 19:21:55'),(6,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS\"}','2026-05-31 20:53:58'),(7,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS TEST MOVIL\"}','2026-05-31 20:57:04'),(8,'CREATE_EXAM',183,'admin:admin','admin','{\"name\": \"ZZ TEST CRUD MOVIL\"}','2026-05-31 20:57:38'),(9,'DELETE_EXAM',183,'admin:admin','admin','{\"name\": \"ZZ TEST CRUD MOVIL\"}','2026-05-31 20:58:19'),(10,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS\"}','2026-05-31 20:59:01'),(11,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS rr\"}','2026-05-31 21:00:02'),(12,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS pp\"}','2026-05-31 21:00:32'),(13,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS\"}','2026-05-31 21:28:31'),(14,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS\"}','2026-05-31 21:28:34'),(15,'UPDATE_EXAM',2,'admin:admin','admin','{\"name\": \"acido folico\"}','2026-05-31 21:28:58'),(16,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS g\"}','2026-05-31 21:32:04'),(17,'promotion_created',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"perfil tiroideo(tsh-t4 libre-t3)\", \"promoPrice\": 120, \"promotionId\": 1}','2026-05-31 21:42:56'),(18,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"perfil tiroideo(tsh-t4 libre-t3)\", \"promoPrice\": 120, \"promotionId\": 1}','2026-05-31 21:44:10'),(19,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"perfil tiroideo(tsh-t4 libre-t3)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-05-31 21:45:39'),(20,'UPDATE_EXAM',1,'admin:admin','admin','{\"name\": \"A.N.C.A ANTI-NEUTROFILOS\"}','2026-05-31 21:51:48'),(21,'UPDATE_EXAM',26,'admin:admin','admin','{\"name\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\"}','2026-05-31 21:53:49'),(22,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T4LIBRE-T3)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 02:11:07'),(23,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 02:14:13'),(24,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 02:16:59'),(25,'UPDATE_EXAM',2,'admin:admin','admin','{\"name\": \"ACIDO FOLICO(VIT. B9)\"}','2026-06-01 02:20:44'),(26,'UPDATE_EXAM',3,'admin:admin','admin','{\"name\": \"AGA Y ELECTROLITOS (ADOMICILIO)\"}','2026-06-01 02:28:01'),(27,'UPDATE_EXAM',4,'admin:admin','admin','{\"name\": \"ALFAFETOPROTEINA (AFP)\"}','2026-06-01 02:29:20'),(28,'UPDATE_EXAM',5,'admin:admin','admin','{\"name\": \"AMILASA SERICA\"}','2026-06-01 02:29:41'),(29,'UPDATE_EXAM',68,'admin:admin','admin','{\"name\": \"VITAMINA B12\"}','2026-06-01 02:32:47'),(30,'UPDATE_EXAM',179,'admin:admin','admin','{\"name\": \"VITAMINA D TOTAL 25-OH\"}','2026-06-01 02:34:25'),(31,'UPDATE_EXAM',179,'admin:admin','admin','{\"name\": \"VITAMINA D TOTAL 25-OH (D2-D3)\"}','2026-06-01 02:35:14'),(32,'UPDATE_EXAM',159,'admin:admin','admin','{\"name\": \"vitamina d total 25-hidroxivitamina d (d2+d3)-lima VITAMINA D TOTAL 25-HIDROXIVITAMINA D (D2-D3)-LIMA\"}','2026-06-01 02:39:11'),(33,'UPDATE_EXAM',159,'admin:admin','admin','{\"name\": \"VITAMINA D TOTAL 25-HIDROXIVITAMINA D (D2-D3)-LIMA\"}','2026-06-01 02:39:25'),(34,'UPDATE_EXAM',8,'admin:admin','admin','{\"name\": \"ANTICUERPOS ANTINUCLEARES(ANA)-LIMA\"}','2026-06-01 02:43:17'),(35,'UPDATE_EXAM',136,'admin:admin','admin','{\"name\": \"complemento c3-c4\"}','2026-06-01 02:44:01'),(36,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 03:29:06'),(37,'promotion_created',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 60, \"promotionId\": 2}','2026-06-01 04:26:50'),(38,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 60, \"promotionId\": 2}','2026-06-01 04:34:09'),(39,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 60, \"promotionId\": 2}','2026-06-01 04:36:09'),(40,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 60, \"promotionId\": 2}','2026-06-01 04:44:03'),(41,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 60, \"promotionId\": 2}','2026-06-01 04:45:34'),(42,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-01 04:47:07'),(43,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-01 04:51:54'),(44,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-01 04:53:54'),(45,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 05:13:28'),(46,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 05:13:49'),(47,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 05:18:03'),(48,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 05:19:40'),(49,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 05:21:54'),(50,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 05:33:12'),(51,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 05:37:42'),(52,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-01 05:38:47'),(53,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 07:32:29'),(54,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-01 07:32:53'),(55,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-01 08:03:03'),(56,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 08:03:26'),(57,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-01 11:52:57'),(58,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-01 11:53:08'),(59,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-02 23:31:52'),(60,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-02 23:32:02'),(61,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-03 01:11:13'),(62,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-03 01:22:06'),(63,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-03 01:22:16'),(64,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-03 02:04:38'),(65,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-03 02:33:54'),(66,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-03 02:34:04'),(67,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-03 02:44:44'),(68,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-03 02:46:08'),(69,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 60, \"title\": \"ACIDO FOLICO(VIT. B9)\", \"promoPrice\": 50, \"promotionId\": 2}','2026-06-03 02:46:28'),(70,'promotion_updated',NULL,'admin:admin','admin','{\"price\": 180, \"title\": \"PERFIL TIROIDEO(TSH-T3-T4 LIBRE)\", \"promoPrice\": 150, \"promotionId\": 1}','2026-06-03 02:59:41'),(71,'CREATE_EXAM',184,'admin:admin','admin','{\"name\": \"Cultivo de secreción uretral\"}','2026-06-06 21:49:20'),(72,'UPDATE_EXAM',184,'admin:admin','admin','{\"name\": \"Cultivo de secreción uretral\"}','2026-06-06 21:51:04'),(73,'UPDATE_EXAM',8,'admin:admin','admin','{\"name\": \"ANTICUERPOS ANTINUCLEARES(ANA)-LIMA\"}','2026-06-13 02:53:56'),(74,'UPDATE_EXAM',68,'admin:admin','admin','{\"name\": \"VITAMINA B12\"}','2026-06-13 05:54:42'),(75,'UPDATE_EXAM',66,'admin:admin','admin','{\"name\": \"serologia cualitativa rpr-sifilis\"}','2026-06-17 20:35:00'),(76,'UPDATE_EXAM',66,'admin:admin','admin','{\"name\": \"serologia cualitativa rpr-sifilis\"}','2026-06-17 20:35:03'),(77,'UPDATE_EXAM',67,'admin:admin','admin','{\"name\": \"serologia semi-cuantitativa rpr-sifilis\"}','2026-06-17 20:35:53'),(78,'CREATE_EXAM',185,'admin:admin','admin','{\"name\": \"Coeficiente Albumina/Creatnina\"}','2026-06-18 18:10:28'),(79,'CREATE_EXAM',186,'admin:admin','admin','{\"name\": \"Transferrina\"}','2026-06-22 16:21:10'),(80,'UPDATE_EXAM',186,'admin:admin','admin','{\"name\": \"Transferrina\"}','2026-06-22 16:22:19'),(81,'UPDATE_EXAM',186,'admin:admin','admin','{\"name\": \"Transferrina\"}','2026-06-22 16:22:47'),(82,'UPDATE_EXAM',179,'admin:admin','admin','{\"name\": \"VITAMINA D TOTAL 25-OH (D2-D3)\"}','2026-08-12 13:30:45'),(83,'UPDATE_EXAM',159,'admin:admin','admin','{\"name\": \"VITAMINA D TOTAL 25-HIDROXIVITAMINA D (D2-D3)-LIMA\"}','2026-08-12 13:31:30'),(84,'CREATE_EXAM',187,'admin:admin','admin','{\"name\": \"INVESTIGACIÓN DE LESHMANIA\"}','2026-08-24 17:05:55'),(85,'UPDATE_EXAM',187,'admin:admin','admin','{\"name\": \"INVESTIGACIÓN DE LESHMANIA\"}','2026-08-24 17:06:21');
/*!40000 ALTER TABLE `exams_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_accessories`
--

DROP TABLE IF EXISTS `inventory_accessories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_accessories` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_id` bigint NOT NULL,
  `accessory_name` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `brand` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `serial_number` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ingress_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_accessories_equipment_id` (`equipment_id`),
  CONSTRAINT `fk_inventory_accessories_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `inventory_equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_accessories`
--

LOCK TABLES `inventory_accessories` WRITE;
/*!40000 ALTER TABLE `inventory_accessories` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_accessories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_equipment`
--

DROP TABLE IF EXISTS `inventory_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_equipment` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `company_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'united_trading_sac',
  `equipment_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'laboratorio',
  `brand` varchar(140) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `serial_number` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `invoice_number` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `import_date` date DEFAULT NULL,
  `install_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ingress_date` date DEFAULT NULL,
  `condition_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cesion_en_uso',
  `operational_status` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operativo',
  `next_maintenance_date` date DEFAULT NULL,
  `maintenance_alert_days` int NOT NULL DEFAULT '30',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_equipment_serial` (`serial_number`),
  KEY `idx_inventory_equipment_company` (`company_name`),
  KEY `idx_inventory_equipment_type` (`equipment_type`),
  KEY `idx_inventory_equipment_status` (`operational_status`),
  KEY `idx_inventory_equipment_maintenance` (`next_maintenance_date`)
) ENGINE=InnoDB AUTO_INCREMENT=82 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_equipment`
--

LOCK TABLES `inventory_equipment` WRITE;
/*!40000 ALTER TABLE `inventory_equipment` DISABLE KEYS */;
INSERT INTO `inventory_equipment` VALUES (1,'united_trading_sac','laboratorio','B&E BIOTECHNOLOGY','HEMAX 53','HRF012835','---','2026-05-30','HOSPITAL AMAZONICO DE YARINACOCHA','LABORATORIO CENTRAL','2026-05-30','cesion_en_uso','operativo','2026-08-01',10,NULL,'2026-06-03 21:25:26','2026-06-03 21:25:26'),(2,'united_trading_sac','laboratorio','THERMOBIO','CHALLENGE IV','ES158WD2060','---',NULL,'HOSPITAL AMAZONICO DE YARINACOCHA','LAB. CENTRAL-BIOQUIMICA','2025-06-01','cesion_en_uso','operativo','2026-08-01',10,NULL,'2026-06-03 21:30:33','2026-06-03 21:30:33'),(3,'united_trading_sac','laboratorio','B&E BIOTECHNOLOGY','HEMAX 33','H30E460280','--',NULL,'HOSPITAL AMAZONICO DE YARINACOCHA','LAB. EMERGENCIA','2026-05-18','cesion_en_uso','operativo','2026-08-01',10,NULL,'2026-06-03 21:38:26','2026-06-03 21:38:26'),(4,'comercial_importadora_sudamericana_sac','laboratorio','THERMOBIO INC','CHALLENGE IV','ES1J9W03147Y',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','inoperativo',NULL,30,'Equipo: FULLY-AUTO CHEMISTRY ANALYZER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(5,'comercial_importadora_sudamericana_sac','laboratorio','HALION','S/N','S/N',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'en_desuso','operativo',NULL,30,'Equipo: PC | Observacion: MONITOR, TECLADO, MOUSE | Estado original: OPERATIVO / SIN USO','2026-06-04 13:28:47','2026-06-04 13:35:02'),(6,'comercial_importadora_sudamericana_sac','laboratorio','HIGH POWER','PIC-5T-2000TM','171021-02',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ESTABILIZADOR','2026-06-04 13:28:47','2026-06-04 13:35:02'),(7,'comercial_importadora_sudamericana_sac','laboratorio','KAISEN','KUE-T02-WB-CH','9000.22102.2106.47',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: UPS','2026-06-04 13:28:47','2026-06-04 13:35:02'),(8,'comercial_importadora_sudamericana_sac','laboratorio','GAMATEC','TRANSF. AISLAMIENTO','2107200567TRFAL-2K',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ESTABILIZADOR','2026-06-04 13:28:47','2026-06-04 13:35:02'),(9,'comercial_importadora_sudamericana_sac','laboratorio','KAISEN','KUE-T02-WBCH','9.00022E+14',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: UPS','2026-06-04 13:28:47','2026-06-04 13:35:02'),(10,'comercial_importadora_sudamericana_sac','laboratorio','THERMOBIO INC','CHALLENGE IV','F0EU00SXLD',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: FULLY-AUTO CHEMISTRY ANALYZER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(11,'comercial_importadora_sudamericana_sac','laboratorio','LENOVO','ES2K1K07131Y','MP29749N',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ALL IN ONE | Observacion: TECLADO, MOUSE','2026-06-04 13:28:47','2026-06-04 13:35:02'),(12,'comercial_importadora_sudamericana_sac','laboratorio','HP','SEOLA-1802-01','BRBSQ7T43',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: IMPRESORA LASER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(13,'comercial_importadora_sudamericana_sac','laboratorio','LG','22V280','812NZCG002699',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ALL IN ONE | Observacion: USO PARA INFORME DE ESTADISTICA MENSUAL','2026-06-04 13:28:47','2026-06-04 13:35:02'),(14,'comercial_importadora_sudamericana_sac','laboratorio','ROBONIK','PRIETEST TOUCH','AT6150317RBK',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: BIOCHEMISTRY ANALYSER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(15,'comercial_importadora_sudamericana_sac','laboratorio','VONDFO','BGA-102','BGA1022207200549',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','inoperativo',NULL,30,'Equipo: ANALIZADOR DE GASES ARTERIALES','2026-06-04 13:28:47','2026-06-04 13:35:02'),(16,'comercial_importadora_sudamericana_sac','laboratorio','EAGLENOS','EN102','EN10224060302',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ANALIZADOR DE GASES ARTERIALES','2026-06-04 13:28:47','2026-06-04 13:35:02'),(17,'comercial_importadora_sudamericana_sac','aire_acondicionado','MONTERO-INVERTER','ELITE18K-24','15DJ3NPB00ZPB1300088',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BIOQUIMICA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: SPLIT TYPE AIR CONDITIONER-18BTU','2026-06-04 13:28:47','2026-06-04 13:35:02'),(18,'united_trading_sac','laboratorio','PROKAN','PE-7100','10107001.2109.00046.086999.00001',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','HEMATOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: FULLY AUTO HEMATOLOGY ANALIZER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(19,'united_trading_sac','laboratorio','LENOVO','F0G000TXLD','MP1ZAYAM',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','HEMATOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ALL IN ONE','2026-06-04 13:28:47','2026-06-04 13:35:02'),(20,'united_trading_sac','laboratorio','HP','SDGOB-1392','BRBSQ3Y0H5',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','HEMATOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: IMPRESORA LASER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(21,'united_trading_sac','laboratorio','GAMATEC','KUE-RTO1-WB-CH','9000.120113.200781',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','HEMATOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ESTABILIZADOR','2026-06-04 13:28:47','2026-06-04 13:35:02'),(22,'united_trading_sac','laboratorio','LINEEAR','ARES','482302045',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','HEMATOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: COAGULATION ANALYZER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(23,'united_trading_sac','aire_acondicionado','MONTERO','M12K-ONOFF23','S/N-HEMATOLOGIA-20',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','HEMATOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: SPLIT TYPE AIR CONDITIONER -INVERTER | Serial original: S/N','2026-06-04 13:28:47','2026-06-04 13:35:02'),(24,'comercial_importadora_sudamericana_sac','laboratorio','LENOVO','F0G000TXLD','MP1ZB4SX',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BANCO DE SANGRE',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ALL IN ONE','2026-06-04 13:28:47','2026-06-04 13:35:02'),(25,'comercial_importadora_sudamericana_sac','laboratorio','AUTOBIO','IWO','2041001893',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BANCO DE SANGRE',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: MICROPLATE WASHER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(26,'comercial_importadora_sudamericana_sac','laboratorio','HP','SEOLA-1802-01','BRBSP7N1YN',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BANCO DE SANGRE',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: IMPRESORA LASER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(27,'comercial_importadora_sudamericana_sac','laboratorio','LENOVO','F0G000TXLD','MP1Z84SX',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BANCO DE SANGRE',NULL,'cesion_en_uso','inoperativo',NULL,30,'Equipo: ALL IN ONE','2026-06-04 13:28:47','2026-06-04 13:35:02'),(28,'comercial_importadora_sudamericana_sac','laboratorio','AUTOBIO','PHOMO','3011004655',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','BANCO DE SANGRE',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: MICROPLATE PHOTOMETER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(29,'comercial_importadora_sudamericana_sac','laboratorio','LABNOVATION','LD-500','LD548105436',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ANALIZADOR AUTOMATIZADO DE HPLC','2026-06-04 13:28:47','2026-06-04 13:35:02'),(30,'comercial_importadora_sudamericana_sac','laboratorio','CDP','UPO11-2i','85827.7001.424',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: UPS','2026-06-04 13:28:47','2026-06-04 13:35:02'),(31,'comercial_importadora_sudamericana_sac','laboratorio','POWERRONIC','S/N','67375',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ESTABILIZADOR','2026-06-04 13:28:47','2026-06-04 13:35:02'),(32,'comercial_importadora_sudamericana_sac','laboratorio','YHLO','iFlash 1800-A','IA0006540A',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: CHEMILUMINESCENCE IMMUNOASSAY ANALIZER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(33,'comercial_importadora_sudamericana_sac','laboratorio','KAISEN','KUE-T02-WB-CH','9000.221022.10656',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: UPS','2026-06-04 13:28:47','2026-06-04 13:35:02'),(34,'comercial_importadora_sudamericana_sac','laboratorio','HP','SEOLA-1802-01','BRBSP7N1YNYH',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: IMPRESORA LASER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(35,'comercial_importadora_sudamericana_sac','laboratorio','LENOVO','F0E800DSLD','MP1RPSJY',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ALL IN ONE | Observacion: TECLADO, MOUSE','2026-06-04 13:28:47','2026-06-04 13:35:02'),(36,'comercial_importadora_sudamericana_sac','aire_acondicionado','LG','OM182C1.NJRO','403CRSF20454',NULL,NULL,'HOSPITAL DE PUCALLPA- EMERGENCIA','INMUNOLOGIA',NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: AIR COINDITIONER','2026-06-04 13:28:47','2026-06-04 13:35:02'),(68,'united_trading_sac','laboratorio','PROKAN','PE-7100','1.0107001.2109.00043.086999.00001',NULL,NULL,'HOSPITAL DE YARINA-CENTRAL',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: FULLY AUTO HEMATOLOGY ANALYZER','2026-06-04 13:43:37','2026-06-04 13:48:09'),(69,'comercial_importadora_sudamericana_sac','laboratorio','HP','BOISB-0207-00','CNCKC90759',NULL,NULL,'HOSPITAL DE YARINA-CENTRAL',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: IMPRESORA LASER','2026-06-04 13:43:37','2026-06-04 13:50:22'),(70,'united_trading_sac','laboratorio','KAISEN','KUE-RT01-WB-CH','900012104260260.00',NULL,NULL,'HOSPITAL DE YARINA-CENTRAL',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: UPS','2026-06-04 13:43:37','2026-06-04 13:51:46'),(71,'comercial_importadora_sudamericana_sac','laboratorio','THERMOBIO INC','CHALLENGE III','ES1J8W02059',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: BIOQUIMICO AUTOMATIZADO','2026-06-04 13:43:37','2026-06-04 13:52:20'),(72,'comercial_importadora_sudamericana_sac','laboratorio','HP','CE858A','BRBSGBJFTJ',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: IMPRESORA LASER','2026-06-04 13:43:37','2026-06-05 01:56:00'),(73,'comercial_importadora_sudamerica_sac','laboratorio','VIEW SONIC','VA1903H','VR4211021865',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: PANTALLA LCD','2026-06-04 13:43:37','2026-06-04 13:43:37'),(74,'comercial_importadora_sudamericana_sac','laboratorio','POWER TECNOLOGIES','FX-1500LCD-U','22036350063',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: UPS','2026-06-04 13:43:37','2026-06-05 01:56:27'),(75,'comercial_importadora_sudamericana_sac','laboratorio','LENOVO','F0BB','MP10TNES',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ALL IN ONE','2026-06-04 13:43:37','2026-06-05 01:54:45'),(76,'comercial_importadora_sudamericana_sac','laboratorio','ENKORE EPIC','S/N','S/N-CONTIN-ENKORE-EPIC',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','inoperativo',NULL,30,'Equipo: PC | Serial original: S/N','2026-06-04 13:43:37','2026-06-05 01:53:47'),(77,'comercial_importadora_sudamericana_sac','laboratorio','POWER SAFE','TAMF-05','1706084',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ESTABILIZADOR','2026-06-04 13:43:37','2026-06-04 13:45:02'),(78,'comercial_importadora_sudamericana_sac','laboratorio','ROBONIK','PRIETEST TOUCH','AT6080317RBK',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ANALIZADOR BIOQUIMICO SEMIAUTOMATICO','2026-06-04 13:43:37','2026-06-05 01:52:56'),(79,'comercial_importadora_sudamericana_sac','laboratorio','LINEAR','ARES','481809009',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: CUAGULOMETRO | Observacion: PIPETA SENSOR MALOGRADO','2026-06-04 13:43:37','2026-06-05 01:52:27'),(80,'comercial_importadora_sudamericana_sac','laboratorio','HIGH POWER','5T-500TM','422ETU037',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: ESTABILIZADOR','2026-06-04 13:43:37','2026-06-05 01:51:03'),(81,'comercial_importadora_sudamericana_sac','aire_acondicionado','LG','VM242C9','011TAEJD7540',NULL,NULL,'HOSPITAL REGIONAL DE PUCALLPA-CONTIN',NULL,NULL,'cesion_en_uso','operativo',NULL,30,'Equipo: SPLIT ROOM AIR CONDITIONER 18 BTU','2026-06-04 13:43:37','2026-06-04 13:46:07');
/*!40000 ALTER TABLE `inventory_equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_maintenance`
--

DROP TABLE IF EXISTS `inventory_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_maintenance` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_id` bigint NOT NULL,
  `planned_date` date NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_maintenance_equipment_id` (`equipment_id`),
  KEY `idx_inventory_maintenance_planned` (`planned_date`),
  CONSTRAINT `fk_inventory_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `inventory_equipment` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_maintenance`
--

LOCK TABLES `inventory_maintenance` WRITE;
/*!40000 ALTER TABLE `inventory_maintenance` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_schedule_executions`
--

DROP TABLE IF EXISTS `inventory_schedule_executions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_schedule_executions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `plan_id` bigint NOT NULL,
  `performed_at` date NOT NULL,
  `month_number` int NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_schedule_executions_plan_month` (`plan_id`,`month_number`),
  KEY `idx_inventory_schedule_executions_plan` (`plan_id`),
  CONSTRAINT `fk_inventory_schedule_executions_plan` FOREIGN KEY (`plan_id`) REFERENCES `inventory_schedule_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_schedule_executions`
--

LOCK TABLES `inventory_schedule_executions` WRITE;
/*!40000 ALTER TABLE `inventory_schedule_executions` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_schedule_executions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_schedule_plan_actions`
--

DROP TABLE IF EXISTS `inventory_schedule_plan_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_schedule_plan_actions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `plan_id` bigint NOT NULL,
  `action_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_schedule_plan_actions_plan` (`plan_id`),
  CONSTRAINT `fk_inventory_schedule_plan_actions_plan` FOREIGN KEY (`plan_id`) REFERENCES `inventory_schedule_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_schedule_plan_actions`
--

LOCK TABLES `inventory_schedule_plan_actions` WRITE;
/*!40000 ALTER TABLE `inventory_schedule_plan_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_schedule_plan_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_schedule_plans`
--

DROP TABLE IF EXISTS `inventory_schedule_plans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_schedule_plans` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `equipment_id` bigint NOT NULL,
  `template_id` bigint NOT NULL,
  `company_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hospital_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `frequency_months` int NOT NULL,
  `start_date` date NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_schedule_plans_equipment` (`equipment_id`),
  KEY `idx_inventory_schedule_plans_template` (`template_id`),
  KEY `idx_inventory_schedule_plans_company` (`company_name`),
  KEY `idx_inventory_schedule_plans_year` (`year`),
  CONSTRAINT `fk_inventory_schedule_plans_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `inventory_equipment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inventory_schedule_plans_template` FOREIGN KEY (`template_id`) REFERENCES `inventory_schedule_templates` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_schedule_plans`
--

LOCK TABLES `inventory_schedule_plans` WRITE;
/*!40000 ALTER TABLE `inventory_schedule_plans` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_schedule_plans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_schedule_template_actions`
--

DROP TABLE IF EXISTS `inventory_schedule_template_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_schedule_template_actions` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `template_id` bigint NOT NULL,
  `action_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_schedule_template_actions_template` (`template_id`),
  CONSTRAINT `fk_inventory_schedule_template_actions_template` FOREIGN KEY (`template_id`) REFERENCES `inventory_schedule_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_schedule_template_actions`
--

LOCK TABLES `inventory_schedule_template_actions` WRITE;
/*!40000 ALTER TABLE `inventory_schedule_template_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_schedule_template_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_schedule_templates`
--

DROP TABLE IF EXISTS `inventory_schedule_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_schedule_templates` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `company_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hospital_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CRONOGRAMA DE MANTENIMIENTO',
  `institution_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_data` longtext COLLATE utf8mb4_unicode_ci,
  `seal_left_data` longtext COLLATE utf8mb4_unicode_ci,
  `seal_right_data` longtext COLLATE utf8mb4_unicode_ci,
  `signature_data` longtext COLLATE utf8mb4_unicode_ci,
  `stamp_data` longtext COLLATE utf8mb4_unicode_ci,
  `footer_left` text COLLATE utf8mb4_unicode_ci,
  `footer_center` text COLLATE utf8mb4_unicode_ci,
  `footer_right` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_schedule_templates_key` (`company_name`,`hospital_name`,`year`),
  KEY `idx_inventory_schedule_templates_company` (`company_name`),
  KEY `idx_inventory_schedule_templates_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_schedule_templates`
--

LOCK TABLES `inventory_schedule_templates` WRITE;
/*!40000 ALTER TABLE `inventory_schedule_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_schedule_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_service_calls`
--

DROP TABLE IF EXISTS `inventory_service_calls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_service_calls` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `company_name` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `equipment_id` bigint DEFAULT NULL,
  `attention_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'espontanea',
  `reported_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `issue_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'abierto',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_service_calls_company` (`company_name`),
  KEY `idx_inventory_service_calls_equipment` (`equipment_id`),
  KEY `idx_inventory_service_calls_reported` (`reported_at`),
  KEY `idx_inventory_service_calls_status` (`status`),
  CONSTRAINT `fk_inventory_service_calls_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `inventory_equipment` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_service_calls`
--

LOCK TABLES `inventory_service_calls` WRITE;
/*!40000 ALTER TABLE `inventory_service_calls` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_service_calls` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `laboratory_tests`
--

DROP TABLE IF EXISTS `laboratory_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `laboratory_tests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sample` text COLLATE utf8mb4_unicode_ci,
  `method` text COLLATE utf8mb4_unicode_ci,
  `price_public` decimal(10,2) NOT NULL,
  `price_convenio` decimal(10,2) NOT NULL,
  `tube` text COLLATE utf8mb4_unicode_ci,
  `info` text COLLATE utf8mb4_unicode_ci,
  `process_time` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_laboratory_tests_name` (`name`),
  KEY `idx_laboratory_tests_active` (`active`)
) ENGINE=InnoDB AUTO_INCREMENT=188 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `laboratory_tests`
--

LOCK TABLES `laboratory_tests` WRITE;
/*!40000 ALTER TABLE `laboratory_tests` DISABLE KEYS */;
INSERT INTO `laboratory_tests` VALUES (1,'A.N.C.A ANTI-NEUTROFILOS','SUERO','IFI - Inmunofluorescencia Indirecta',180.00,150.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','6 días',1,1,'2026-05-31 01:49:05','2026-05-31 21:51:48'),(2,'ACIDO FOLICO(VIT. B9)','suero','CLIA',60.00,50.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-06-01 02:20:44'),(3,'AGA Y ELECTROLITOS (ADOMICILIO)','sangre arterial','gasometria',150.00,100.00,'jeringa especial con heparina','no requiere ayuno previo','1 día',1,1,'2026-05-31 01:49:05','2026-06-01 02:28:01'),(4,'ALFAFETOPROTEINA (AFP)','suero','quimioluminiscencia',60.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-06-01 02:29:20'),(5,'AMILASA SERICA','suero','cinetico',25.00,15.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-06-01 02:29:41'),(6,'ANTI DNA-DS','suero','IFI - Inmunofluorescencia Indirecta',160.00,130.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','10 días',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(7,'ANTI-CCP(PEPTIDO CICLICO CITRUNILADO)IGG','suero','elisa',250.00,220.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','8 días',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(8,'ANTICUERPOS ANTINUCLEARES(ANA)-LIMA','suero','clia',180.00,120.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','6 días',1,1,'2026-05-31 01:49:05','2026-06-13 02:53:56'),(9,'ANTICUERPOS ANTITIROIDES','suero','ECLIA / MODULAR PE',150.00,130.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','5 dias',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(10,'ANTIGENO CARCINO EMBRIONARIO(CEA)','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(11,'CA 125(ovario)','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(12,'CA 15-3(mama)','suero','quimioluminiscencia',75.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(13,'glucosa','suero','colorimetrico',10.00,6.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(14,'colesterol total','suero','colorimetrico',10.00,6.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(15,'trigliceridos','suero','colorimetrico',15.00,7.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(16,'hemograma completo automatizada','sangre total','citometría de flujo',30.00,15.00,'tapa lila','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(17,'hemoglobina','sangre total','manual',10.00,5.00,'tapa lila','no requiere ayuno','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(18,'examen completo de orina(eco)','orina ocasional','manual',15.00,7.00,'frasco tapa rojo no esteril','se recomienda primera orina de la mañana chorro intermedio ','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(19,'hemoglobina glicosilada(HbA1c)','sangre total','inmunoflorescencia',70.00,40.00,'tapa lila','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(20,'tsh','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(21,'t4 libre','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(22,'t3 libre','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(23,'perfil lipidico','suero','colorimetrico',40.00,28.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(24,'perfil hepatico','suero','cinetico/colorimetrico',50.00,30.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(25,'perfil renal simple','suero','cinetico/colorimetrico',35.00,20.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(26,'PERFIL TIROIDEO(TSH-T3-T4 LIBRE)','suero','quimioluminiscencia',180.00,115.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 21:53:49'),(27,'CREATINFOSFOQUINASA (CPK TOTAL)','suero','cinetico',50.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(28,'PROLACTINA POOL','suero','quimioluminiscencia',120.00,80.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 HRS. NO EJERCICIOS, EVITAR ESTADOS DE ESTRES. (30 MINUTOS DE REPOSO ANTES DE LA TOMA DE MUESTRA) INCLUYE 03 TOMAS CADA 15 MINUTOS, SALVO OTRA INDICACION DEL MÉDICO TRATANTE.','1 días',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(29,'BIOPSIA PIEZA OPERATORIA <= 1 MM. (L7)','BIOPSIA','MICROSCOPIA / MANUAL',150.00,80.00,'MEDIO CON FORMOL','LA MUESTRA DEBE COLOCARSE INMEDIATAMENTE EN UN CONTENEDOR CON FORMOL AL 10% DE TAL MANERA QUE ESTA CUBRA TODA LA MUESTRA Y CERRAR HERMETICAMENTE.NO DEBE SER MAYOR A 5MM, OBLIGATORIO: ORDEN MEDICA CON DETALLE DE ZONA + FIRMA Y SELLO','7 días',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(30,'prolactina','suero','elisa',70.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(31,'lh','suero','inmunoflorescencia',70.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(32,'fsh','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(33,'testosterona total','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(34,'testosterona libre','suero','quimioluminiscencia',75.00,48.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(35,'estradiol-e2','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(36,'progesterona','suero','quimioluminiscencia',70.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(37,'troponina i','suero','inmunoflorescencia',60.00,50.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(38,'lipasa','suero','cinetico',40.00,25.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(39,'ggt-gamma-glutamil','suero','cinetico',40.00,25.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(40,'calcio serico','suero','colorimetrico',25.00,15.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(41,'acido urico','suero','colorimetrico',15.00,7.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(42,'perfil de coagulacion(tp-ttpa-tc-ts)','plasma/citrato','coagulometria',45.00,30.00,'tapa celeste','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(43,'grupo sanguineo','sangre total','manual',15.00,10.00,'tapa lila','no requiere ayuno previo','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(44,'heces seriado x 3 veces','heces','microscopia',25.00,15.00,'frasco tapa rosca','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. CANTIDAD: = NUEZ. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(45,'heces simple x 1 veces','heces','microscopia',10.00,5.00,'frasco tapa rosca','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. CANTIDAD: = NUEZ. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(46,'heces coprofuncional','heces','microscopia',25.00,15.00,'frasco tapa rosca','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. CANTIDAD: = NUEZ. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(47,'metodo de faust-heces concentrada','heces','microscopia',20.00,12.00,'frasco tapa rosca','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. CANTIDAD: = NUEZ. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(48,'metodo de baermann-strongyloides','heces','microscopia',20.00,12.00,'frasco tapa rosca','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. CANTIDAD: = NUEZ. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(49,'reaccion inflamatoria-heces','heces','microscopia',10.00,5.00,'frasco tapa rosca','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. CANTIDAD: = NUEZ. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(50,'thevenon-heces-sangre oculta','heces','manual',15.00,8.00,'frasco tapa rosca','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. CANTIDAD: = NUEZ. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(51,'tolerancia a la glucosa','suero','bioquimica automatizada',70.00,45.00,'tapa rojo ó amarillo','MUESTRAS TOMADAS:BASAL 30, 60, 90, 120 MINUTOS. SALVO OTRA INDICACION DEL MEDICO. ADULTOS: 75 GR. glucosa anhidra Y  EMBARAZADAS SÓLO ENTRE 24 A 28 SEMANAS. REVISAR ORDEN MEDICA: 100 GR glucosa anhidra .. DISOLVER EN 250 - 300 ML DE AGUA','1 día',1,1,'2026-05-31 01:49:05','2026-05-31 01:49:05'),(52,'lamina periferica','sangre total con edta','microscopia/automatizado',60.00,45.00,'tapa lila mas lamina','TODA REFERENCIA: DEBE REALIZAR EXTENDIDO DE SANGRE SOBRE LÁMINA PORTAOBJETOS. ADEMÁS ENVIAR TUBO TAPA LILA.','1 dia',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(53,'recuento de plaquetas en lamina','sangre total con edta','microscopia/manual',15.00,8.00,'tapa lila mas lamina','TODA REFERENCIA: DEBE REALIZAR EXTENDIDO DE SANGRE SOBRE LÁMINA PORTAOBJETOS. ADEMÁS ENVIAR TUBO TAPA LILA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(54,'espermatograma','semen','microscopia/manual',60.00,50.00,'frasco tapa verde esteril','SE REQUIERE DE 3 A 5 DÍAS DE ABSTINENCIA. SE RECEPCIONAN LUNES A SÁBADO DE 7 AM A 11 AM. EN CASO DE FERIADO SE RECIBE UN DÍA ANTES. INDICAR FECHA DE VASECTOMÍA SI CORRESPONDE.','2 días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(55,'insulina','suero','elisa',70.00,50.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(56,'dimero d','suero','coagulacion',70.00,55.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(57,'ferritina','suero','elisa',60.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 dia',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(58,'procalcitonina agotado','suero','inmunoflorescencia',110.00,90.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(59,'inmunoglobulina e (ige)','suero','elisa',60.00,40.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(60,'cortisol am','suero','quimioluminiscencia',60.00,40.00,'tapa rojo ó amarillo','MUESTRA TOMADA ENTRE 8:00 Y 9:00 AM. SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(61,'cortisol pm','suero','quimioluminiscencia',60.00,40.00,'tapa rojo ó amarillo','MUESTRA TOMADA ENTRE 4:00 Y 5:00 pm. SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(62,'coombs directo','sangre total','HEMAGLUTINACION / MANUAL',45.00,30.00,'tapa lila','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(63,'coombs indirecto','suero','HEMAGLUTINACION / MANUAL',50.00,30.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(64,'vih combo(CLIA)','suero','quimioluminiscencia',65.00,40.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(65,'vih prueba rapida','suero','cromatografia',45.00,25.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(66,'serologia cualitativa rpr-sifilis','suero','floculacion/manual',25.00,15.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-06-17 20:35:00'),(67,'serologia semi-cuantitativa rpr-sifilis','suero','floculacion/manual',50.00,25.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-06-17 20:35:53'),(68,'VITAMINA B12','suero','elisa',75.00,55.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-06-13 05:54:42'),(69,'cpk-mb','suero','inmunoflorescencia',60.00,45.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(70,'hdl colesterol','suero','cinetico',20.00,15.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(71,'psa libre','suero','quimioluminiscencia',75.00,55.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(72,'psa total','suero','elisa',65.00,40.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(73,'variante du','sangre total','HEMAGLUTINACION / MANUAL',25.00,15.00,'tapa lila','no necesita ayuno previo.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(74,'CHLAMYDIA TRACHOMATIS IGM-igg','suero','elisa',150.00,80.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(75,'ANTICOAGULANTE LUPICO ','suero','COAGULOMETRICO',150.00,120.00,'tapa celeste','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA, 3 tubos tapa celeste','6 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(76,'ANTI CARDIOLIPINA IGG-IGM','suero','ELISA',180.00,150.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','7 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(77,'BETA 2 GLICOPROTEINA I IGG-IGM','suero','ELISA',180.00,170.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','8 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(78,'tiempo de protrombina(tp) más inr','plasma/citrato','coagulometria',15.00,15.00,'tapa celeste','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(79,'tiempo de tromboplastina parcial activada(ttpa)','plasma/citrato','coagulometria',15.00,15.00,'tapa celeste','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(80,'retraccion de coagulo','sangre total','optico-mecanico/manual',15.00,10.00,'tubo sin aditivo','no requiere preparacion previa. MUESTRA: MÍNIMO 3.0 ML DE SANGRE TOTAL (SIN NINGÚN ADITIVO), TRANSPORTAR DE INMEDIATO AL LABORATORIO PARA EL PRONTO PROCESO.','1 días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(81,'microalbuminuria orina simple','orina simple','turbidimetria',60.00,45.00,'frasco tapa rosca','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(82,'helicobacter pylori igg','suero','quimioluminiscencia',60.00,40.00,'tapa rojo/amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(83,'recuento de reticulocitos','sangre total','microscopia manual',25.00,15.00,'tapa lila','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(84,'constantes corpusculares','sangre total','manual',15.00,15.00,'tapa lila','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(85,'OXIUROS (TEST DE GRAHAM)','CINTA ADHESIVA','microscopia/manual',10.00,6.00,'lamina','LIMPIEZA DE LA ZONA ANAL ANTES DE ACOSTARSE. elisa DE TOMA DE MUESTRA : 5:00 A 6:00 AM MATERIALES: CINTA ADHESIVA TRANSPARENTE (1 A 2 X 10 A 12 CM) + LÁMINA PORTAOJETOS (LABORATORIO) PROCEDIMIENTO: COLOCAR AL PCTE. BOCA ABAJO EN LA POSICIÓN QUE PERMITA QUE LAS NALGAS SE SEPAREN PARA COLOCAR LA CINTA EN EL ANO ( AL CENTRO Y ALREDEDOR Ó PERI-ANAL) Y LUEGO COLOCARLO EN LA LAMINA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(86,'ACIDOS BILIARES suiza lab (lima)','suero','ELISA',200.00,180.00,'tapa amarillo primario','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','7 dias aprox',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(87,'HORMONA ANTI MULLERIANA (AMH/MIS) Roe','suero','ECLIA / COBAS 8000',500.00,450.00,'tapa amarillo primario','NO REQUIERE AYUNO ANTES DE LA PRUEBA.','7 dias aprox',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(88,'ANTI CORE','suero','elisa',60.00,45.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(89,'hepatitis b-antigeno de superficie (hbsag)','suero','inmunocromatografia',35.00,25.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(90,'hepatitis a (igm)','suero','inmunocromatografia',45.00,30.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(91,'hepatitis c (hcv)','suero','inmunocromatografia',45.00,30.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(92,'urea','suero','cinetico',10.00,6.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(93,'creatinina','suero','cinetico',10.00,6.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(94,'proteinuria orina de 24 h','orina de 24 horas','colorimetrico',25.00,15.00,'galorena','NO CONSUMIR ALCOHOL DURANTE TODO EL PROCESO DE RECOLECCIÓN. RECOLECCIÓN: ELIMINAR LA 1RA ORINA DE LA MAÑANA, RECOLECTAR DESDE LA 2DA EN ADELANTE HASTA EL DÍA SIGUIENTE DONDE SE RECOLECTA LA 1RA ORINA. NO ELIMINAR NI BOTAR NINGUNA MUESTRA DE ORINA. ANOTAR FECHA, INICIO Y TERMINO DE LA RECOLECCIÓN.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(95,'depuracion de creatinina en orina de 24 h','orina de 24 horas/suero','cinetico',25.00,15.00,'galorena/tubo rojo o amarillo','NO CONSUMIR ALCOHOL DURANTE TODO EL PROCESO DE RECOLECCIÓN. RECOLECCIÓN: ELIMINAR LA 1RA ORINA DE LA MAÑANA, RECOLECTAR DESDE LA 2DA EN ADELANTE HASTA EL DÍA SIGUIENTE DONDE SE RECOLECTA LA 1RA ORINA. NO ELIMINAR NI BOTAR NINGUNA MUESTRA DE ORINA. ANOTAR FECHA, INICIO Y TERMINO DE LA RECOLECCIÓN.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(96,'hcg cuantificado','suero','elisa',60.00,40.00,'tubo rojo o amarillo','no requiere ayuno previo','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(97,'hcg cualitativo','suero','cromatografia',20.00,12.00,'tubo rojo o amarillo','no requiere ayuno previo','30 minutos',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(98,'hierro serico','suero','cinetico',45.00,35.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(99,'leptospira igm-igg','suero','cromatografia',70.00,55.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(100,'fosforo','suero','cinetico',50.00,35.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(101,'magnesio','suero','cinetico',45.00,35.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(102,'urocultivo más antibiograma','orina simple','CULTIVO / MICROSCOPIA',50.00,20.00,'frasco esteril','COLECTAR LA MUESTRA EN FRASCO ESTÉRIL. NO ANTIBIOTICOS 03 DIAS PREVIOS AL nameEN. HIGIENE PREVIA DE LOS GENITALES. RECOLECTAR 1RA ORINA DE LA MAÑANA (ELIMINANDO EL 1ER CHORRO) Y REMITIR LA MUESTRA LO ANTES POSIBLE AL LABORATORIO (O MANTENER EN REFRIGERACIÓN 2 a 8°C HASTA SU ENVÍO)','3 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(103,'proteina c reactiva (pcr cuantificado)','suero','inmunoflorescencia',50.00,40.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(104,'proteina c reactiva (pcr cualitativo)','suero','turbidimetria',20.00,12.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(105,'factor reumatoideo (fr cualitativo)','suero','turbidimetria',20.00,12.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(106,'antiestreptolisina (aso cualitativo)','suero','turbidimetria',20.00,12.00,'tubo rojo o amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(107,'hematocrito hto','sangre total','manual',10.00,5.00,'tubo lila','no requiere preparacion previa','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(108,'bilirrubinas totales y fraccionadas','suero','colorimetrico',20.00,15.00,'tubo rojo / amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(109,'aglutinaciones febriles','suero','aglutinacion/manual',25.00,15.00,'tubo rojo / amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(110,'electrolitos sericos (cl, na, k)','suero','potenciometria',65.00,55.00,'tubo rojo / amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(111,'aga electrolitos','sangre arterial','potenciometria',100.00,90.00,'jeringa con heparina','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(112,'COCAINA PBC (ORINA SIMPLE) - CUALITATIVO','orina simple','INMUNOCROMATOGRAFIA',45.00,35.00,'FRASCO ORINA','NO REQUIERE AYUNO Y/O PREPARACIÓN PREVIA. NOTA: ES RESPONSABILIDAD DE LA REFERENCIA; IDENTIFICAR Y CONSTATAR QUE LA ORINA PERTENECE AL PACIENTE. NO HABER INGERIDO MATE DE COCA 24 horas ANTES DEL nameEN. NO CONSUMIR MEDICACIÓN DEL TIPO DE ANTINFLAMATORIOS, POR LO MENOS 48 horas ANTES DEL nameEN.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(113,'MARIHUANA THC (ORINA SIMPLE) - CUALITATIVO','orina simple','INMUNOCROMATOGRAFIA / MANUAL',45.00,35.00,'FRASCO ORINA','NO REQUIERE AYUNO Y/O PREPARACIÓN PREVIA. EL PACIENTE DEBERA PRESENTARSE CON DNI EN EL CASO DE MENORES DE EDAD CON LA PERSONA RESPONSABLE CON DNI Y FIRMAR FORMATO DE CONSENTIMIENTO PARA LA PRUEBA Y COLOCAR SU HUELLA DIGITAL, MUESTRA > 20 ML. ORINA REFRIGERADO. NOTA: ES RESPONSABILIDAD DE LA REFERENCIA; IDENTIFICAR Y CONSTATAR QUE LA ORINA PERTENECE AL PACIENTE.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(114,'ROTAVIRUS + ADENOVIRUS (HECES)','heces','inmunocromatografia',50.00,40.00,'FRASCO CON TAPA ROSCA','MUESTRA FRESCA, LA QUE CONTENGA MOCO Y/O SANGRE. INFANTES: EL PAÑAL COLOCAR AL REVES, PARA EVITAR QUE EL GEL DEL PAÑAL ABSORBA LOS LIQUIDOS O QUE LAS HECES SE IMPREGNEN. REFRIGERAR, EN EL CASO DE NO ENVIAR INMEDIATAMENTE.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(115,'bk esputo directo','varios','microscopia manual',35.00,15.00,'FRASCO CON TAPA ROSCA','NO REQUIERE DE PREPARACION PREVIA SEGÚN EL TIPO DE MUESTRA. SE SUGIERE OBTENER LA MUESTRA DE ESPUTO MUY TEMPRANO AL LEVANTARSE, ANTES DE CEPILLARSE LOS DIENTES, ANTES DE ENJUAGARSE LA BOCA Y ANTES DE INGERIR ALIMENTOS. SE DEBE RECOLECTAR LA EXPECTORACION BRONQUIAL,SIN SALIVA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(116,'cultivo de secrecion vaginal','secrecion vaginal','CULTIVO / MICROSCOPIA / MANUAL',50.00,25.00,'MEDIO DE TRANSPORTE','NO ANTIBIÓICOS, NI OVULOS 3 DIAS ANTES DEL EXÁMEN Y SIN ASEO PREVIO, MEDIO DE TRANSPORTE + 02 LAMINAS (FROTIS) INCLUYE : nameEN DIRECTO + ANTIBIOGRAMA','3 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(117,'cultivo herida','herida','CULTIVO / MICROSCOPIA / MANUAL',50.00,25.00,'MEDIO DE TRANSPORTE','NO ANTIBIÓTICOS 3 DIAS ANTES DE LA PRUEBA, NI APLICACIÓN DE ANTISEPTICAS. HERIDAS: SI ES NECESARIO LIMPIAR CON SUERO FISIOLOG. Y/O TOMAR LA MUESTRA DE LA PARTE INTERNA DE LA HERIDA. (MEDIO DE TRANSPORTE + 02 LAMINAS (FROTIS) ) ASPIRADOS: ENVIAR LA MUESTRA EN EL MISMO FRASCO O JERINGA QUE SE TOMÓ. NOTA: ES IMPORTANTE QUE SE INDIQUE LA UBICACIÓN Y TIPO DE MUESTRA QUE SE TOME EN LA ORDEN O TICKET. TIEMPO DE RESPUESTA NO SE INCLUYE DOMINGOS NI FERIADOS.','3 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(118,'hemocultivo con removedor de antibiotico','sangre total','CULTIVO / MICROSCOPIA / MANUAL',90.00,80.00,'frasco hemocultivo con removedor de antibiotico','DE PREFERENCIA NO ANTIBIOTICOS, 03 DIAS PREVIOS AL nameEN. REVISAR SOLICITUD MÉDICA Y PRECISAR LUGAR DE PUNCIÓN EN EL FRASCO DE HEMOCULTIVO. INCLUYE REMOVEDOR DE ATB, MEDIO ADULTOS: 5-10 ML DE SANGRE TOTAL (VENOSA), MEDIO PEDIATRICO: INFANTES: 1-2 ML Y NIÑOS: 3-5 ML DE SANGRE TOTAL (VENOSA).','7 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(119,'tiempo de coagulacion y tiempo de sangria','sangre total','manual',10.00,5.00,'sin aditivo','no requiere ayuna.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(120,'transaminasa tgo','suero','cinetico',15.00,8.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(121,'transaminasa tgp','suero','cinetico',15.00,8.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(122,'fosfatasa alcalina','suero','cinetico',15.00,8.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(123,'proteinas totales y fraccionadas','suero','colorimetrico',15.00,12.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(124,'ca 19-9','suero','quimioluminiscencia',60.00,45.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(125,'velocidad de sedimentacion glovular(vsg)','sangre total','manual',10.00,5.00,'tubo lila','No necesita ayuno previo.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(126,'gota gruesa en lamina','sangre total','manual/coloracion giensa',15.00,10.00,'tubo lila más lamina','No necesita ayuno previo.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(127,'prueba cruzada','suero','manual',35.00,30.00,'tubo rojo/amarillo','No necesita ayuno previo.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(128,'secrecion directa mas gram','secrecion','microscopia manual',15.00,12.00,'tubo con solucion salina más lamina','No aseo previo.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(129,'dengue igg-igm-ns1','suero','inmunocromatografia',60.00,40.00,'tubo rojo ó amarillo','No requiere ayuno previo.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(130,'deshidrogenasa lactica-dhl','suero','cinetico',45.00,25.00,'tubo rojo ó amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(131,'raspado de piel directo-koh hongos-acaros','raspado de piel','microscopia/manual',30.00,15.00,'placa petri pequeña','SUSPENDER ANTIMICÓTICOS (ORALES, CREMAS, SOLUCIONES)5 DÍAS PREVIOS AL nameEN, NO REALIZAR BAÑO PREVIO O ASEO DE LA ZONA AFECTADA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(132,'cultivo de hongos','una, piel, cuero cabelludo, orina','cultivo/microscopia/manual',60.00,40.00,'placa petri pequeña','NO ANTIMICÓTICOS (ORALES, CREMAS O SOLUCIONES) POR 03 DIAS ANTES DE LA PRUEBA, NO ASEO DE LA ZONA AFECTADA. EN CULTIVOS DE UÑAS, DEJAR QUE ESTAS CREZCAN PARA LA OBTENCIÓN DE UNA MEJOR MUESTRA. EN EL CASO DE AUSENCIA O ESCASAS ESCAMAS (PIEL), ENVIAR EL BISTURÍ CON EL CUAL SE HIZO EL RASPADO. INDICAR LA ZONA AFECTADA Y EL TIPO DE MUESTRA EN LA ORDEN O TICKET.','7 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(133,'herpes 2 (igg)','suero','quimiolumiscencia',80.00,55.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(134,'troponina t','suero','quimiolumiscencia',60.00,50.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','2 elisa',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(135,'estradiol libre','suero','electroquimioluminiscencia',230.00,210.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','13 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(136,'complemento c3-c4','suero','electroquimioluminiscencia',180.00,160.00,'tapa rojo ó amarillo','se sugiere ayuno de 8 hras','6 dias',1,1,'2026-05-31 01:49:06','2026-06-01 02:44:01'),(137,'ENA - PERFIL AUTOINMUNE','suero','---',320.00,300.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas. PERFIL INCLUYE: NRNP/SM, SM, SS-A, SS-B, SCL70, JO-1, DNADS, HISTONAS, PROTEINA P-RIBOSOMAL, M2, NUCLEOSOMAS. PM-SCL, CENTROMERO.','7 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(138,'cloro serico (cl)','suero','colorimetrico',40.00,30.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(139,'sodio serico (na)','suero','colorimetrico',40.00,30.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(140,'potasio serico (k)','suero','colorimetrico',45.00,25.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(141,'perfil reumatoideo(fr-aso-pcr)','suero','inmunoturbidimetria',50.00,36.00,'tubo rojo / amarillo','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(142,'coprocultivo mas antibiograma','heces','cultivo',50.00,25.00,'frasco tapa rosca boca ancha','COLECTAR LA MUESTRA EN FRASCO boca ancha tapa rosca. NO ANTIBIOTICOS 03 DIAS PREVIOS AL nameEN. REMITIR LA MUESTRA LO ANTES POSIBLE AL LABORATORIO (O MANTENER EN REFRIGERACIÓN 2 a 8°C HASTA SU ENVÍO).','3 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(143,'BNP (PEPTIDO NATRIUREICO)','suero','INMUNOQUIMIOLUMINISCENCIA',150.00,130.00,'tapa dorada','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 díaS',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(144,'HELICOBACTER PYLORI, TEST DE ALIENTO - CARBONO 13 (TEST UREASA)','aliento','Espectometría infrarrojo (C13)',230.00,220.00,'bolsa metalica','NO REQUIERE CITA PREVIA. RECOMENDABLE EN AYUNAS SIN CEPILLARSE LOS DIENTES EL DÍA DE LA PRUEBA, CASO ESPECIAL: NO INGERIR ALIMENTOS MÍNIMO 1 díaS ANTES. MEDICINAS: NO ANTIBIÓTICOS, BISMUTOL O PEPTOBISMOL POR 4 SEMANAS. NO ANTIÁCIDOS POR 2 SEMANAS (OMEPRAZOL O DERIVADOS).','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(145,'PAPANICOLAOU','varios','MICROSCOPIA / MANUAL',45.00,20.00,'lamina','NO REQUIERE AYUNO Y/O PREPARACIÓN PREVIA. ABSTINENCIA SEXUAL 3 DIAS ANTES, NO OVULOS NI CREMAS VAGINALES. NO ESTAR EN EL PERIODO MENSTRUAL. ENVIAR 2 LÁMINAS FIJADAS, ROTULADAS Y PROTEGIDAS','6 Días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(146,'test de ada','liquido varios/suero','colorimetrico',60.00,55.00,'envase esteril/tubo rojo','no requiere ayuno','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(147,'helicobacter pylori total(prueba rapida)','suero','cromatografia',45.00,35.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(148,'helicobacter pylori igm','suero','quimioluminiscencia',60.00,40.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(149,'citomegalovirus igm','suero','quimioluminiscencia ',60.00,40.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(150,'citomegalovirus igg','suero','quimioluminiscencia ',60.00,40.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(151,'rubeola igg','suero','quimioluminiscencia ',60.00,40.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(152,'rubeola igm','suero','quimioluminiscencia ',60.00,40.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(153,'toxoplasma igg','suero','quimioluminiscencia ',80.00,55.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(154,'toxoplasma igm','suero','quimioluminiscencia ',80.00,55.00,'tubo rojo','se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(155,'creatinina en orina de 24 h','orina de 24 h','cinetico',15.00,10.00,'frasco tapa rosca','NO CONSUMIR ALCOHOL DURANTE TODO EL PROCESO DE RECOLECCIÓN. RECOLECCIÓN: ELIMINAR LA 1RA ORINA DE LA MAÑANA, RECOLECTAR DESDE LA 2DA EN ADELANTE HASTA EL DÍA SIGUIENTE DONDE SE RECOLECTA LA 1RA ORINA. NO ELIMINAR NI BOTAR NINGUNA MUESTRA DE ORINA. ANOTAR FECHA, INICIO Y TERMINO DE LA RECOLECCIÓN.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(156,'rosa de bengala','suero','aglutinacion',50.00,40.00,'rojo/amarillo','no requiere ayuno previo','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(157,'t3','suero','quimioluminiscencia',60.00,40.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(158,'albumina','suero','colorimetrico',10.00,6.00,'tapa rojo ó amarillo','se recomienda ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(159,'VITAMINA D TOTAL 25-HIDROXIVITAMINA D (D2-D3)-LIMA','SUERO','CLIA',320.00,260.00,'TAPA AMARILLLA','se recomienda ayuno de 8 horas','6 dias',1,1,'2026-05-31 01:49:06','2026-08-12 13:31:30'),(160,'fibrinogeno','plasma','coagulometria',40.00,35.00,'tapa celeste','No es necesario estar en ayunas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(161,'cultivo de secreción faringea','secreción fraringe','CULTIVO / MICROSCOPIA / MANUAL',50.00,30.00,'tubo esteril de vidrio + lamina','EN AYUNAS, NO ASEO BUCAL, NI INGESTA DE LIQUIDOS NI SOLUCIONES DESINFECTANTES. NO ANTIBIÓTICOS 3 DIAS ANTES DEL EXÁMEN.','4 días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(162,'recuento de hematies','sangre total','manual',15.00,10.00,'tubo lila','no requiere ayuno previo','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(163,'chagas','suero','quimioluminiscencia',60.00,48.00,'tubo rojo ó amarillo','se sugiere ayuno de 8 horas','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(164,'cultivo de herida','secre. herida','CULTIVO / MICROSCOPIA / MANUAL',50.00,30.00,'MEDIO DE TRANSPORTE + 02 LAMINAS (FROTIS) INCLUYE: DIRECTO + ANTIBIOGRAMA','NO ANTIBIÓTICOS 3 DIAS ANTES DEL EXÁMEN, NI ASEO PREVIO.','4 días aprox.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(165,'DESPISTAJE ALERGICO AMPLIADO (295 ALERGENOS)','suero','MICROARRAY / ALEX2',1100.00,1100.00,'tubo rojo ó amarillo','NO ES NECESARIO EL AYUNO NI LA SUSPENCIÓN DE MEDICAMENTOS (ANTIHISTAMÍNICOS, INHALADORES, ANTILEUCOTRIENOS, CORTICOIDES, ETC). SE REALIZA EL DOSAJE DE INMUGLOBULINA IGE PARA CADA ALERGENO.','7 días aprox.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(166,'DESPISTAJE ALERGICO BASICO (36 ALERGENOS)','suero','MICROARRAY / ALEX2',320.00,260.00,'tubo rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas. LA PRUEBA DETERMINA 36 TIPOS DIFERENTES DE ALERGENOS (NO INCLUYE MEDICAMENTOS). INCLUYE: ALIMENTOS, ANIMALES, POLEN, PLANTAS E INSECTOS. SE REALIZA EL DOSAJE DE INMUGLOBULINA IGE PARA CADA ALERGENO.','5 días aprox.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(167,'perfil reumatoideo cuantificado','suero','inmunoflorescencia',60.00,60.00,'tubo rojo ó amarillo','no requiere ayuno previo.','1 día.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(168,'dhea-s','suero','elisa',70.00,50.00,'tubo rojo ó amarillo','no requiere ayuno previo.','1 día.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(169,'acth','plasma','elisa',60.00,40.00,'tubo lila','no requiere ayuno previo.','1 día.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(170,'mioglobina','suero','clia',80.00,55.00,'tubo rojo ó amarillo','no requiere ayuno previo.','1 día.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(171,'block cell-biopsia','MUESTRAS FRESCAS (LIQ. SINOVIAL, PLEURAL, ASCITICO, ETC), TOMADAS POR MEDICO TRATANTE. ORDEN MÉDICA OBLIGATORIA.','microscopia/manual',140.00,90.00,'FRASCO ESTERIL','MUESTRAS FRESCAS (LIQ. SINOVIAL, PLEURAL, ASCITICO, ETC), TOMADAS POR MEDICO TRATANTE. TRANSPORTAR MUESTRAS A LA BREVEDAD POSIBLE Y EN CADENA DE FRÍO. SE REQUIERE MÍNIMO 60ML PARA UN BUEN REPORTE.','7 dias aprox.',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(172,'citoquimico-liquido','MUESTRAS FRESCAS (LIQ. SINOVIAL, PLEURAL, ASCITICO, ETC), TOMADAS POR MEDICO TRATANTE. ORDEN MÉDICA OBLIGATORIA.','citoquimico/manual',60.00,35.00,'FRASCO ESTERIL','DEBE SER UNA MUESTRA TOMADA POR EL MÉDICO TRATANTE (PARACENTESIS), TODA MUESTRA CON PRESENCIA DE COÁGULO Y/O LIQUIDOS HEMORRAGICOS SERAN RECHAZADAS.','1 dia',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(173,'herpes 2 (igm)','suero','quimiolumiscencia',80.00,55.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(174,'ACIDOS BILIARES laboratorio roe (lima)','suero','bioquimico',380.00,330.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','5 días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(175,'cultivo de semen ','semen','cultivo',45.00,20.00,'frasco esteril tapa verde','COLECTAR LA MUESTRA EN FRASCO ESTÉRIL. NO ANTIBIOTICOS 03 DIAS PREVIOS AL examen. HIGIENE PREVIA DE LOS GENITALES, REMITIR LA MUESTRA LO ANTES POSIBLE AL LABORATORIO (O MANTENER EN REFRIGERACIÓN 2 a 8°C HASTA SU ENVÍO)','3 días',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(176,'HORMONA ANTI MULLERIANA (AMH/MIS) ROE','SUERO','ECLIA',480.00,450.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','5 a 6 dias',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(177,'vih 1/2 elisa','SUERO','elisa',60.00,40.00,'tapa rojo ó amarillo','SE RECOMIENDA AYUNO DE 8 horas ANTES DE LA PRUEBA.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(178,'albuminuria en orina simple','orina','enzimatico',45.00,25.00,'frasco tapa verde/rojo','no requiere ayuno.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(179,'VITAMINA D TOTAL 25-OH (D2-D3)','SUERO','IFA',180.00,90.00,'TUBO ROJO','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-08-12 13:30:45'),(180,'perfil anemia(hma +  lamina periferica, rcto reticu, vita b12, vita b9, ferritina, hierro, transferrina)','suero/plasma','clia',410.00,400.00,'tapa amarilla + tapa lila + lamina','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(181,'perfil TORCH (TOXOPLASMA IGG/IGM, RUBEOLA IGG/IGM, CITOMEGALOVIRUS IGG/IGM, HERPES IGG/IGM 1 Y 2)','suero/plasma','clia',480.00,400.00,'tapa amarilla + tapa lila + lamina','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(182,'pro bnp','suero','clia',150.00,130.00,'tapa dorada','Se recomienda ayuno de 8 horas.','1 día',1,1,'2026-05-31 01:49:06','2026-05-31 01:49:06'),(184,'Cultivo de secreción uretral','Secreción uretral','Cultivo',50.00,25.00,'Secreción mas lamina','No antibióticos, sin lavarse los genitales','4',1,1,'2026-06-06 21:49:20','2026-06-06 21:51:04'),(185,'Coeficiente Albumina/Creatnina','Orina simple/orina 24h','Cinetico',60.00,45.00,'Fracaso verde tapa rosca','Recolestar orina simple ú orina de 24 horas','1 dia',1,1,'2026-06-18 18:10:28','2026-06-18 18:10:28'),(186,'Transferrina','Suero','Bioquimico',55.00,45.00,'Rojo','De preferencia ayuno de 8 horas','1 día',1,1,'2026-06-22 16:21:10','2026-06-22 16:22:47'),(187,'INVESTIGACIÓN DE LESHMANIA','LINFA','COLORACION GIENSA',50.00,25.00,'2 LAMINAS','HERIDA LAVADA SIN RESTOS DE MEDICAMENTO','1 DIA',1,1,'2026-08-24 17:05:55','2026-08-24 17:06:21');
/*!40000 ALTER TABLE `laboratory_tests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `promotions`
--

DROP TABLE IF EXISTS `promotions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int DEFAULT NULL,
  `code` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `fundament` text COLLATE utf8mb4_unicode_ci,
  `long_description` text COLLATE utf8mb4_unicode_ci,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_url` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_card_url` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_modal_url` varchar(600) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applies_to` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'publico',
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `promo_price` decimal(10,2) DEFAULT NULL,
  `promo_price_public` decimal(10,2) DEFAULT NULL,
  `promo_price_convenio` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `display_order` int NOT NULL DEFAULT '0',
  `starts_at` datetime DEFAULT NULL,
  `ends_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_promotions_code` (`code`),
  KEY `idx_promotions_active` (`is_active`),
  KEY `idx_promotions_order` (`display_order`),
  KEY `idx_promotions_exam_id` (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `promotions`
--

LOCK TABLES `promotions` WRITE;
/*!40000 ALTER TABLE `promotions` DISABLE KEYS */;
INSERT INTO `promotions` VALUES (1,26,'PROMO-001','PERFIL TIROIDEO(TSH-T3-T4 LIBRE)','“Evalúa función tiroidea para detectar alteraciones hormonales tempranas. Resultado en 24 h.”','quimioluminiscencia','Perfil Tiroideo!\nSi quieres realizarte un examen completo para conocer el funcionamiento de la tiroides te invitamos a realizarte un perfil tiroideo.\nObtén tus resultados de forma rápida y segura.','PERFIL TIROIDEO(TSH-T3-T4 LIBRE)','https://storage.googleapis.com/tarifario-inbioslab-assets/promotions/promo-1780455524883-925667674-modal.webp','https://storage.googleapis.com/tarifario-inbioslab-assets/promotions/promo-1780455524883-925667674-card.webp','https://storage.googleapis.com/tarifario-inbioslab-assets/promotions/promo-1780455524883-925667674-modal.webp','ambos',180.00,150.00,150.00,100.00,1,0,NULL,NULL,'2026-05-31 21:42:56','2026-06-03 02:59:41'),(2,2,'PROMO-002','ACIDO FOLICO(VIT. B9)','“Mide ácido fólico para identificar deficiencias nutricionales y riesgo de anemia. No requiere ayuno.”','CLIA','El examen de ácido fólico (vitamina B9) es un análisis de sangre diseñado para detectar deficiencias nutricionales, diagnosticar ciertos tipos de anemia (como la megaloblástica) y evaluar problemas de absorción o malnutrición.','ACIDO FOLICO(VIT. B9)','https://storage.googleapis.com/tarifario-inbioslab-assets/promotions/promo-1780454751574-935389424-modal.webp','https://storage.googleapis.com/tarifario-inbioslab-assets/promotions/promo-1780454751574-935389424-card.webp','https://storage.googleapis.com/tarifario-inbioslab-assets/promotions/promo-1780454751574-935389424-modal.webp','ambos',60.00,50.00,50.00,45.00,1,0,NULL,NULL,'2026-06-01 04:26:50','2026-06-03 02:46:08');
/*!40000 ALTER TABLE `promotions` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-08-31  1:01:22
