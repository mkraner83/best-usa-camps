<?php
/**
 * Debug script to check logo values in database
 * Place this file in WordPress root and access via browser
 */

// Load WordPress
require_once( 'wp-load.php' );

// Check if user has admin capabilities
if ( ! current_user_can( 'manage_options' ) ) {
	die( 'Access denied. Please login as administrator.' );
}

global $wpdb;

// Get all camps with their logos
$camps = $wpdb->get_results(
	"SELECT id, camp_name, logo, user_id FROM {$wpdb->prefix}camp_management ORDER BY id",
	ARRAY_A
);

?>
<!DOCTYPE html>
<html>
<head>
	<title>Logo Debug Check</title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; }
		table { border-collapse: collapse; width: 100%; }
		th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
		th { background-color: #4CAF50; color: white; }
		tr:nth-child(even) { background-color: #f2f2f2; }
		.logo-img { max-width: 100px; max-height: 100px; }
		.error { color: red; }
		.success { color: green; }
	</style>
</head>
<body>
	<h1>Camp Logo Database Check</h1>
	<p>Total camps: <?php echo count( $camps ); ?></p>
	
	<table>
		<thead>
			<tr>
				<th>ID</th>
				<th>Camp Name</th>
				<th>User ID</th>
				<th>Logo URL (Database)</th>
				<th>Logo Preview</th>
				<th>File Exists</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $camps as $camp ) : ?>
				<?php
					$logo_url = $camp['logo'];
					$file_exists = false;
					
					if ( ! empty( $logo_url ) ) {
						// Convert URL to file path
						$upload_dir = wp_upload_dir();
						$file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $logo_url );
						$file_exists = file_exists( $file_path );
					}
				?>
				<tr>
					<td><?php echo esc_html( $camp['id'] ); ?></td>
					<td><?php echo esc_html( $camp['camp_name'] ); ?></td>
					<td><?php echo esc_html( $camp['user_id'] ); ?></td>
					<td>
						<?php if ( ! empty( $logo_url ) ) : ?>
							<a href="<?php echo esc_url( $logo_url ); ?>" target="_blank">
								<?php echo esc_html( basename( $logo_url ) ); ?>
							</a>
							<br>
							<small style="color: #666;"><?php echo esc_html( $logo_url ); ?></small>
						<?php else : ?>
							<span class="error">No logo</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! empty( $logo_url ) && pathinfo( $logo_url, PATHINFO_EXTENSION ) !== 'pdf' ) : ?>
							<img src="<?php echo esc_url( $logo_url ); ?>" class="logo-img" alt="Logo">
						<?php elseif ( ! empty( $logo_url ) ) : ?>
							<span>PDF File</span>
						<?php else : ?>
							<span class="error">-</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! empty( $logo_url ) ) : ?>
							<?php if ( $file_exists ) : ?>
								<span class="success">✓ Yes</span>
							<?php else : ?>
								<span class="error">✗ No</span>
							<?php endif; ?>
						<?php else : ?>
							<span>-</span>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	
	<h2>Database Query Check</h2>
	<pre><?php
		// Show raw SQL query
		$query = "SELECT id, camp_name, logo FROM {$wpdb->prefix}camp_management ORDER BY id LIMIT 5";
		echo "Query: " . $query . "\n\n";
		echo "Results:\n";
		print_r( $wpdb->get_results( $query, ARRAY_A ) );
	?></pre>
</body>
</html>
