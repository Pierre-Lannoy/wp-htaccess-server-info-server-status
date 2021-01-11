<?php
/**
 * Provide a admin-facing view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @package    Plugin
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

use Hsiss\System\Role;

wp_localize_script(
	HSISS_LIVESTATUS_ID,
	'livestatus',
	[
		'nonce'     => wp_create_nonce( 'ajax_hsiss' ),
		'frequency' => 1000,
	]
);

wp_enqueue_script( 'hsiss-switchery' );
wp_enqueue_script( HSISS_ASSETS_ID );
wp_enqueue_script( HSISS_LIVESTATUS_ID );
wp_enqueue_style( HSISS_ASSETS_ID );
wp_enqueue_style( 'hsiss-switchery' );
wp_enqueue_style( 'hsiss-tooltip' );


?>

<div class="wrap">
	<div class="hsiss-dashboard">
		<div class="hsiss-row">
			<?php echo $insights->get_title_bar() ?>
		</div>
        <div class="hsiss-row">
	        <?php echo $insights->get_kpi_bar() ?>
        </div>
        <div class="hsiss-row">
            <div class="hsiss-box hsiss-box-40-60-line">
				<?php echo $insights->get_scoreboard_box() ?>
				<?php echo $insights->get_detail_box() ?>
            </div>
        </div>
	</div>
</div>
