-- KhajaTime Database Schema
-- Import this file in phpMyAdmin (XAMPP) to set everything up.

CREATE DATABASE IF NOT EXISTS khajatime CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE khajatime;

-- ---------------------------------------------------------------
-- Users (students + kitchen staff, distinguished by role)
-- ---------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    pin VARCHAR(255) NOT NULL,          -- hashed 4-digit PIN
    role ENUM('student','kitchen') NOT NULL DEFAULT 'student',
    college_id VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Menu categories
-- ---------------------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (name) VALUES
('Rice'), ('Snacks'), ('Drinks'), ('Noodles'), ('Thali'), ('Sweets'), ('Beverages'), ('Main Course');

-- ---------------------------------------------------------------
-- Menu items
-- ---------------------------------------------------------------
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category_id INT DEFAULT NULL,
    image VARCHAR(255) DEFAULT NULL,
    available TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO menu_items (name, price, category_id, image, available) VALUES
('Veg Momo', 120, 2, NULL, 1),
('Chiya', 30, 7, NULL, 1),
('Fried Rice', 150, 1, NULL, 1),
('Samosa', 40, 2, NULL, 0),
('Thakali Set', 350, 5, NULL, 1),
('Mango Lassi', 90, 7, NULL, 1),
('Chicken Momo', 150, 2, NULL, 1),
('Veg Chowmein', 130, 4, NULL, 1),
('Masala Tea', 35, 7, NULL, 1),
('Sel Roti', 50, 6, NULL, 1);

-- ---------------------------------------------------------------
-- Orders
-- ---------------------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_number INT NOT NULL,
    status ENUM('preparing','ready','completed') NOT NULL DEFAULT 'preparing',
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Order items (line items per order)
-- ---------------------------------------------------------------
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT DEFAULT NULL,
    item_name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    is_checked TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- Demo accounts (PIN for both is 1234)
-- Hash below is a real bcrypt hash of "1234" and works with PHP's password_verify()
-- ---------------------------------------------------------------
INSERT INTO users (full_name, email, pin, role, college_id) VALUES
('Test Student', 'test@gmail.com', '$2b$10$yazIbmIMoEKGYP1zKkD5oeN5BVGdoioDnof1oNSv5OhIqEf8YNxEq', 'student', 'SWC-2024-101'),
('Test Staff', 'kitchen@gmail.com', '$2b$10$yazIbmIMoEKGYP1zKkD5oeN5BVGdoioDnof1oNSv5OhIqEf8YNxEq', 'kitchen', NULL);
