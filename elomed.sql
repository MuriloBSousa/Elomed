-- ============================================================
-- ELOMED - BANCO DE DADOS FINAL (schema + dados de demonstracao)
-- MariaDB 10.4 / MySQL 8.0+ | InnoDB | utf8mb4
-- ============================================================
-- Script completo e idempotente de instalacao. Recria o banco
-- `elomed` do zero (DROP DATABASE IF EXISTS) e popula com o
-- schema atual e TODOS os dados de demonstracao: usuarios/RBAC,
-- profissionais, pacientes, protocolos, agendamentos, atendimentos,
-- triagens, prontuarios, registros, convenios, enderecos, etc.
--
-- Gerado automaticamente por mysqldump a partir do banco em
-- funcionamento (nao editar a mao: para re-gerar, use o dump).
--
-- Aplicar:
--   mysql -u root < elomed.sql
--   (ou importar o arquivo completo no phpMyAdmin)
--
-- Acessos de demonstracao:
--   Admin:    admin@gmail.com           / Admin@123
--   Medico:   arnaldo.silva@elomed.com  / EloMed@123
--   Paciente: roberto.alencar@gmail.com / EloMed@123
-- ============================================================


-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: elomed
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Current Database: `elomed`
--

/*!40000 DROP DATABASE IF EXISTS `elomed`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `elomed` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `elomed`;

--
-- Table structure for table `tbl_agendamento`
--

DROP TABLE IF EXISTS `tbl_agendamento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_agendamento` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `data_hora_inicio` datetime NOT NULL,
  `data_hora_fim` datetime NOT NULL,
  `status` enum('agendado','em_andamento','pendente','concluido','cancelado','ausente') NOT NULL DEFAULT 'pendente',
  `vezes_reagendado` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `duracao_minutos` smallint(5) unsigned NOT NULL DEFAULT 30,
  `justificativa_cancelamento` text DEFAULT NULL,
  `classificacao_ia` enum('vermelho','amarelo','verde') DEFAULT NULL,
  `justificativa_classificacao_ia` text DEFAULT NULL,
  `profissional_recomendado_ia` varchar(150) DEFAULT NULL,
  `justificativa_recomendacao_ia` text DEFAULT NULL,
  `classificacao_final` enum('vermelho','amarelo','verde') DEFAULT NULL,
  `justificativa_encaminhamento` text DEFAULT NULL,
  `modelo_ia` varchar(100) DEFAULT NULL,
  `data_analise_ia` datetime DEFAULT NULL,
  `fk_profissional` bigint(20) unsigned NOT NULL,
  `fk_profissional_origem` bigint(20) unsigned DEFAULT NULL,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `fk_triagem` bigint(20) unsigned DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_agendamento_data_status` (`data_hora_inicio`,`status`),
  KEY `idx_agendamento_profissional` (`fk_profissional`,`data_hora_inicio`),
  KEY `idx_agendamento_paciente` (`fk_paciente`,`data_hora_inicio`),
  KEY `idx_agendamento_origem` (`fk_profissional_origem`),
  KEY `fk_agendamento_triagem` (`fk_triagem`),
  CONSTRAINT `fk_agendamento_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_agendamento_profissional` FOREIGN KEY (`fk_profissional`) REFERENCES `tbl_profissional` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_agendamento_profissional_origem` FOREIGN KEY (`fk_profissional_origem`) REFERENCES `tbl_profissional` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_agendamento_triagem` FOREIGN KEY (`fk_triagem`) REFERENCES `tbl_triagem` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_agendamento_periodo` CHECK (`data_hora_fim` > `data_hora_inicio`),
  CONSTRAINT `chk_agendamento_duracao` CHECK (`duracao_minutos` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_agendamento`
--

LOCK TABLES `tbl_agendamento` WRITE;
/*!40000 ALTER TABLE `tbl_agendamento` DISABLE KEYS */;
INSERT INTO `tbl_agendamento` VALUES (3,'2026-10-14 09:00:00','2026-10-14 10:00:00','concluido',0,60,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,1,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(4,'2026-10-14 10:00:00','2026-10-14 11:00:00','em_andamento',0,60,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,2,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(5,'2026-10-14 11:00:00','2026-10-14 12:00:00','agendado',0,60,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,3,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(6,'2026-10-14 14:00:00','2026-10-14 14:30:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,5,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(7,'2026-10-14 15:30:00','2026-10-14 16:00:00','pendente',0,30,NULL,'amarelo','Crise asmatica.',NULL,NULL,'amarelo',NULL,NULL,NULL,16,NULL,4,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(8,'2026-10-15 09:00:00','2026-10-15 09:30:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,6,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(9,'2026-10-16 10:00:00','2026-10-16 10:30:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,2,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(10,'2026-10-17 11:00:00','2026-10-17 11:30:00','pendente',0,30,NULL,'amarelo','Retorno pos-triagem.',NULL,NULL,'amarelo',NULL,NULL,NULL,16,NULL,1,20,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(11,'2026-10-13 10:00:00','2026-10-13 10:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,2,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(12,'2026-10-11 09:00:00','2026-10-11 09:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,3,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(13,'2026-10-09 14:00:00','2026-10-09 14:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,5,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(14,'2026-10-06 10:00:00','2026-10-06 10:30:00','cancelado',0,30,'Paciente solicitou cancelamento por motivos pessoais.',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,4,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(15,'2026-10-04 09:00:00','2026-10-04 09:30:00','ausente',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,1,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(16,'2026-09-30 10:00:00','2026-09-30 10:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,16,NULL,6,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(17,'2026-10-14 08:00:00','2026-10-14 08:30:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(18,'2026-10-14 13:00:00','2026-10-14 13:30:00','em_andamento',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,3,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(19,'2026-10-14 16:00:00','2026-10-14 16:30:00','pendente',0,30,NULL,'amarelo','Dislipidemia.',NULL,NULL,'amarelo',NULL,NULL,NULL,1,NULL,5,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(20,'2026-10-15 08:30:00','2026-10-15 09:00:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,2,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(21,'2026-10-18 09:00:00','2026-10-18 09:30:00','pendente',0,30,NULL,'verde','Dermatologia.',NULL,NULL,'verde',NULL,NULL,NULL,1,NULL,3,21,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(22,'2026-10-12 08:00:00','2026-10-12 08:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,1,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(23,'2026-10-08 09:00:00','2026-10-08 09:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,2,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(24,'2026-10-14 09:30:00','2026-10-14 10:00:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,3,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(25,'2026-10-13 09:00:00','2026-10-13 09:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,4,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(26,'2026-10-14 10:30:00','2026-10-14 11:00:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,1,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(27,'2026-10-11 10:00:00','2026-10-11 10:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,2,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(28,'2026-10-14 11:00:00','2026-10-14 11:30:00','agendado',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,6,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(29,'2026-10-12 11:00:00','2026-10-12 11:30:00','concluido',0,30,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,3,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_agendamento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_anamnese`
--

DROP TABLE IF EXISTS `tbl_anamnese`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_anamnese` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `alergias` text DEFAULT NULL,
  `doencas_previas` text DEFAULT NULL,
  `medicamentos_uso` text DEFAULT NULL,
  `cirurgias` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_anamnese_paciente` (`fk_paciente`),
  CONSTRAINT `fk_anamnese_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_anamnese`
--

LOCK TABLES `tbl_anamnese` WRITE;
/*!40000 ALTER TABLE `tbl_anamnese` DISABLE KEYS */;
INSERT INTO `tbl_anamnese` VALUES (1,6,'Nega alergias','Nega doencas previas','Nega medicamentos','Nega cirurgias',NULL,'2026-10-14 15:36:07','2026-10-14 15:47:04'),(2,1,'Alergia a penicilina','Hipertensao arterial','Losartana 50mg','Apendicectomia (2010)',NULL,'2026-10-14 15:47:04','2026-10-14 15:47:04'),(3,2,'Nega alergias','Nega doencas cronicas','Nega uso continuo','Nega cirurgias',NULL,'2026-10-14 15:47:04','2026-10-14 15:47:04'),(4,3,'Alergia a dipirona','Diabetes tipo 2','Metformina 850mg','Colecistectomia (2015)',NULL,'2026-10-14 15:47:04','2026-10-14 15:47:04'),(5,4,'Nega alergias','Asma leve','Salbutamol SOS','Nega cirurgias',NULL,'2026-10-14 15:47:04','2026-10-14 15:47:04'),(6,5,'Alergia a AAS','Hipertensao e dislipidemia','Sinvastatina 20mg','Revascularizacao (2018)',NULL,'2026-10-14 15:47:04','2026-10-14 15:47:04');
/*!40000 ALTER TABLE `tbl_anamnese` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_atendimento`
--

DROP TABLE IF EXISTS `tbl_atendimento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_atendimento` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_agendamento` bigint(20) unsigned NOT NULL,
  `fk_profissional` bigint(20) unsigned NOT NULL,
  `horario_inicio` datetime NOT NULL,
  `horario_fim` datetime DEFAULT NULL,
  `status` enum('agendado','em_andamento','concluido','cancelado','ausente') NOT NULL DEFAULT 'agendado',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_atendimento_agendamento` (`fk_agendamento`),
  KEY `idx_atendimento_profissional` (`fk_profissional`,`horario_inicio`),
  CONSTRAINT `fk_atendimento_agendamento` FOREIGN KEY (`fk_agendamento`) REFERENCES `tbl_agendamento` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_atendimento_profissional` FOREIGN KEY (`fk_profissional`) REFERENCES `tbl_profissional` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_atendimento`
--

LOCK TABLES `tbl_atendimento` WRITE;
/*!40000 ALTER TABLE `tbl_atendimento` DISABLE KEYS */;
INSERT INTO `tbl_atendimento` VALUES (2,3,16,'2026-10-14 09:00:00','2026-10-14 10:00:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(3,4,16,'2026-10-14 10:00:00',NULL,'em_andamento',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(4,11,16,'2026-10-13 10:00:00','2026-10-13 10:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(5,12,16,'2026-10-11 09:00:00','2026-10-11 09:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(6,13,16,'2026-10-09 14:00:00','2026-10-09 14:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(7,14,16,'2026-10-06 10:00:00',NULL,'cancelado',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(8,15,16,'2026-10-04 09:00:00',NULL,'ausente',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(9,16,16,'2026-09-30 10:00:00','2026-09-30 10:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(10,18,1,'2026-10-14 13:00:00',NULL,'em_andamento',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(11,22,1,'2026-10-12 08:00:00','2026-10-12 08:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(12,23,1,'2026-10-08 09:00:00','2026-10-08 09:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(13,25,2,'2026-10-13 09:00:00','2026-10-13 09:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(14,27,3,'2026-10-11 10:00:00','2026-10-11 10:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(15,29,4,'2026-10-12 11:00:00','2026-10-12 11:30:00','concluido',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_atendimento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_auditoria`
--

DROP TABLE IF EXISTS `tbl_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_auditoria` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned DEFAULT NULL,
  `usuario_nome` varchar(100) DEFAULT NULL,
  `usuario_perfil` varchar(100) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `descricao` varchar(500) NOT NULL,
  `propriedades` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`propriedades`)),
  `ip` varchar(45) DEFAULT NULL,
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_data` (`data_hora`),
  KEY `idx_auditoria_acao` (`acao`),
  KEY `idx_auditoria_usuario` (`fk_usuario`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_auditoria`
--

LOCK TABLES `tbl_auditoria` WRITE;
/*!40000 ALTER TABLE `tbl_auditoria` DISABLE KEYS */;
INSERT INTO `tbl_auditoria` VALUES (1,32,'Richard Nascimento','medico','login','Richard Nascimento entrou com Google','{\"email\":\"richardnascimentosousa16@gmail.com\",\"descricao\":\"Richard Nascimento entrou com Google\",\"usuario_perfil\":\"medico\"}','::1','2026-09-26 14:17:42'),(2,32,'Richard Nascimento','medico','logout','usuario saiu do sistema','{\"usuario_id\":32,\"descricao\":\"usuario saiu do sistema\",\"usuario_perfil\":\"medico\"}','::1','2026-09-26 14:21:03'),(3,32,'Richard Nascimento','medico','login','Richard Nascimento entrou com Google','{\"email\":\"richardnascimentosousa16@gmail.com\",\"descricao\":\"Richard Nascimento entrou com Google\",\"usuario_perfil\":\"medico\"}','::1','2026-09-26 14:21:46'),(4,32,'Richard Nascimento','medico','logout','usuario saiu do sistema','{\"usuario_id\":32,\"descricao\":\"usuario saiu do sistema\",\"usuario_perfil\":\"medico\"}','::1','2026-09-26 14:23:21'),(5,32,'Richard Nascimento','medico','login','Richard Nascimento entrou com Google','{\"email\":\"richardnascimentosousa16@gmail.com\",\"descricao\":\"Richard Nascimento entrou com Google\",\"usuario_perfil\":\"medico\"}','::1','2026-09-26 14:26:53'),(6,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-08 10:59:25'),(7,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-08 10:59:55'),(8,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-08 11:00:40'),(9,33,'Paciente Teste WhatsApp','paciente','analise_ia_encaminhamento','análise IA de encaminhamento gerada automaticamente','{\"id_agendamento\":1,\"classificacao\":\"verde\",\"profissional_indicado\":\"enfermeiro\",\"descricao\":\"análise IA de encaminhamento gerada automaticamente\",\"usuario_perfil\":\"paciente\"}','::1','2026-10-08 11:00:41'),(10,33,'Paciente Teste WhatsApp','paciente','criar_agendamento','novo agendamento solicitado','{\"fk_paciente\":6,\"data\":\"2026-10-09\",\"descricao\":\"novo agendamento solicitado\",\"usuario_perfil\":\"paciente\"}','::1','2026-10-08 11:00:41'),(11,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-08 12:52:54'),(12,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-08 12:53:08'),(13,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-08 12:53:20'),(14,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-08 12:53:31'),(15,32,'Richard Nascimento','medico','login','Richard Nascimento entrou com Google','{\"email\":\"richardnascimentosousa16@gmail.com\",\"descricao\":\"Richard Nascimento entrou com Google\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:32:45'),(16,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:34:06'),(17,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:34:30'),(18,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:35:47'),(19,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:36:06'),(20,33,'Paciente Teste WhatsApp','paciente','atualizar_usuario','paciente completou o cadastro','{\"fk_usuario\":33,\"descricao\":\"paciente completou o cadastro\",\"usuario_perfil\":\"paciente\"}','::1','2026-10-14 12:36:07'),(21,32,'Richard Nascimento','medico','logout','usuario saiu do sistema','{\"usuario_id\":32,\"descricao\":\"usuario saiu do sistema\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:36:30'),(22,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:36:34'),(23,34,'Richard rich','paciente','login','Richard rich entrou com Google','{\"email\":\"richarddonascimentosousa16@gmail.com\",\"descricao\":\"Richard rich entrou com Google\",\"usuario_perfil\":\"paciente\"}','::1','2026-10-14 12:36:42'),(24,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:36:46'),(25,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:37:02'),(26,33,'Paciente Teste WhatsApp',NULL,'login','Paciente Teste WhatsApp fez login','{\"perfil\":\"paciente\",\"descricao\":\"Paciente Teste WhatsApp fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:38:07'),(27,1,'Dr. Arnaldo Silva',NULL,'login','Dr. Arnaldo Silva fez login','{\"perfil\":\"medico\",\"descricao\":\"Dr. Arnaldo Silva fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:50:30'),(28,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:50:30'),(29,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:50:30'),(30,32,'Richard Nascimento',NULL,'login','Richard Nascimento fez login','{\"perfil\":\"medico\",\"descricao\":\"Richard Nascimento fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:50:50'),(31,32,'Richard Nascimento','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:50:50'),(32,32,'Richard Nascimento','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:50:50'),(33,32,'Richard Nascimento',NULL,'login','Richard Nascimento fez login','{\"perfil\":\"medico\",\"descricao\":\"Richard Nascimento fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:51:09'),(34,32,'Richard Nascimento','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:51:10'),(35,34,'Richard rich','paciente','logout','usuario saiu do sistema','{\"usuario_id\":34,\"descricao\":\"usuario saiu do sistema\",\"usuario_perfil\":\"paciente\"}','::1','2026-10-14 12:52:01'),(36,1,'Dr. Arnaldo Silva',NULL,'login','Dr. Arnaldo Silva fez login','{\"perfil\":\"medico\",\"descricao\":\"Dr. Arnaldo Silva fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:52:15'),(37,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:17'),(38,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:22'),(39,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Evolucao e Tratamento) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"evolucao\",\"descricao\":\"Abriu o prontuario (Evolucao e Tratamento) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:30'),(40,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Informacoes Complementares) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"complementar\",\"descricao\":\"Abriu o prontuario (Informacoes Complementares) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:34'),(41,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Evolucao e Tratamento) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"evolucao\",\"descricao\":\"Abriu o prontuario (Evolucao e Tratamento) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:44'),(42,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:45'),(43,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:46'),(44,1,'Dr. Arnaldo Silva',NULL,'login','Dr. Arnaldo Silva fez login','{\"perfil\":\"medico\",\"descricao\":\"Dr. Arnaldo Silva fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:55:49'),(45,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(46,1,'Dr. Arnaldo Silva','medico','editar_prontuario','Atualizou os dados cadastrais do prontuario de Roberto Alencar','{\"paciente_id\":1,\"descricao\":\"Atualizou os dados cadastrais do prontuario de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(47,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(48,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(49,1,'Dr. Arnaldo Silva','medico','registrar_prontuario','Registrou em Anamnese no prontuario de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"status\":\"rascunho\",\"descricao\":\"Registrou em Anamnese no prontuario de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(50,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(51,1,'Dr. Arnaldo Silva','medico','registrar_prontuario','Registrou em Evolucao e Tratamento no prontuario de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"evolucao\",\"status\":\"finalizado\",\"descricao\":\"Registrou em Evolucao e Tratamento no prontuario de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(52,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Evolucao e Tratamento) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"evolucao\",\"descricao\":\"Abriu o prontuario (Evolucao e Tratamento) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:55:49'),(53,1,'Dr. Arnaldo Silva',NULL,'login','Dr. Arnaldo Silva fez login','{\"perfil\":\"medico\",\"descricao\":\"Dr. Arnaldo Silva fez login\",\"usuario_perfil\":null}','::1','2026-10-14 12:59:49'),(54,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:59:49'),(55,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 12:59:49'),(56,1,'Dr. Arnaldo Silva',NULL,'login','Dr. Arnaldo Silva fez login','{\"perfil\":\"medico\",\"descricao\":\"Dr. Arnaldo Silva fez login\",\"usuario_perfil\":null}','::1','2026-10-14 13:01:38'),(57,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (dados do paciente) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"dados\",\"descricao\":\"Abriu o prontuario (dados do paciente) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 13:01:38'),(58,1,'Dr. Arnaldo Silva','medico','consultar_prontuario','Abriu o prontuario (Anamnese) de Roberto Alencar','{\"paciente_id\":1,\"secao\":\"anamnese\",\"descricao\":\"Abriu o prontuario (Anamnese) de Roberto Alencar\",\"usuario_perfil\":\"medico\"}','::1','2026-10-14 13:01:38'),(59,1,'Dr. Arnaldo Silva',NULL,'login','Dr. Arnaldo Silva fez login','{\"perfil\":\"medico\",\"descricao\":\"Dr. Arnaldo Silva fez login\",\"usuario_perfil\":null}','::1','2026-10-16 08:45:52');
/*!40000 ALTER TABLE `tbl_auditoria` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_contato_emergencia`
--

DROP TABLE IF EXISTS `tbl_contato_emergencia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_contato_emergencia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `nome` varchar(100) NOT NULL,
  `parentesco` varchar(50) DEFAULT NULL,
  `telefone` varchar(20) NOT NULL,
  `observacao` varchar(255) DEFAULT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_emergencia_paciente` (`fk_paciente`),
  CONSTRAINT `fk_emergencia_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_contato_emergencia`
--

LOCK TABLES `tbl_contato_emergencia` WRITE;
/*!40000 ALTER TABLE `tbl_contato_emergencia` DISABLE KEYS */;
INSERT INTO `tbl_contato_emergencia` VALUES (1,1,'Maria Alencar','Mãe','11999991111',NULL,1),(2,2,'José Regina','Irmão','21999992222',NULL,1),(3,3,'Carla Ramos','Esposa','31999993333',NULL,1),(4,4,'Marcos Lopes','Pai','51999994444',NULL,1),(5,5,'Patrícia Pires','Esposa','41999995555',NULL,1),(6,6,'Contato Emergencia','Amigo','61997776655',NULL,1);
/*!40000 ALTER TABLE `tbl_contato_emergencia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_convenio`
--

DROP TABLE IF EXISTS `tbl_convenio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_convenio` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome_operadora` varchar(100) NOT NULL,
  `registro_ans` varchar(20) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_convenio_nome` (`nome_operadora`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_convenio`
--

LOCK TABLES `tbl_convenio` WRITE;
/*!40000 ALTER TABLE `tbl_convenio` DISABLE KEYS */;
INSERT INTO `tbl_convenio` VALUES (1,'Unimed','000000',1),(2,'Bradesco Saúde','005711',1),(3,'SulAmérica Saúde','006246',1);
/*!40000 ALTER TABLE `tbl_convenio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_diagnostico`
--

DROP TABLE IF EXISTS `tbl_diagnostico`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_diagnostico` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `fk_medico` bigint(20) unsigned NOT NULL,
  `queixa_principal` text DEFAULT NULL,
  `sintomas` text DEFAULT NULL,
  `historico_medico` text DEFAULT NULL,
  `sinais_vitais` text DEFAULT NULL,
  `diagnostico_final` text DEFAULT NULL,
  `conduta_medica` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_diag_paciente` (`fk_paciente`),
  KEY `idx_diag_medico` (`fk_medico`),
  CONSTRAINT `fk_diag_medico` FOREIGN KEY (`fk_medico`) REFERENCES `tbl_usuario` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_diag_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_diagnostico`
--

LOCK TABLES `tbl_diagnostico` WRITE;
/*!40000 ALTER TABLE `tbl_diagnostico` DISABLE KEYS */;
INSERT INTO `tbl_diagnostico` VALUES (1,1,32,'Dor no peito ao esforco','Dor retroesternal aos esforcos','Hipertenso, em uso de losartana. Pai com historico de IAM.','PA 148/92 mmHg; FC 88 bpm; Tax 36.5C; SatO2 97%','Angina estavel','Solicitado ECG e troponina; iniciado AAS 100mg; retorno em 7 dias.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(2,2,32,'Febre e dor no corpo','Mialgia e cefaleia','Sem doencas cronicas. Viajou para area endemica ha 6 dias.','Tax 38.9C; PA 112/74; FC 96 bpm','Dengue (suspeita clinica)','Hidratacao oral, dipirona SOS, hemograma e sorologia. Retorno se sinais de alarme.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(3,3,32,'Controle de diabetes','Poliuria e polidipsia','Diabetes tipo 2 ha 8 anos em uso de metformina.','PA 130/85; IMC 29; Glicemia jejum 146 mg/dL','Diabetes mellitus tipo 2 descompensado','Ajuste de metformina 1000mg 2x/dia; orientacao dietetica; HbA1c solicitada.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(4,5,32,'Retorno hipertensao','','Hipertenso e dislipidemico, pos-revascularizacao.','PA 138/86; FC 72 bpm','Hipertensao arterial controlada','Mantida losartana + sinvastatina; retorno em 3 meses.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(5,6,32,'Consulta inicial','','Paciente sem comorbidades.','PA 120/80; FC 78 bpm; Tax 36.6C','Saudavel','Sem medicacoes. Orientacoes gerais de saude.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(6,1,1,'Check-up cardiologico','','Hipertenso controlado.','PA 128/82; FC 70 bpm','Hipertensao arterial sistemica','Mantido tratamento; solicitado ECG de controle.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(7,2,1,'Palpitacoes','','Episodios esporadicos de palpitacao.','PA 118/76; FC 82 bpm','Extrassistoles benignas','Holter de 24h; evitar cafeina.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(8,4,2,'Crise de asma','','Asmatica leve.','FR 22; SatO2 95%','Crise asmatica leve','Salbutamol spray; orientado retorno se piora.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(9,2,3,'Lesao de pele','','Lesao em dorso.','Pele integra, lesao pigmentada 1cm','Nevo melanocitico','Orientado observacao; retorno se mudanca.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(10,3,4,'Dor lombar','','Dor lombar mecanica.','Sem deficit neurologico','Lombalgia aguda','Analgesico e fisioterapia.','2026-10-14 15:49:27','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_diagnostico` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_endereco`
--

DROP TABLE IF EXISTS `tbl_endereco`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_endereco` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `logradouro` varchar(150) NOT NULL,
  `numero` varchar(20) NOT NULL,
  `complemento` varchar(100) DEFAULT NULL,
  `bairro` varchar(100) NOT NULL,
  `cep` char(8) NOT NULL,
  `cidade` varchar(100) NOT NULL,
  `uf` char(2) NOT NULL,
  `principal` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_endereco_usuario` (`fk_usuario`),
  CONSTRAINT `fk_endereco_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_endereco`
--

LOCK TABLES `tbl_endereco` WRITE;
/*!40000 ALTER TABLE `tbl_endereco` DISABLE KEYS */;
INSERT INTO `tbl_endereco` VALUES (1,16,'SQN 202 Bloco A','302',NULL,'Asa Norte','70842010','Brasilia','DF',1),(2,17,'Rua das Palmeiras','145',NULL,'Jardim America','74255070','Goiania','GO',1),(3,18,'Av. Paulista','1500',NULL,'Bela Vista','01310200','Sao Paulo','SP',1),(4,19,'Rua do Sol','88',NULL,'Centro','65010000','Sao Luis','MA',1),(5,20,'Rua das Acacias','410',NULL,'Boa Viagem','51020030','Recife','PE',1),(6,33,'SQS 308 Bloco C','apto 501',NULL,'Asa Sul','70355030','Brasilia','DF',1);
/*!40000 ALTER TABLE `tbl_endereco` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_feedback`
--

DROP TABLE IF EXISTS `tbl_feedback`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_feedback` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_atendimento` bigint(20) unsigned NOT NULL,
  `fk_profissional` bigint(20) unsigned NOT NULL,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `nota` tinyint(3) unsigned NOT NULL,
  `comentario` varchar(500) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_feedback_atendimento` (`fk_atendimento`),
  KEY `idx_feedback_profissional` (`fk_profissional`),
  KEY `idx_feedback_paciente` (`fk_paciente`),
  KEY `idx_feedback_criado` (`criado_em`),
  KEY `fk_feedback_usuario` (`fk_usuario`),
  CONSTRAINT `fk_feedback_atendimento` FOREIGN KEY (`fk_atendimento`) REFERENCES `tbl_atendimento` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_profissional` FOREIGN KEY (`fk_profissional`) REFERENCES `tbl_profissional` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_feedback_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_feedback_nota` CHECK (`nota` between 0 and 5)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_feedback`
--

LOCK TABLES `tbl_feedback` WRITE;
/*!40000 ALTER TABLE `tbl_feedback` DISABLE KEYS */;
INSERT INTO `tbl_feedback` VALUES (1,2,16,1,16,5,'Muito atencioso, explicou tudo.','2026-10-14 15:49:27'),(2,4,16,2,17,4,'Oriuntada com clareza sobre sinais de alarme.','2026-10-14 15:49:27'),(3,5,16,3,18,5,'Gostei do acompanhamento.','2026-10-14 15:49:27'),(4,6,16,5,20,5,'Profissional excelente.','2026-10-14 15:49:27'),(5,9,16,6,33,5,'Primeiro atendimento, otimo.','2026-10-14 15:49:27'),(6,11,1,1,16,5,'Muito bom.','2026-10-14 15:49:27'),(7,12,1,2,17,4,'Atencioso.','2026-10-14 15:49:27'),(8,13,2,4,19,5,'Bom atendimento.','2026-10-14 15:49:27'),(9,14,3,2,17,5,'Otimo.','2026-10-14 15:49:27'),(10,15,4,3,18,4,'Bom.','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_feedback` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_lembrete`
--

DROP TABLE IF EXISTS `tbl_lembrete`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_lembrete` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_agendamento` bigint(20) unsigned NOT NULL,
  `mensagem` varchar(255) NOT NULL,
  `status` enum('enviado','pendente','falha') NOT NULL DEFAULT 'pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `enviado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_lembrete_agendamento` (`fk_agendamento`),
  KEY `idx_lembrete_status` (`status`),
  CONSTRAINT `fk_lembrete_agendamento` FOREIGN KEY (`fk_agendamento`) REFERENCES `tbl_agendamento` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_lembrete`
--

LOCK TABLES `tbl_lembrete` WRITE;
/*!40000 ALTER TABLE `tbl_lembrete` DISABLE KEYS */;
INSERT INTO `tbl_lembrete` VALUES (1,5,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(2,6,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(3,8,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(4,9,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(5,17,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(6,20,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(7,24,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(8,26,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27'),(9,28,'Lembrete de consulta agendada no EloMed.','enviado','2026-10-14 15:49:27','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_lembrete` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_paciente`
--

DROP TABLE IF EXISTS `tbl_paciente`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_paciente` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `data_nascimento` date NOT NULL,
  `cpf` char(11) DEFAULT NULL,
  `rg` varchar(20) DEFAULT NULL,
  `sexo` enum('feminino','masculino','nao_informado') NOT NULL DEFAULT 'nao_informado',
  `estado_civil` enum('solteiro','casado','divorciado','viuvo','uniao_estavel','separado','nao_informado') NOT NULL DEFAULT 'nao_informado',
  `foto` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paciente_usuario` (`fk_usuario`),
  UNIQUE KEY `uq_paciente_cpf` (`cpf`),
  CONSTRAINT `fk_paciente_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_paciente`
--

LOCK TABLES `tbl_paciente` WRITE;
/*!40000 ALTER TABLE `tbl_paciente` DISABLE KEYS */;
INSERT INTO `tbl_paciente` VALUES (1,16,'1985-04-12',NULL,NULL,'masculino','casado',NULL,'2026-09-26 17:15:20','2026-10-14 15:55:49'),(2,17,'1990-08-23','11144477735','2345678','feminino','solteiro',NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(3,18,'1978-12-02','39053344705','3456789','masculino','divorciado',NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(4,19,'1995-01-15','45317828791','4567890','feminino','uniao_estavel',NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(5,20,'1963-06-30','35649887772','5678901','masculino','viuvo',NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(6,33,'1990-05-10','78687687621','6789012','masculino','nao_informado',NULL,'2026-10-08 11:54:34','2026-10-14 15:47:04');
/*!40000 ALTER TABLE `tbl_paciente` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_paciente_convenio`
--

DROP TABLE IF EXISTS `tbl_paciente_convenio`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_paciente_convenio` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `fk_convenio` bigint(20) unsigned NOT NULL,
  `numero_carteirinha` varchar(50) NOT NULL,
  `validade` date DEFAULT NULL,
  `titular_nome` varchar(100) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paciente_convenio` (`fk_paciente`,`fk_convenio`,`numero_carteirinha`),
  KEY `idx_pc_convenio` (`fk_convenio`),
  CONSTRAINT `fk_pc_convenio` FOREIGN KEY (`fk_convenio`) REFERENCES `tbl_convenio` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_pc_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_paciente_convenio`
--

LOCK TABLES `tbl_paciente_convenio` WRITE;
/*!40000 ALTER TABLE `tbl_paciente_convenio` DISABLE KEYS */;
INSERT INTO `tbl_paciente_convenio` VALUES (1,1,1,'001122334455','2027-12-31',NULL,1),(2,3,2,'556677889900','2026-12-31',NULL,1),(3,5,3,'998877665544','2027-06-30',NULL,1);
/*!40000 ALTER TABLE `tbl_paciente_convenio` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_perguntas_protocolo`
--

DROP TABLE IF EXISTS `tbl_perguntas_protocolo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_perguntas_protocolo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fk_protocolo` int(10) unsigned NOT NULL,
  `ordem` int(10) unsigned NOT NULL DEFAULT 1,
  `tipo` enum('texto','numero','select','radio','checkbox','textarea') NOT NULL DEFAULT 'texto',
  `pergunta` text NOT NULL,
  `placeholder` varchar(255) DEFAULT NULL,
  `obrigatorio` tinyint(1) NOT NULL DEFAULT 0,
  `opcoes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`opcoes`)),
  `condicional` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`condicional`)),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pergunta_ordem` (`fk_protocolo`,`ordem`),
  KEY `idx_pergunta_protocolo` (`fk_protocolo`),
  CONSTRAINT `fk_pergunta_protocolo` FOREIGN KEY (`fk_protocolo`) REFERENCES `tbl_protocolos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_perguntas_protocolo`
--

LOCK TABLES `tbl_perguntas_protocolo` WRITE;
/*!40000 ALTER TABLE `tbl_perguntas_protocolo` DISABLE KEYS */;
INSERT INTO `tbl_perguntas_protocolo` VALUES (1,1,1,'textarea','Descreva detalhadamente os sintomas que motivaram a consulta','Ex: dor de cabeça, febre, cansaço...',1,NULL,NULL,'2026-09-26 17:15:20'),(2,1,2,'texto','Há quanto tempo os sintomas começaram?','Ex: 2 dias, 1 semana...',1,NULL,NULL,'2026-09-26 17:15:20'),(3,1,3,'select','Os sintomas são contínuos ou intermitentes?',NULL,1,'[\"Contínuos\",\"Intermitentes\",\"Variável\"]',NULL,'2026-09-26 17:15:20'),(4,1,4,'textarea','Já fez algum tratamento para estes sintomas?','Descreva medicamentos ou procedimentos...',0,NULL,NULL,'2026-09-26 17:15:20'),(5,1,5,'textarea','Possui algum histórico familiar de doenças similares?','Descreva histórico familiar...',0,NULL,NULL,'2026-09-26 17:15:20'),(6,2,1,'texto','Tem febre há quantos dias?','Ex: 2 dias, 5 dias...',1,NULL,NULL,'2026-09-26 17:15:20'),(7,2,2,'select','Apresenta dor atrás dos olhos?',NULL,1,'[\"Sim\",\"Não\"]',NULL,'2026-09-26 17:15:20'),(8,2,3,'select','Apresenta dor muscular ou nas articulações?',NULL,1,'[\"Sim\",\"Não\"]',NULL,'2026-09-26 17:15:20'),(9,2,4,'select','Apresenta manchas vermelhas na pele?',NULL,1,'[\"Sim\",\"Não\"]',NULL,'2026-09-26 17:15:20'),(10,2,5,'select','Apresenta náuseas ou vômitos?',NULL,0,'[\"Sim\",\"Não\"]',NULL,'2026-09-26 17:15:20'),(11,2,6,'numero','Qual a temperatura máxima medida?','Ex: 38.5',1,NULL,NULL,'2026-09-26 17:15:20'),(12,2,7,'textarea','Descreva outros sintomas presentes','Ex: dor abdominal, sangramentos...',0,NULL,NULL,'2026-09-26 17:15:20');
/*!40000 ALTER TABLE `tbl_perguntas_protocolo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_permissions`
--

DROP TABLE IF EXISTS `tbl_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL DEFAULT 'geral',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permission_nome` (`nome`),
  UNIQUE KEY `uq_permission_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_permissions`
--

LOCK TABLES `tbl_permissions` WRITE;
/*!40000 ALTER TABLE `tbl_permissions` DISABLE KEYS */;
INSERT INTO `tbl_permissions` VALUES (1,'Meu Cadastro','meu_cadastro','geral'),(2,'Visualizar dashboard próprio','dashboard_visualizar','geral'),(3,'Acessar painel geral','painel_acessar','sistema'),(4,'Gerenciar usuários','usuario_gerenciar','sistema'),(5,'Cadastrar profissional','usuario_cadastrar_profissional','sistema'),(6,'Cadastrar paciente','usuario_cadastrar_paciente','sistema'),(7,'Ver trilha de auditoria','auditoria_visualizar','sistema'),(8,'Gerar relatório de auditoria em PDF','auditoria_gerar_pdf','sistema'),(9,'Acessar monitoramento','auditoria_monitorar','sistema'),(10,'Gerenciar agendamentos','agendamento_gerenciar','sistema'),(11,'Solicitar agendamento','agendamento_solicitar','geral'),(12,'Acessar módulo médico','medico_acessar','medico'),(13,'Ver pendências médicas','medico_pendencias','medico'),(14,'Ver agenda médica própria','medico_minha_agenda','medico'),(15,'Ver histórico de atendimentos próprios','medico_historico_atendimentos','medico'),(16,'Iniciar atendimento','atendimento_iniciar','medico'),(17,'Gerenciar prontuário','prontuario_gerenciar','medico'),(18,'Usar diagnóstico com IA','diagnostico_ia','medico'),(19,'Aprovar triagens','triagem_aprovar','medico'),(20,'Acessar módulo enfermagem','enfermeiro_acessar','enfermeiro'),(21,'Ver agenda de enfermagem','enfermeiro_minha_agenda','enfermeiro'),(22,'Ver histórico de enfermagem','enfermeiro_historico','enfermeiro'),(23,'Criar e gerenciar triagens','triagem_gerenciar','enfermeiro'),(24,'Acessar módulo técnico','tecnico_acessar','tecnico'),(25,'Ver agenda do técnico','tecnico_minha_agenda','tecnico'),(26,'Ver histórico do técnico','tecnico_historico','tecnico'),(27,'Acessar módulo paciente','paciente_acessar','paciente'),(28,'Ver próprias triagens','paciente_minhas_triagens','paciente'),(29,'Ver próprios agendamentos','paciente_meus_agendamentos','paciente'),(30,'Ver histórico de atendimentos','paciente_historico','paciente'),(31,'Ver linha do tempo','paciente_linha_do_tempo','paciente'),(32,'Analisar e encaminhar triagens','triagem_analisar','tecnico'),(33,'Consultar prontuario do paciente','prontuario_paciente_acessar','medico'),(34,'Registrar no prontuario do paciente','prontuario_paciente_registrar','medico'),(35,'Visualizar feedback dos pacientes','feedback_visualizar','sistema'),(36,'Avaliar atendimento recebido','feedback_registrar','paciente');
/*!40000 ALTER TABLE `tbl_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_profissional`
--

DROP TABLE IF EXISTS `tbl_profissional`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_profissional` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `especialidade` varchar(150) NOT NULL,
  `numero_registro` varchar(100) NOT NULL,
  `tempo_experiencia_anos` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `limite_diario` int(10) unsigned NOT NULL DEFAULT 12,
  `foto` varchar(255) DEFAULT NULL,
  `pausa_inicio` time DEFAULT NULL,
  `pausa_fim` time DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profissional_usuario` (`fk_usuario`),
  UNIQUE KEY `uq_profissional_registro` (`numero_registro`),
  CONSTRAINT `fk_profissional_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `chk_profissional_limite` CHECK (`limite_diario` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_profissional`
--

LOCK TABLES `tbl_profissional` WRITE;
/*!40000 ALTER TABLE `tbl_profissional` DISABLE KEYS */;
INSERT INTO `tbl_profissional` VALUES (1,1,'Cardiologia','CRM/SP 123456',18,12,NULL,'12:00:00','13:00:00','2026-09-26 17:15:20','2026-10-14 15:47:04'),(2,2,'Pediatria','CRM/RJ 234567',15,12,NULL,'12:30:00','13:30:00','2026-09-26 17:15:20','2026-10-14 15:47:04'),(3,3,'Dermatologia','CRM/MG 345678',10,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(4,4,'Ortopedia','CRM/RS 456789',8,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(5,5,'Enfermagem','COREN/PR 567890',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(6,6,'Enfermagem','COREN/SC 678901',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(7,7,'Enfermagem','COREN/BA 789012',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(8,8,'Enfermagem','COREN/PE 890123',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(9,9,'Enfermagem','COREN/CE 901234',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(10,10,'Enfermagem','COREN/GO 012345',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(11,11,'Enfermagem','COREN/DF 112233',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(12,12,'Enfermagem','COREN/ES 223344',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(13,13,'Enfermagem','COREN/AM 334455',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(14,14,'Enfermagem','COREN/MT 445566',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(15,15,'Enfermagem','COREN/MS 556677',9,12,NULL,NULL,NULL,'2026-09-26 17:15:20','2026-10-14 15:47:04'),(16,32,'Clinica Medica','CRM/DF 165432',12,12,NULL,'12:00:00','13:00:00','2026-10-14 15:47:04','2026-10-14 15:47:04');
/*!40000 ALTER TABLE `tbl_profissional` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_prontuario`
--

DROP TABLE IF EXISTS `tbl_prontuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_prontuario` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_atendimento` bigint(20) unsigned NOT NULL,
  `queixa_principal` text DEFAULT NULL,
  `historico_medico` text DEFAULT NULL,
  `sinais_vitais` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sinais_vitais`)),
  `analise_ia` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analise_ia`)),
  `diagnostico_final` text DEFAULT NULL,
  `conduta_medica` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prontuario_atendimento` (`fk_atendimento`),
  CONSTRAINT `fk_prontuario_atendimento` FOREIGN KEY (`fk_atendimento`) REFERENCES `tbl_atendimento` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_prontuario`
--

LOCK TABLES `tbl_prontuario` WRITE;
/*!40000 ALTER TABLE `tbl_prontuario` DISABLE KEYS */;
INSERT INTO `tbl_prontuario` VALUES (1,2,'Dor no peito ao esforco','Hipertenso, em uso de losartana. Pai com historico de IAM.','{\"texto\":\"PA 148\\/92 mmHg; FC 88 bpm; Tax 36.5C; SatO2 97%\"}','{\"resumo\":\"Dor no peito ao esforco\",\"hipoteses\":[\"Angina estavel\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Angina estavel','Solicitado ECG e troponina; iniciado AAS 100mg; retorno em 7 dias.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(2,4,'Febre e dor no corpo','Sem doencas cronicas. Viajou para area endemica ha 6 dias.','{\"texto\":\"Tax 38.9C; PA 112\\/74; FC 96 bpm\"}','{\"resumo\":\"Febre e dor no corpo\",\"hipoteses\":[\"Dengue (suspeita clinica)\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Dengue (suspeita clinica)','Hidratacao oral, dipirona SOS, hemograma e sorologia. Retorno se sinais de alarme.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(3,5,'Controle de diabetes','Diabetes tipo 2 ha 8 anos em uso de metformina.','{\"texto\":\"PA 130\\/85; IMC 29; Glicemia jejum 146 mg\\/dL\"}','{\"resumo\":\"Controle de diabetes\",\"hipoteses\":[\"Diabetes mellitus tipo 2 descompensado\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Diabetes mellitus tipo 2 descompensado','Ajuste de metformina 1000mg 2x/dia; orientacao dietetica; HbA1c solicitada.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(4,6,'Retorno hipertensao','Hipertenso e dislipidemico, pos-revascularizacao.','{\"texto\":\"PA 138\\/86; FC 72 bpm\"}','{\"resumo\":\"Retorno hipertensao\",\"hipoteses\":[\"Hipertensao arterial controlada\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Hipertensao arterial controlada','Mantida losartana + sinvastatina; retorno em 3 meses.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(5,9,'Consulta inicial','Paciente sem comorbidades.','{\"texto\":\"PA 120\\/80; FC 78 bpm; Tax 36.6C\"}','{\"resumo\":\"Consulta inicial\",\"hipoteses\":[\"Saudavel\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Saudavel','Sem medicacoes. Orientacoes gerais de saude.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(6,11,'Check-up cardiologico','Hipertenso controlado.','{\"texto\":\"PA 128\\/82; FC 70 bpm\"}','{\"resumo\":\"Check-up cardiologico\",\"hipoteses\":[\"Hipertensao arterial sistemica\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Hipertensao arterial sistemica','Mantido tratamento; solicitado ECG de controle.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(7,12,'Palpitacoes','Episodios esporadicos de palpitacao.','{\"texto\":\"PA 118\\/76; FC 82 bpm\"}','{\"resumo\":\"Palpitacoes\",\"hipoteses\":[\"Extrassistoles benignas\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Extrassistoles benignas','Holter de 24h; evitar cafeina.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(8,13,'Crise de asma','Asmatica leve.','{\"texto\":\"FR 22; SatO2 95%\"}','{\"resumo\":\"Crise de asma\",\"hipoteses\":[\"Crise asmatica leve\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Crise asmatica leve','Salbutamol spray; orientado retorno se piora.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(9,14,'Lesao de pele','Lesao em dorso.','{\"texto\":\"Pele integra, lesao pigmentada 1cm\"}','{\"resumo\":\"Lesao de pele\",\"hipoteses\":[\"Nevo melanocitico\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Nevo melanocitico','Orientado observacao; retorno se mudanca.','2026-10-14 15:49:27','2026-10-14 15:49:27'),(10,15,'Dor lombar','Dor lombar mecanica.','{\"texto\":\"Sem deficit neurologico\"}','{\"resumo\":\"Dor lombar\",\"hipoteses\":[\"Lombalgia aguda\"],\"classificacao\":\"amarelo\",\"modelo\":\"openai\\/gpt-oss-20b\"}','Lombalgia aguda','Analgesico e fisioterapia.','2026-10-14 15:49:27','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_prontuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_prontuario_registro`
--

DROP TABLE IF EXISTS `tbl_prontuario_registro`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_prontuario_registro` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `fk_profissional` bigint(20) unsigned DEFAULT NULL,
  `fk_agendamento` bigint(20) unsigned DEFAULT NULL,
  `secao` enum('anamnese','evolucao','complementar') NOT NULL,
  `conteudo` longtext NOT NULL,
  `status` enum('rascunho','finalizado') NOT NULL DEFAULT 'rascunho',
  `data_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_preg_paciente_secao` (`fk_paciente`,`secao`,`data_hora`),
  KEY `idx_preg_autor` (`fk_usuario`),
  KEY `fk_preg_profissional` (`fk_profissional`),
  KEY `fk_preg_agendamento` (`fk_agendamento`),
  CONSTRAINT `fk_preg_agendamento` FOREIGN KEY (`fk_agendamento`) REFERENCES `tbl_agendamento` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_preg_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_preg_profissional` FOREIGN KEY (`fk_profissional`) REFERENCES `tbl_profissional` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_preg_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_prontuario_registro`
--

LOCK TABLES `tbl_prontuario_registro` WRITE;
/*!40000 ALTER TABLE `tbl_prontuario_registro` DISABLE KEYS */;
INSERT INTO `tbl_prontuario_registro` VALUES (1,6,32,16,NULL,'anamnese','Paciente relata quadro de dor de garganta ha 2 dias, sem febre. Refere bom estado geral. Nega alergias.','finalizado','2026-10-03 01:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(2,6,32,16,NULL,'evolucao','Avaliacao inicial: orofaringe hiperemiada, sem exsudato. Conduta: sintomaticos e hidratacao.','finalizado','2026-09-16 04:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(3,6,32,16,NULL,'evolucao','Retorno: melhora significativa dos sintomas. Mantida observacao.','finalizado','2026-10-12 04:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(4,6,32,16,NULL,'evolucao','Evolucao em elaboracao: aguardando resultado de exames laboratoriais.','rascunho','2026-09-11 00:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(5,6,32,16,NULL,'complementar','Solicitados exames: hemograma completo e PCR. Oriundo da triagem via WhatsApp.','finalizado','2026-09-12 00:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(6,1,1,1,NULL,'anamnese','Paciente hipertenso, refere dor toracica aos esforcos. Historico familiar de infarto.','finalizado','2026-09-13 03:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(7,1,1,1,NULL,'evolucao','Iniciado AAS e solicitado ECG. Pressao controlada com losartana.','finalizado','2026-09-17 18:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(8,1,32,16,NULL,'evolucao','Nota do Dr. Richard: revisar resultado do ECG antes do retorno do paciente.','rascunho','2026-10-02 10:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(9,1,1,1,NULL,'complementar','Exames: ECG com alteracoes inespecificas. Troponina negativa.','finalizado','2026-09-06 13:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(10,2,32,16,NULL,'anamnese','Quadro febril ha 4 dias com mialgia e cefaleia. Investigacao de dengue em curso.','finalizado','2026-10-07 01:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(11,2,32,16,NULL,'evolucao','Hidratacao e sintomaticos. Solicitado hemograma e sorologia.','finalizado','2026-10-14 13:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(12,3,1,1,NULL,'anamnese','Paciente diabetico, em uso de metformina. Queixa de poliuria.','finalizado','2026-09-28 23:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27'),(13,3,1,1,NULL,'evolucao','Ajuste de dose de metformina. Orientacao dietetica reforcada.','finalizado','2026-09-10 00:49:27','2026-10-14 15:49:27','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_prontuario_registro` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_prontuario_visualizacao`
--

DROP TABLE IF EXISTS `tbl_prontuario_visualizacao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_prontuario_visualizacao` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_prontuario` bigint(20) unsigned NOT NULL,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pv_prontuario` (`fk_prontuario`),
  KEY `idx_pv_usuario` (`fk_usuario`),
  CONSTRAINT `fk_pv_prontuario` FOREIGN KEY (`fk_prontuario`) REFERENCES `tbl_prontuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pv_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_prontuario_visualizacao`
--

LOCK TABLES `tbl_prontuario_visualizacao` WRITE;
/*!40000 ALTER TABLE `tbl_prontuario_visualizacao` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_prontuario_visualizacao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_protocolos`
--

DROP TABLE IF EXISTS `tbl_protocolos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_protocolos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `categoria` enum('cronico','agudo','investigacao','acompanhamento') NOT NULL DEFAULT 'agudo',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_protocolo_nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_protocolos`
--

LOCK TABLES `tbl_protocolos` WRITE;
/*!40000 ALTER TABLE `tbl_protocolos` DISABLE KEYS */;
INSERT INTO `tbl_protocolos` VALUES (1,'Investigação Diagnóstica','Protocolo genérico para casos onde o diagnóstico ainda não é conhecido','investigacao',1,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(2,'Dengue','Protocolo para suspeita de dengue e outras arboviroses','agudo',1,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(3,'Diabetes Tipo 2','Protocolo para acompanhamento de diabetes mellitus tipo 2','cronico',1,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(4,'Hipertensão Arterial','Protocolo para acompanhamento de hipertensão arterial sistêmica','cronico',1,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(5,'Infarto Agudo do Miocárdio','Protocolo para suspeita de infarto agudo do miocárdio','agudo',1,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(6,'AVC (Acidente Vascular Cerebral)','Protocolo para suspeita de acidente vascular cerebral','agudo',1,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(7,'Asma','Protocolo para crise asmática e acompanhamento de asma','cronico',1,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(8,'COVID-19','Protocolo para suspeita de infecção por COVID-19','agudo',1,'2026-09-26 17:15:20','2026-09-26 17:15:20');
/*!40000 ALTER TABLE `tbl_protocolos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_recuperacao_senha`
--

DROP TABLE IF EXISTS `tbl_recuperacao_senha`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_recuperacao_senha` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `token` char(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recuperacao_token` (`token`),
  KEY `idx_recuperacao_usuario` (`fk_usuario`),
  KEY `idx_recuperacao_validacao` (`token`,`usado`,`expira_em`),
  CONSTRAINT `fk_recuperacao_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_recuperacao_senha`
--

LOCK TABLES `tbl_recuperacao_senha` WRITE;
/*!40000 ALTER TABLE `tbl_recuperacao_senha` DISABLE KEYS */;
/*!40000 ALTER TABLE `tbl_recuperacao_senha` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_resposta_protocolo`
--

DROP TABLE IF EXISTS `tbl_resposta_protocolo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_resposta_protocolo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_triagem` bigint(20) unsigned NOT NULL,
  `fk_pergunta` int(10) unsigned NOT NULL,
  `resposta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`resposta`)),
  `respondido_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resposta_triagem_pergunta` (`fk_triagem`,`fk_pergunta`),
  KEY `idx_resposta_pergunta` (`fk_pergunta`),
  CONSTRAINT `fk_resposta_pergunta` FOREIGN KEY (`fk_pergunta`) REFERENCES `tbl_perguntas_protocolo` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_resposta_triagem` FOREIGN KEY (`fk_triagem`) REFERENCES `tbl_triagem` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_resposta_protocolo`
--

LOCK TABLES `tbl_resposta_protocolo` WRITE;
/*!40000 ALTER TABLE `tbl_resposta_protocolo` DISABLE KEYS */;
INSERT INTO `tbl_resposta_protocolo` VALUES (23,19,1,'\"Dor retroesternal ao subir escadas, sete de 6\\/10, melhora com repouso.\"','2026-10-14 15:49:27'),(24,19,2,'\"Ha 3 dias\"','2026-10-14 15:49:27'),(25,19,3,'\"Intermitentes\"','2026-10-14 15:49:27'),(26,19,4,'\"Nao fez tratamento ainda.\"','2026-10-14 15:49:27'),(27,19,5,'\"Pai com historico de infarto.\"','2026-10-14 15:49:27'),(28,20,6,'\"4 dias\"','2026-10-14 15:49:27'),(29,20,7,'\"Sim\"','2026-10-14 15:49:27'),(30,20,8,'\"Sim\"','2026-10-14 15:49:27'),(31,20,9,'\"Sim\"','2026-10-14 15:49:27'),(32,20,10,'\"Nao\"','2026-10-14 15:49:27'),(33,20,11,'\"39.2\"','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_resposta_protocolo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_role_permission`
--

DROP TABLE IF EXISTS `tbl_role_permission`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_role_permission` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_role` bigint(20) unsigned NOT NULL,
  `fk_permission` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permission` (`fk_role`,`fk_permission`),
  KEY `idx_role_permission_permission` (`fk_permission`),
  CONSTRAINT `fk_role_permission_permission` FOREIGN KEY (`fk_permission`) REFERENCES `tbl_permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_role_permission_role` FOREIGN KEY (`fk_role`) REFERENCES `tbl_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_role_permission`
--

LOCK TABLES `tbl_role_permission` WRITE;
/*!40000 ALTER TABLE `tbl_role_permission` DISABLE KEYS */;
INSERT INTO `tbl_role_permission` VALUES (1,1,1),(2,1,2),(3,1,3),(4,1,4),(5,1,5),(6,1,6),(7,1,7),(8,1,8),(9,1,9),(10,1,10),(11,1,11),(12,1,12),(13,1,13),(14,1,14),(15,1,15),(16,1,16),(17,1,17),(18,1,18),(19,1,19),(20,1,20),(21,1,21),(22,1,22),(23,1,23),(24,1,24),(25,1,25),(26,1,26),(27,1,27),(28,1,28),(29,1,29),(30,1,30),(31,1,31),(32,1,32),(64,1,33),(65,1,34),(33,2,1),(34,2,2),(35,2,11),(36,2,12),(37,2,13),(38,2,14),(39,2,15),(40,2,16),(41,2,17),(42,2,18),(43,2,19),(68,2,33),(69,2,34),(44,3,1),(45,3,2),(46,3,20),(47,3,21),(48,3,22),(49,3,23),(66,3,33),(67,3,34),(50,4,1),(51,4,2),(52,4,24),(53,4,25),(54,4,26),(55,4,32),(70,4,33),(71,4,34),(56,5,1),(57,5,2),(58,5,11),(59,5,27),(60,5,28),(61,5,29),(62,5,30),(63,5,31),(72,1,35),(73,5,36);
/*!40000 ALTER TABLE `tbl_role_permission` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_roles`
--

DROP TABLE IF EXISTS `tbl_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_nome` (`nome`),
  UNIQUE KEY `uq_role_slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_roles`
--

LOCK TABLES `tbl_roles` WRITE;
/*!40000 ALTER TABLE `tbl_roles` DISABLE KEYS */;
INSERT INTO `tbl_roles` VALUES (1,'Administrador','admin'),(2,'Médico','medico'),(3,'Enfermeiro','enfermeiro'),(4,'Técnico de Enfermagem','tecnico_enfermagem'),(5,'Paciente','paciente');
/*!40000 ALTER TABLE `tbl_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_telefone`
--

DROP TABLE IF EXISTS `tbl_telefone`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_telefone` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `telefone` varchar(20) NOT NULL,
  `tipo` enum('principal','secundario','comercial') NOT NULL DEFAULT 'principal',
  PRIMARY KEY (`id`),
  KEY `idx_telefone_usuario` (`fk_usuario`),
  CONSTRAINT `fk_telefone_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_telefone`
--

LOCK TABLES `tbl_telefone` WRITE;
/*!40000 ALTER TABLE `tbl_telefone` DISABLE KEYS */;
INSERT INTO `tbl_telefone` VALUES (1,1,'6190000001','principal'),(2,2,'6190000002','principal'),(3,3,'6190000003','principal'),(4,4,'6190000004','principal'),(5,5,'6190000005','principal'),(6,6,'6190000006','principal'),(7,7,'6190000007','principal'),(8,8,'6190000008','principal'),(9,9,'6190000009','principal'),(10,10,'6190000010','principal'),(11,11,'6190000011','principal'),(12,12,'6190000012','principal'),(13,13,'6190000013','principal'),(14,14,'6190000014','principal'),(15,15,'6190000015','principal'),(16,16,'6190000016','principal'),(17,17,'6190000017','principal'),(18,18,'6190000018','principal'),(19,19,'6190000019','principal'),(20,20,'6190000020','principal'),(21,31,'6190000031','principal'),(22,32,'6190000032','principal'),(32,33,'61999990001','principal');
/*!40000 ALTER TABLE `tbl_telefone` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_triagem`
--

DROP TABLE IF EXISTS `tbl_triagem`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_triagem` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_paciente` bigint(20) unsigned NOT NULL,
  `fk_profissional` bigint(20) unsigned DEFAULT NULL,
  `fk_analisado_por` bigint(20) unsigned DEFAULT NULL,
  `nome_solicitante` varchar(150) NOT NULL,
  `status` enum('pendente','aprovada','recusada') NOT NULL DEFAULT 'pendente',
  `classificacao` enum('vermelho','amarelo','verde') DEFAULT NULL,
  `ia_classificacao` enum('vermelho','amarelo','verde') DEFAULT NULL,
  `ia_justificativa_classificacao` varchar(500) DEFAULT NULL,
  `ia_perfil_recomendado` enum('medico','enfermeiro') DEFAULT NULL,
  `ia_justificativa_perfil` varchar(500) DEFAULT NULL,
  `ia_profissional_sugerido` bigint(20) unsigned DEFAULT NULL,
  `ia_modelo` varchar(100) DEFAULT NULL,
  `ia_analisada_em` datetime DEFAULT NULL,
  `queixa_principal` text DEFAULT NULL,
  `descricao_sintomas` text DEFAULT NULL,
  `data_inicio_sintomas` date DEFAULT NULL,
  `justificativa_reprovacao` text DEFAULT NULL,
  `justificativa_encaminhamento` varchar(500) DEFAULT NULL,
  `data_aprovacao` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_triagem_paciente_status` (`fk_paciente`,`status`),
  KEY `idx_triagem_profissional` (`fk_profissional`),
  KEY `idx_triagem_analisado` (`fk_analisado_por`),
  KEY `idx_triagem_ia_profissional` (`ia_profissional_sugerido`),
  CONSTRAINT `fk_triagem_analisado` FOREIGN KEY (`fk_analisado_por`) REFERENCES `tbl_profissional` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_triagem_paciente` FOREIGN KEY (`fk_paciente`) REFERENCES `tbl_paciente` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_triagem_profissional` FOREIGN KEY (`fk_profissional`) REFERENCES `tbl_profissional` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_triagem`
--

LOCK TABLES `tbl_triagem` WRITE;
/*!40000 ALTER TABLE `tbl_triagem` DISABLE KEYS */;
INSERT INTO `tbl_triagem` VALUES (19,1,NULL,NULL,'Roberto Alencar','pendente',NULL,'amarelo','Dor toracica aos esforcos com fatores de risco cardiovascular.','medico','Caso com suspeita de origem cardiaca; encaminhar a medico.',1,'openai/gpt-oss-20b','2026-10-14 08:10:00','Dor no peito ao esforco','Dor retroesternal ao subir escadas, melhora com repouso. Iniciou ha 3 dias.','2026-10-11',NULL,NULL,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(20,2,16,10,'Sandra Regina','pendente','amarelo','amarelo','Quadro febril com mialgia e cefaleia.','medico','Suspeita de arbovirose; avaliacao medica.',16,'openai/gpt-oss-20b','2026-10-13 15:20:00','Febre e dor no corpo','Febre ha 4 dias, dor muscular, cefaleia e manchas na pele.','2026-10-10',NULL,'Encaminhada ao Dr. Richard para investigacao de dengue.',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(21,3,1,11,'Thiago Ramos','pendente','verde','verde','Lesao dermatologica sem sinais de gravidade.','medico','Avaliacao dermatologica eletiva.',3,'openai/gpt-oss-20b','2026-10-12 09:00:00','Mancha na perna','Mancha pigmentada em perna direita, indolor, ha 2 meses.','2026-08-15',NULL,'Encaminhada para avaliacao dermatologica.',NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(22,4,2,12,'Vanessa Lopes','aprovada','amarelo','amarelo','Crise asmatica moderada.','medico','Necessita avaliacao medica.',2,'openai/gpt-oss-20b','2026-10-11 10:00:00','Falta de ar','Crises de tosse e chiado ha 2 dias.','2026-10-12',NULL,NULL,'2026-10-11 11:00:00','2026-10-14 15:49:27','2026-10-14 15:49:27'),(23,5,3,13,'William Pires','recusada','verde','verde','Queixa leve, sem urgencia.','enfermeiro','Pode ser acompanhado pela enfermagem.',5,'openai/gpt-oss-20b','2026-10-09 08:30:00','Orientacao sobre dieta','Busca orientacao nutricional.','2026-10-13','Paciente foi orientado a procurar a unidade basica.',NULL,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27'),(24,6,NULL,NULL,'Paciente Teste WhatsApp','pendente',NULL,'verde','Quadro de baixa complexidade.','medico','Avaliacao clinica geral.',16,'openai/gpt-oss-20b','2026-10-14 07:45:00','Dor de garganta','Dor de garganta ha 2 dias, sem febre.','2026-10-12',NULL,NULL,NULL,'2026-10-14 15:49:27','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_triagem` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_user_role`
--

DROP TABLE IF EXISTS `tbl_user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_user_role` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `fk_role` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_role` (`fk_usuario`,`fk_role`),
  KEY `idx_user_role_role` (`fk_role`),
  CONSTRAINT `fk_user_role_role` FOREIGN KEY (`fk_role`) REFERENCES `tbl_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_user_role_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_user_role`
--

LOCK TABLES `tbl_user_role` WRITE;
/*!40000 ALTER TABLE `tbl_user_role` DISABLE KEYS */;
INSERT INTO `tbl_user_role` VALUES (1,1,2),(2,2,2),(3,3,2),(4,4,2),(5,5,3),(6,6,3),(7,7,3),(8,8,3),(9,9,4),(10,10,4),(11,11,4),(12,12,4),(13,13,4),(14,14,4),(15,15,4),(16,16,5),(17,17,5),(18,18,5),(19,19,5),(20,20,5),(21,31,1),(22,32,2),(23,33,5),(24,34,5);
/*!40000 ALTER TABLE `tbl_user_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_uso_ia`
--

DROP TABLE IF EXISTS `tbl_uso_ia`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_uso_ia` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `fk_usuario` bigint(20) unsigned NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `quantidade_consultas` int(10) unsigned NOT NULL DEFAULT 1,
  `data_consulta` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_uso_ia_usuario_data` (`fk_usuario`,`data_consulta`),
  CONSTRAINT `fk_uso_ia_usuario` FOREIGN KEY (`fk_usuario`) REFERENCES `tbl_usuario` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `chk_uso_ia_quantidade` CHECK (`quantidade_consultas` > 0)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_uso_ia`
--

LOCK TABLES `tbl_uso_ia` WRITE;
/*!40000 ALTER TABLE `tbl_uso_ia` DISABLE KEYS */;
INSERT INTO `tbl_uso_ia` VALUES (1,32,'openai/gpt-oss-20b',7,'2026-10-14','2026-10-14 15:49:27'),(2,32,'openai/gpt-oss-20b',4,'2026-10-13','2026-10-14 15:49:27'),(3,1,'openai/gpt-oss-20b',3,'2026-10-14','2026-10-14 15:49:27');
/*!40000 ALTER TABLE `tbl_uso_ia` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tbl_usuario`
--

DROP TABLE IF EXISTS `tbl_usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tbl_usuario` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `status` enum('ativo','inativo','bloqueado') NOT NULL DEFAULT 'ativo',
  `primeiro_acesso` tinyint(1) NOT NULL DEFAULT 1,
  `ultima_troca_senha` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_email` (`email`),
  KEY `idx_usuario_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tbl_usuario`
--

LOCK TABLES `tbl_usuario` WRITE;
/*!40000 ALTER TABLE `tbl_usuario` DISABLE KEYS */;
INSERT INTO `tbl_usuario` VALUES (1,'Dr. Arnaldo Silva','arnaldo.silva@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(2,'Dra. Beatriz Santos','beatriz.santos@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(3,'Dr. Carlos Oliveira','carlos.oliveira@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(4,'Dra. Daniela Costa','daniela.costa@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(5,'Dr. Eduardo Souza','eduardo.souza@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(6,'Dra. Fernanda Lima','fernanda.lima@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(7,'Dr. Gabriel Almeida','gabriel.almeida@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(8,'Dra. Helena Ribeiro','helena.ribeiro@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(9,'Dr. Igor Carvalho','igor.carvalho@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(10,'Dra. Juliana Martins','juliana.martins@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(11,'Dr. Lucas Ferreira','lucas.ferreira@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(12,'Dra. Mariana Rocha','mariana.rocha@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(13,'Dr. Nicolas Gomes','nicolas.gomes@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(14,'Dra. Olivia Mendes','olivia.mendes@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(15,'Dr. Pedro Rezende','pedro.rezende@elomed.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(16,'Roberto Alencar','roberto.alencar@gmail.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(17,'Sandra Regina','sandra.regina@gmail.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(18,'Thiago Ramos','thiago.ramos@gmail.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(19,'Vanessa Lopes','vanessa.lopes@gmail.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(20,'William Pires','william.pires@gmail.com','$2y$10$wJWpcLxevw61MGzIvt0GV.7gtb805K1I/cHwWgfPZHE0SZS0Tf5Ey','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(31,'Administrador','admin@gmail.com','$2y$10$1MScr.9f9LacV1.0vuubpuTssN.1QpP93jWe8QtRUqEg4/t0jWnTG','ativo',0,NULL,'2026-09-26 17:15:20','2026-09-26 17:15:20'),(32,'Richard Nascimento','richardnascimentosousa16@gmail.com','$2y$10$3QkaFPilEro0/oeYJya3G.E3HXQRcEy594Gs7eoHuRms5hAYz2QVq','ativo',0,NULL,'2026-09-26 17:17:42','2026-10-14 15:51:10'),(33,'Paciente Teste WhatsApp','teste.whatsapp@elomed.com','$2y$10$EVK0PO7PumjZG91oBRRaIeZkmMPJNkGdJBj45n1lgwZtqbUbT30vS','ativo',0,NULL,'2026-10-08 11:54:34','2026-10-08 13:59:55'),(34,'Richard rich','richarddonascimentosousa16@gmail.com','$2y$10$CXukcexqNNUnlQSr9kzyv.yIdQhMLOThF/GAhMKLBGJjRK4U7jZ8i','ativo',0,NULL,'2026-10-14 15:36:42','2026-10-14 15:36:42');
/*!40000 ALTER TABLE `tbl_usuario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'elomed'
--

--
-- Dumping routines for database 'elomed'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-16  8:50:26
