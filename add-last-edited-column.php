<?php
/**
 * One-time script to add last_edited column to camps table
 * Run this via: php add-last-edited-column.php
 */

// Load WordPress
require_once(__DIR__ . '/../../../wp-load.php');

global $wpdb;

$table_name = $wpdb->prefix . 'camp_management';

// Check if column exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'last_edited'");

if (empty($column_exists)) {
    echo "Adding last_edited column to {$table_name}...\n";
    $result = $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN last_edited DATETIME NULL AFTER updated_at");
    
    if ($result === false) {
        echo "ERROR: Failed to add column. SQL Error: " . $wpdb->last_error . "\n";
    } else {
        echo "SUCCESS: Column added successfully!\n";
    }
} else {
    echo "Column 'last_edited' already exists in {$table_name}\n";
}

// Show current table structure
echo "\nCurrent table columns:\n";
$columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}");
foreach ($columns as $column) {
    echo "- {$column->Field} ({$column->Type})\n";
}
