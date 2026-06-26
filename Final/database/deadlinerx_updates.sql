SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

CREATE TABLE IF NOT EXISTS department (
    Department_ID INT AUTO_INCREMENT PRIMARY KEY,
    Department_Name VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS class (
    Class_ID INT AUTO_INCREMENT PRIMARY KEY,
    Class_Name VARCHAR(50) NOT NULL,
    Department_ID INT NOT NULL,
    FOREIGN KEY (Department_ID) REFERENCES department(Department_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teacher (
    Teacher_ID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(100) NOT NULL,
    College_Email_ID VARCHAR(150) NOT NULL UNIQUE,
    Department_ID INT NOT NULL,
    Password VARCHAR(100) NOT NULL,
    FOREIGN KEY (Department_ID) REFERENCES department(Department_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student (
    Student_ID INT AUTO_INCREMENT PRIMARY KEY,
    Username VARCHAR(100) NOT NULL,
    College_Email_ID VARCHAR(150) NOT NULL UNIQUE,
    Department_ID INT NOT NULL,
    Class_ID INT NOT NULL,
    Password VARCHAR(100) NOT NULL,
    FOREIGN KEY (Department_ID) REFERENCES department(Department_ID),
    FOREIGN KEY (Class_ID) REFERENCES class(Class_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS academic_calendar (
    Calendar_ID INT AUTO_INCREMENT PRIMARY KEY,
    Dates DATE NOT NULL,
    Events VARCHAR(255) NOT NULL,
    Holidays ENUM('Yes', 'No') DEFAULT 'No'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS assignment (
    Assignment_ID INT AUTO_INCREMENT PRIMARY KEY,
    Subject VARCHAR(100) NOT NULL,
    Title VARCHAR(150) NOT NULL,
    Given_Date DATE NOT NULL,
    Type VARCHAR(50) DEFAULT 'Theory',
    Weightage DECIMAL(5,2) DEFAULT 0,
    No_of_Pages INT DEFAULT 0,
    Difficulty_Index INT DEFAULT 5,
    Due_Date DATE NOT NULL,
    Class_ID INT NOT NULL,
    Teacher_Username VARCHAR(100) NOT NULL,
    Description TEXT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Class_ID) REFERENCES class(Class_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS test (
    Test_ID INT AUTO_INCREMENT PRIMARY KEY,
    Subject VARCHAR(100) NOT NULL,
    Title VARCHAR(150) NULL,
    Test_Title VARCHAR(150) NULL,
    Test_Date DATE NOT NULL,
    Date_Given DATE NULL,
    Given_Date DATE NULL,
    Weightage DECIMAL(5,2) DEFAULT 0,
    Difficulty_Index INT DEFAULT 5,
    Class_ID INT NOT NULL,
    Teacher_Username VARCHAR(100) NOT NULL,
    Description TEXT NULL,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (Class_ID) REFERENCES class(Class_ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_task_progress (
    progress_id INT AUTO_INCREMENT PRIMARY KEY,
    student_email VARCHAR(150) NOT NULL,
    task_type ENUM('assignment', 'test') NOT NULL,
    task_id VARCHAR(150) NOT NULL,
    completion_percentage INT DEFAULT 0,
    estimated_hours_left DECIMAL(5,2) DEFAULT 0,
    available_hours_today DECIMAL(5,2) DEFAULT 0,
    status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
    is_completed TINYINT(1) DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_student_task (student_email, task_type, task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ai_plans (
    plan_id INT AUTO_INCREMENT PRIMARY KEY,
    student_email VARCHAR(150) NOT NULL,
    plan_text LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS student_personal_tasks (
    Personal_Task_ID INT AUTO_INCREMENT PRIMARY KEY,
    student_email VARCHAR(150) NOT NULL,
    Title VARCHAR(150) NOT NULL,
    Subject VARCHAR(100) DEFAULT 'Personal Task',
    Due_Date DATE NOT NULL,
    Difficulty_Index INT DEFAULT 5,
    Weightage DECIMAL(5,2) DEFAULT 0,
    Estimated_Hours DECIMAL(5,2) DEFAULT 1,
    Description TEXT NULL,
    Is_Completed TINYINT(1) DEFAULT 0,
    Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO department (Department_ID, Department_Name) VALUES
(1, 'Computer Department'),
(2, 'Civil Department'),
(3, 'Mechanical Department'),
(4, 'Electronics and Computer Science Department'),
(5, 'Science and Humanities Department')
ON DUPLICATE KEY UPDATE Department_Name = VALUES(Department_Name);

INSERT INTO class (Class_ID, Class_Name, Department_ID) VALUES
(1, 'FE COMP 1', 1),
(2, 'FE COMP 2', 1),
(3, 'SE COMP 1', 1),
(4, 'SE COMP 2', 1),
(5, 'TE COMP 1', 1),
(6, 'BE COMP 1', 1)
ON DUPLICATE KEY UPDATE Class_Name = VALUES(Class_Name), Department_ID = VALUES(Department_ID);

INSERT INTO teacher (Username, College_Email_ID, Department_ID, Password) VALUES
('Amey Tilve', 'amey.tilve@dbcegoa.ac.in', 1, '1234'),
('Gaurang Patkar', 'gaurang.patkar@dbcegoa.ac.in', 1, '1234')
ON DUPLICATE KEY UPDATE
Username = VALUES(Username),
Department_ID = VALUES(Department_ID),
Password = VALUES(Password);

INSERT INTO student (Username, College_Email_ID, Department_ID, Class_ID, Password) VALUES
('Parima Tendulkar', '2414047@dbcegoa.ac.in', 1, 3, '1234'),
('Megha Gobre', '2414038@dbcegoa.ac.in', 1, 3, '1234'),
('Nirat Nayak', '2414046@dbcegoa.ac.in', 1, 3, '1234')
ON DUPLICATE KEY UPDATE
Username = VALUES(Username),
Department_ID = VALUES(Department_ID),
Class_ID = VALUES(Class_ID),
Password = VALUES(Password);

INSERT INTO academic_calendar (Dates, Events, Holidays) VALUES
('2026-04-10', 'Good Friday', 'Yes'),
('2026-04-14', 'Ambedkar Jayanti', 'Yes'),
('2026-04-20', 'IT/ISA Exam Begins', 'No'),
('2026-04-21', 'IT/ISA Exam', 'No'),
('2026-04-22', 'IT/ISA Exam Ends', 'No'),
('2026-05-01', 'Labour Day', 'Yes');

ALTER TABLE student_task_progress 
MODIFY task_type ENUM('assignment', 'test', 'personal') NOT NULL;

COMMIT;