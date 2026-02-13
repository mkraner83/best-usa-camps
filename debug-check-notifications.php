<?php
/**
 * Debug script to check daily notification cron status
 * 
 * IMPORTANT: To find this file's URL on your site:
 * 1. Go to WordPress admin → Plugins
 * 2. Find "Creative DBS Camp Management" plugin
 * 3. Click "View details" and check the plugin folder name in the URL
 * 4. The URL will be: https://yoursite.com/wp-content/plugins/[FOLDER-NAME]/debug-check-notifications.php
 * 
 * Common folder names: creativedbs-camp-mgmt, best-usa-camps, camp-management
 */

// Try to load WordPress from various possible locations
$wp_load_paths = [
    __DIR__ . '/../../../wp-load.php',  // Standard plugin location
    __DIR__ . '/../../../../wp-load.php',  // Custom location
];

$wp_loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $wp_loaded = true;
        break;
    }
}

if (!$wp_loaded) {
    die('<h1>Error: Cannot find WordPress</h1><p>Please contact your administrator.</p>');
}

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
	die( 'You must be an administrator to access this page.' );
}

global $wpdb;

echo '<h1>Daily Notification System - Debug Report</h1>';
echo '<p>Generated: ' . date( 'Y-m-d H:i:s' ) . ' (Server Time)</p>';
echo '<hr>';

// 1. Check if cron is scheduled
echo '<h2>1. Cron Schedule Status</h2>';
$next_run = wp_next_scheduled( 'camp_send_daily_notifications' );

if ( $next_run ) {
	$timezone = new DateTimeZone( 'Europe/Paris' );
	$server_time = new DateTime( 'now' );
	$cet_time = new DateTime( 'now', $timezone );
	$next_run_date = new DateTime( '@' . $next_run, $timezone );
	
	echo '<p style="color: green;">✓ <strong>Cron is scheduled!</strong></p>';
	echo '<ul>';
	echo '<li><strong>Next run:</strong> ' . $next_run_date->format( 'Y-m-d H:i:s' ) . ' CET</li>';
	echo '<li><strong>Current CET time:</strong> ' . $cet_time->format( 'Y-m-d H:i:s' ) . '</li>';
	echo '<li><strong>Server time:</strong> ' . $server_time->format( 'Y-m-d H:i:s' ) . ' (UTC' . $server_time->format( 'P' ) . ')</li>';
	echo '<li><strong>Time until next run:</strong> ' . human_time_diff( time(), $next_run ) . '</li>';
	echo '</ul>';
} else {
	echo '<p style="color: red;">✗ <strong>Cron is NOT scheduled!</strong></p>';
	echo '<p><strong>Fix:</strong> The cron needs to be rescheduled. This should happen automatically on next page load.</p>';
	echo '<form method="post"><button type="submit" name="reschedule_cron" style="padding: 10px 20px; background: #0073aa; color: white; border: none; cursor: pointer; border-radius: 4px;">Reschedule Cron Now</button></form>';
}

// 2. Check notification queue
echo '<hr>';
echo '<h2>2. Notification Queue</h2>';
$table_name = $wpdb->prefix . 'camp_notification_queue';
$queue_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );
$today_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE DATE(update_time) = CURDATE()" );

echo '<p><strong>Total queued notifications:</strong> ' . $queue_count . '</p>';
echo '<p><strong>Today\'s notifications:</strong> ' . $today_count . '</p>';

if ( $today_count > 0 ) {
	echo '<h3>Today\'s Queued Updates:</h3>';
	$today_notifications = $wpdb->get_results(
		"SELECT * FROM {$table_name} WHERE DATE(update_time) = CURDATE() ORDER BY update_time DESC"
	);
	
	echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
	echo '<tr style="background: #f0f0f0;"><th>Camp ID</th><th>Camp Name</th><th>Update Time</th><th>Photos</th><th>Logo</th></tr>';
	foreach ( $today_notifications as $notif ) {
		echo '<tr>';
		echo '<td>' . esc_html( $notif->camp_id ) . '</td>';
		echo '<td>' . esc_html( $notif->camp_name ) . '</td>';
		echo '<td>' . esc_html( $notif->update_time ) . '</td>';
		echo '<td>' . ( $notif->photos_uploaded ? '✓' : '—' ) . '</td>';
		echo '<td>' . ( $notif->logo_uploaded ? '✓' : '—' ) . '</td>';
		echo '</tr>';
	}
	echo '</table>';
}

// 3. Test email sending (manual trigger)
echo '<hr>';
echo '<h2>3. Manual Email Actions</h2>';
echo '<p><strong>You have ' . $queue_count . ' notifications in the queue.</strong></p>';

if ( $today_count > 0 ) {
	echo '<p>Send today\'s notifications (' . $today_count . ' updates):</p>';
	echo '<form method="post" style="display: inline-block; margin-right: 10px;">';
	echo '<button type="submit" name="test_send_notifications" style="padding: 10px 20px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 4px;">Send Today\'s Notifications</button>';
	echo '</form>';
} else {
	echo '<p style="color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px;">⚠️ No notifications from today to send.</p>';
}

