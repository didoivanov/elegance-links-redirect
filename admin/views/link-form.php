<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var object|null $link */
/** @var array $rules */
/** @var string $home */
/** @var object|null $editing_rule */

$is_edit   = $link && isset( $link->id );
$link_id   = $is_edit ? (int) $link->id : 0;
$slug      = $is_edit ? (string) $link->slug : '';
$title     = $is_edit ? (string) $link->title : '';
$target    = $is_edit ? (string) $link->target_url : '';
$type      = $is_edit ? (int) $link->redirect_type : 307;
$nofollow  = $is_edit ? (int) $link->nofollow : 0;
$track     = $is_edit ? (int) $link->track_clicks : 1;
$is_active = $is_edit ? (int) $link->is_active : 1;

$editing_rule      = isset( $editing_rule ) ? $editing_rule : null;
$rule_is_edit      = ! empty( $editing_rule );
$rule_id_val       = $rule_is_edit ? (int) $editing_rule->id : 0;
$rule_type_val     = $rule_is_edit ? (string) $editing_rule->rule_type : 'country';
$rule_match_val    = $rule_is_edit ? (string) $editing_rule->match_value : '';
$rule_target_val   = $rule_is_edit ? (string) $editing_rule->target_url : '';
$rule_code_val     = $rule_is_edit ? (int) $editing_rule->redirect_type : 307;
$rule_priority_val = $rule_is_edit ? (int) $editing_rule->priority : 10;
$rule_active_val   = $rule_is_edit ? (int) $editing_rule->is_active : 1;
?>
<div class="wrap elr-wrap">
	<h1 class="wp-heading-inline"><?php echo $is_edit ? esc_html__( 'Edit Link', 'elegance-links-redirect' ) : esc_html__( 'Add New Link', 'elegance-links-redirect' ); ?></h1>
	<hr class="wp-header-end" />
	<?php ELR_Admin::render_notice(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="elr-form">
		<?php wp_nonce_field( 'elr_save_link' ); ?>
		<input type="hidden" name="action" value="elr_save_link" />
		<input type="hidden" name="id" value="<?php echo esc_attr( $link_id ); ?>" />

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="elr-title"><?php esc_html_e( 'Title', 'elegance-links-redirect' ); ?></label></th>
				<td><input type="text" class="regular-text" id="elr-title" name="title" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Internal reference (optional)', 'elegance-links-redirect' ); ?>"></td>
			</tr>
			<tr>
				<th><label for="elr-slug"><?php esc_html_e( 'Pretty URL', 'elegance-links-redirect' ); ?> <span class="description">*</span></label></th>
				<td>
					<code><?php echo esc_html( $home ); ?></code>
					<input type="text" class="regular-text" id="elr-slug" name="slug" value="<?php echo esc_attr( $slug ); ?>" placeholder="go" required>
					<p class="description"><?php esc_html_e( 'Letters, numbers, dashes, and underscores only. Example: go, play, spring-offer.', 'elegance-links-redirect' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="elr-target"><?php esc_html_e( 'Target URL', 'elegance-links-redirect' ); ?> <span class="description">*</span></label></th>
				<td><input type="url" class="large-text" id="elr-target" name="target_url" value="<?php echo esc_attr( $target ); ?>" placeholder="https://example.com/long/tracked/url" required></td>
			</tr>
			<tr>
				<th><label for="elr-redirect-type"><?php esc_html_e( 'Redirect Type', 'elegance-links-redirect' ); ?></label></th>
				<td>
					<select id="elr-redirect-type" name="redirect_type">
						<?php foreach ( array( 301 => '301 - Permanent', 302 => '302 - Found', 303 => '303 - See Other', 307 => '307 - Temporary', 308 => '308 - Permanent (keeps method)' ) as $code => $label ) : ?>
							<option value="<?php echo (int) $code; ?>" <?php selected( $type, $code ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Options', 'elegance-links-redirect' ); ?></th>
				<td>
					<label><input type="checkbox" name="is_active" value="1" <?php checked( $is_active, 1 ); ?>> <?php esc_html_e( 'Active', 'elegance-links-redirect' ); ?></label><br>
					<label><input type="checkbox" name="track_clicks" value="1" <?php checked( $track, 1 ); ?>> <?php esc_html_e( 'Track clicks', 'elegance-links-redirect' ); ?></label><br>
					<label><input type="checkbox" name="nofollow" value="1" <?php checked( $nofollow, 1 ); ?>> <?php esc_html_e( 'Suggest nofollow (used when rendering links)', 'elegance-links-redirect' ); ?></label>
				</td>
			</tr>
		</table>
		<?php submit_button( $is_edit ? __( 'Update Link', 'elegance-links-redirect' ) : __( 'Create Link', 'elegance-links-redirect' ) ); ?>
	</form>

	<?php if ( $is_edit ) : ?>
		<hr>
		<h2><?php esc_html_e( 'Dynamic Redirect Rules', 'elegance-links-redirect' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Rules are evaluated in priority order (lowest first). The first matching rule wins, otherwise the default target URL is used.', 'elegance-links-redirect' ); ?></p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Priority', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Type', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Match', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Target', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Code', 'elegance-links-redirect' ); ?></th>
					<th><?php esc_html_e( 'Status', 'elegance-links-redirect' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $rules ) ) : ?>
				<tr><td colspan="7"><?php esc_html_e( 'No rules yet.', 'elegance-links-redirect' ); ?></td></tr>
			<?php else : foreach ( $rules as $rule ) :
				$delete_url = wp_nonce_url(
					admin_url( 'admin-post.php?action=elr_delete_rule&rule_id=' . (int) $rule->id . '&link_id=' . (int) $link_id ),
					'elr_delete_rule'
				);
				$edit_url = admin_url( 'admin.php?page=elr-link-edit&link_id=' . (int) $link_id . '&edit_rule=' . (int) $rule->id ) . '#elr-rule-form';
				?>
				<tr<?php echo $rule_is_edit && $rule_id_val === (int) $rule->id ? ' class="elr-row-editing"' : ''; ?>>
					<td><?php echo (int) $rule->priority; ?></td>
					<td><?php echo esc_html( $rule->rule_type ); ?></td>
					<td><code><?php echo esc_html( $rule->match_value ); ?></code></td>
					<td class="elr-truncate"><?php echo esc_html( $rule->target_url ); ?></td>
					<td><?php echo (int) $rule->redirect_type; ?></td>
					<td><?php echo $rule->is_active ? esc_html__( 'Active', 'elegance-links-redirect' ) : esc_html__( 'Disabled', 'elegance-links-redirect' ); ?></td>
					<td>
						<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'elegance-links-redirect' ); ?></a> |
						<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this rule?', 'elegance-links-redirect' ) ); ?>');"><?php esc_html_e( 'Delete', 'elegance-links-redirect' ); ?></a>
					</td>
				</tr>
			<?php endforeach; endif; ?>
			</tbody>
		</table>

		<h3 id="elr-rule-form"><?php echo $rule_is_edit ? esc_html__( 'Edit Rule', 'elegance-links-redirect' ) : esc_html__( 'Add Rule', 'elegance-links-redirect' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="elr-form">
			<?php wp_nonce_field( 'elr_save_rule' ); ?>
			<input type="hidden" name="action" value="elr_save_rule" />
			<input type="hidden" name="link_id" value="<?php echo (int) $link_id; ?>" />
			<input type="hidden" name="rule_id" value="<?php echo (int) $rule_id_val; ?>" />

			<table class="form-table" role="presentation">
				<tr>
					<th><label for="elr-rule-type"><?php esc_html_e( 'Rule Type', 'elegance-links-redirect' ); ?></label></th>
					<td>
						<select id="elr-rule-type" name="rule_type">
							<option value="country" <?php selected( $rule_type_val, 'country' ); ?>><?php esc_html_e( 'Visitor country', 'elegance-links-redirect' ); ?></option>
							<option value="device" <?php selected( $rule_type_val, 'device' ); ?>><?php esc_html_e( 'User device', 'elegance-links-redirect' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="elr-match-value"><?php esc_html_e( 'Match Value', 'elegance-links-redirect' ); ?></label></th>
					<td>
						<input type="text" id="elr-match-value" class="regular-text" name="match_value" value="<?php echo esc_attr( $rule_match_val ); ?>" placeholder="US, CA, GB" required autocomplete="off">
						<p class="description elr-match-hint" data-country-hint="<?php esc_attr_e( 'Start typing a country name or ISO 3166-1 alpha-2 code. Separate multiple values with commas (e.g. US, CA, GB).', 'elegance-links-redirect' ); ?>" data-device-hint="<?php esc_attr_e( 'Pick one or more device types: desktop, mobile, tablet, bot. Separate multiple values with commas.', 'elegance-links-redirect' ); ?>"></p>
					</td>
				</tr>
				<tr>
					<th><label for="elr-rule-target"><?php esc_html_e( 'Target URL', 'elegance-links-redirect' ); ?></label></th>
					<td><input type="url" id="elr-rule-target" class="large-text" name="target_url" value="<?php echo esc_attr( $rule_target_val ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="elr-rule-code"><?php esc_html_e( 'Redirect Code', 'elegance-links-redirect' ); ?></label></th>
					<td>
						<select id="elr-rule-code" name="redirect_type">
							<?php foreach ( array( 301, 302, 303, 307, 308 ) as $code ) : ?>
								<option value="<?php echo (int) $code; ?>" <?php selected( $rule_code_val, $code ); ?>><?php echo (int) $code; ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="elr-rule-priority"><?php esc_html_e( 'Priority', 'elegance-links-redirect' ); ?></label></th>
					<td><input type="number" id="elr-rule-priority" name="priority" value="<?php echo (int) $rule_priority_val; ?>" min="0" class="small-text"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'elegance-links-redirect' ); ?></th>
					<td><label><input type="checkbox" name="is_active" value="1" <?php checked( $rule_active_val, 1 ); ?>> <?php esc_html_e( 'Active', 'elegance-links-redirect' ); ?></label></td>
				</tr>
			</table>
			<p class="submit">
				<?php submit_button( $rule_is_edit ? __( 'Update Rule', 'elegance-links-redirect' ) : __( 'Add Rule', 'elegance-links-redirect' ), 'secondary', 'submit', false ); ?>
				<?php if ( $rule_is_edit ) : ?>
					<a class="button-link" href="<?php echo esc_url( admin_url( 'admin.php?page=elr-link-edit&link_id=' . (int) $link_id ) ); ?>"><?php esc_html_e( 'Cancel', 'elegance-links-redirect' ); ?></a>
				<?php endif; ?>
			</p>
		</form>
	<?php endif; ?>
</div>
