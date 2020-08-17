-- MySQL dump 10.15  Distrib 10.0.36-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: mydbname
-- ------------------------------------------------------
-- Server version	10.0.36-MariaDB-0ubuntu0.16.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bof_archive`
--

DROP TABLE IF EXISTS `bof_archive`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bof_archive` (
  `archive_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `year` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `iccm_edition` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`archive_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bof_archive`
--

LOCK TABLES `bof_archive` WRITE;
/*!40000 ALTER TABLE `bof_archive` DISABLE KEYS */;
/*!40000 ALTER TABLE `bof_archive` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bof_archive_leader`
--

DROP TABLE IF EXISTS `bof_archive_leader`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bof_archive_leader` (
  `leader_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `bof_id` int(10) unsigned DEFAULT NULL,
  `leader` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`leader_id`),
  KEY `bof_id` (`bof_id`),
  CONSTRAINT `bof_archive_leader_ibfk_1` FOREIGN KEY (`bof_id`) REFERENCES `bof_archive_session` (`bof_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=151 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bof_archive_leader`
--

LOCK TABLES `bof_archive_leader` WRITE;
/*!40000 ALTER TABLE `bof_archive_leader` DISABLE KEYS */;
/*!40000 ALTER TABLE `bof_archive_leader` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bof_archive_session`
--

DROP TABLE IF EXISTS `bof_archive_session`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bof_archive_session` (
  `bof_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `archive_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `description` text CHARACTER SET latin1 COLLATE latin1_general_ci NOT NULL,
  `votes` float DEFAULT NULL,
  PRIMARY KEY (`bof_id`),
  KEY `archive_id` (`archive_id`),
  CONSTRAINT `bof_archive_session_ibfk_1` FOREIGN KEY (`archive_id`) REFERENCES `bof_archive` (`archive_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=164 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bof_archive_session`
--

LOCK TABLES `bof_archive_session` WRITE;
/*!40000 ALTER TABLE `bof_archive_session` DISABLE KEYS */;
/*!40000 ALTER TABLE `bof_archive_session` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bof_metadata`
--

DROP TABLE IF EXISTS `bof_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bof_metadata` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `iccm_edition` varchar(3) COLLATE utf8_unicode_ci NOT NULL,
  `year` varchar(10) COLLATE utf8_unicode_ci NOT NULL,
  `maxvotes` int(10) NOT NULL,
  `force_bof` tinyint(1) NOT NULL DEFAULT '0',
  `location` int(10) unsigned NOT NULL,
  `round` int(10) unsigned NOT NULL,
  `bof_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bof_metadata`
--

LOCK TABLES `bof_metadata` WRITE;
/*!40000 ALTER TABLE `bof_metadata` DISABLE KEYS */;
/*!40000 ALTER TABLE `bof_metadata` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item` varchar(30) COLLATE latin1_general_ci NOT NULL,
  `value` varchar(30) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config`
--

LOCK TABLES `config` WRITE;
/*!40000 ALTER TABLE `config` DISABLE KEYS */;
INSERT INTO `config` VALUES (1,'nomination_begins','2019-02-13 21:45:36'),(2,'nomination_ends','2019-02-14 21:45:36'),(3,'voting_begins','2019-02-14 23:45:36'),(4,'voting_ends','2019-02-16 21:45:36');
/*!40000 ALTER TABLE `config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_old`
--

DROP TABLE IF EXISTS `config_old`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config_old` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `voting_begins` datetime NOT NULL,
  `voting_ends` datetime NOT NULL,
  `nomination_begins` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `nomination_ends` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_old`
--

LOCK TABLES `config_old` WRITE;
/*!40000 ALTER TABLE `config_old` DISABLE KEYS */;
/*!40000 ALTER TABLE `config_old` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `location`
--

DROP TABLE IF EXISTS `location`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `location` (
  `id` int(10) unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `location`
--

LOCK TABLES `location` WRITE;
/*!40000 ALTER TABLE `location` DISABLE KEYS */;
INSERT INTO `location` VALUES (0,'Room A'),(1,'Room B'),(2,'Room C');
/*!40000 ALTER TABLE `location` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `participant`
--

DROP TABLE IF EXISTS `participant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `participant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `password` varchar(255) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=460 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `participant`
--

LOCK TABLES `participant` WRITE;
/*!40000 ALTER TABLE `participant` DISABLE KEYS */;
INSERT INTO `participant` VALUES (1,'admin','$2y$10$Coh8TTXxnNnmouU8dxJ8aO91vyBHLTdwtWs3axSdVDfTJrZjPU5fy'),(401,'user1','$2y$05$yNWQCylGhHmErcgSkQW51ukOrA.tUHrKm6Q0WUnBndQ5f1EFAGphS'),(402,'user2','$2y$05$Cj6LRWTShYGGo5e/rkmmLefH3zDb98MmZNDyLQ69ECTCDPoG7i/Qq'),(403,'user3','$2y$05$ynLefqZ9lZNR0SW3t7Tc5uiRJNNHZbdtsiJkSefFJQz9aIBCFq6oG'),(404,'user4','$2y$05$Sib6AN3YBF7EHieIESKyjObICcJi38KH.vbOmju40hPPxluSzZb8y'),(405,'user5','$2y$05$RYHChfsLF02A9Chx4a817.FvP1aN34paOMllJB6H9X/53rK5fslp.'),(406,'user6','$2y$05$l/hLELVjLSyn85vzntS6kODQLJnMZTCTv24rVi6cw65I9KwDbl5pu'),(407,'user7','$2y$05$f0K1tLT5DUiTlOqDnwOFuOdT/p8xE.Ule.W/3b.glKH6KcFJ/YaBm'),(408,'user8','$2y$05$rbUv1kv6tOwV1HaQNfqM6.VOo.Jq6SvCAb8i4yfBKwwQpxtlF.XIG'),(409,'user9','$2y$05$64KXjSp.4dLkEPD9vPiPd.htynyW8.dm0vN83J8zbwNkall7lR/Na'),(410,'user10','$2y$05$CJcnuT6ST25TNejUZOiCjukbwaoTYyWNT4/lyncu.kiQ4i3L7rKOe'),(411,'user11','$2y$05$J3QwHBJmwfwbCvfZ4Vr53eo5/aU.//oNEOBDpU3wB2za2cSY7gM4e'),(412,'user12','$2y$05$Ux/hijV0VeP0xjIZVIElve0mKQK/KWd8LRWDVWlhqf.JnZZoOH1YO'),(413,'user13','$2y$05$LH6N046.bBqNdGa9wnyWB.rL/osmX/6.2RRXdz7ZqGRm/VVIQ4mH6'),(414,'user14','$2y$05$8FR7Paz/euLwi2HIk4pex.6ymvaRykUXWOT6wKEKedAWDUm3TPJ3q'),(415,'user15','$2y$05$Gx.YyKc6l74XFOnOw7wsR.XQ/3jcrEfb4O5mlc0bUoUhykaMyV2xS'),(416,'user16','$2y$05$xm8hcp/QIkLPMBqEPuhw3uzOQN7Kf/6/fnoPdNlRr99bJhJ8s06e6'),(417,'user17','$2y$05$zDDuW/ZypI.czPUcQuE3O.CpuX3KZrLupHCLavJfvMJUbtNB9Wo5q'),(418,'user18','$2y$05$xRjrSdUHb12FWwj.svslruCK03LELP/VL5obuHfQ67qGeC4X34zr6'),(419,'user19','$2y$05$Fn2bjAeZq/slDLYDlsMVbeifNm6ooLnvlr3ZwPAMd1wAAhXTvjgdm'),(420,'user20','$2y$05$7HvNsiTmk2x4f.10ogBV8.gY2yF101NS5ULJUyvhBoWBB286du4nW'),(421,'user21','$2y$05$8WjxEvXNIfOpRIhmIBZZ2OOc4pRIsXpd2/zPbAgGdyoc.q4zDlQxO'),(422,'user22','$2y$05$VIpmXw0Z3vOg8r9dk/euaOxCbYWxNqa/8WMX0kANfffe3lwxifr8W'),(423,'user23','$2y$05$Jg0GbrdnozqJHPBspptuLeZbKgaX4iPnTIh9VxlnAzCbtSnewJAo2'),(424,'user24','$2y$05$WaueVZ7hHFLYaYPdKIoTHOiuVl7ClQhxHtVyOj/iiwAK.PjMPiBGq'),(425,'user25','$2y$05$3D7KDkRtHpcQJP0ARK2hPuK4Y.diFLjSu3ZnnPwuOx2zd5M3lVWLK'),(426,'user26','$2y$05$tDuXi5j69J6oREA78wiZRuhOZcwWzZUMKUfCvhxhrW7ha77f36sFy'),(427,'user27','$2y$05$AHkTRmgKIFZxhbyU6XQj1u36AgV2WL.mf5JKP2OHznUo11pUTyHWm'),(428,'user28','$2y$05$ZCdVjEDlH8DhBGpsMRJTn.L6dUzxQj1oQdLAQoxN/r3GrKGvKjYGS'),(429,'user29','$2y$05$8Ems40SfRX0xSM8m2ZklJu6Q3G4vUfOUQmNOqG9vFS/mWWPL6RALC'),(430,'user30','$2y$05$gFrVfvefq1EZLCR0IK5KvuD9GuLbuS8Ej.uyVr0X4ZDkt2Mb96K5a'),(431,'user31','$2y$05$dT3WXq9JrD/bnjHidSgAl.17mMMwYKO9O6IzvKjsx8VEBpqLXUZ0a'),(432,'user32','$2y$05$iDEJ7xPeed8y2T7yaIBzQu/nbenFxo3rzU7h5FvnQnUP7LKkZPmiq'),(433,'user33','$2y$05$XPHV6CIWBmFg47JnyyX73uhh756X1NiPm4xRUvOyYnOO6tIvb7/HW'),(434,'user34','$2y$05$rAR.zwY3duJvv/kmnmneHOU7UjpHTduHa8GTyG/s2wM65SNP9fIrG'),(435,'user35','$2y$05$b0KbqQjZR36JmUp1H0gk6.vDfjzCLy4877sOOLLzSAzasw9vU/Lie'),(436,'user36','$2y$05$nygoWG.5osDUnFg2YIkkuOAg1fS0P6ys1YesvcFERy88ee.LcQqr2'),(437,'user37','$2y$05$otY1i.0Qtq24ad9mjuDf0u3NfsnoGn3YcOobxqnr4FusFEWLvVsVK'),(438,'user38','$2y$05$RCGknNwKtuylclLkgTdDZudSLa5JStpsy1ZuMgnPBG3oqoWuBqMLC'),(439,'user39','$2y$05$lr45n4Jn/cj31Oyz1CzKFeb6aqp06hPW.JwZJPse/azPH.nTjZ3UG'),(440,'user40','$2y$05$qxnIk6Joqjf8lycY1KlMbefwGVKFTCTBh0JEfjN2upKiUpjXf6D7O'),(441,'user41','$2y$05$MIEOEqOIArS/gQlN/uvWhuaoHuJ42XqARUUqNVhYwZIu9X.Oyg0ui'),(442,'user42','$2y$05$qaLbu0pcJ.hSejxZvWWzaOltPlLSdA7EDJCPriOVm4NDP92DbE5XS'),(443,'user43','$2y$05$VbyQRRtwZfFLxT26z0YudeOKqd5Sk2o6mlQe/Bsk1PBpqjUMhxLOy'),(444,'user44','$2y$05$Wx7zkjnT6vv34s5qUNs8E.HISIguvDnoMyHfgsW2/51zgqxPzmIYu'),(445,'user45','$2y$05$aflvjGPtruT7O/t6Nus8IOuHLafcZIfcae/CVTGhy1nzpKxKIQ7QC'),(446,'user46','$2y$05$vIIyCmDwE3WLrFXFuo6YluXNczvKcyfwP9ObBxJbN6YsfNnrpXnZq'),(447,'user47','$2y$05$UStiZ6lPOqOSIJxuOQaOwOf9o.VocVfzzqiMtOjdoMe245FSi/bVS'),(448,'user48','$2y$05$SN69QLErWPpGbZDVOppPeett0s1c9XJEuSPPQ55DuwH9NM2nPkcJC'),(449,'user49','$2y$05$Luzzu5bXWkFdhMQQlgw3Jeu0FQj3/EjbSMVwor81hMsW4bmNLVcty'),(450,'user50','$2y$05$T5KgyMtAEfWhOAmdWse/juWAReUt4N7qAJKUtk08eI6iU3MnHygPS'),(451,'user51','$2y$05$K5ln184QBGZUgkot08t2zeXXQ1bUxxk6yTMaVk/0n8/Gz7uTvuzBq'),(452,'user52','$2y$05$hCA02.fUtBcKA8DX2DiJj.o8tI7cO1QMjwm5dbG1LXrojpHWD3bEq'),(453,'user53','$2y$05$DgT/XLJTzGu8jOaTfVv4VOd3P/kOpTAQ.Q8criO7qyLhpzR7o9OTi'),(454,'user54','$2y$05$jiIXb4lSlZS6hevAUfyDiOOJMPGr1mG74UJ8LjqlgtUV1dpjOyF5m'),(455,'user55','$2y$05$yT/MgYo/mgYc56KyS33Xmex64gOAT/X6KOz0kVyBMYLnWsJsg7QeW'),(456,'user56','$2y$05$DQl0HrlahgBJR48v5WNkk.iIlqIkWk5X9f/fAMCv6leX/70cgtode'),(457,'user57','$2y$05$JtQAy3F3wX2zOg64sGzEfuRH7KmriotDyMhUOcyP/nhvCTnsreTxO'),(458,'user58','$2y$05$/txD3imGA0Gp0BltlZX9l.hdXa7DXobTV6riXaEaCgiHNCbU5AyNK'),(459,'user59','$2y$05$pxs5y7ZhbBCjfnS0ewhqTu6Ld/l88LrMPEaVT9fl51nW9P7QA/OaC');

/*!40000 ALTER TABLE `participant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `round`
--

DROP TABLE IF EXISTS `round`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `round` (
  `id` int(10) unsigned NOT NULL,
  `time_period` varchar(50) COLLATE latin1_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `round`
--

LOCK TABLES `round` WRITE;
/*!40000 ALTER TABLE `round` DISABLE KEYS */;
INSERT INTO `round` VALUES (0,'first'),(1,'second'),(2,'third');
/*!40000 ALTER TABLE `round` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workshop`
--

DROP TABLE IF EXISTS `workshop`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workshop` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `creator_id` int(10) unsigned DEFAULT NULL,
  `round_id` int(10) unsigned DEFAULT NULL,
  `location_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE latin1_general_ci NOT NULL,
  `description` text COLLATE latin1_general_ci NOT NULL,
  `published` tinyint(1) NOT NULL,
  `votes` float DEFAULT NULL,
  `available` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=110 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workshop`
--

LOCK TABLES `workshop` WRITE;
/*!40000 ALTER TABLE `workshop` DISABLE KEYS */;
INSERT INTO `workshop` VALUES (1,NULL,NULL,NULL,'Prep Team','The Prep Team is a handful of people who plan these annual conferences. If you might be interested in joining this team please come to this BOF. We\'re always looking for new ideas and help to make ICCM special every year!',0,NULL,NULL),(96,401,NULL,NULL,'topic1','description for topic1',0,NULL,NULL),(97,401,NULL,NULL,'topic2','description for topic2',0,NULL,NULL),(98,401,NULL,NULL,'topic3','description for topic3',0,NULL,NULL),(99,401,NULL,NULL,'topic4','description for topic4',0,NULL,NULL),(100,401,NULL,NULL,'topic5','description for topic5',0,NULL,NULL),(101,401,NULL,NULL,'topic6','description for topic6',0,NULL,NULL),(102,401,NULL,NULL,'topic7','description for topic7',0,NULL,NULL),(103,401,NULL,NULL,'topic8','description for topic8',0,NULL,NULL),(104,401,NULL,NULL,'topic9','description for topic9',0,NULL,NULL),(105,401,NULL,NULL,'topic10','description for topic10',0,NULL,NULL),(106,401,NULL,NULL,'topic11','description for topic11',0,NULL,NULL),(107,401,NULL,NULL,'topic12','description for topic12',0,NULL,NULL),(108,401,NULL,NULL,'topic13','description for topic13',0,NULL,NULL),(109,401,NULL,NULL,'topic14','description for topic14',0,NULL,NULL);
/*!40000 ALTER TABLE `workshop` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workshop_participant`
--

DROP TABLE IF EXISTS `workshop_participant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `workshop_participant` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workshop_id` int(10) unsigned NOT NULL,
  `participant_id` int(10) unsigned NOT NULL,
  `leader` tinyint(1) NOT NULL DEFAULT '0',
  `participant` float NOT NULL DEFAULT '0.25',
  PRIMARY KEY (`id`),
  UNIQUE KEY `workshop_participant are unique` (`workshop_id`,`participant_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1772 DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workshop_participant`
--

LOCK TABLES `workshop_participant` WRITE;
/*!40000 ALTER TABLE `workshop_participant` DISABLE KEYS */;
INSERT INTO `workshop_participant` VALUES (1333,96,401,0,0.25),(1334,97,401,0,0.25),(1335,98,401,0,0.25),(1336,101,401,1,1),(1337,102,401,1,1),(1338,103,401,1,1),(1339,105,401,0,0.25),(1340,1,402,0,0.25),(1341,96,402,1,1),(1342,99,402,0,0.25),(1343,100,402,1,1),(1344,103,402,0,0.25),(1345,104,402,1,1),(1346,106,402,0,0.25),(1347,107,402,0,0.25),(1348,1,403,1,0.25),(1349,99,403,0,0.25),(1350,100,403,0,0.25),(1351,102,403,0,1),(1352,105,403,0,0.25),(1353,107,403,0,0.25),(1354,108,403,0,0.25),(1355,96,404,0,0.25),(1356,99,404,0,0.25),(1357,100,404,0,1),(1358,101,404,0,1),(1359,102,404,0,1),(1360,103,404,0,0.25),(1361,104,404,0,0.25),(1362,100,405,0,1),(1363,101,405,0,0.25),(1364,103,405,0,0.25),(1365,104,405,0,0.25),(1366,105,405,0,0.25),(1367,106,405,0,0.25),(1368,108,405,1,1),(1369,98,406,0,0.25),(1370,99,406,0,0.25),(1371,100,406,0,0.25),(1372,102,406,0,0.25),(1373,103,406,0,1),(1374,104,406,0,1),(1375,106,406,0,0.25),(1376,100,407,0,1),(1377,101,407,0,0.25),(1378,102,407,0,0.25),(1379,103,407,0,0.25),(1380,106,407,0,0.25),(1381,107,407,0,0.25),(1382,108,407,0,1),(1383,1,408,0,0.25),(1384,97,408,1,1),(1385,98,408,1,1),(1386,101,408,0,1),(1387,102,408,0,0.25),(1388,104,408,0,0.25),(1389,108,408,0,0.25),(1390,1,409,0,0.25),(1391,97,409,0,1),(1392,102,409,0,0.25),(1393,103,409,0,0.25),(1394,105,409,1,1),(1395,106,409,0,0.25),(1396,108,409,0,1),(1397,1,410,0,0.25),(1398,96,410,1,1),(1399,97,410,0,1),(1400,98,410,0,0.25),(1401,100,410,0,0.25),(1402,101,410,0,0.25),(1403,102,410,0,1),(1404,1,411,0,0.25),(1405,97,411,0,1),(1406,99,411,1,1),(1407,101,411,0,0.25),(1408,103,411,0,0.25),(1409,104,411,0,1),(1410,105,411,0,0.25),(1411,1,412,1,0.25),(1412,98,412,1,1),(1413,99,412,0,0.25),(1414,102,412,0,0.25),(1415,103,412,0,0.25),(1416,104,412,0,0.25),(1417,105,412,0,0.25),(1418,106,412,1,1),(1419,1,413,0,0.25),(1420,98,413,0,0.25),(1421,100,413,0,1),(1422,103,413,0,1),(1423,105,413,0,1),(1424,107,413,0,0.25),(1425,108,413,0,0.25),(1426,97,414,0,0.25),(1427,98,414,0,1),(1428,100,414,0,1),(1429,101,414,0,0.25),(1430,102,414,0,0.25),(1431,103,414,0,1),(1432,105,414,0,0.25),(1433,1,415,0,0.25),(1434,96,415,0,0.25),(1435,99,415,0,0.25),(1436,100,415,0,1),(1437,101,415,0,0.25),(1438,102,415,0,0.25),(1439,103,415,0,1),(1440,97,416,0,0.25),(1441,98,416,0,0.25),(1442,100,416,0,0.25),(1443,101,416,0,1),(1444,102,416,0,1),(1445,106,416,0,0.25),(1446,107,416,0,0.25),(1447,108,416,0,1),(1448,1,417,0,0.25),(1449,96,417,0,0.25),(1450,99,417,0,0.25),(1451,102,417,0,0.25),(1452,105,417,0,1),(1453,106,417,0,0.25),(1454,107,417,1,1),(1455,108,417,0,0.25),(1456,96,418,0,1),(1457,100,418,0,0.25),(1458,101,418,0,0.25),(1459,102,418,0,0.25),(1460,106,418,0,1),(1461,107,418,0,0.25),(1462,108,418,0,1),(1463,1,419,0,0.25),(1464,96,419,0,0.25),(1465,97,419,0,0.25),(1466,98,419,0,0.25),(1467,99,419,0,1),(1468,107,419,0,1),(1469,108,419,0,0.25),(1470,1,420,0,0.25),(1471,99,420,0,1),(1472,102,420,0,0.25),(1473,103,420,0,0.25),(1474,104,420,0,1),(1475,105,420,0,1),(1476,106,420,0,0.25),(1477,107,420,0,0.25),(1478,96,421,0,0.25),(1479,97,421,0,0.25),(1480,99,421,0,1),(1481,100,421,0,0.25),(1482,102,421,0,0.25),(1483,103,421,0,1),(1484,105,421,0,1),(1485,108,421,0,0.25),(1486,96,422,0,0.25),(1487,99,422,0,0.25),(1488,100,422,0,0.25),(1489,101,422,0,1),(1490,105,422,0,0.25),(1491,106,422,0,0.25),(1492,108,422,0,1),(1493,97,423,0,0.25),(1494,98,423,0,0.25),(1495,99,423,0,1),(1496,103,423,0,0.25),(1497,104,423,0,0.25),(1498,105,423,0,1),(1499,106,423,0,0.25),(1500,1,424,0,0.25),(1501,96,424,0,1),(1502,99,424,0,0.25),(1503,100,424,0,0.25),(1504,101,424,0,1),(1505,103,424,0,0.25),(1506,107,424,0,0.25),(1507,108,424,0,0.25),(1508,1,425,0,0.25),(1509,97,425,0,0.25),(1510,99,425,0,0.25),(1511,100,425,0,1),(1512,105,425,0,0.25),(1513,106,425,0,1),(1514,107,425,0,0.25),(1515,1,426,0,0.25),(1516,97,426,0,1),(1517,98,426,0,0.25),(1518,101,426,0,0.25),(1519,102,426,0,1),(1520,103,426,0,1),(1521,105,426,0,0.25),(1522,107,426,0,0.25),(1523,1,427,0,0.25),(1524,96,427,0,1),(1525,99,427,0,0.25),(1526,101,427,0,1),(1527,104,427,0,1),(1528,105,427,0,0.25),(1529,106,427,0,0.25),(1530,107,427,0,0.25),(1531,1,428,0,0.25),(1532,96,428,0,1),(1533,99,428,0,0.25),(1534,102,428,0,0.25),(1535,103,428,0,0.25),(1536,105,428,0,1),(1537,106,428,0,0.25),(1538,107,428,0,0.25),(1539,97,429,0,0.25),(1540,98,429,0,0.25),(1541,101,429,0,0.25),(1542,103,429,0,1),(1543,104,429,0,1),(1544,105,429,0,1),(1545,106,429,0,0.25),(1546,1,430,0,0.25),(1547,96,430,0,1),(1548,101,430,0,1),(1549,102,430,0,0.25),(1550,104,430,0,0.25),(1551,105,430,0,0.25),(1552,106,430,0,0.25),(1553,107,430,0,0.25),(1554,97,431,0,1),(1555,98,431,0,0.25),(1556,99,431,0,0.25),(1557,101,431,0,1),(1558,104,431,0,1),(1559,105,431,0,0.25),(1560,107,431,0,0.25),(1561,108,431,0,0.25),(1562,1,432,0,0.25),(1563,96,432,0,0.25),(1564,97,432,0,0.25),(1565,98,432,0,0.25),(1566,99,432,0,0.25),(1567,105,432,0,0.25),(1568,107,432,0,1),(1569,1,433,0,0.25),(1570,98,433,0,0.25),(1571,100,433,0,0.25),(1572,101,433,0,0.25),(1573,102,433,0,0.25),(1574,103,433,0,1),(1575,105,433,0,1),(1576,108,433,0,0.25),(1577,1,434,0,0.25),(1578,96,434,0,1),(1579,97,434,0,0.25),(1580,98,434,0,0.25),(1581,100,434,0,1),(1582,107,434,0,1),(1583,108,434,0,0.25),(1584,97,435,0,1),(1585,98,435,0,0.25),(1586,100,435,0,0.25),(1587,101,435,0,1),(1588,105,435,0,0.25),(1589,106,435,0,0.25),(1590,107,435,0,0.25),(1591,1,436,0,0.25),(1592,96,436,0,0.25),(1593,97,436,0,1),(1594,99,436,0,1),(1595,100,436,0,0.25),(1596,101,436,0,0.25),(1597,102,436,0,0.25),(1598,103,436,0,0.25),(1599,1,437,0,0.25),(1600,102,437,0,0.25),(1601,103,437,0,0.25),(1602,104,437,0,1),(1603,105,437,0,1),(1604,106,437,0,0.25),(1605,107,437,0,1),(1606,108,437,0,0.25),(1607,96,438,0,1),(1608,98,438,0,1),(1609,99,438,0,0.25),(1610,101,438,0,0.25),(1611,102,438,0,0.25),(1612,103,438,0,1),(1613,105,438,0,0.25),(1614,108,438,0,0.25),(1615,96,439,0,0.25),(1616,97,439,0,0.25),(1617,101,439,0,1),(1618,102,439,0,0.25),(1619,104,439,0,1),(1620,106,439,0,1),(1621,107,439,0,0.25),(1622,1,440,0,0.25),(1623,98,440,0,1),(1624,100,440,0,0.25),(1625,102,440,0,0.25),(1626,105,440,0,1),(1627,107,440,0,1),(1628,108,440,0,0.25),(1629,1,441,0,0.25),(1630,98,441,0,1),(1631,102,441,0,0.25),(1632,103,441,0,1),(1633,105,441,0,0.25),(1634,106,441,0,0.25),(1635,108,441,0,0.25),(1636,1,442,0,0.25),(1637,97,442,0,1),(1638,98,442,0,0.25),(1639,99,442,0,1),(1640,100,442,0,1),(1641,107,442,0,0.25),(1642,108,442,0,0.25),(1643,97,443,0,0.25),(1644,98,443,0,1),(1645,101,443,0,0.25),(1646,102,443,0,1),(1647,104,443,0,1),(1648,105,443,0,0.25),(1649,107,443,0,0.25),(1650,96,444,0,1),(1651,97,444,0,1),(1652,98,444,0,0.25),(1653,101,444,0,0.25),(1654,104,444,0,0.25),(1655,105,444,0,1),(1656,107,444,0,0.25),(1657,108,444,0,0.25),(1658,1,445,0,0.25),(1659,96,445,0,0.25),(1660,97,445,0,0.25),(1661,98,445,0,1),(1662,99,445,0,1),(1663,101,445,0,0.25),(1664,107,445,0,1),(1665,108,445,0,0.25),(1666,1,446,0,0.25),(1667,96,446,0,0.25),(1668,97,446,0,0.25),(1669,99,446,0,1),(1670,104,446,0,0.25),(1671,105,446,0,1),(1672,107,446,0,1),(1673,1,447,0,0.25),(1674,98,447,0,1),(1675,99,447,0,0.25),(1676,101,447,0,0.25),(1677,102,447,0,0.25),(1678,105,447,0,1),(1679,107,447,0,1),(1680,108,447,0,0.25),(1681,96,448,0,0.25),(1682,97,448,0,0.25),(1683,100,448,0,0.25),(1684,102,448,0,1),(1685,105,448,0,0.25),(1686,106,448,0,1),(1687,107,448,0,0.25),(1688,108,448,0,1),(1689,1,449,0,0.25),(1690,98,449,0,1),(1691,100,449,0,0.25),(1692,102,449,0,0.25),(1693,103,449,0,0.25),(1694,105,449,0,1),(1695,107,449,0,1),(1696,108,449,0,0.25),(1697,1,450,0,0.25),(1698,96,450,0,0.25),(1699,102,450,0,1),(1700,103,450,0,1),(1701,104,450,0,0.25),(1702,105,450,0,0.25),(1703,106,450,0,1),(1704,108,450,0,0.25),(1705,1,451,0,0.25),(1706,97,451,0,0.25),(1707,99,451,0,1),(1708,100,451,0,0.25),(1709,101,451,0,0.25),(1710,102,451,0,0.25),(1711,104,451,0,1),(1712,105,451,0,0.25),(1713,96,452,0,0.25),(1714,97,452,0,0.25),(1715,98,452,0,1),(1716,101,452,0,0.25),(1717,103,452,0,1),(1718,104,452,0,0.25),(1719,105,452,0,0.25),(1720,107,452,0,1),(1721,96,453,0,0.25),(1722,98,453,0,0.25),(1723,99,453,0,1),(1724,101,453,0,0.25),(1725,102,453,0,1),(1726,105,453,0,0.25),(1727,107,453,0,0.25),(1728,108,453,0,1),(1729,1,454,0,0.25),(1730,96,454,0,0.25),(1731,98,454,0,1),(1732,100,454,0,0.25),(1733,104,454,0,1),(1734,106,454,0,1),(1735,108,454,0,0.25),(1736,96,455,0,0.25),(1737,99,455,0,0.25),(1738,100,455,0,0.25),(1739,101,455,0,1),(1740,102,455,0,1),(1741,105,455,0,1),(1742,107,455,0,0.25),(1743,96,456,0,1),(1744,97,456,0,0.25),(1745,100,456,0,0.25),(1746,102,456,0,0.25),(1747,103,456,0,1),(1748,106,456,0,0.25),(1749,108,456,0,1),(1750,1,457,0,0.25),(1751,96,457,0,0.25),(1752,97,457,0,0.25),(1753,99,457,0,0.25),(1754,103,457,0,1),(1755,105,457,0,0.25),(1756,107,457,0,1),(1757,108,457,0,1),(1758,1,458,0,0.25),(1759,97,458,0,0.25),(1760,100,458,0,1),(1761,101,458,0,0.25),(1762,102,458,0,0.25),(1763,103,458,0,1),(1764,104,458,0,0.25),(1765,1,459,0,0.25),(1766,96,459,0,0.25),(1767,100,459,0,0.25),(1768,102,459,0,1),(1769,103,459,0,0.25),(1770,104,459,0,1),(1771,108,459,0,0.25);
/*!40000 ALTER TABLE `workshop_participant` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2019-02-16  5:14:40
