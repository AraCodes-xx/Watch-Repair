-- Migration: Booking System Improvements
-- Run this if you already have the database created
-- This adds: watch_types table, booking_services table, and updates bookings table

USE watch_repair_shop;

-- Create Watch Types Table
CREATE TABLE IF NOT EXISTS watch_types (
    watch_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert Default Watch Types
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

-- Add watch_type_id column to bookings table (if not exists)
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS watch_type_id INT AFTER timeslot_id,
ADD FOREIGN KEY IF NOT EXISTS (watch_type_id) REFERENCES watch_types(watch_type_id) ON DELETE SET NULL;

-- Create Booking Services Junction Table (for multiple services per booking)
CREATE TABLE IF NOT EXISTS booking_services (
    booking_service_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    service_id INT NOT NULL,
    service_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
);

-- Migrate existing watch_type data (if column exists as VARCHAR)
-- This converts old watch_type VARCHAR to new watch_type_id INT
UPDATE bookings b
LEFT JOIN watch_types wt ON b.watch_type = wt.type_name
SET b.watch_type_id = wt.watch_type_id
WHERE b.watch_type IS NOT NULL AND b.watch_type_id IS NULL;

-- Update message
SELECT 'Migration completed: watch_types table, booking_services table, and watch_type_id column added' as message;
