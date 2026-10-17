-- Create database
CREATE DATABASE IF NOT EXISTS student_guidance;
USE student_guidance;

-- 1. Create departments table FIRST (since users references it)
CREATE TABLE departments (
    dept_id INT PRIMARY KEY AUTO_INCREMENT,
    dept_code VARCHAR(10) UNIQUE NOT NULL,
    dept_name VARCHAR(100) NOT NULL,
    description TEXT,
    required_skills TEXT,
    natural_requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Create users table WITHOUT foreign key initially
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    interests TEXT,
    skills TEXT,
    gpa DECIMAL(3,2),
    department_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    role ENUM('student', 'admin', 'department_admin') DEFAULT 'student',
    INDEX idx_department (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Add foreign key separately
ALTER TABLE users 
ADD CONSTRAINT fk_user_department 
FOREIGN KEY (department_id) REFERENCES departments(dept_id) 
ON DELETE SET NULL;

-- 4. Create courses table
CREATE TABLE courses (
    course_id INT PRIMARY KEY AUTO_INCREMENT,
    dept_id INT NOT NULL,
    course_title VARCHAR(200) NOT NULL,
    course_description TEXT,
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create careers table
CREATE TABLE careers (
    career_id INT PRIMARY KEY AUTO_INCREMENT,
    dept_id INT NOT NULL,
    career_title VARCHAR(200) NOT NULL,
    career_description TEXT,
    job_level ENUM('Entry', 'Mid', 'Senior') DEFAULT 'Entry',
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Create role_models table
CREATE TABLE role_models (
    model_id INT PRIMARY KEY AUTO_INCREMENT,
    dept_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    achievement TEXT,
    description TEXT,
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Create recommendations table
CREATE TABLE recommendations (
    rec_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    dept_id INT NOT NULL,
    reason TEXT,
    match_percentage INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (dept_id) REFERENCES departments(dept_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Create admin_permissions table
CREATE TABLE admin_permissions (
    permission_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    can_manage_department BOOLEAN DEFAULT TRUE,
    can_manage_courses BOOLEAN DEFAULT TRUE,
    can_manage_careers BOOLEAN DEFAULT TRUE,
    can_manage_models BOOLEAN DEFAULT TRUE,
    can_view_recommendations BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert departments
INSERT INTO departments (dept_code, dept_name, description, required_skills, natural_requirements) VALUES
('EE', 'Electrical Engineering', 
'Focuses on electricity, electronics, circuits, and power systems. Students learn about electrical power generation, transmission, distribution, and utilization.',
'Mathematics, Physics, Circuit Analysis, Problem-solving',
'Interest in electronics and circuits, Technical mindset, Hardware-oriented thinking'),

('CS', 'Computer Science', 
'Focuses on programming, algorithms, artificial intelligence, and software systems. Students learn the theoretical foundations of computation and practical programming skills.',
'Logical thinking, Mathematics, Programming, Problem-solving',
'Strong logic and mathematics, Abstract thinking, Algorithm-focused mindset'),

('IS', 'Information Systems', 
'Focuses on combining business processes with information technology systems. Students learn to bridge the gap between business needs and technology solutions.',
'Business analysis, Communication, IT skills, Project management',
'Business + IT interest, Communication skills, Organizational thinking'),

('SW', 'Software Engineering', 
'Focuses on designing, developing, testing, and maintaining software applications. Students learn software development lifecycle and engineering principles.',
'Programming, Software design, Testing, Project management',
'Creativity + coding, Detail-oriented, System design interest');

-- Insert courses for EE
INSERT INTO courses (dept_id, course_title, course_description) VALUES
(1, 'Circuit Analysis', 'Study of electrical circuits, Kirchhoff laws, and circuit theorems'),
(1, 'Electronics', 'Study of electronic devices and circuits'),
(1, 'Power Systems', 'Study of power generation and distribution systems'),
(1, 'Electromagnetic Fields', 'Study of electromagnetic theory and applications'),
(1, 'Control Systems', 'Study of control theory and automation systems');

-- Insert courses for CS
INSERT INTO courses (dept_id, course_title, course_description) VALUES
(2, 'Programming Fundamentals', 'Introduction to programming concepts and problem-solving'),
(2, 'Data Structures', 'Study of data organization and manipulation'),
(2, 'Algorithms', 'Study of algorithmic design and analysis'),
(2, 'Artificial Intelligence', 'Study of AI principles and applications'),
(2, 'Database Systems', 'Study of database design and management');

-- Insert courses for IS
INSERT INTO courses (dept_id, course_title, course_description) VALUES
(3, 'Business Information Systems', 'Study of IT systems in business environments'),
(3, 'Database Management', 'Study of database systems for business applications'),
(3, 'System Analysis', 'Study of system requirements and analysis methods'),
(3, 'IT Project Management', 'Study of project management in IT contexts'),
(3, 'E-Commerce', 'Study of electronic commerce systems');

-- Insert courses for SW
INSERT INTO courses (dept_id, course_title, course_description) VALUES
(4, 'Software Engineering Principles', 'Study of software development methodologies'),
(4, 'Web Development', 'Study of web application development'),
(4, 'Mobile App Development', 'Study of mobile application development'),
(4, 'Software Testing', 'Study of software quality assurance and testing'),
(4, 'Software Design Patterns', 'Study of software architecture and design patterns');

-- Insert careers for EE
INSERT INTO careers (dept_id, career_title, career_description, job_level) VALUES
(1, 'Electrical Engineer', 'Design and develop electrical systems', 'Entry'),
(1, 'Power Systems Engineer', 'Work on power generation and distribution', 'Mid'),
(1, 'Electronics Designer', 'Design electronic circuits and devices', 'Entry'),
(1, 'Renewable Energy Engineer', 'Work on sustainable energy solutions', 'Mid');

-- Insert careers for CS
INSERT INTO careers (dept_id, career_title, career_description, job_level) VALUES
(2, 'Software Developer', 'Develop software applications', 'Entry'),
(2, 'AI Engineer', 'Develop artificial intelligence systems', 'Mid'),
(2, 'Data Scientist', 'Analyze and interpret complex data', 'Mid'),
(2, 'Systems Architect', 'Design complex computing systems', 'Senior');

-- Insert careers for IS
INSERT INTO careers (dept_id, career_title, career_description, job_level) VALUES
(3, 'System Analyst', 'Analyze and design information systems', 'Entry'),
(3, 'IT Consultant', 'Advise on IT solutions for businesses', 'Mid'),
(3, 'Business Analyst', 'Bridge business needs and IT solutions', 'Entry'),
(3, 'IT Manager', 'Manage IT departments and resources', 'Senior');

-- Insert careers for SW
INSERT INTO careers (dept_id, career_title, career_description, job_level) VALUES
(4, 'Software Engineer', 'Design and develop software systems', 'Entry'),
(4, 'Web Developer', 'Develop web-based applications', 'Entry'),
(4, 'Mobile App Developer', 'Develop mobile applications', 'Mid'),
(4, 'Quality Assurance Engineer', 'Test software quality and reliability', 'Entry');

-- Insert role models
INSERT INTO role_models (dept_id, name, achievement, description) VALUES
(1, 'Nikola Tesla', 'Pioneer of AC electricity', 'Famous electrical engineer and inventor'),
(1, 'Thomas Edison', 'Inventor and businessman', 'Prolific inventor in electrical fields'),
(2, 'Alan Turing', 'Father of computer science', 'Pioneer in AI and computing'),
(2, 'Linus Torvalds', 'Creator of Linux', 'World-leading software engineer'),
(3, 'Bill Gates', 'Microsoft co-founder', 'Tech entrepreneur and business leader'),
(3, 'Meg Whitman', 'Former eBay CEO', 'Successful tech business executive'),
(4, 'Mark Zuckerberg', 'Facebook founder', 'Software engineer and entrepreneur'),
(4, 'Ada Lovelace', 'First computer programmer', 'Pioneer in software development');

-- Create password reset requests table
CREATE TABLE password_reset_requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    status ENUM('pending', 'completed', 'rejected') DEFAULT 'pending',
    requested_by VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;