if ( $queue_count > $today_count ) {
	$old_count = $queue_count - $today_count;
	echo '<p style="margin-top: 15px;">You have <strong>' . $old_count . ' old notifications</strong> from previous days:</p>';
	echo '<form method="post" style="display: inline-block; margin-right: 10px;">';
	echo '<button type="submit" name="send_all_notifications" onclick="return confirm(\'Send emails for all ' . $queue_count . ' pending notifications?\');" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px;">Send ALL Pending Notifications</button>';
	echo '</form>';
	echo '<form method="post" style="display: inline-block;">';
	echo '<button type="submit" name="clear_old_queue" onclick="return confirm(\'Delete all old notifications without sending?\');" style="padding: 10px 20px; background: #dc3545; color: white; border: none; cursor: pointer; border-radius: 4px;">Clear Old Queue</button>';
	echo '</form>';
	echo '<p style="font-size: 12px; color: #666; margin-top: 10px;"><em>Old notifications can accumulate when the 8 PM cron doesn\'t fire due to low site traffic.</em></p>';
}

// 4. WordPress Cron info
echo '<hr>';
echo '<h2>4. WordPress Cron Info</h2>';
echo '<p><strong>Note:</strong> WordPress cron only runs when someone visits your site. If you have low traffic at 8 PM, the emails might be delayed.</p>';
echo '<p><strong>Recommendation:</strong> Set up a real server cron job to hit wp-cron.php every hour:</p>';
echo '<pre style="background: #f0f0f0; padding: 10px; border-radius: 4px;">0 * * * * wget -q -O - ' . site_url( 'wp-cron.php' ) . ' >/dev/null 2>&1</pre>';

// Handle actions
if ( isset( $_POST['reschedule_cron'] ) ) {
	wp_clear_scheduled_hook( 'camp_send_daily_notifications' );
	
	$timezone = new DateTimeZone( 'Europe/Paris' );
	$now = new DateTime( 'now', $timezone );
	$target = new DateTime( 'today 20:00', $timezone );
	
	if ( $now > $target ) {
		$target->modify( '+1 day' );
	}
	
	$timestamp = $target->getTimestamp();
	wp_schedule_event( $timestamp, 'daily', 'camp_send_daily_notifications' );
	
	echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 4px;">';
	echo '<strong>✓ Cron rescheduled successfully!</strong>';
	echo '</div>';
	echo '<script>window.location.reload();</script>';
}

if ( isset( $_POST['test_send_notifications'] ) ) {
	echo '<div style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; margin: 20px 0; border-radius: 4px;">';
	echo '<strong>⏳ Sending today\'s notifications...</strong>';
	echo '</div>';
	
	do_action( 'camp_send_daily_notifications' );
	
	echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 4px;">';
	echo '<strong>✓ Notifications sent! Check your admin email inbox.</strong>';
	echo '</div>';
	echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
}

if ( isset( $_POST['send_all_notifications'] ) ) {
	echo '<div style="background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; margin: 20px 0; border-radius: 4px;">';
	echo '<strong>⏳ Sending ALL pending notifications...</strong>';
	echo '</div>';
	
	// Get all notifications grouped by camp
	$all_notifications = $wpdb->get_results(
		"SELECT * FROM {$table_name} ORDER BY camp_id, update_time ASC"
	);
	
	if ( !empty( $all_notifications ) ) {
		// Group by camp
		$grouped_by_camp = [];
		foreach ( $all_notifications as $notification ) {
			$grouped_by_camp[ $notification->camp_id ][] = $notification;
		}
		
		$admin_email = get_option( 'admin_email' );
		$sent_count = 0;
		
		// Send one email per camp
		foreach ( $grouped_by_camp as $camp_id => $camp_notifications ) {
			$camp_name = $camp_notifications[0]->camp_name;
			$update_count = count( $camp_notifications );
			
			$subject = "Pending Updates Summary: {$camp_name} ({$update_count} update" . ( $update_count > 1 ? 's' : '' ) . ")";
			
			// Build simple email
			$message = '<h2>' . esc_html( $camp_name ) . '</h2>';
			$message .= '<p>This camp has ' . $update_count . ' pending notification(s):</p>';
			$message .= '<ul>';
			foreach ( $camp_notifications as $notif ) {
				$message .= '<li>' . date( 'M j, Y g:i A', strtotime( $notif->update_time ) ) . ' - Profile updated</li>';
			}
			$message .= '</ul>';
			
			$headers = [
				'Content-Type: text/html; charset=UTF-8',
				'From: Best USA Summer Camps <noreply@bestusacamps.com>',
			];
			
			if ( wp_mail( $admin_email, $subject, $message, $headers ) ) {
				$sent_count++;
			}
		}
		
		// Clear all notifications
		$wpdb->query( "DELETE FROM {$table_name}" );
		
		echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 4px;">';
		echo '<strong>✓ Sent ' . $sent_count . ' email(s) and cleared queue! Check your admin email inbox.</strong>';
		echo '</div>';
	} else {
		echo '<div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 4px;">';
		echo '<strong>No notifications to send.</strong>';
		echo '</div>';
	}
	
	echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
}

if ( isset( $_POST['clear_old_queue'] ) ) {
	$deleted = $wpdb->query(
		"DELETE FROM {$table_name} WHERE DATE(update_time) < CURDATE()"
	);
	
	echo '<div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px 0; border-radius: 4px;">';
	echo '<strong>✓ Cleared ' . $deleted . ' old notification(s) from the queue.</strong>';
	echo '</div>';
	echo '<script>setTimeout(function(){ window.location.reload(); }, 1500);</script>';
}

echo '<hr>';
echo '<p><a href="' . admin_url( 'admin.php?page=creativedbs-camp-mgmt' ) . '" style="padding: 10px 20px; background: #0073aa; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">← Back to Camps</a></p>';
