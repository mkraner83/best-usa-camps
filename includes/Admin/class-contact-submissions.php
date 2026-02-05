<?php
/**
 * Contact Form Submissions Admin
 *
 * @package CreativeDBS\CampMgmt
 */

namespace CreativeDBS\CampMgmt\Admin;

defined( 'ABSPATH' ) || exit;

class Contact_Submissions {
	
	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'cdbs_contact_submissions';
		
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 25 );
		add_action( 'admin_post_delete_contact_submission', [ $this, 'handle_delete' ] );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'creativedbs-camp-mgmt',
			'Contact Submissions',
			'Contact Submissions',
			'manage_options',
			'cdbs-contact-submissions',
			[ $this, 'render_submissions_page' ]
		);
	}

	/**
	 * Save contact submission to database
	 */
	public static function save_submission( $first_name, $last_name, $email, $phone, $message, $status = 'success', $error_message = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'cdbs_contact_submissions';
		
		$wpdb->insert(
			$table_name,
			[
				'first_name' => $first_name,
				'last_name' => $last_name,
				'email' => $email,
				'phone' => $phone,
				'message' => $message,
				'status' => $status,
				'error_message' => $error_message,
			],
			[
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			]
		);
	}

	/**
	 * Handle delete action
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Unauthorized' );
		}

		check_admin_referer( 'delete_contact_submission' );

		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		
		if ( $id > 0 ) {
			global $wpdb;
			$wpdb->delete(
				$this->table_name,
				[ 'id' => $id ],
				[ '%d' ]
			);
		}

		wp_redirect( admin_url( 'admin.php?page=cdbs-contact-submissions&deleted=1' ) );
		exit;
	}

	/**
	 * Render submissions page
	 */
	public function render_submissions_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;

		// Get filter status
		$filter_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

		// Pagination
		$per_page = 20;
		$current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$offset = ( $current_page - 1 ) * $per_page;

		// Build query with optional status filter
		$where = '';
		if ( $filter_status ) {
			$where = $wpdb->prepare( " WHERE status = %s", $filter_status );
		}

		// Get total count
		$total_items = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table_name}{$where}" );
		$total_pages = ceil( $total_items / $per_page );

		// Get status counts for filters
		$status_counts = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$this->table_name} GROUP BY status",
			OBJECT_K
		);

		// Get submissions
		$submissions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->table_name}{$where} ORDER BY submitted_at DESC LIMIT %d OFFSET %d",
				$per_page,
				$offset
			)
		);

		?>
		<div class="wrap">
			<h1>Contact Form Submissions</h1>

			<?php if ( isset( $_GET['deleted'] ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>Submission deleted successfully.</p>
				</div>
			<?php endif; ?>

			<!-- Status Filter Tabs -->
			<ul class="subsubsub">
				<li>
					<a href="<?php echo admin_url( 'admin.php?page=cdbs-contact-submissions' ); ?>" class="<?php echo empty( $filter_status ) ? 'current' : ''; ?>">
						All <span class="count">(<?php echo $total_items; ?>)</span>
					</a> |
				</li>
				<li>
					<a href="<?php echo admin_url( 'admin.php?page=cdbs-contact-submissions&status=success' ); ?>" class="<?php echo $filter_status === 'success' ? 'current' : ''; ?>">
						Successful <span class="count">(<?php echo isset( $status_counts['success'] ) ? $status_counts['success']->count : 0; ?>)</span>
					</a> |
				</li>
				<li>
					<a href="<?php echo admin_url( 'admin.php?page=cdbs-contact-submissions&status=validation_failed' ); ?>" class="<?php echo $filter_status === 'validation_failed' ? 'current' : ''; ?>">
						Validation Failed <span class="count">(<?php echo isset( $status_counts['validation_failed'] ) ? $status_counts['validation_failed']->count : 0; ?>)</span>
					</a> |
				</li>
				<li>
					<a href="<?php echo admin_url( 'admin.php?page=cdbs-contact-submissions&status=email_failed' ); ?>" class="<?php echo $filter_status === 'email_failed' ? 'current' : ''; ?>">
						Email Failed <span class="count">(<?php echo isset( $status_counts['email_failed'] ) ? $status_counts['email_failed']->count : 0; ?>)</span>
					</a> |
				</li>
				<li>
					<a href="<?php echo admin_url( 'admin.php?page=cdbs-contact-submissions&status=partial_success' ); ?>" class="<?php echo $filter_status === 'partial_success' ? 'current' : ''; ?>">
						Partial Success <span class="count">(<?php echo isset( $status_counts['partial_success'] ) ? $status_counts['partial_success']->count : 0; ?>)</span>
					</a>
				</li>
			</ul>
			<br class="clear">

			<?php if ( empty( $submissions ) ) : ?>
				<p>No contact submissions found.</p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" style="width: 120px;">Date</th>
							<th scope="col">Name</th>
							<th scope="col">Email</th>
							<th scope="col">Phone</th>
							<th scope="col">Message</th>
							<th scope="col" style="width: 100px;">Status</th>
							<th scope="col" style="width: 80px;">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $submissions as $submission ) : ?>
							<tr>
								<td><?php echo esc_html( date( 'M j, Y g:i a', strtotime( $submission->submitted_at ) ) ); ?></td>
								<td><?php echo esc_html( $submission->first_name . ' ' . $submission->last_name ); ?></td>
								<td><a href="mailto:<?php echo esc_attr( $submission->email ); ?>"><?php echo esc_html( $submission->email ); ?></a></td>
								<td><?php echo esc_html( $submission->phone ?: '—' ); ?></td>
								<td style="max-width: 300px;">
									<?php 
									$message = esc_html( $submission->message );
									if ( strlen( $message ) > 100 ) {
										echo '<details><summary>' . substr( $message, 0, 100 ) . '... <span style="color: #2271b1;">Read more</span></summary><div style="margin-top: 8px; padding: 8px; background: #f0f0f1; border-radius: 3px;">' . $message . '</div></details>';
									} else {
										echo $message;
									}
									?>
								</td>
								<td>
									<?php
									$status_class = '';
									$status_label = '';
									switch ( $submission->status ) {
										case 'success':
											$status_class = 'status-success';
											$status_label = '✓ Success';
											break;
										case 'validation_failed':
											$status_class = 'status-error';
											$status_label = '✗ Validation Failed';
											break;
										case 'email_failed':
											$status_class = 'status-error';
											$status_label = '✗ Email Failed';
											break;
										case 'partial_success':
											$status_class = 'status-warning';
											$status_label = '⚠ Partial';
											break;
										default:
											$status_label = esc_html( $submission->status );
									}
									?>
									<span class="<?php echo $status_class; ?>" style="display: inline-block; padding: 3px 8px; border-radius: 3px; font-size: 12px; <?php 
										if ( $status_class === 'status-success' ) echo 'background: #d4edda; color: #155724;';
										elseif ( $status_class === 'status-error' ) echo 'background: #f8d7da; color: #721c24;';
										elseif ( $status_class === 'status-warning' ) echo 'background: #fff3cd; color: #856404;';
									?>">
										<?php echo $status_label; ?>
									</span>
									<?php if ( $submission->error_message ) : ?>
										<br><small style="color: #d63638;" title="<?php echo esc_attr( $submission->error_message ); ?>">
											<?php echo esc_html( strlen( $submission->error_message ) > 30 ? substr( $submission->error_message, 0, 30 ) . '...' : $submission->error_message ); ?>
										</small>
									<?php endif; ?>
								</td>
								<td>
									<a 
										href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=delete_contact_submission&id=' . $submission->id ), 'delete_contact_submission' ); ?>"
										class="button button-small"
										onclick="return confirm('Are you sure you want to delete this submission?');"
									>
										Delete
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $total_pages > 1 ) : ?>
					<div class="tablenav">
						<div class="tablenav-pages">
							<?php
							$base_url = add_query_arg( 'page', 'cdbs-contact-submissions', admin_url( 'admin.php' ) );
							if ( $filter_status ) {
								$base_url = add_query_arg( 'status', $filter_status, $base_url );
							}
							echo paginate_links( [
								'base' => add_query_arg( 'paged', '%#%', $base_url ),
								'format' => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total' => $total_pages,
								'current' => $current_page,
							] );
							?>
						</div>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
