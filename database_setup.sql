USE leave_management;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS leave_requests;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE users (
 id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(100) NOT NULL UNIQUE, password VARCHAR(255) NOT NULL, role ENUM('student','advisor','hod') NOT NULL,
 full_name VARCHAR(150) NOT NULL, email VARCHAR(255) NULL, reg_no VARCHAR(50) NULL, department VARCHAR(100) NULL, branch VARCHAR(150) NULL, batch VARCHAR(50) NULL, semester VARCHAR(20) NULL, academic_year VARCHAR(30) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE leave_requests (
 id INT AUTO_INCREMENT PRIMARY KEY, student_id INT NOT NULL, reg_no VARCHAR(50) NOT NULL, date_from DATE NOT NULL, date_to DATE NOT NULL, batch VARCHAR(50) NULL, semester VARCHAR(20) NULL, academic_year VARCHAR(30) NULL, reason TEXT NOT NULL, proof VARCHAR(255) NULL, advisor_id INT NOT NULL, advisor_status TINYINT NOT NULL DEFAULT 4, hod_id INT NOT NULL, hod_status TINYINT NOT NULL DEFAULT 4, forwarded_to_hod TINYINT NOT NULL DEFAULT 0, advisor_lock_notified TINYINT NOT NULL DEFAULT 0, advisor_deadline_at DATETIME NULL, student_notified TINYINT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX(student_id), INDEX(advisor_id), INDEX(hod_id), FOREIGN KEY(student_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(advisor_id) REFERENCES users(id) ON DELETE RESTRICT, FOREIGN KEY(hod_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (username,password,role,full_name,reg_no,department,branch,batch,semester,academic_year) VALUES
('ragavarshini','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','student','Ragavarshini','24BEE01','EEE','Electrical and Electronics Engineering','2024-2028','5','2026-2027'),
('sowmiya','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','student','Sowmiya','24BEE02','EEE','Electrical and Electronics Engineering','2024-2028','5','2026-2027'),
('sujatha','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','student','Sujatha','24BCB01','CSBS','Computer Science and Business Systems','2024-2028','5','2026-2027'),
('libika','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','student','Libika','24BCB02','CSBS','Computer Science and Business Systems','2024-2028','5','2026-2027'),
('theshitha','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','student','Theshitha','24BIT01','IT','Information Technology','2024-2028','5','2026-2027'),
('tharanya','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','student','Tharanya','24BIT02','IT','Information Technology','2024-2028','5','2026-2027'),
('advisor_eee','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','advisor','EEE Advisor',NULL,'EEE',NULL,NULL,NULL,NULL),
('advisor_csbs','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','advisor','CSBS Advisor',NULL,'CSBS',NULL,NULL,NULL,NULL),
('advisor_it','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','advisor','IT Advisor',NULL,'IT',NULL,NULL,NULL,NULL),
('hod_eee','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','hod','EEE HOD',NULL,'EEE',NULL,NULL,NULL,NULL),
('hod_csbs','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','hod','CSBS HOD',NULL,'CSBS',NULL,NULL,NULL,NULL),
('hod_it','$2y$12$7R2U.Y/uyLfObYsSwa1pC.HAgN6JebWH782Odxk6t/RVGg.CJp9fa','hod','IT HOD',NULL,'IT',NULL,NULL,NULL,NULL);
