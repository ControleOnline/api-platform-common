<?php

declare(strict_types=1);

namespace DoctrineMigrations\Common;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260714190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Baseline schema for common module from s.controleonline.com";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('CREATE TABLE IF NOT EXISTS `address` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) DEFAULT NULL,
  `number` int(11) DEFAULT NULL,
  `street_id` int(11) NOT NULL,
  `nickname` varchar(50) CHARACTER SET utf8 NOT NULL,
  `complement` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `locator` varchar(250) CHARACTER SET utf8 DEFAULT NULL,
  `opening_time` time DEFAULT NULL,
  `closing_time` time DEFAULT NULL,
  `search_for` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_3` (`people_id`,`number`,`street_id`,`complement`) USING BTREE,
  KEY `user_id` (`people_id`),
  KEY `cep_id` (`street_id`),
  KEY `user_id_2` (`people_id`,`nickname`) USING BTREE,
  CONSTRAINT `address_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `address_ibfk_2` FOREIGN KEY (`street_id`) REFERENCES `street` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12840 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8 DEFAULT NULL,
  `color` varchar(50) CHARACTER SET utf8 DEFAULT \'$primary\',
  `context` varchar(100) CHARACTER SET utf8 NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `category_ibfk_2` (`parent_id`),
  CONSTRAINT `category_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `category_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=370 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `category_file` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_id` (`category_id`,`file_id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `category_file_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `category_file_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `cep` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cep` int(8) unsigned zerofill NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `CEP` (`cep`)
) ENGINE=InnoDB AUTO_INCREMENT=6389 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `city` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cod_ibge` int(11) DEFAULT NULL,
  `city` varchar(80) CHARACTER SET utf8 NOT NULL,
  `state_id` int(11) NOT NULL,
  `seo` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `city` (`city`,`state_id`),
  UNIQUE KEY `cod_ibge` (`cod_ibge`),
  KEY `state_id` (`state_id`),
  KEY `seo` (`seo`),
  CONSTRAINT `city_ibfk_1` FOREIGN KEY (`state_id`) REFERENCES `state` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55253 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `cms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(80) CHARACTER SET utf8 DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `cms_type` enum(\'page\',\'article\') CHARACTER SET utf8 NOT NULL,
  `people_domain_id` int(11) NOT NULL,
  `class` varchar(500) CHARACTER SET utf8 DEFAULT NULL,
  `style` text CHARACTER SET utf8,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `people_domain_id` (`people_domain_id`),
  KEY `cms_type_id` (`cms_type`) USING BTREE,
  CONSTRAINT `cms_ibfk_1` FOREIGN KEY (`people_domain_id`) REFERENCES `people_domain` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `cms_section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cms_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `class` varchar(500) CHARACTER SET utf8 DEFAULT NULL,
  `style` text CHARACTER SET utf8,
  `order` int(11) NOT NULL,
  `creation_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alter_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`section_id`),
  KEY `cms_id` (`cms_id`),
  CONSTRAINT `cms_section_ibfk_1` FOREIGN KEY (`cms_id`) REFERENCES `cms` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms_section_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `cms_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `cms_section_component` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_id` int(11) NOT NULL,
  `component_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `section_id` (`section_id`),
  KEY `component_id` (`component_id`),
  CONSTRAINT `cms_section_component_ibfk_1` FOREIGN KEY (`section_id`) REFERENCES `cms_section` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cms_section_component_ibfk_2` FOREIGN KEY (`component_id`) REFERENCES `module_component` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visibility` enum(\'public\',\'private\') CHARACTER SET utf8 NOT NULL DEFAULT \'private\',
  `people_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `config_key` varchar(50) CHARACTER SET utf8 NOT NULL,
  `config_value` text CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `people_id` (`people_id`,`config_key`,`module_id`) USING BTREE,
  KEY `module_id` (`module_id`),
  CONSTRAINT `config_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `config_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=515 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `connections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `status_id` int(11) NOT NULL,
  `type` enum(\'relationship\',\'support\',\'order\') CHARACTER SET utf8 DEFAULT NULL,
  `name` varchar(50) CHARACTER SET utf8 NOT NULL,
  `phone_id` int(11) DEFAULT NULL,
  `channel` enum(\'whatsapp\') CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone_id` (`phone_id`,`channel`),
  KEY `status_id` (`status_id`),
  KEY `people_id` (`people_id`),
  CONSTRAINT `connections_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `connections_ibfk_2` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `connections_ibfk_3` FOREIGN KEY (`phone_id`) REFERENCES `phone` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `countryCode` char(3) CHARACTER SET utf8 NOT NULL,
  `countryName` varchar(45) CHARACTER SET utf8 NOT NULL,
  `currencyCode` char(3) CHARACTER SET utf8 DEFAULT NULL,
  `population` int(20) DEFAULT NULL,
  `fipsCode` char(2) CHARACTER SET utf8 DEFAULT NULL,
  `isoNumeric` char(4) CHARACTER SET utf8 DEFAULT NULL,
  `north` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `south` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `east` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `west` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `capital` varchar(30) CHARACTER SET utf8 DEFAULT NULL,
  `continentName` varchar(15) CHARACTER SET utf8 DEFAULT NULL,
  `continent` char(2) CHARACTER SET utf8 DEFAULT NULL,
  `areaInSqKm` varchar(20) CHARACTER SET utf8 DEFAULT NULL,
  `isoAlpha3` char(3) CHARACTER SET utf8 DEFAULT NULL,
  `geonameId` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `countryCode` (`countryCode`)
) ENGINE=InnoDB AUTO_INCREMENT=252 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alias` varchar(50) CHARACTER SET utf8 DEFAULT \'Caixa\',
  `device` varchar(50) CHARACTER SET utf8 NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device` (`device`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=393 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `device_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `device_type` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `people_id` int(11) NOT NULL,
  `configs` text CHARACTER SET utf8 NOT NULL,
  `alter_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`,`people_id`,`device_type`) USING BTREE,
  KEY `people_id` (`people_id`),
  CONSTRAINT `device_configs_ibfk_1` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `device_configs_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=481 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `district` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `district` varchar(255) CHARACTER SET utf8 NOT NULL,
  `city_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `city_id` (`city_id`),
  CONSTRAINT `district_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4870 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `register_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type` enum(\'imposto\',\'declaracao\') CHARACTER SET utf8 NOT NULL,
  `name` enum(\'das\',\'pis\',\'confins\') CHARACTER SET utf8 NOT NULL,
  `date_period` date NOT NULL,
  `status_id` int(11) NOT NULL,
  `file_name_guide` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `file_name_receipt` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `people_id` int(11) NOT NULL,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  KEY `people_id` (`people_id`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `company_ibfk` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `docs_ibfk_1` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `people_ibfk` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `extra_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `extra_fields_id` int(11) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `entity_name` varchar(60) CHARACTER SET utf8 NOT NULL,
  `data_value` varchar(255) CHARACTER SET utf8 NOT NULL,
  `source` varchar(64) CHARACTER SET utf8 DEFAULT NULL,
  `dateTime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `extra_fields_id` (`extra_fields_id`,`entity_id`,`entity_name`,`data_value`) USING BTREE,
  KEY `people_id` (`entity_id`),
  KEY `particulars_type_id` (`extra_fields_id`),
  CONSTRAINT `extra_data_ibfk_1` FOREIGN KEY (`extra_fields_id`) REFERENCES `extra_fields` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16954 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `extra_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `field_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `field_type` varchar(64) CHARACTER SET utf8 NOT NULL,
  `context` varchar(64) CHARACTER SET utf8 NOT NULL,
  `required` tinyint(1) NOT NULL,
  `field_configs` longtext CHARACTER SET utf8,
  PRIMARY KEY (`id`),
  UNIQUE KEY `field_name` (`field_name`,`field_type`,`context`)
) ENGINE=InnoDB AUTO_INCREMENT=178 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `people_id` int(11) NOT NULL,
  `content` longblob NOT NULL,
  `context` varchar(50) CHARACTER SET utf8 NOT NULL,
  `file_type` varchar(50) CHARACTER SET utf8 NOT NULL,
  `extension` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `people_id` (`people_id`),
  CONSTRAINT `files_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8398 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `hardware` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hardware` varchar(50) CHARACTER SET utf8 NOT NULL,
  `hardware_type` varchar(50) CHARACTER SET utf8 DEFAULT \'display\',
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `hardware_ibfk_1` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `imports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `import_type` varchar(50) CHARACTER SET utf8 NOT NULL,
  `status_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `people_id` int(11) NOT NULL,
  `file_format` enum(\'csv\',\'xml\') CHARACTER SET utf8 NOT NULL DEFAULT \'csv\',
  `feedback` longtext CHARACTER SET utf8,
  `upload_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `file_id` (`file_id`),
  KEY `people_id` (`people_id`),
  KEY `status_id` (`status_id`),
  CONSTRAINT `imports_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`),
  CONSTRAINT `imports_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`),
  CONSTRAINT `imports_ibfk_3` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `labels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `carrier_id` int(11) NOT NULL,
  `shipment_id` varchar(255) CHARACTER SET utf8 NOT NULL,
  `order_id` int(11) NOT NULL,
  `cod_barra` varchar(255) CHARACTER SET utf8 NOT NULL,
  `last_mile` varchar(255) CHARACTER SET utf8 NOT NULL,
  `unidade_destino` varchar(255) CHARACTER SET utf8 NOT NULL,
  `posicao` varchar(255) CHARACTER SET utf8 NOT NULL,
  `prioridade` int(11) NOT NULL,
  `seq_volume` int(11) NOT NULL,
  `rota` varchar(255) CHARACTER SET utf8 NOT NULL,
  `rua` varchar(255) CHARACTER SET utf8 NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `people_id` (`people_id`),
  KEY `carrier_id` (`carrier_id`),
  KEY `labels_ibfk_3` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `language` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language` varchar(10) CHARACTER SET utf8 NOT NULL,
  `locked` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language` (`language`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `language_country` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `language_id` int(11) NOT NULL,
  `country_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_id` (`language_id`,`country_id`),
  KEY `country_id` (`country_id`),
  CONSTRAINT `language_country_ibfk_1` FOREIGN KEY (`language_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `language_country_ibfk_2` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(64) CHARACTER SET utf8 NOT NULL,
  `row` int(11) DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8 NOT NULL,
  `class` varchar(255) CHARACTER SET utf8 NOT NULL,
  `object` longtext CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `log_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1240278 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `measure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `measure` varchar(50) CHARACTER SET utf8 NOT NULL,
  `measure_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `measure` (`measure`),
  KEY `measuretype_id` (`measure_type_id`),
  CONSTRAINT `measure_ibfk_1` FOREIGN KEY (`measure_type_id`) REFERENCES `measure_type` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `measure_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `measure_type` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `measure_type` (`measure_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `menu` varchar(50) CHARACTER SET utf8 NOT NULL,
  `route_id` int(11) NOT NULL,
  `menu_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `app_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'MANAGER\',
  `menu_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'home\',
  `route_params` longtext COLLATE utf8mb4_unicode_ci,
  `sort_order` int(11) NOT NULL DEFAULT \'0\',
  `enabled` tinyint(1) NOT NULL DEFAULT \'1\',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `menu_ibfk_3` (`route_id`),
  KEY `menu_app_type_idx` (`app_type`),
  CONSTRAINT `menu_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `menu_ibfk_3` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `menu_link_type` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `link_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_link_type_unique` (`menu_id`,`link_type`),
  KEY `menu_link_type_link_type_idx` (`link_type`),
  KEY `IDX_486AA71ACCD7E912` (`menu_id`),
  CONSTRAINT `FK_486AA71ACCD7E912` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=373 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `menu_role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `menu_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `menu_id` (`menu_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `menu_role_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menu` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `menu_role_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `role` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `body` longtext NOT NULL,
  `headers` longtext NOT NULL,
  `queue_name` varchar(190) NOT NULL,
  `created_at` datetime NOT NULL,
  `available_at` datetime NOT NULL,
  `delivered_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750` (`queue_name`,`available_at`,`delivered_at`,`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10105 DEFAULT CHARSET=utf8mb4');
        $this->addSql('CREATE TABLE IF NOT EXISTS `model` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `model` varchar(255) CHARACTER SET utf8 NOT NULL,
  `context` set(\'proposal\',\'contract\',\'email\',\'menu\') CHARACTER SET utf8 NOT NULL,
  `people_id` int(11) NOT NULL,
  `signer_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `model` (`model`,`context`,`people_id`) USING BTREE,
  KEY `category_id` (`category_id`),
  KEY `people_id` (`people_id`),
  KEY `signer_id` (`signer_id`),
  KEY `file_id` (`file_id`),
  CONSTRAINT `model_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `model_ibfk_3` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `model_ibfk_4` FOREIGN KEY (`signer_id`) REFERENCES `people` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `model_ibfk_5` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8 NOT NULL,
  `color` varchar(50) CHARACTER SET utf8 NOT NULL DEFAULT \'$primary\',
  `icon` varchar(50) CHARACTER SET utf8 NOT NULL,
  `description` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UX_MODULE_NAME` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `module_component` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `component` varchar(50) CHARACTER SET utf8 NOT NULL,
  `props` longtext CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `module_component_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `module_product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_id` (`product_id`,`module_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `module_product_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `module_product_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `notification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `notification` text CHARACTER SET utf8 NOT NULL,
  `route` varchar(50) CHARACTER SET utf8 NOT NULL,
  `route_id` int(11) NOT NULL,
  `notification_read` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `people_id` (`people_id`),
  CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `oauth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_type` enum(\'mercado_livre\') CHARACTER SET utf8 NOT NULL,
  `user_id` int(11) NOT NULL,
  `refresh_token` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `access_token` varchar(255) CHARACTER SET utf8 NOT NULL,
  `company_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `app_type` (`app_type`,`user_id`),
  KEY `company_id` (`company_id`),
  CONSTRAINT `oauth_ibfk_2` FOREIGN KEY (`company_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `operation_patterns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` set(\'sale\',\'purchase\',\'transfer\',\'loss\',\'quote\') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` set(\'open\',\'closed\',\'canceled\',\'pending\') COLLATE utf8mb4_unicode_ci NOT NULL,
  `previous_status` set(\'open\',\'closed\',\'canceled\',\'pending\') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `available_op` enum(\'+\',\'-\') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sales_op` enum(\'+\',\'-\') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchases_op` enum(\'+\',\'-\') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checked` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `note` varchar(128) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type` (`type`,`status`,`previous_status`,`available_op`,`sales_op`,`purchases_op`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `rating` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rating` enum(\'1\',\'2\',\'3\',\'4\',\'5\') CHARACTER SET utf8 NOT NULL DEFAULT \'5\',
  `rating_type` enum(\'Confidence\',\'Speed\',\'Quality\',\'Attendance\') CHARACTER SET utf8 NOT NULL,
  `order_rated` int(11) DEFAULT NULL,
  `people_rated` int(11) NOT NULL,
  `people_evaluator` int(11) NOT NULL,
  `rating_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `note` text CHARACTER SET utf8,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_rated` (`order_rated`,`rating_type`,`people_evaluator`,`people_rated`) USING BTREE,
  KEY `people_evaluator` (`people_evaluator`),
  KEY `rating` (`rating`),
  KEY `rating_type` (`rating_type`),
  KEY `people_rated` (`people_rated`),
  CONSTRAINT `rating_ibfk_1` FOREIGN KEY (`people_evaluator`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rating_ibfk_2` FOREIGN KEY (`order_rated`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `rating_ibfk_3` FOREIGN KEY (`people_rated`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `retrieve` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `retrieve_number` int(11) NOT NULL,
  `retrieve_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `retrieve_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `role` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role` varchar(64) CHARACTER SET utf8 NOT NULL,
  `people_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `people_id` (`people_id`),
  CONSTRAINT `role_ibfk_1` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `route` varchar(50) CHARACTER SET utf8 NOT NULL,
  `color` varchar(50) CHARACTER SET utf8 NOT NULL,
  `icon` varchar(50) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `route` (`route`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `routes_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=117 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `seo_url` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(255) CHARACTER SET utf8 NOT NULL,
  `city_origin` int(11) NOT NULL,
  `city_destination` int(11) NOT NULL,
  `weight` float NOT NULL DEFAULT \'1\',
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `url` (`url`) USING BTREE,
  UNIQUE KEY `city_origin_2` (`city_origin`,`city_destination`,`weight`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `city_origin` (`city_origin`),
  KEY `city_destination` (`city_destination`),
  CONSTRAINT `seo_url_ibfk_1` FOREIGN KEY (`city_origin`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `seo_url_ibfk_2` FOREIGN KEY (`city_destination`) REFERENCES `city` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `seo_url_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `sessions` (
  `id` char(32) CHARACTER SET utf8 NOT NULL DEFAULT \'\',
  `name` varchar(255) CHARACTER SET utf8 NOT NULL,
  `modified` int(11) DEFAULT NULL,
  `lifetime` int(11) DEFAULT NULL,
  `data` text CHARACTER SET utf8,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `spool` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `register_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  KEY `file_id` (`file_id`),
  KEY `status_id` (`status_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `spool_ibfk_2` FOREIGN KEY (`device_id`) REFERENCES `device` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `spool_ibfk_3` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `spool_ibfk_4` FOREIGN KEY (`status_id`) REFERENCES `status` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `spool_ibfk_5` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=991 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cod_ibge` int(11) DEFAULT NULL,
  `state` varchar(50) CHARACTER SET utf8 NOT NULL,
  `country_id` int(11) NOT NULL,
  `UF` varchar(2) CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UF` (`UF`),
  UNIQUE KEY `cod_ibge` (`cod_ibge`),
  KEY `country_id` (`country_id`),
  CONSTRAINT `state_ibfk_1` FOREIGN KEY (`country_id`) REFERENCES `country` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `status` varchar(64) CHARACTER SET utf8 NOT NULL,
  `context` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'order\',
  `real_status` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT \'open\',
  `visibility` enum(\'public\',\'private\') CHARACTER SET utf8 NOT NULL DEFAULT \'public\',
  `notify` tinyint(1) NOT NULL,
  `system` tinyint(1) NOT NULL,
  `color` varchar(7) CHARACTER SET utf8 NOT NULL DEFAULT \'#000000\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `status` (`status`,`context`) USING BTREE,
  KEY `real_status` (`real_status`)
) ENGINE=InnoDB AUTO_INCREMENT=855 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `street` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `street` varchar(255) CHARACTER SET utf8 NOT NULL,
  `cep_id` int(10) NOT NULL,
  `district_id` int(11) NOT NULL,
  `confirmed` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `street_2` (`street`,`district_id`),
  KEY `country_id` (`district_id`),
  KEY `cep` (`cep_id`) USING BTREE,
  KEY `street` (`street`) USING BTREE,
  CONSTRAINT `street_ibfk_1` FOREIGN KEY (`district_id`) REFERENCES `district` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `street_ibfk_2` FOREIGN KEY (`cep_id`) REFERENCES `cep` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7669 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `theme` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `theme` varchar(64) CHARACTER SET utf8 NOT NULL DEFAULT \'Default\',
  `background` int(11) NOT NULL,
  `colors` longtext CHARACTER SET utf8 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `theme` (`theme`),
  KEY `background` (`background`),
  CONSTRAINT `theme_ibfk_1` FOREIGN KEY (`background`) REFERENCES `files` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `timezones` (
  `id` smallint(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_timezones_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE TABLE IF NOT EXISTS `translate` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `people_id` int(11) NOT NULL,
  `store` varchar(64) CHARACTER SET utf8 NOT NULL,
  `type` varchar(64) CHARACTER SET utf8 NOT NULL,
  `lang_id` int(11) NOT NULL,
  `translate_key` varchar(64) CHARACTER SET utf8 NOT NULL,
  `translate` varchar(255) CHARACTER SET utf8 NOT NULL,
  `revised` tinyint(1) unsigned NOT NULL DEFAULT \'0\',
  `creationDate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `lastUpdate` datetime NOT NULL DEFAULT \'0000-00-00 00:00:00\' ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `language_id` (`lang_id`,`translate_key`,`people_id`,`store`,`type`) USING BTREE,
  KEY `translate_key` (`translate_key`),
  KEY `people_id` (`people_id`),
  CONSTRAINT `translate_ibfk_1` FOREIGN KEY (`lang_id`) REFERENCES `language` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `translate_ibfk_2` FOREIGN KEY (`people_id`) REFERENCES `people` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4858 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        $this->addSql('CREATE OR REPLACE VIEW `vw_devices` AS select `D`.`id` AS `id`,`D`.`alias` AS `alias`,`D`.`device` AS `device`,`DC`.`people_id` AS `people_id`,`DC`.`configs` AS `configs`,`DC`.`alter_date` AS `alter_date` from (`device` `D` left join `device_configs` `DC` on((`D`.`id` = `DC`.`device_id`)))');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP VIEW IF EXISTS `vw_devices`');
        $this->addSql('DROP TABLE IF EXISTS `translate`');
        $this->addSql('DROP TABLE IF EXISTS `timezones`');
        $this->addSql('DROP TABLE IF EXISTS `theme`');
        $this->addSql('DROP TABLE IF EXISTS `street`');
        $this->addSql('DROP TABLE IF EXISTS `status`');
        $this->addSql('DROP TABLE IF EXISTS `state`');
        $this->addSql('DROP TABLE IF EXISTS `spool`');
        $this->addSql('DROP TABLE IF EXISTS `sessions`');
        $this->addSql('DROP TABLE IF EXISTS `seo_url`');
        $this->addSql('DROP TABLE IF EXISTS `routes`');
        $this->addSql('DROP TABLE IF EXISTS `role`');
        $this->addSql('DROP TABLE IF EXISTS `retrieve`');
        $this->addSql('DROP TABLE IF EXISTS `rating`');
        $this->addSql('DROP TABLE IF EXISTS `operation_patterns`');
        $this->addSql('DROP TABLE IF EXISTS `oauth`');
        $this->addSql('DROP TABLE IF EXISTS `notification`');
        $this->addSql('DROP TABLE IF EXISTS `module_product`');
        $this->addSql('DROP TABLE IF EXISTS `module_component`');
        $this->addSql('DROP TABLE IF EXISTS `module`');
        $this->addSql('DROP TABLE IF EXISTS `model`');
        $this->addSql('DROP TABLE IF EXISTS `messenger_messages`');
        $this->addSql('DROP TABLE IF EXISTS `menu_role`');
        $this->addSql('DROP TABLE IF EXISTS `menu_link_type`');
        $this->addSql('DROP TABLE IF EXISTS `menu`');
        $this->addSql('DROP TABLE IF EXISTS `measure_type`');
        $this->addSql('DROP TABLE IF EXISTS `measure`');
        $this->addSql('DROP TABLE IF EXISTS `log`');
        $this->addSql('DROP TABLE IF EXISTS `language_country`');
        $this->addSql('DROP TABLE IF EXISTS `language`');
        $this->addSql('DROP TABLE IF EXISTS `labels`');
        $this->addSql('DROP TABLE IF EXISTS `imports`');
        $this->addSql('DROP TABLE IF EXISTS `hardware`');
        $this->addSql('DROP TABLE IF EXISTS `files`');
        $this->addSql('DROP TABLE IF EXISTS `extra_fields`');
        $this->addSql('DROP TABLE IF EXISTS `extra_data`');
        $this->addSql('DROP TABLE IF EXISTS `docs`');
        $this->addSql('DROP TABLE IF EXISTS `district`');
        $this->addSql('DROP TABLE IF EXISTS `device_configs`');
        $this->addSql('DROP TABLE IF EXISTS `device`');
        $this->addSql('DROP TABLE IF EXISTS `country`');
        $this->addSql('DROP TABLE IF EXISTS `connections`');
        $this->addSql('DROP TABLE IF EXISTS `config`');
        $this->addSql('DROP TABLE IF EXISTS `cms_section_component`');
        $this->addSql('DROP TABLE IF EXISTS `cms_section`');
        $this->addSql('DROP TABLE IF EXISTS `cms`');
        $this->addSql('DROP TABLE IF EXISTS `city`');
        $this->addSql('DROP TABLE IF EXISTS `cep`');
        $this->addSql('DROP TABLE IF EXISTS `category_file`');
        $this->addSql('DROP TABLE IF EXISTS `category`');
        $this->addSql('DROP TABLE IF EXISTS `address`');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
