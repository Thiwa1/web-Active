-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

-- -----------------------------------------------------
-- Schema tiptromr_vacancies
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema tiptromr_vacancies
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `tiptromr_vacancies` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci ;
USE `tiptromr_vacancies` ;

-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`employer_profile`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`employer_profile` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_to_user` INT(11) NOT NULL,
  `employer_name` VARCHAR(200) NULL DEFAULT NULL,
  `employer_address_1` VARCHAR(45) NULL DEFAULT NULL,
  `employer_address_2` VARCHAR(45) NULL DEFAULT NULL,
  `employer_address_3` VARCHAR(45) NULL DEFAULT NULL,
  `employer_logo` MEDIUMBLOB NULL DEFAULT NULL,
  `employer_BR` MEDIUMBLOB NULL DEFAULT NULL,
  `employer` VARCHAR(20) NULL DEFAULT NULL,
  `employer_mobile_no` VARCHAR(20) NULL DEFAULT NULL,
  `employer_whatsapp_no` VARCHAR(20) NULL DEFAULT NULL,
  `employer_about_company` MEDIUMTEXT NULL DEFAULT NULL,
  `employer_Verified` TINYINT NULL DEFAULT 0,
  `employer_Verified_by` VARCHAR(100) NULL,
  `logo_path` VARCHAR(255) NULL DEFAULT NULL,
  `br_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `link_to_user_UNIQUE` (`link_to_user` ASC) VISIBLE,
  UNIQUE INDEX `employee_name_UNIQUE` (`employer_name` ASC) VISIBLE,
  UNIQUE INDEX `employee_land_no_UNIQUE` (`employer` ASC) VISIBLE,
  UNIQUE INDEX `employee_mobile_no_UNIQUE` (`employer_mobile_no` ASC) VISIBLE,
  UNIQUE INDEX `employee_whatsapp_no_UNIQUE` (`employer_whatsapp_no` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`job_category_table`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`job_category_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `Description` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  UNIQUE INDEX `Description_UNIQUE` (`Description` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`district_table`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`district_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `District_name` VARCHAR(45) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `District_name_UNIQUE` (`District_name` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`city_table`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`city_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `City` VARCHAR(45) NULL DEFAULT NULL,
  `City_link` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `City_UNIQUE` (`City` ASC) VISIBLE,
  INDEX `key_district_to_city_idx` (`City_link` ASC) VISIBLE,
  CONSTRAINT `key_district_to_city`
    FOREIGN KEY (`City_link`)
    REFERENCES `tiptromr_vacancies`.`district_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`advertising_table`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`advertising_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_to_employer_profile` INT(11) NOT NULL,
  `Opening_date` DATE NULL DEFAULT NULL,
  `Closing_date` DATE NULL DEFAULT NULL,
  `Industry` VARCHAR(255) NULL DEFAULT NULL,
  `Job_category` VARCHAR(255) NULL DEFAULT NULL,
  `Job_role` VARCHAR(255) NULL DEFAULT NULL,
  `job_type` VARCHAR(50) NULL DEFAULT 'Full Time',
  `job_type` VARCHAR(50) NULL DEFAULT 'Full Time',
  `Img` MEDIUMBLOB NULL DEFAULT NULL,
  `City` VARCHAR(45) NULL DEFAULT NULL,
  `job_description` MEDIUMTEXT NULL DEFAULT NULL,
  `District` VARCHAR(45) NULL DEFAULT NULL,
  `Apply_by_email` TINYINT(4) NULL DEFAULT '0',
  `Apply_by_system` TINYINT(4) NULL DEFAULT '1',
  `apply_WhatsApp` TINYINT(4) NULL DEFAULT '0',
  `Apply_by_email_address` VARCHAR(200) NULL DEFAULT NULL,
  `apply_WhatsApp_No` VARCHAR(20) NULL DEFAULT NULL,
  `Approved` TINYINT(4) NULL DEFAULT '0',
  `Rejection_comment` VARCHAR(200) NULL DEFAULT NULL,
  `rejection_reason` VARCHAR(255) NULL DEFAULT NULL,
  `views` INT(11) NULL DEFAULT '0',
  `img_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `ad_table_to_employee_profile_idx` (`link_to_employer_profile` ASC) VISIBLE,
  INDEX `ad_table_to_job_category_idx` (`Job_category` ASC) VISIBLE,
  INDEX `key_to_district_idx` (`District` ASC) VISIBLE,
  INDEX `key_to_city_idx` (`City` ASC) VISIBLE,
  CONSTRAINT `ad_table_to_employee_profile`
    FOREIGN KEY (`link_to_employer_profile`)
    REFERENCES `tiptromr_vacancies`.`employer_profile` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `ad_table_to_job_category`
    FOREIGN KEY (`Job_category`)
    REFERENCES `tiptromr_vacancies`.`job_category_table` (`Description`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `key_to_city`
    FOREIGN KEY (`City`)
    REFERENCES `tiptromr_vacancies`.`city_table` (`City`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `key_to_district`
    FOREIGN KEY (`District`)
    REFERENCES `tiptromr_vacancies`.`district_table` (`District_name`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`advertising_table_deleted`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`advertising_table_deleted` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_to_employer_profile` INT(11) NOT NULL,
  `Opening_date` DATE NULL DEFAULT NULL,
  `Closing_date` DATE NULL DEFAULT NULL,
  `Industry` VARCHAR(255) NULL DEFAULT NULL,
  `Job_category` VARCHAR(255) NULL DEFAULT NULL,
  `Job_role` VARCHAR(255) NULL DEFAULT NULL,
  `Img` MEDIUMBLOB NULL DEFAULT NULL,
  `City` VARCHAR(45) NULL DEFAULT NULL,
  `job_description` MEDIUMTEXT NULL DEFAULT NULL,
  `District` VARCHAR(45) NULL DEFAULT NULL,
  `Apply_by_email` TINYINT(4) NULL DEFAULT '0',
  `Apply_by_system` TINYINT(4) NULL DEFAULT '1',
  `apply_WhatsApp` TINYINT(4) NULL DEFAULT '0',
  `Apply_by_email_address` VARCHAR(200) NULL DEFAULT NULL,
  `apply_WhatsApp_No` VARCHAR(20) NULL DEFAULT NULL,
  `Approved` TINYINT(4) NULL DEFAULT '0',
  `Rejection_comment` VARCHAR(200) NULL DEFAULT NULL,
  `rejection_reason` VARCHAR(255) NULL DEFAULT NULL,
  `deleted_by` INT(11) NULL DEFAULT NULL,
  `deleted_date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `link_to_employer_profile` (`link_to_employer_profile` ASC) VISIBLE,
  INDEX `Job_category` (`Job_category` ASC) VISIBLE,
  INDEX `City` (`City` ASC) VISIBLE,
  INDEX `District` (`District` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`user_type_table`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`user_type_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_type_select` VARCHAR(45) NOT NULL,
  `type_hide` TINYINT(4) NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  UNIQUE INDEX `user_type_select_UNIQUE` (`user_type_select` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`user_table`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`user_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_email` VARCHAR(200) NOT NULL,
  `user_password` VARBINARY(255) NOT NULL,
  `full_name` VARCHAR(200) NOT NULL,
  `Birthday` DATE NOT NULL,
  `male_female` VARCHAR(45) NOT NULL,
  `user_type` VARCHAR(45) NOT NULL,
  `mobile_number` VARCHAR(20) NOT NULL,
  `WhatsApp_number` VARCHAR(20) NOT NULL,
  `max_login_attempt` INT(11) NULL DEFAULT '0',
  `user_active` TINYINT(4) NULL DEFAULT '1',
  `country` VARCHAR(45) NULL DEFAULT NULL,
  `send_opt` INT(11) NULL DEFAULT NULL,
  `send_time` DATETIME NULL DEFAULT NULL,
  `max_validate_time` INT(11) NULL DEFAULT NULL,
  `user_block` TINYINT(4) NULL DEFAULT '0',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `id_UNIQUE` (`id` ASC) VISIBLE,
  UNIQUE INDEX `user_email_UNIQUE` (`user_email` ASC) VISIBLE,
  UNIQUE INDEX `mobile_number_UNIQUE` (`mobile_number` ASC) VISIBLE,
  UNIQUE INDEX `WhatsApp_number_UNIQUE` (`WhatsApp_number` ASC) VISIBLE,
  INDEX `fk_to_type_idx` (`user_type` ASC) VISIBLE,
  CONSTRAINT `fk_to_type`
    FOREIGN KEY (`user_type`)
    REFERENCES `tiptromr_vacancies`.`user_type_table` (`user_type_select`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`candidate_profile`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`candidate_profile` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_to_user` INT(11) NOT NULL,
  `full_name` VARCHAR(200) NULL DEFAULT NULL,
  `resume_cv` MEDIUMBLOB NULL DEFAULT NULL,
  `profile_img` MEDIUMBLOB NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `link_to_user_UNIQUE` (`link_to_user` ASC) VISIBLE,
  CONSTRAINT `candidate_to_user`
    FOREIGN KEY (`link_to_user`)
    REFERENCES `tiptromr_vacancies`.`user_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`employee_alerted_setting`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`employee_alerted_setting` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `district` VARCHAR(45) NULL DEFAULT NULL,
  `city` VARCHAR(45) NULL DEFAULT NULL,
  `job_category` VARCHAR(45) NULL DEFAULT NULL,
  `link_to_employee_profile` INT(11) NULL DEFAULT NULL,
  `active` TINYINT(4) NULL DEFAULT '1',
  `Total_count` INT(11) NULL DEFAULT '0',
  `last_alert_sent` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `index_link_to_employer_profile` (`link_to_employee_profile` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`employee_document`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`employee_document` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_to_employee_profile` INT(11) NULL DEFAULT NULL,
  `document_type` VARCHAR(45) NULL DEFAULT NULL,
  `document` MEDIUMBLOB NULL DEFAULT NULL,
  `doc_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `link_to_employer_profile_idx` (`link_to_employee_profile` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`employee_profile_seeker`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`employee_profile_seeker` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_to_user` INT(11) NOT NULL,
  `employee_full_name` VARCHAR(200) NULL DEFAULT NULL,
  `employee_name_with_initial` VARCHAR(100) NULL DEFAULT NULL,
  `employee_cv` MEDIUMBLOB NULL DEFAULT NULL,
  `employee_cover_letter` MEDIUMBLOB NULL DEFAULT NULL,
  `employee_img` MEDIUMBLOB NULL DEFAULT NULL,
  `img_path` VARCHAR(255) NULL DEFAULT NULL,
  `cv_path` VARCHAR(255) NULL DEFAULT NULL,
  `cl_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `link_to_user_UNIQUE` (`link_to_user` ASC) VISIBLE,
  CONSTRAINT `employer_to_User`
    FOREIGN KEY (`link_to_user`)
    REFERENCES `tiptromr_vacancies`.`user_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`guest_job_applications`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`guest_job_applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_ad_link` INT(11) NOT NULL,
  `guest_full_name` VARCHAR(200) NOT NULL,
  `guest_contact_no` VARCHAR(20) NOT NULL,
  `guest_gender` VARCHAR(10) NOT NULL,
  `guest_cv` MEDIUMBLOB NOT NULL,
  `guest_cover_letter` MEDIUMBLOB NULL DEFAULT NULL,
  `applied_date` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `application_status` VARCHAR(45) NULL DEFAULT 'Pending',
  `rejection_reason` VARCHAR(255) NULL DEFAULT NULL,
  `cv_path` VARCHAR(255) NULL DEFAULT NULL,
  `cl_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_guest_job_idx` (`job_ad_link` ASC) VISIBLE,
  CONSTRAINT `fk_guest_job_to_ad`
    FOREIGN KEY (`job_ad_link`)
    REFERENCES `tiptromr_vacancies`.`advertising_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`job_applications`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`job_applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_ad_link` INT(11) NOT NULL,
  `seeker_link` INT(11) NOT NULL,
  `applied_date` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `application_status` VARCHAR(45) NULL DEFAULT 'Pending',
  `rejection_reason` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `fk_job_link_idx` (`job_ad_link` ASC) VISIBLE,
  INDEX `fk_seeker_link_idx` (`seeker_link` ASC) VISIBLE,
  CONSTRAINT `fk_job_to_app`
    FOREIGN KEY (`job_ad_link`)
    REFERENCES `tiptromr_vacancies`.`advertising_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_seeker_to_app`
    FOREIGN KEY (`seeker_link`)
    REFERENCES `tiptromr_vacancies`.`employee_profile_seeker` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`job_views_log`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`job_views_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NULL DEFAULT NULL,
  `viewed_at` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `job_id` (`job_id` ASC) VISIBLE,
  INDEX `viewed_at` (`viewed_at` ASC) VISIBLE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`payment_table`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`payment_table` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employer_link` INT(11) NULL DEFAULT NULL,
  `VAT_enable` TINYINT(4) NULL DEFAULT '0',
  `Totaled_received` DOUBLE NULL DEFAULT '0',
  `Payment_slip` MEDIUMBLOB NULL DEFAULT NULL,
  `Approval` TINYINT(4) NULL DEFAULT '0',
  `payment_date` DATE NULL DEFAULT NULL,
  `Approval_date` DATE NULL DEFAULT NULL,
  `Reject_comment` VARCHAR(100) NULL DEFAULT NULL,
  `slip_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `payment_to_employee_idx` (`employer_link` ASC) VISIBLE,
  CONSTRAINT `payment_to_employee`
    FOREIGN KEY (`employer_link`)
    REFERENCES `tiptromr_vacancies`.`employer_profile` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`paid_advertising`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`paid_advertising` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `slip_link` INT(11) NULL DEFAULT NULL,
  `add_link` INT(11) NULL DEFAULT NULL,
  `paid` TINYINT(4) NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `unique_ad_slip` (`add_link` ASC, `slip_link` ASC) VISIBLE,
  INDEX `index_slip_link` (`slip_link` ASC) VISIBLE,
  CONSTRAINT `pad_add_to_Ad_table`
    FOREIGN KEY (`add_link`)
    REFERENCES `tiptromr_vacancies`.`advertising_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `pad_add_to_payment_table`
    FOREIGN KEY (`slip_link`)
    REFERENCES `tiptromr_vacancies`.`payment_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`recruiter_profile`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`recruiter_profile` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `link_to_user` INT(11) NOT NULL,
  `company_name` VARCHAR(200) NULL DEFAULT NULL,
  `address_line_1` VARCHAR(45) NULL DEFAULT NULL,
  `company_logo` MEDIUMBLOB NULL DEFAULT NULL,
  `is_verified` TINYINT(4) NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE INDEX `link_to_user_UNIQUE` (`link_to_user` ASC) VISIBLE,
  CONSTRAINT `recruiter_to_user`
    FOREIGN KEY (`link_to_user`)
    REFERENCES `tiptromr_vacancies`.`user_table` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`site_settings`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`site_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `setting_key` (`setting_key` ASC) VISIBLE)
ENGINE = InnoDB
AUTO_INCREMENT = 4
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`sms_logs`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`sms_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `phone_number` VARCHAR(20) NOT NULL,
  `status` VARCHAR(20) NULL DEFAULT 'Sent',
  `sent_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `job_id` (`job_id` ASC) VISIBLE,
  CONSTRAINT `sms_logs_ibfk_1`
    FOREIGN KEY (`job_id`)
    REFERENCES `tiptromr_vacancies`.`advertising_table` (`id`)
    ON DELETE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`system_bank_accounts`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`system_bank_accounts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bank_name` VARCHAR(255) NOT NULL,
  `account_number` VARCHAR(100) NOT NULL,
  `branch_name` VARCHAR(100) NULL DEFAULT NULL,
  `branch_code` VARCHAR(50) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`talent_offers`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`talent_offers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `seeker_link` INT(11) NOT NULL,
  `headline` VARCHAR(255) NOT NULL,
  `skills_tags` TEXT NULL DEFAULT NULL,
  `experience_years` INT(11) NULL DEFAULT '0',
  `expected_salary` DOUBLE NULL DEFAULT '0',
  `description` MEDIUMTEXT NULL DEFAULT NULL,
  `expiry_date` DATE NOT NULL,
  `is_active` TINYINT(4) NULL DEFAULT '1',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_talent_seeker` (`seeker_link` ASC) VISIBLE,
  CONSTRAINT `fk_talent_to_seeker`
    FOREIGN KEY (`seeker_link`)
    REFERENCES `tiptromr_vacancies`.`employee_profile_seeker` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
AUTO_INCREMENT = 2
DEFAULT CHARACTER SET = utf8mb4
COLLATE = utf8mb4_0900_ai_ci;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`Compan_details`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`Compan_details` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `company_name` VARCHAR(255) NULL,
  `Compan_detailscol` VARCHAR(255) NULL,
  `addres1` VARCHAR(255) NULL,
  `addres2` VARCHAR(255) NULL,
  `addres3` VARCHAR(255) NULL,
  `TP_No` VARCHAR(255) NULL,
  `logo` MEDIUMBLOB NULL,
  `logo_path` VARCHAR(255) NULL DEFAULT NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`Price_setting`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`Price_setting` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `Unit_of_add` INT NULL,
  `selling_price` DOUBLE NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `tiptromr_vacancies`.`Industry_Setting`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `tiptromr_vacancies`.`Industry_Setting` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `Industry_name` VARCHAR(45) NULL,
  PRIMARY KEY (`id`))
ENGINE = InnoDB;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
