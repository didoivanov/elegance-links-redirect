<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var object|null $link */
/** @var array $clicks */
/** @var array $summary */
/** @var string $home */
/** @var array $links */

if ( empty( $link ) ) :
	?>
	<div class="wrap elr-wrap">
		<h1><?php esc_html_e( 'Link Stats', 'elegance-links-redirect' ); ?></h1>
		<p><?php esc_html_e( 'Select a link to view its stats.', 'elegance-links-redirect' ); ?></p>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Slug', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Title', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Status', 'elegance-links-redirect' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $links ) ) : ?>
				<tr><td colspan="5"><?php esc_html_e( 'No links yet.', 'elegance-links-redirect' ); ?></td></tr>
			<?php else : foreach ( $links as $row ) : ?>
				<tr>
					<td><code><?php echo esc_html( $home . $row->slug ); ?></code></td>
					<td><?php echo esc_html( $row->title ); ?></td>
					<td><?php echo (int) $row->hits; ?></td>
					<td><?php echo $row->is_active ? esc_html__( 'Active', 'elegance-links-redirect' ) : esc_html__( 'Disabled', 'elegance-links-redirect' ); ?></td>
					<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-stats&link_id=' . (int) $row->id ) ); ?>"><?php esc_html_e( 'View Stats', 'elegance-links-redirect' ); ?></a></td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>
	</div>
	<?php
	return;
endif;
?>
<div class="wrap elr-wrap">
	<h1><?php printf( esc_html__( 'Stats: %s', 'elegance-links-redirect' ), esc_html( $link->title ? $link->title : $link->slug ) ); ?></h1>
	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-links' ) ); ?>">&larr; <?php esc_html_e( 'Back to links', 'elegance-links-redirect' ); ?></a> |
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-edit&link_id=' . (int) $link->id ) ); ?>"><?php esc_html_e( 'Edit link', 'elegance-links-redirect' ); ?></a>
	</p>
	<p><?php esc_html_e( 'Pretty URL:', 'elegance-links-redirect' ); ?> <code><?php echo esc_html( $home . $link->slug ); ?></code></p>
	<p><?php esc_html_e( 'Total hits:', 'elegance-links-redirect' ); ?> <strong><?php echo (int) $link->hits; ?></strong></p>

	<div class="elr-stats-grid">
		<div class="elr-stats-card">
			<h2><?php esc_html_e( 'Top Countries', 'elegance-links-redirect' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Country', 'elegance-links-redirect' ); ?></th><th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( (array) $summary['countries'] as $row ) : ?>
					<tr><td><?php echo esc_html( $row->country_name . ( $row->country ? ' (' . $row->country . ')' : '' ) ); ?></td><td><?php echo (int) $row->hits; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="elr-stats-card">
			<h2><?php esc_html_e( 'Devices', 'elegance-links-redirect' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Device', 'elegance-links-redirect' ); ?></th><th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( (array) $summary['devices'] as $row ) : ?>
					<tr><td><?php echo esc_html( $row->device_type ? $row->device_type : '—' ); ?></td><td><?php echo (int) $row->hits; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<div class="elr-stats-card">
			<h2><?php esc_html_e( 'Browsers', 'elegance-links-redirect' ); ?></h2>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Browser', 'elegance-links-redirect' ); ?></th><th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( (array) $summary['browsers'] as $row ) : ?>
					<tr><td><?php echo esc_html( $row->browser ? $row->browser : '—' ); ?></td><td><?php echo (int) $row->hits; ?></td></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<h2><?php esc_html_e( 'Recent Clicks', 'elegance-links-redirect' ); ?></h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'When', 'elegance-links-redirect' ); ?></th>
				<th><?php esc_html_e( 'IP', 'elegance-links-redirect' ); ?></th>
				<th><?php esc_html_e( 'Country', 'elegance-links-redirect' ); ?></th>
				<th><?php esc_html_e( 'Device', 'elegance-links-redirect' ); ?></th>
				<th><?php esc_html_e( 'Browser', 'elegance-links-redirect' ); ?></th>
				<th><?php esc_html_e( 'OS', 'elegance-links-redirect' ); ?></th>
				<th><?php esc_html_e( 'Referrer', 'elegance-links-redirect' ); ?></th>
				<th><?php esc_html_e( 'Destination', 'elegance-links-redirect' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $clicks ) ) : ?>
			<tr><td colspan="8"><?php esc_html_e( 'No clicks recorded yet.', 'elegance-links-redirect' ); ?></td></tr>
		<?php else : foreach ( $clicks as $click ) : ?>
			<tr>
				<td><?php echo esc_html( $click->clicked_at ); ?></td>
				<td><?php echo esc_html( $click->ip_address ); ?></td>
				<td><?php echo esc_html( trim( $click->country_name . ' ' . ( $click->country ? '(' . $click->country . ')' : '' ) ) ); ?></td>
				<td><?php echo esc_html( $click->device_type ); ?></td>
				<td><?php echo esc_html( $click->browser ); ?></td>
				<td><?php echo esc_html( $click->os ); ?></td>
				<td class="elr-truncate"><?php echo esc_html( $click->referrer ); ?></td>
				<td class="elr-truncate"><?php echo esc_html( $click->destination ); ?></td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>
