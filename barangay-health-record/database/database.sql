-- =====================================================================
-- Barangay Health Record System  -  database schema + sample data
-- Select the target database before importing through phpMyAdmin, or run:
-- mysql -u root -p barangay_health_db < database.sql
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS maternal_child_records;
DROP TABLE IF EXISTS immunizations;
DROP TABLE IF EXISTS consultations;
DROP TABLE IF EXISTS health_records;
DROP TABLE IF EXISTS residents;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  full_name     VARCHAR(100) NOT NULL,
  email         VARCHAR(120) NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','health_worker') NOT NULL DEFAULT 'health_worker',
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  last_login    DATETIME NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE residents (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  resident_code VARCHAR(20)  NOT NULL,
  first_name    VARCHAR(60)  NOT NULL,
  middle_name   VARCHAR(60)  NULL,
  last_name     VARCHAR(60)  NOT NULL,
  sex           ENUM('Male','Female') NOT NULL,
  birthdate     DATE NOT NULL,
  civil_status  ENUM('Single','Married','Widowed','Separated') NOT NULL DEFAULT 'Single',
  contact_no    VARCHAR(20)  NULL,
  purok         VARCHAR(60)  NOT NULL,
  address       VARCHAR(150) NULL,
  blood_type    ENUM('A+','A-','B+','B-','AB+','AB-','O+','O-','Unknown') NOT NULL DEFAULT 'Unknown',
  philhealth_no VARCHAR(30)  NULL,
  guardian_name VARCHAR(120) NULL,
  is_archived   TINYINT(1) NOT NULL DEFAULT 0,
  created_by    INT UNSIGNED NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_resident_code (resident_code),
  UNIQUE KEY uq_resident_identity (first_name, last_name, birthdate),
  KEY idx_resident_name (last_name, first_name),
  KEY idx_resident_purok (purok),
  KEY idx_resident_archived (is_archived),
  CONSTRAINT fk_resident_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE health_records (
  id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  resident_id         INT UNSIGNED NOT NULL,
  medical_history     TEXT NULL,
  allergies           TEXT NULL,
  chronic_conditions  TEXT NULL,
  current_medications TEXT NULL,
  height_cm           DECIMAL(5,1) NULL,
  weight_kg           DECIMAL(5,1) NULL,
  health_notes        TEXT NULL,
  is_archived         TINYINT(1) NOT NULL DEFAULT 0,
  created_by          INT UNSIGNED NULL,
  created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_health_resident (resident_id),
  CONSTRAINT fk_health_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE RESTRICT,
  CONSTRAINT fk_health_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE consultations (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  resident_id      INT UNSIGNED NOT NULL,
  consult_date     DATE NOT NULL,
  symptoms         TEXT NOT NULL,
  blood_pressure   VARCHAR(10) NULL,
  temperature_c    DECIMAL(4,1) NULL,
  weight_kg        DECIMAL(5,1) NULL,
  assessment       TEXT NULL,
  diagnosis        VARCHAR(255) NULL,
  treatment        TEXT NULL,
  follow_up_date   DATE NULL,
  status           ENUM('Open','Follow-up','Closed') NOT NULL DEFAULT 'Open',
  attended_by      INT UNSIGNED NULL,
  is_archived      TINYINT(1) NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_consult_date (consult_date),
  KEY idx_consult_resident (resident_id),
  KEY idx_consult_followup (follow_up_date),
  CONSTRAINT fk_consult_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE RESTRICT,
  CONSTRAINT fk_consult_user FOREIGN KEY (attended_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE immunizations (
  id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
  resident_id        INT UNSIGNED NOT NULL,
  vaccine_name       VARCHAR(100) NOT NULL,
  dose_no            TINYINT UNSIGNED NOT NULL DEFAULT 1,
  date_administered  DATE NOT NULL,
  next_dose_date     DATE NULL,
  remarks            VARCHAR(255) NULL,
  administered_by    INT UNSIGNED NULL,
  is_archived        TINYINT(1) NOT NULL DEFAULT 0,
  created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_immunization_dose (resident_id, vaccine_name, dose_no),
  KEY idx_imm_date (date_administered),
  KEY idx_imm_next (next_dose_date),
  CONSTRAINT fk_imm_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE RESTRICT,
  CONSTRAINT fk_imm_user FOREIGN KEY (administered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE maternal_child_records (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  resident_id      INT UNSIGNED NOT NULL,
  record_type      ENUM('Prenatal','Postnatal','Child Growth') NOT NULL,
  visit_date       DATE NOT NULL,
  lmp_date         DATE NULL,
  expected_delivery DATE NULL,
  gestational_weeks TINYINT UNSIGNED NULL,
  blood_pressure   VARCHAR(10) NULL,
  weight_kg        DECIMAL(5,2) NULL,
  height_cm        DECIMAL(5,1) NULL,
  head_circ_cm     DECIMAL(4,1) NULL,
  nutrition_status ENUM('Normal','Underweight','Severely Underweight','Overweight','Stunted') NULL,
  risk_notes       TEXT NULL,
  notes            TEXT NULL,
  next_visit_date  DATE NULL,
  recorded_by      INT UNSIGNED NULL,
  is_archived      TINYINT(1) NOT NULL DEFAULT 0,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_mc_resident (resident_id),
  KEY idx_mc_type_date (record_type, visit_date),
  CONSTRAINT fk_mc_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE RESTRICT,
  CONSTRAINT fk_mc_user FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id     INT UNSIGNED NULL,
  action      VARCHAR(40)  NOT NULL,
  module      VARCHAR(40)  NOT NULL,
  target_id   INT UNSIGNED NULL,
  description VARCHAR(255) NULL,
  ip_address  VARCHAR(45)  NULL,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_log_user (user_id),
  KEY idx_log_created (created_at),
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================================
-- SAMPLE DATA  -  ALL RECORDS BELOW ARE FICTIONAL (for demonstration only)
-- No user accounts are inserted: create the first administrator with
-- auth/setup.php so that no default password ever exists.
-- Delete this section before real use.
-- =====================================================================
INSERT INTO residents (resident_code, first_name, middle_name, last_name, sex, birthdate, civil_status, contact_no, purok, address, blood_type, philhealth_no, guardian_name) VALUES
('R-0001','Maria','Santos','Dela Cruz','Female','1994-03-12','Married','09171230001','Purok 1','Sample St., Brgy. Demo','O+','SAMPLE-0001',NULL),
('R-0002','Juan','Reyes','Dela Cruz','Male','1990-07-25','Married','09171230002','Purok 1','Sample St., Brgy. Demo','A+','SAMPLE-0002',NULL),
('R-0003','Ana','Lopez','Garcia','Female','2023-11-02','Single',NULL,'Purok 2','Sample Ave., Brgy. Demo','B+',NULL,'Rosa Garcia'),
('R-0004','Pedro','Cruz','Ramos','Male','1958-01-30','Widowed','09171230004','Purok 3','Sample Rd., Brgy. Demo','AB+','SAMPLE-0004',NULL),
('R-0005','Liza','Mendoza','Torres','Female','1999-09-14','Single','09171230005','Purok 2','Sample Ave., Brgy. Demo','O-',NULL,NULL),
('R-0006','Carlo','Bautista','Villanueva','Male','2019-05-20','Single',NULL,'Purok 4','Sample Lane, Brgy. Demo','Unknown',NULL,'Elena Villanueva'),
('R-0007','Rosa','Aquino','Garcia','Female','1988-12-05','Married','09171230007','Purok 2','Sample Ave., Brgy. Demo','A-','SAMPLE-0007',NULL),
('R-0008','Miguel','Tan','Santiago','Male','1975-04-18','Married','09171230008','Purok 4','Sample Lane, Brgy. Demo','B-',NULL,NULL);

INSERT INTO health_records (resident_id, medical_history, allergies, chronic_conditions, current_medications, height_cm, weight_kg, health_notes) VALUES
(1,'No major past illnesses.','Penicillin','None',NULL,158.0,54.5,'Currently pregnant, monitored under prenatal care.'),
(2,'Appendectomy (2012).','None','Hypertension','Amlodipine 5 mg daily',170.0,78.0,'Advised to reduce salt intake.'),
(4,'Stroke history (2019).','Seafood','Hypertension, Type 2 diabetes','Metformin 500 mg, Losartan 50 mg',165.0,70.0,'Monthly BP and glucose monitoring.'),
(8,'Asthma since childhood.','Dust','Asthma','Salbutamol inhaler as needed',172.0,81.0,NULL);

INSERT INTO consultations (resident_id, consult_date, symptoms, blood_pressure, temperature_c, weight_kg, assessment, diagnosis, treatment, follow_up_date, status) VALUES
(2, DATE_SUB(CURDATE(), INTERVAL 75 DAY), 'Headache, dizziness','150/95',36.7,78.0,'Elevated blood pressure','Hypertension','Continue amlodipine; low-salt diet',DATE_SUB(CURDATE(), INTERVAL 45 DAY),'Closed'),
(4, DATE_SUB(CURDATE(), INTERVAL 52 DAY), 'Fatigue, frequent urination','140/90',36.5,70.0,'High blood sugar','Type 2 diabetes mellitus','Metformin; diet counseling',DATE_SUB(CURDATE(), INTERVAL 22 DAY),'Closed'),
(6, DATE_SUB(CURDATE(), INTERVAL 31 DAY), 'Cough and colds for 3 days',NULL,37.8,18.0,'Upper respiratory infection','Common cold','Increase fluids; paracetamol syrup',NULL,'Closed'),
(8, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Shortness of breath at night','120/80',36.6,81.0,'Mild asthma exacerbation','Bronchial asthma','Salbutamol inhaler; avoid dust',DATE_ADD(CURDATE(), INTERVAL 5 DAY),'Follow-up'),
(4, DATE_SUB(CURDATE(), INTERVAL 9 DAY), 'Routine check-up','135/85',36.5,69.5,'Stable','Hypertension; Type 2 diabetes','Continue medications',DATE_ADD(CURDATE(), INTERVAL 21 DAY),'Follow-up'),
(5, CURDATE(), 'Fever and body aches','110/70',38.2,52.0,'Viral illness suspected','Viral fever','Paracetamol; rest and fluids',DATE_ADD(CURDATE(), INTERVAL 3 DAY),'Open');

INSERT INTO immunizations (resident_id, vaccine_name, dose_no, date_administered, next_dose_date, remarks) VALUES
(3,'BCG',1,'2023-11-03',NULL,'At birth'),
(3,'Hepatitis B',1,'2023-11-03','2024-01-05','At birth'),
(3,'Pentavalent',1,'2024-01-05','2024-02-05',NULL),
(3,'Pentavalent',2,'2024-02-05',DATE_ADD(CURDATE(), INTERVAL 10 DAY),NULL),
(6,'MMR',1,'2020-05-25','2021-05-25',NULL),
(6,'MMR',2,'2021-05-25',NULL,'Complete'),
(1,'Tetanus Toxoid',1,DATE_SUB(CURDATE(), INTERVAL 40 DAY),DATE_ADD(CURDATE(), INTERVAL 14 DAY),'Prenatal'),
(4,'Influenza',1,DATE_SUB(CURDATE(), INTERVAL 100 DAY),NULL,'Annual dose');

INSERT INTO maternal_child_records (resident_id, record_type, visit_date, lmp_date, expected_delivery, gestational_weeks, blood_pressure, weight_kg, nutrition_status, risk_notes, notes, next_visit_date) VALUES
(1,'Prenatal',DATE_SUB(CURDATE(), INTERVAL 56 DAY),DATE_SUB(CURDATE(), INTERVAL 140 DAY),DATE_ADD(CURDATE(), INTERVAL 140 DAY),20,'110/70',52.0,'Normal',NULL,'First prenatal visit; iron supplements given.',DATE_SUB(CURDATE(), INTERVAL 28 DAY)),
(1,'Prenatal',DATE_SUB(CURDATE(), INTERVAL 14 DAY),DATE_SUB(CURDATE(), INTERVAL 140 DAY),DATE_ADD(CURDATE(), INTERVAL 140 DAY),28,'115/75',54.5,'Normal',NULL,'Fetal movement normal.',DATE_ADD(CURDATE(), INTERVAL 14 DAY));
INSERT INTO maternal_child_records (resident_id, record_type, visit_date, weight_kg, height_cm, head_circ_cm, nutrition_status, notes, next_visit_date) VALUES
(3,'Child Growth',DATE_SUB(CURDATE(), INTERVAL 60 DAY),9.2,72.0,45.0,'Normal','Monthly weighing.',DATE_SUB(CURDATE(), INTERVAL 30 DAY)),
(3,'Child Growth',DATE_SUB(CURDATE(), INTERVAL 5 DAY),9.8,74.0,45.5,'Normal','Good appetite.',DATE_ADD(CURDATE(), INTERVAL 25 DAY)),
(6,'Child Growth',DATE_SUB(CURDATE(), INTERVAL 20 DAY),16.5,106.0,NULL,'Underweight','Referred for feeding program.',DATE_ADD(CURDATE(), INTERVAL 10 DAY));
