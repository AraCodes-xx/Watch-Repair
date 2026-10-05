-- Add payment_status column to bookings table
ALTER TABLE bookings 
ADD COLUMN IF NOT EXISTS payment_status ENUM('Down Payment Only', 'Fully Paid') DEFAULT 'Down Payment Only' AFTER status;

-- Update message
SELECT 'Payment status column added to bookings table' as message;
