-- ============================================================
-- MCU E-Library Management System — Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS mcu_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mcu_library;

-- ─────────────────────────────────────────
-- Table: admin
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS admin (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin  (password: Admin@123)
INSERT INTO admin (username, email, password, full_name) VALUES
('admin', 'admin@mcu.ac.in', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Library Administrator');

-- ─────────────────────────────────────────
-- Table: users  (students)
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    phone         VARCHAR(15),
    enrollment_no VARCHAR(30)  NOT NULL UNIQUE,
    department    VARCHAR(100),
    semester      TINYINT,
    password      VARCHAR(255) NOT NULL,
    status        ENUM('active','suspended') DEFAULT 'active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Table: books
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS books (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    title            VARCHAR(255) NOT NULL,
    author           VARCHAR(150) NOT NULL,
    isbn             VARCHAR(20),
    category         VARCHAR(100) NOT NULL,
    publisher        VARCHAR(150),
    publish_year     YEAR,
    description      TEXT,
    cover_image      VARCHAR(255) DEFAULT 'default_cover.png',
    total_quantity   INT NOT NULL DEFAULT 1,
    issued_quantity  INT NOT NULL DEFAULT 0,
    location         VARCHAR(100) COMMENT 'Shelf / rack location',
    added_at         DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_search (title, author, description)
) ENGINE=InnoDB;

-- Sample books
INSERT INTO books (title, author, isbn, category, publisher, publish_year, description, total_quantity, issued_quantity) VALUES
('Introduction to Algorithms', 'Thomas H. Cormen', '978-0262033848', 'Computer Science', 'MIT Press', 2009, 'A comprehensive introduction to algorithms and data structures.', 5, 2),
('Data Structures and Algorithms in Python', 'Michael T. Goodrich', '978-1118290275', 'Computer Science', 'Wiley', 2013, 'Covers fundamental data structures and algorithms using Python.', 4, 1),
('Database System Concepts', 'Abraham Silberschatz', '978-0073523323', 'Database', 'McGraw-Hill', 2019, 'Authoritative textbook on relational databases and SQL.', 6, 3),
('Operating System Concepts', 'Abraham Silberschatz', '978-1119320913', 'Computer Science', 'Wiley', 2018, 'Comprehensive coverage of modern operating systems.', 5, 0),
('Computer Networks', 'Andrew S. Tanenbaum', '978-0132126953', 'Networking', 'Pearson', 2010, 'Classic text on computer networking from top to bottom.', 4, 2),
('Artificial Intelligence: A Modern Approach', 'Stuart Russell', '978-0134610993', 'Artificial Intelligence', 'Pearson', 2020, 'The definitive AI textbook used worldwide.', 3, 3),
('Clean Code', 'Robert C. Martin', '978-0132350884', 'Software Engineering', 'Prentice Hall', 2008, 'A handbook of agile software craftsmanship.', 4, 1),
('The Pragmatic Programmer', 'David Thomas', '978-0135957059', 'Software Engineering', 'Addison-Wesley', 2019, 'Timeless lessons for every programmer.', 3, 0),
('Physics for Scientists and Engineers', 'Paul A. Tipler', '978-1429201247', 'Physics', 'W. H. Freeman', 2007, 'Calculus-based physics for engineers.', 5, 2),
('Engineering Mathematics', 'K.A. Stroud', '978-1137031204', 'Mathematics', 'Palgrave Macmillan', 2013, 'Essential engineering mathematics textbook.', 6, 1),
('Machine Learning', 'Tom M. Mitchell', '978-0070428072', 'Artificial Intelligence', 'McGraw-Hill', 1997, 'Foundational machine learning text.', 3, 2),
('Web Technologies', 'Chris Bates', '978-0470017784', 'Web Development', 'Wiley', 2006, 'HTML, CSS, JavaScript and server-side programming.', 5, 1),
('Digital Electronics', 'Morris Mano', '978-0131914865', 'Electronics', 'Pearson', 2012, 'Digital design from basics to VHDL.', 4, 0),
('Discrete Mathematics', 'Kenneth H. Rosen', '978-0072880083', 'Mathematics', 'McGraw-Hill', 2018, 'Applications of discrete mathematics.', 5, 2),
('Compiler Design', 'Alfred V. Aho', '978-0321486813', 'Computer Science', 'Addison-Wesley', 2006, 'Principles, techniques and tools for compiler design.', 3, 1);

-- ─────────────────────────────────────────
-- Table: issued_books
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS issued_books (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    book_id      INT NOT NULL,
    issue_date   DATE,
    due_date     DATE,
    return_date  DATE,
    status       ENUM('pending','approved','returned','rejected') DEFAULT 'pending',
    fine_amount  DECIMAL(8,2) DEFAULT 0.00,
    fine_paid    TINYINT(1)   DEFAULT 0,
    admin_note   VARCHAR(255),
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- Table: notifications
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    message    TEXT NOT NULL,
    is_read    TINYINT(1) DEFAULT 0,
    type       ENUM('info','success','warning','danger') DEFAULT 'info',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
