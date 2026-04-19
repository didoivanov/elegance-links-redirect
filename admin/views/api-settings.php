<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var bool $enabled */
/** @var string $token */
/** @var string $url */

$has_token   = '' !== $token;
$sample_url  = $has_token && $enabled ? $url : rest_url( ELR_API::NAMESPACE_V1 . '/clicks/{TOKEN}' );
$after_id    = $has_token && $enabled ? add_query_arg( array( 'after_id' => 0, 'limit' => 1000 ), $url ) : $sample_url . '?after_id=0&limit=1000';
$before_id   = $has_token && $enabled ? add_query_arg( array( 'before_id' => 1000, 'limit' => 1000 ), $url ) : $sample_url . '?before_id=1000&limit=1000';
?>
<div class="wrap elr-wrap">
	<h1><?php esc_html_e( 'Elegance Links — API', 'elegance-links-redirect' ); ?></h1>
	<?php ELR_Admin::render_notice(); ?>
	<p><?php esc_html_e( 'Expose click data as JSON at a single, unguessable public URL. Useful for importing into analytics, CRMs, or a data warehouse.', 'elegance-links-redirect' ); ?></p>

	<?php if ( $enabled && $has_token ) : ?>
		<div class="elr-api-endpoint-card" data-url="<?php echo esc_attr( $url ); ?>">
			<div class="elr-api-endpoint-label"><?php esc_html_e( 'Your API endpoint', 'elegance-links-redirect' ); ?></div>
			<div class="elr-api-endpoint-row">
				<input type="text" readonly class="elr-api-endpoint-input" id="elr-api-url-field" value="<?php echo esc_attr( $url ); ?>" onclick="this.select();" />
				<button type="button" class="button button-primary elr-api-copy" data-copy-target="elr-api-url-field">
					<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
					<?php esc_html_e( 'Copy URL', 'elegance-links-redirect' ); ?>
				</button>
				<a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Test in browser', 'elegance-links-redirect' ); ?></a>
			</div>
			<p class="elr-api-endpoint-hint"><?php esc_html_e( 'Anyone with this URL can read every click. Treat it like a password.', 'elegance-links-redirect' ); ?></p>
		</div>
	<?php elseif ( $has_token && ! $enabled ) : ?>
		<div class="elr-api-endpoint-card elr-api-endpoint-card--disabled">
			<div class="elr-api-endpoint-label"><?php esc_html_e( 'API is disabled', 'elegance-links-redirect' ); ?></div>
			<p class="elr-api-endpoint-hint"><?php esc_html_e( 'Re-enable below to activate the existing token, or revoke it to start fresh.', 'elegance-links-redirect' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="elr-form">
		<?php wp_nonce_field( 'elr_save_api' ); ?>
		<input type="hidden" name="action" value="elr_save_api" />

		<table class="form-table" role="presentation">
			<tr>
				<th><?php esc_html_e( 'Status', 'elegance-links-redirect' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="api_enabled" value="1" <?php checked( $enabled, true ); ?> />
						<?php esc_html_e( 'Enable API', 'elegance-links-redirect' ); ?>
					</label>
					<p class="description"><?php esc_html_e( 'When enabled, the URL below becomes reachable by anyone who has the token. Disable to turn it off instantly; revoke to also delete the current token.', 'elegance-links-redirect' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Endpoint URL', 'elegance-links-redirect' ); ?></th>
				<td>
					<?php if ( $enabled && $has_token ) : ?>
						<code class="elr-api-url"><?php echo esc_html( $url ); ?></code>
						<p class="description"><?php esc_html_e( 'Paste this URL into your importer. Treat it like a password.', 'elegance-links-redirect' ); ?></p>
					<?php else : ?>
						<code><?php echo esc_html( $sample_url ); ?></code>
						<p class="description"><?php esc_html_e( 'Enable the API and save to generate a token.', 'elegance-links-redirect' ); ?></p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Token action', 'elegance-links-redirect' ); ?></th>
				<td>
					<label><input type="radio" name="token_action" value="" checked /> <?php esc_html_e( 'Keep current token', 'elegance-links-redirect' ); ?></label><br />
					<label><input type="radio" name="token_action" value="regenerate" /> <?php esc_html_e( 'Regenerate token (old token stops working immediately)', 'elegance-links-redirect' ); ?></label><br />
					<label><input type="radio" name="token_action" value="revoke" /> <?php esc_html_e( 'Disable API and revoke token', 'elegance-links-redirect' ); ?></label>
				</td>
			</tr>
		</table>
		<?php submit_button( __( 'Save API settings', 'elegance-links-redirect' ) ); ?>
	</form>

	<h2><?php esc_html_e( 'How to use', 'elegance-links-redirect' ); ?></h2>
	<p><?php esc_html_e( 'Send a GET request to the endpoint. The response is JSON with a clicks array and pagination metadata.', 'elegance-links-redirect' ); ?></p>

	<h3><?php esc_html_e( 'Query parameters', 'elegance-links-redirect' ); ?></h3>
	<table class="widefat striped" style="max-width: 900px;">
		<thead><tr><th><?php esc_html_e( 'Parameter', 'elegance-links-redirect' ); ?></th><th><?php esc_html_e( 'Description', 'elegance-links-redirect' ); ?></th></tr></thead>
		<tbody>
			<tr>
				<td><code>limit</code></td>
				<td><?php printf( esc_html__( 'Rows to return. Default %1$d, max %2$d.', 'elegance-links-redirect' ), ELR_API::DEFAULT_LIMIT, ELR_API::MAX_LIMIT ); ?></td>
			</tr>
			<tr>
				<td><code>after_id</code></td>
				<td><?php esc_html_e( 'Returns clicks with id greater than this value, ordered ascending. Use this for incremental syncs — store the last_id your importer received and pass it on the next call.', 'elegance-links-redirect' ); ?></td>
			</tr>
			<tr>
				<td><code>before_id</code></td>
				<td><?php esc_html_e( 'Returns clicks with id less than this value, ordered descending. Use this to walk backward into historical data.', 'elegance-links-redirect' ); ?></td>
			</tr>
		</tbody>
	</table>

	<h3><?php esc_html_e( 'Response shape', 'elegance-links-redirect' ); ?></h3>
	<pre class="elr-api-sample">{
  "total_matching": 45231,
  "returned": 1000,
  "limit": 1000,
  "first_id": 44232,
  "last_id": 45231,
  "clicks": [
    {
      "id": 45231,
      "link_id": 7,
      "link_slug": "go",
      "link_title": "Affiliate offer",
      "rule_id": 3,
      "rule_type": "country",
      "rule_match": "US,CA",
      "ip_address": "203.0.113.42",
      "country": "US",
      "country_name": "United States",
      "city": "New York",
      "device_type": "mobile",
      "browser": "Safari",
      "os": "iOS",
      "user_agent": "Mozilla/5.0 (...)",
      "referrer": "https://twitter.com/",
      "destination": "https://partner.example.com/landing",
      "clicked_at": "2026-04-19 14:30:12"
    }
  ]
}</pre>

	<h3><?php esc_html_e( 'Recipes', 'elegance-links-redirect' ); ?></h3>
	<p><strong><?php esc_html_e( 'Most recent 1,000 clicks', 'elegance-links-redirect' ); ?></strong></p>
	<p><code><?php echo esc_html( $sample_url ); ?></code></p>

	<p><strong><?php esc_html_e( 'Incremental sync (resume from last seen id)', 'elegance-links-redirect' ); ?></strong></p>
	<p><code><?php echo esc_html( $after_id ); ?></code></p>

	<p><strong><?php esc_html_e( 'Walk older history', 'elegance-links-redirect' ); ?></strong></p>
	<p><code><?php echo esc_html( $before_id ); ?></code></p>

	<p class="description"><?php esc_html_e( 'Every row includes an id so external systems can deduplicate. The endpoint returns 403 if the token does not match and 404 if the API is disabled.', 'elegance-links-redirect' ); ?></p>
</div>
<script>
(function () {
	var btn = document.querySelector('.elr-api-copy');
	if (!btn) { return; }
	btn.addEventListener('click', function () {
		var id    = btn.getAttribute('data-copy-target');
		var input = id ? document.getElementById(id) : null;
		if (!input) { return; }
		var done = function () {
			var original = btn.getAttribute('data-original') || btn.innerHTML;
			btn.setAttribute('data-original', original);
			btn.innerHTML = '<span class="dashicons dashicons-yes" aria-hidden="true"></span> <?php echo esc_js( __( 'Copied', 'elegance-links-redirect' ) ); ?>';
			setTimeout(function () { btn.innerHTML = original; }, 1800);
		};
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(input.value).then(done, function () {
				input.select();
				document.execCommand('copy');
				done();
			});
		} else {
			input.select();
			document.execCommand('copy');
			done();
		}
	});
})();
</script>
