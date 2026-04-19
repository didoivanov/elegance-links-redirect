<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $links */
/** @var string $home */
/** @var array $rule_counts */

$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$rule_counts = isset( $rule_counts ) ? (array) $rule_counts : array();
?>
<div class="wrap elr-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Elegance Links', 'elegance-links-redirect' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'elegance-links-redirect' ); ?></a>
	<hr class="wp-header-end" />
	<?php ELR_Admin::render_notice(); ?>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="elr-search-form">
		<input type="hidden" name="page" value="elr-links" />
		<p class="search-box">
			<label class="screen-reader-text" for="elr-search-input"><?php esc_html_e( 'Search Links', 'elegance-links-redirect' ); ?></label>
			<input type="search" id="elr-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Slug, title or target URL', 'elegance-links-redirect' ); ?>" />
			<?php submit_button( __( 'Search Links', 'elegance-links-redirect' ), '', '', false ); ?>
			<?php if ( '' !== $search ) : ?>
				<a class="button-link" href="<?php echo esc_url( admin_url( 'admin.php?page=elr-links' ) ); ?>"><?php esc_html_e( 'Clear', 'elegance-links-redirect' ); ?></a>
			<?php endif; ?>
		</p>
	</form>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'elr_bulk_links' ); ?>
		<input type="hidden" name="action" value="elr_bulk_links" />

		<div class="tablenav top">
			<div class="alignleft actions bulkactions">
				<label for="elr-bulk-action" class="screen-reader-text"><?php esc_html_e( 'Bulk actions', 'elegance-links-redirect' ); ?></label>
				<select name="bulk_action" id="elr-bulk-action">
					<option value=""><?php esc_html_e( 'Bulk actions', 'elegance-links-redirect' ); ?></option>
					<option value="delete"><?php esc_html_e( 'Delete', 'elegance-links-redirect' ); ?></option>
				</select>
				<?php submit_button( __( 'Apply', 'elegance-links-redirect' ), 'action', '', false, array( 'onclick' => 'return confirm(\'' . esc_js( __( 'Apply bulk action to the selected links? Deleted links and their click data cannot be recovered.', 'elegance-links-redirect' ) ) . '\');' ) ); ?>
			</div>
		</div>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="elr-check-all" /></td>
					<th><?php esc_html_e( 'Title', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Pretty URL', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Target', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Type', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Status', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'elegance-links-redirect' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $links ) ) : ?>
				<tr><td colspan="8"><?php echo '' !== $search ? esc_html__( 'No links match your search.', 'elegance-links-redirect' ) : esc_html__( 'No links yet. Create your first pretty link!', 'elegance-links-redirect' ); ?></td></tr>
			<?php else : ?>
				<?php foreach ( $links as $link ) :
					$pretty     = esc_url( $home . $link->slug );
					$edit_url   = admin_url( 'admin.php?page=elr-link-edit&link_id=' . (int) $link->id );
					$stats_url  = admin_url( 'admin.php?page=elr-link-stats&link_id=' . (int) $link->id );
					$delete_url = wp_nonce_url(
						admin_url( 'admin-post.php?action=elr_delete_link&link_id=' . (int) $link->id ),
						'elr_delete_link'
					);
					?>
					<?php
					$rule_n     = isset( $rule_counts[ (int) $link->id ] ) ? (int) $rule_counts[ (int) $link->id ] : 0;
					$rule_label = sprintf(
						/* translators: %d: number of active dynamic redirect rules. */
						_n( '%d dynamic redirect rule', '%d dynamic redirect rules', max( 1, $rule_n ), 'elegance-links-redirect' ),
						$rule_n
					);
					?>
					<tr>
						<th scope="row" class="check-column"><input type="checkbox" name="link_ids[]" value="<?php echo (int) $link->id; ?>" /></th>
						<td>
							<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $link->title ? $link->title : $link->slug ); ?></a></strong>
							<?php if ( $rule_n > 0 ) : ?>
								<a class="elr-rule-badge" href="<?php echo esc_url( $edit_url . '#elr-rule-form' ); ?>" title="<?php echo esc_attr( $rule_label ); ?>">
									<span class="dashicons dashicons-randomize" aria-hidden="true"></span><?php echo (int) $rule_n; ?>
								</a>
							<?php endif; ?>
						</td>
						<td><a href="<?php echo $pretty; ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $pretty ); ?></a></td>
						<td class="elr-truncate"><a href="<?php echo esc_url( $link->target_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link->target_url ); ?></a></td>
						<td><?php echo (int) $link->redirect_type; ?></td>
						<td><?php echo (int) $link->hits; ?></td>
						<td><?php echo $link->is_active ? esc_html__( 'Active', 'elegance-links-redirect' ) : esc_html__( 'Disabled', 'elegance-links-redirect' ); ?></td>
						<td>
							<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'elegance-links-redirect' ); ?></a> |
							<a href="<?php echo esc_url( $stats_url ); ?>"><?php esc_html_e( 'Stats', 'elegance-links-redirect' ); ?></a> |
							<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this link and all its click data?', 'elegance-links-redirect' ) ); ?>');"><?php esc_html_e( 'Delete', 'elegance-links-redirect' ); ?></a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
	</form>
</div>
<script>
(function () {
	var master = document.getElementById('elr-check-all');
	if (!master) { return; }
	master.addEventListener('change', function () {
		var checks = document.querySelectorAll('input[name="link_ids[]"]');
		for (var i = 0; i < checks.length; i++) { checks[i].checked = master.checked; }
	});
})();
</script>
