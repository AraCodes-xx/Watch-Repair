-- ============================================
-- MC REPAIR SHOP - COMPLETE DATABASE
-- ============================================
-- This file contains the complete database schema
-- with all tables, relationships, and sample data
-- 
-- HOW TO USE:
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Click "New" to create database OR select existing database
-- 3. Click "Import" tab
-- 4. Choose this file
-- 5. Click "Go"
-- ============================================

-- Create Database
CREATE DATABASE IF NOT EXISTS repair_shop;
USE repair_shop;

-- ============================================
-- TABLE: admin
-- ============================================
CREATE TABLE IF NOT EXISTS admin (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: watch_types
-- ============================================
CREATE TABLE IF NOT EXISTS watch_types (
    watch_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: services
-- ============================================
CREATE TABLE IF NOT EXISTS services (
    service_id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    base_price DECIMAL(10, 2) NOT NULL,
    duration_hours INT DEFAULT 2,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: technicians
-- ============================================
CREATE TABLE IF NOT EXISTS technicians (
    technician_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    contact_number VARCHAR(20) NOT NULL,
    specialization VARCHAR(100),
    is_available BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3, 2) DEFAULT 0.00,
    total_jobs INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: timeslots
-- ============================================
CREATE TABLE IF NOT EXISTS timeslots (
    timeslot_id INT PRIMARY KEY AUTO_INCREMENT,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- TABLE: bookings
-- ============================================
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    service_id INT NOT NULL,
    technician_id INT,
    booking_date DATE NOT NULL,
    timeslot_id INT NOT NULL,
    watch_type_id INT,
    problem_description TEXT NOT NULL,
    user_address TEXT NOT NULL,
    total_cost DECIMAL(10, 2) NOT NULL,
    down_payment DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed', 'Cancelled') DEFAULT 'Pending',
    payment_status ENUM('Down Payment Only', 'Fully Paid') DEFAULT 'Down Payment Only',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(technician_id) ON DELETE SET NULL,
    FOREIGN KEY (timeslot_id) REFERENCES timeslots(timeslot_id) ON DELETE CASCADE,
    FOREIGN KEY (watch_type_id) REFERENCES watch_types(watch_type_id) ON DELETE SET NULL
);

-- ============================================
-- TABLE: booking_services (Junction Table)
-- ============================================
CREATE TABLE IF NOT EXISTS booking_services (
    booking_service_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    service_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: payments
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_proof VARCHAR(255) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'GCash',
    verification_status ENUM('Pending', 'Verified', 'Rejected') DEFAULT 'Pending',
    admin_remarks TEXT,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin(admin_id) ON DELETE SET NULL
);

-- ============================================
-- TABLE: feedback
-- ============================================
CREATE TABLE IF NOT EXISTS feedback (
    feedback_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    user_id INT NOT NULL,
    technician_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(technician_id) ON DELETE CASCADE
);

-- ============================================
-- TABLE: notifications
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_id INT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- ============================================
-- DEFAULT DATA: Admin Account
-- ============================================
INSERT INTO admin (username, password, email, full_name) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@mcrepair.com', 'System Administrator');
-- Default Login: username = admin, password = password

-- ============================================
-- DEFAULT DATA: Time Slots
-- ============================================
INSERT INTO timeslots (start_time, end_time) VALUES 
('08:00:00', '10:00:00'),
('10:00:00', '12:00:00'),
('12:00:00', '14:00:00'),
('14:00:00', '16:00:00'),
('16:00:00', '18:00:00');

-- ============================================
-- DEFAULT DATA: Watch Types
-- ============================================
INSERT INTO watch_types (type_name, description) VALUES 
('Mechanical Watch', 'Traditional mechanical movement watches'),
('Automatic Watch', 'Self-winding mechanical watches'),
('Quartz Watch', 'Battery-powered quartz movement watches'),
('Digital Watch', 'Electronic digital display watches'),
('Smartwatch', 'Smart wearable devices with digital features'),
('Chronograph', 'Watches with stopwatch functionality'),
('Diving Watch', 'Water-resistant watches for diving'),
('Pocket Watch', 'Traditional pocket-style timepieces'),
('Luxury/Designer Watch', 'High-end luxury brand watches'),
('Vintage Watch', 'Antique or vintage timepieces'),
('Other', 'Other types of watches');

-- ============================================
-- DEFAULT DATA: Services
-- ============================================
INSERT INTO services (service_name, description, base_price, duration_hours) VALUES 
('Battery Replacement', 'Replace watch battery with genuine parts', 500.00, 1),
('Watch Cleaning', 'Complete cleaning and polishing service', 800.00, 2),
('Strap Replacement', 'Replace worn or damaged watch straps', 600.00, 1),
('Movement Repair', 'Repair or replace watch movement mechanism', 2500.00, 3),
('Crystal Replacement', 'Replace scratched or cracked watch crystal', 1200.00, 2),
('Water Resistance Testing', 'Test and restore water resistance', 700.00, 1),
('Complete Overhaul', 'Full service including cleaning, oiling, and adjustment', 3500.00, 4);

-- ============================================
-- DEFAULT DATA: Technicians
-- ============================================
INSERT INTO technicians (full_name, email, contact_number, specialization) VALUES 
('Juan Dela Cruz', 'juan.delacruz@mcrepair.com', '09171234567', 'Mechanical Watches'),
('Maria Santos', 'maria.santos@mcrepair.com', '09181234567', 'Quartz Watches'),
('Pedro Reyes', 'pedro.reyes@mcrepair.com', '09191234567', 'Luxury Watches');

-- ============================================
-- COMPLETE!
-- ============================================
-- Database setup complete!
-- 
-- DEFAULT ADMIN LOGIN:
-- Username: admin
-- Password: password
-- 
-- Access your system at:
-- http://localhost/repair_shop
-- ============================================
