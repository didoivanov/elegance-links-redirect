<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $links */
/** @var string $home */
?>
<div class="wrap elr-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Elegance Links', 'elegance-links-redirect' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-edit' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'elegance-links-redirect' ); ?></a>
	<hr class="wp-header-end" />
	<?php ELR_Admin::render_notice(); ?>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
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
			<tr><td colspan="7"><?php esc_html_e( 'No links yet. Create your first pretty link!', 'elegance-links-redirect' ); ?></td></tr>
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
				<tr>
					<td><strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $link->title ? $link->title : $link->slug ); ?></a></strong></td>
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
</div>
