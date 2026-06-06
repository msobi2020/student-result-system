CREATE DATABASE IF NOT EXISTS student_result_db;
USE student_result_db;

CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    class VARCHAR(50) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject_code VARCHAR(20) NOT NULL UNIQUE,
    subject_name VARCHAR(100) NOT NULL,
    class VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    exam_year YEAR NOT NULL,
    term ENUM('Term 1', 'Term 2', 'Term 3') NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL DEFAULT 0,
    total_marks DECIMAL(5,2) NOT NULL DEFAULT 100,
    grade VARCHAR(5),
    remarks VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
);

INSERT INTO admin (username, password, full_name) VALUES
('admin', 'admin@123', 'System Administrator');

INSERT INTO subjects (subject_code, subject_name, class) VALUES
('MATH-F1', 'Mathematics', 'Form 1'),
('ENG-F1', 'English Language', 'Form 1'),
('KIS-F1', 'Kiswahili', 'Form 1'),
('BIO-F1', 'Biology', 'Form 1'),
('PHY-F1', 'Physics', 'Form 1'),
('CHEM-F1', 'Chemistry', 'Form 1'),
('HIST-F1', 'History', 'Form 1'),
('GEO-F1', 'Geography', 'Form 1'),
('CIV-F1', 'Civics', 'Form 1'),
('MATH-F2', 'Mathematics', 'Form 2'),
('ENG-F2', 'English Language', 'Form 2'),
('KIS-F2', 'Kiswahili', 'Form 2'),
('BIO-F2', 'Biology', 'Form 2'),
('PHY-F2', 'Physics', 'Form 2'),
('CHEM-F2', 'Chemistry', 'Form 2'),
('HIST-F2', 'History', 'Form 2'),
('GEO-F2', 'Geography', 'Form 2'),
('CIV-F2', 'Civics', 'Form 2'),
('MATH-F3', 'Mathematics', 'Form 3'),
('ENG-F3', 'English Language', 'Form 3'),
('KIS-F3', 'Kiswahili', 'Form 3'),
('BIO-F3', 'Biology', 'Form 3'),
('PHY-F3', 'Physics', 'Form 3'),
('CHEM-F3', 'Chemistry', 'Form 3'),
('HIST-F3', 'History', 'Form 3'),
('GEO-F3', 'Geography', 'Form 3'),
('CIV-F3', 'Civics', 'Form 3'),
('MATH-F4', 'Mathematics', 'Form 4'),
('ENG-F4', 'English Language', 'Form 4'),
('KIS-F4', 'Kiswahili', 'Form 4'),
('BIO-F4', 'Biology', 'Form 4'),
('PHY-F4', 'Physics', 'Form 4'),
('CHEM-F4', 'Chemistry', 'Form 4'),
('HIST-F4', 'History', 'Form 4'),
('GEO-F4', 'Geography', 'Form 4'),
('CIV-F4', 'Civics', 'Form 4');

INSERT INTO students (student_id, full_name, class, gender, date_of_birth) VALUES
('STD-2024-001', 'James Nkanda Msobi', 'Form 1', 'Male', '2010-03-15');
