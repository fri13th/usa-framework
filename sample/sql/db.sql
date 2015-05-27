-- MySQL dump 10.13  Distrib 5.6.20, for osx10.9 (x86_64)
--
-- Host: localhost    Database: usagidb
-- ------------------------------------------------------
-- Server version	5.6.20

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `u_board`
--

DROP TABLE IF EXISTS `u_board`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `u_board` (
  `seq` int(11) NOT NULL AUTO_INCREMENT,
  `board_type` int(11) DEFAULT '1',
  `title` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `content` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `author` int(11) DEFAULT NULL,
  `thumbnail_seq` int(11) DEFAULT NULL,
  `delete_yn` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `create_dt` datetime DEFAULT NULL,
  `modify_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `u_board`
--

LOCK TABLES `u_board` WRITE;
/*!40000 ALTER TABLE `u_board` DISABLE KEYS */;
INSERT INTO `u_board` VALUES (1,3,'테스트1','컨텐츠1',1,1,'N','2015-02-09 11:00:00',NULL),(2,1,'테스트2','컨텐츠2',1,2,'N','2015-02-09 11:00:00',NULL);
/*!40000 ALTER TABLE `u_board` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `u_file`
--

DROP TABLE IF EXISTS `u_file`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `u_file` (
  `seq` int(11) NOT NULL,
  `board_seq` int(11) DEFAULT NULL,
  `file_detail_seq` int(11) DEFAULT NULL,
  `caption` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `delete_yn` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `u_file`
--

LOCK TABLES `u_file` WRITE;
/*!40000 ALTER TABLE `u_file` DISABLE KEYS */;
INSERT INTO `u_file` VALUES (1,2,3,'file2-1cap','N','2015-02-09 11:00:00'),(2,2,4,'file2-2cap','N','2015-02-09 11:00:00');
/*!40000 ALTER TABLE `u_file` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `u_file_detail`
--

DROP TABLE IF EXISTS `u_file_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `u_file_detail` (
  `seq` int(11) NOT NULL,
  `save_nm` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `org_nm` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `delete_yn` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `create_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `u_file_detail`
--

LOCK TABLES `u_file_detail` WRITE;
/*!40000 ALTER TABLE `u_file_detail` DISABLE KEYS */;
INSERT INTO `u_file_detail` VALUES (1,'file1.jpg','file1org.jpg','N','2015-02-09 11:00:00'),(2,'file2.jpg','file2.org.jpg','N','2015-02-09 11:00:00'),(3,'file2-1.jpg','file2-1.org.jpg','N','2015-02-09 11:00:00'),(4,'file2-2.jpg','file2-2.org.jpg','N','2015-02-09 11:00:00');
/*!40000 ALTER TABLE `u_file_detail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `u_type`
--

DROP TABLE IF EXISTS `u_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `u_type` (
  `type_code` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `delete_yn` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `create_dt` datetime DEFAULT NULL,
  `modify_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`type_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `u_type`
--

LOCK TABLES `u_type` WRITE;
/*!40000 ALTER TABLE `u_type` DISABLE KEYS */;
INSERT INTO `u_type` VALUES (1,'공지','N','2015-02-09 13:00:00',NULL),(2,'자유게시판','N','2015-02-09 13:00:00',NULL),(3,'포럼','N','2015-02-09 13:00:00',NULL),(5,'방명록','N','2015-02-09 13:00:00',NULL);
/*!40000 ALTER TABLE `u_type` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `u_user`
--

DROP TABLE IF EXISTS `u_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `u_user` (
  `seq` int(11) NOT NULL,
  `userid` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `userpass` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nickname` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `delete_yn` char(1) COLLATE utf8_unicode_ci DEFAULT 'N',
  `create_dt` datetime DEFAULT NULL,
  `last_login_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`seq`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `u_user`
--

LOCK TABLES `u_user` WRITE;
/*!40000 ALTER TABLE `u_user` DISABLE KEYS */;
INSERT INTO `u_user` VALUES (1,'root','usagi','usagidance','N','2015-02-09 15:00:00','2015-02-09 15:00:00');
/*!40000 ALTER TABLE `u_user` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-02-10 18:38:01
