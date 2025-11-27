<?php
/**
 * Debug script to check existing camp submissions and Ninja Forms entries.
 * 
 * Upload this file to your WordPress root and access it via:
 * yourdomain.com/debug-check-submissions.php
 */

// Load WordPress
require_once __DIR__ . '/../../wp-load.php';

// Security check
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied' );
}

global $wpdb;

echo '<h1>Debug: Camp Submissions & Ninja Forms Data</h1>';
echo '<style>table { border-collapse: collapse; width: 100%; margin: 20px 0; } th, td { border: 1px solid #ddd; padding: 8px; text-align: left; } th { background: #f2f2f2; }</style>';

// Check camp_management table
echo '<h2>1. Camp Management Table (wp_camp_management)</h2>';
$camps_table = $wpdb->prefix . 'camp_management';
$camps = $wpdb->get_results( "SELECT id, ninja_entry_id, camp_name, email, created_at FROM {$camps_table} ORDER BY id DESC LIMIT 10" );

if ( $camps ) {
    echo '<table>';
    echo '<tr><th>ID</th><th>Ninja Entry ID</th><th>Camp Name</th><th>Email</th><th>Created At</th></tr>';
    foreach ( $camps as $camp ) {
        echo '<tr>';
        echo '<td>' . esc_html( $camp->id ) . '</td>';
        echo '<td>' . esc_html( $camp->ninja_entry_id ?: 'NULL' ) . '</td>';
        echo '<td>' . esc_html( $camp->camp_name ) . '</td>';
        echo '<td>' . esc_html( $camp->email ) . '</td>';
        echo '<td>' . esc_html( $camp->created_at ) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>No camps found.</p>';
}

// Check Ninja Forms submissions (nf3_objects)
echo '<h2>2. Ninja Forms Submissions (wp_nf3_objects)</h2>';
$nf_objects = $wpdb->prefix . 'nf3_objects';
if ( $wpdb->get_var( "SHOW TABLES LIKE '{$nf_objects}'" ) ) {
    $submissions = $wpdb->get_results( "SELECT id, type, parent_id, created_at FROM {$nf_objects} WHERE type = 'submission' ORDER BY id DESC LIMIT 10" );
    
    if ( $submissions ) {
        echo '<table>';
        echo '<tr><th>Submission ID</th><th>Type</th><th>Form ID (parent_id)</th><th>Created At</th></tr>';
        foreach ( $submissions as $sub ) {
            echo '<tr>';
            echo '<td>' . esc_html( $sub->id ) . '</td>';
            echo '<td>' . esc_html( $sub->type ) . '</td>';
            echo '<td>' . esc_html( $sub->parent_id ) . '</td>';
            echo '<td>' . esc_html( $sub->created_at ) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No submissions found.</p>';
    }
} else {
    echo '<p>Table does not exist.</p>';
}

// Check WordPress users with Camp role
echo '<h2>3. WordPress Users with "Camp" Role</h2>';
$camp_users = get_users( [ 'role' => 'camp', 'number' => 10 ] );

if ( $camp_users ) {
    echo '<table>';
    echo '<tr><th>User ID</th><th>Username</th><th>Email</th><th>Camp Name (meta)</th><th>NF Entry ID (meta)</th><th>Registered</th></tr>';
    foreach ( $camp_users as $user ) {
        $camp_name = get_user_meta( $user->ID, 'camp_name', true );
        $nf_entry = get_user_meta( $user->ID, 'ninja_forms_entry_id', true );
        echo '<tr>';
        echo '<td>' . esc_html( $user->ID ) . '</td>';
        echo '<td>' . esc_html( $user->user_login ) . '</td>';
        echo '<td>' . esc_html( $user->user_email ) . '</td>';
        echo '<td>' . esc_html( $camp_name ?: 'N/A' ) . '</td>';
        echo '<td>' . esc_html( $nf_entry ?: 'N/A' ) . '</td>';
        echo '<td>' . esc_html( $user->user_registered ) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>No camp users found.</p>';
}

// Check all available Ninja Forms tables
echo '<h2>4. Available Ninja Forms Tables</h2>';
$nf_tables = $wpdb->get_results( "SHOW TABLES LIKE '{$wpdb->prefix}nf%'" );
if ( $nf_tables ) {
    echo '<ul>';
    foreach ( $nf_tables as $table ) {
        $table_name = array_values( (array) $table )[0];
        echo '<li>' . esc_html( $table_name ) . '</li>';
    }
    echo '</ul>';
} else {
    echo '<p>No Ninja Forms tables found.</p>';
}

echo '<hr><p><strong>Instructions:</strong> Delete this file after reviewing the data for security.</p>';
