-- Migration: Add watch_type column to bookings table
-- Run this if you already have the database created

USE watch_repair_shop;

-- Add watch_type column to bookings table
ALTER TABLE bookings 
ADD COLUMN watch_type VARCHAR(50) AFTER timeslot_id;

-- Update message
SELECT 'Migration completed: watch_type column added to bookings table' as message;
