-- Add last_edited column to wp_camp_management table
-- Run this SQL in phpMyAdmin or your database tool

-- Check if column exists (this will show an error if it already exists, which is fine)
ALTER TABLE wp_camp_management 
ADD COLUMN last_edited DATETIME NULL AFTER updated_at;

-- Verify the column was added
SHOW COLUMNS FROM wp_camp_management LIKE 'last_edited';
