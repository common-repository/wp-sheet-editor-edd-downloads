<?php
defined( 'ABSPATH' ) || exit;

$instance = vgse_edd_downloads();
?>
<p><?php _e('Thank you for installing our plugin.', $instance->textname); ?></p>

<?php
$steps = array();

if (!class_exists('Easy_Digital_Downloads')) {
	$steps['install_edd'] = '<p>' . __('This plugin requires the Easy Digital Downloads plugin. Please install it.', $instance->textname) . '</p>';
}
$steps['open_editor'] = '<p>' . sprintf(__('The plugin is ready. You can <a href="%s">start editing</a> on the spreadsheet', $instance->textname), esc_url(VGSE()->helpers->get_editor_url($instance->post_type))) . '</p>';

include VGSE_DIR . '/views/free-extensions-for-welcome.php';
$steps['free_extensions'] = $free_extensions_html;

$steps = apply_filters('vg_sheet_editor/edd_downloads/welcome_steps', $steps);

if (!empty($steps)) {
	echo '<ol class="steps">';
	foreach ($steps as $key => $step_content) { if(empty($step_content)){continue;}
		?>
		<li><?php echo wp_kses_post($step_content); ?></li>		
		<?php
	}

	echo '</ol>';
}	