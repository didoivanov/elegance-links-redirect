<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var object|null $link */
/** @var array $clicks */
/** @var array $summary */
/** @var string $home */
/** @var array $links */
/** @var array $daily */
/** @var int $total */
/** @var array $countries */

$filters = array(
	'country'       => isset( $_GET['country'] ) ? sanitize_text_field( wp_unslash( $_GET['country'] ) ) : '',
	'q'             => isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '',
	'with_referrer' => ! empty( $_GET['with_referrer'] ) ? 1 : 0,
);
$filter_active = '' !== $filters['country'] || '' !== $filters['q'] || $filters['with_referrer'];

$max_hits = 0;
foreach ( (array) $daily as $day ) {
	if ( (int) $day['hits'] > $max_hits ) {
		$max_hits = (int) $day['hits'];
	}
}

$chart_scale = $max_hits > 0 ? $max_hits : 1;
$base_url    = admin_url( 'admin.php?page=elr-link-stats' );
if ( $link ) {
	$base_url = add_query_arg( 'link_id', (int) $link->id, $base_url );
}
?>
<div class="wrap elr-wrap">
	<?php if ( $link ) : ?>
		<h1><?php printf( esc_html__( 'Stats: %s', 'elegance-links-redirect' ), esc_html( $link->title ? $link->title : $link->slug ) ); ?></h1>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-links' ) ); ?>">&larr; <?php esc_html_e( 'Back to links', 'elegance-links-redirect' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-edit&link_id=' . (int) $link->id ) ); ?>"><?php esc_html_e( 'Edit link', 'elegance-links-redirect' ); ?></a> |
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-stats' ) ); ?>"><?php esc_html_e( 'All links overview', 'elegance-links-redirect' ); ?></a>
		</p>
		<p><?php esc_html_e( 'Pretty URL:', 'elegance-links-redirect' ); ?> <code><?php echo esc_html( $home . $link->slug ); ?></code></p>
	<?php else : ?>
		<h1><?php esc_html_e( 'Link Stats', 'elegance-links-redirect' ); ?></h1>
		<p><?php esc_html_e( 'Stats across every tracked link. Pick a specific link below for its individual breakdown.', 'elegance-links-redirect' ); ?></p>
	<?php endif; ?>

	<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="elr-stats-filter">
		<input type="hidden" name="page" value="elr-link-stats" />
		<?php if ( $link ) : ?>
			<input type="hidden" name="link_id" value="<?php echo (int) $link->id; ?>" />
		<?php endif; ?>
		<div class="elr-stats-filter-row">
			<label>
				<span class="elr-filter-label"><?php esc_html_e( 'Country', 'elegance-links-redirect' ); ?></span>
				<select name="country">
					<option value=""><?php esc_html_e( 'Any country', 'elegance-links-redirect' ); ?></option>
					<?php foreach ( (array) $countries as $c ) :
						$code = strtoupper( (string) $c->country );
						$name = (string) $c->country_name;
						?>
						<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $filters['country'], $code ); ?>>
							<?php echo esc_html( ( $name ? $name : $code ) . ' (' . $code . ')' ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<label>
				<span class="elr-filter-label"><?php esc_html_e( 'Search', 'elegance-links-redirect' ); ?></span>
				<input type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php esc_attr_e( 'IP, referrer, browser, OS, city…', 'elegance-links-redirect' ); ?>" />
			</label>
			<label class="elr-filter-check">
				<input type="checkbox" name="with_referrer" value="1" <?php checked( $filters['with_referrer'], 1 ); ?> />
				<span><?php esc_html_e( 'Only clicks with referrer', 'elegance-links-redirect' ); ?></span>
			</label>
			<?php submit_button( __( 'Apply filters', 'elegance-links-redirect' ), 'secondary', 'filter', false ); ?>
			<?php if ( $filter_active ) : ?>
				<a class="button-link" href="<?php echo esc_url( $base_url ); ?>"><?php esc_html_e( 'Reset', 'elegance-links-redirect' ); ?></a>
			<?php endif; ?>
		</div>
	</form>

	<p class="elr-stats-totals">
		<?php
		if ( $link ) {
			printf(
				/* translators: 1: total hits on the link, 2: filtered hit count. */
				esc_html__( 'Lifetime hits: %1$d. Matching current filters: %2$d.', 'elegance-links-redirect' ),
				(int) $link->hits,
				(int) $total
			);
		} else {
			printf(
				/* translators: %d: total clicks matching filters. */
				esc_html__( 'Matching current filters: %d clicks.', 'elegance-links-redirect' ),
				(int) $total
			);
		}
		?>
	</p>

	<div class="elr-stats-card">
		<h2><?php esc_html_e( 'Clicks per day (last 30 days)', 'elegance-links-redirect' ); ?></h2>
		<?php if ( 0 === $max_hits ) : ?>
			<p class="elr-empty"><?php esc_html_e( 'No clicks in the last 30 days match your filters.', 'elegance-links-redirect' ); ?></p>
		<?php else : ?>
			<div class="elr-chart" role="img" aria-label="<?php esc_attr_e( 'Bar chart of clicks per day for the last 30 days.', 'elegance-links-redirect' ); ?>">
				<?php foreach ( (array) $daily as $day ) :
					$hits    = (int) $day['hits'];
					$percent = max( 2, (int) round( $hits / $chart_scale * 100 ) );
					$label   = sprintf(
						/* translators: 1: date, 2: hits. */
						__( '%1$s — %2$d clicks', 'elegance-links-redirect' ),
						$day['day'],
						$hits
					);
					?>
					<span class="elr-bar-wrap" title="<?php echo esc_attr( $label ); ?>">
						<span class="elr-bar" style="height: <?php echo esc_attr( $percent ); ?>%;">
							<?php if ( $hits > 0 ) : ?>
								<span class="elr-bar-value"><?php echo (int) $hits; ?></span>
							<?php endif; ?>
						</span>
					</span>
				<?php endforeach; ?>
			</div>
			<div class="elr-chart-axis">
				<?php foreach ( (array) $daily as $i => $day ) :
					$show = ( 0 === (int) $i ) || ( count( $daily ) - 1 === (int) $i ) || ( 0 === (int) $i % 7 );
					?>
					<span class="elr-chart-day"><?php echo $show ? esc_html( mysql2date( 'M j', $day['day'] . ' 00:00:00' ) ) : ''; ?></span>
				<?php endforeach; ?>
			</div>
			<p class="description"><?php printf( esc_html__( 'Peak: %d clicks / day.', 'elegance-links-redirect' ), (int) $max_hits ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $link ) : ?>
		<div class="elr-stats-grid">
			<div class="elr-stats-card">
				<h2><?php esc_html_e( 'Top Countries', 'elegance-links-redirect' ); ?></h2>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Country', 'elegance-links-redirect' ); ?></th><th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th></tr></thead>
					<tbody>
					<?php if ( empty( $summary['countries'] ) ) : ?>
						<tr><td colspan="2">—</td></tr>
					<?php else : foreach ( (array) $summary['countries'] as $row ) : ?>
						<tr><td><?php echo esc_html( $row->country_name . ( $row->country ? ' (' . $row->country . ')' : '' ) ); ?></td><td><?php echo (int) $row->hits; ?></td></tr>
					<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
			<div class="elr-stats-card">
				<h2><?php esc_html_e( 'Devices', 'elegance-links-redirect' ); ?></h2>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Device', 'elegance-links-redirect' ); ?></th><th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th></tr></thead>
					<tbody>
					<?php if ( empty( $summary['devices'] ) ) : ?>
						<tr><td colspan="2">—</td></tr>
					<?php else : foreach ( (array) $summary['devices'] as $row ) : ?>
						<tr><td><?php echo esc_html( $row->device_type ? $row->device_type : '—' ); ?></td><td><?php echo (int) $row->hits; ?></td></tr>
					<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
			<div class="elr-stats-card">
				<h2><?php esc_html_e( 'Browsers', 'elegance-links-redirect' ); ?></h2>
				<table class="widefat striped">
					<thead><tr><th><?php esc_html_e( 'Browser', 'elegance-links-redirect' ); ?></th><th><?php esc_html_e( 'Hits', 'elegance-links-redirect' ); ?></th></tr></thead>
					<tbody>
					<?php if ( empty( $summary['browsers'] ) ) : ?>
						<tr><td colspan="2">—</td></tr>
					<?php else : foreach ( (array) $summary['browsers'] as $row ) : ?>
						<tr><td><?php echo esc_html( $row->browser ? $row->browser : '—' ); ?></td><td><?php echo (int) $row->hits; ?></td></tr>
					<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	<?php else : ?>
		<h2><?php esc_html_e( 'Links', 'elegance-links-redirect' ); ?></h2>
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
	<?php endif; ?>

	<h2><?php echo $link ? esc_html__( 'Recent Clicks', 'elegance-links-redirect' ) : esc_html__( 'Recent Clicks (all links)', 'elegance-links-redirect' ); ?></h2>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'When', 'elegance-links-redirect' ); ?></th>
				<?php if ( ! $link ) : ?><th><?php esc_html_e( 'Link', 'elegance-links-redirect' ); ?></th><?php endif; ?>
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
			<tr><td colspan="<?php echo $link ? 8 : 9; ?>"><?php esc_html_e( 'No clicks match the current filters.', 'elegance-links-redirect' ); ?></td></tr>
		<?php else : foreach ( $clicks as $click ) :
			$click_link_id = isset( $click->link_id ) ? (int) $click->link_id : 0;
			?>
			<tr>
				<td><?php echo esc_html( $click->clicked_at ); ?></td>
				<?php if ( ! $link ) : ?>
					<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-stats&link_id=' . $click_link_id ) ); ?>">#<?php echo $click_link_id; ?></a></td>
				<?php endif; ?>
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
