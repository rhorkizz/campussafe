CREATE DATABASE IF NOT EXISTS campus_incident_system;
USE campus_incident_system;

CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE
);

CREATE TABLE IF NOT EXISTS departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    user_id VARCHAR(20) PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    department_id INT DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    must_change_password BOOLEAN DEFAULT TRUE,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

CREATE TABLE IF NOT EXISTS incident_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    department_id INT NOT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(department_id)
);

CREATE TABLE IF NOT EXISTS incident_routing (
    category_id INT PRIMARY KEY,
    assigned_role_id INT,
    FOREIGN KEY (category_id) REFERENCES incident_categories(category_id),
    FOREIGN KEY (assigned_role_id) REFERENCES roles(role_id)
);

CREATE TABLE IF NOT EXISTS incidents (
    incident_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    location VARCHAR(100),
    is_anonymous BOOLEAN DEFAULT FALSE,
    reported_by VARCHAR(20) DEFAULT NULL,
    assigned_role_id INT,
    assigned_user_id VARCHAR(20) DEFAULT NULL,
    status ENUM('Pending','In Progress','Resolved','Deleted') DEFAULT 'Pending',
    priority ENUM('low','medium','high','critical') DEFAULT 'medium',
    attachment_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES incident_categories(category_id),
    FOREIGN KEY (reported_by) REFERENCES users(user_id),
    FOREIGN KEY (assigned_role_id) REFERENCES roles(role_id),
    FOREIGN KEY (assigned_user_id) REFERENCES users(user_id)
);

CREATE TABLE IF NOT EXISTS incident_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT,
    user_id VARCHAR(20),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(incident_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

INSERT INTO roles (role_name) VALUES
('Student'),
('Campus Officer'),
('Hostel Officer'),
('Admin');

INSERT INTO departments (department_name) VALUES
('IT Support'),
('Maintenance'),
('Security'),
('Lecturers Affairs'),
('Hostel Management');

-- NOTE: All passwords below are bcrypt hashes. Plain-text equivalents:
--   UPSA001: 2001-05-14 | UPSA002: 2002-09-22 | UPSA003: 2000-12-03
--   STAFF*: staff123 | HOST001: hostel123 | ADMIN001: admin123
INSERT INTO users (user_id, full_name, role_id, password) VALUES
('UPSA001', 'Kwame Mensah',  1, '$2y$10$tJTPqeZOLdexMoTVexXyb.X4/naKEICfdjH5VhFCCjwKvUReWLiBW'),
('UPSA002', 'Ama Boateng',   1, '$2y$10$qBoCTq.tJq1KuTZhtY9QYeZpBGgSER/BK6dtRIT4EKPva5Pqx/fVW'),
('UPSA003', 'Yaw Addo',      1, '$2y$10$XM0EtVPAkiEUjfC0qrFHhe8GyOrr.hWACjDLvpa2fL8WQ9JqFeEkG');

INSERT INTO users (user_id, full_name, role_id, department_id, password) VALUES
('STAFF001', 'Mr. Kofi Asare',    2, 1, '$2y$10$y4uxUrGhWkegRLJq4c3Rg.L5qjZdKLZLuWXgzmyq4fFwwo0FOrGIm'),
('STAFF002', 'Mrs. Akua Owusu',   2, 2, '$2y$10$y4uxUrGhWkegRLJq4c3Rg.L5qjZdKLZLuWXgzmyq4fFwwo0FOrGIm'),
('STAFF003', 'Mr. Daniel Tetteh', 2, 3, '$2y$10$y4uxUrGhWkegRLJq4c3Rg.L5qjZdKLZLuWXgzmyq4fFwwo0FOrGIm');

INSERT INTO users (user_id, full_name, role_id, department_id, password) VALUES
('HOST001', 'Porter Ibrahim', 3, 5, '$2y$10$VeYwQhiidjsDKsLePQ.gE.0rQQWKHze9Z9Gc/00yaMFz/Yu.hoyeq');

INSERT INTO users (user_id, full_name, role_id, password) VALUES
('ADMIN001', 'System Administrator', 4, '$2y$10$/piBFrpnj6GhMgVy07/Xiu7dhVPrAOpqjyhhUT7FoYktJC9P85BEG');

INSERT INTO incident_categories (category_name, department_id) VALUES
('Faulty Projector', 1),
('Internet Issue', 1),
('Broken Chair', 2),
('Electrical Fault', 2),
('Harassment', 3),
('Suspicious Activity', 3),
('Lecturer Absence', 4),
('Water Problem', 5);

INSERT INTO incident_routing (category_id, assigned_role_id) VALUES
(1, 2), -- Faulty Projector → Campus Officer
(2, 2), -- Internet Issue → Campus Officer
(3, 2), -- Broken Chair → Campus Officer
(4, 2), -- Electrical Fault → Campus Officer
(5, 2), -- Harassment → Campus Officer
(6, 2), -- Suspicious Activity → Campus Officer
(7, 2), -- Lecturer Absence → Campus Officer
(8, 3); -- Water Problem → Hostel Officer

INSERT INTO incidents
(title, description, category_id, location, status, reported_by)
VALUES
('Projector not working', 'Projector in Lecture Theatre 5 is not turning on', 1, 'Campus', 'Pending', 'UPSA001'),
('Internet down in library', 'No internet connectivity in main library', 2, 'Campus', 'Pending', 'UPSA002'),
('Broken chair in classroom', 'Chair in Room B12 is broken and unsafe', 3, 'Campus', 'In Progress', 'UPSA003'),
('Electrical fault in hostel', 'Power keeps going off in Hostel Block C', 4, 'Hostel', 'Pending', NULL),
('Water leakage in hostel', 'Water leaking in Hostel Room 203', 8, 'Hostel', 'Pending', NULL),
('Harassment complaint', 'Student reports verbal harassment near library', 5, 'Campus', 'Pending', NULL);
