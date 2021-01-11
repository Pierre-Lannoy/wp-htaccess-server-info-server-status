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
		'frequency' => 750,
	]
);

wp_enqueue_script( 'hsiss-switchery' );
wp_enqueue_script( 'hsiss-chartist' );
wp_enqueue_script( 'hsiss-chartist-tooltip' );
wp_enqueue_script( HSISS_ASSETS_ID );
wp_enqueue_script( HSISS_LIVESTATUS_ID );
wp_enqueue_style( HSISS_ASSETS_ID );
wp_enqueue_style( 'hsiss-switchery' );
wp_enqueue_style( 'hsiss-tooltip' );
wp_enqueue_style( 'hsiss-chartist' );
wp_enqueue_style( 'hsiss-chartist-tooltip' );


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
				<?php echo $insights->get_map_box() ?>
            </div>
        </div>




        <?php if ( 'summary' === $insights->type && '' === $insights->extra ) { ?>

		<?php } ?>
		<?php if ( 'domain' === $insights->type && '' === $insights->extra ) { ?>
            <div class="hsiss-row">
                <div class="hsiss-box hsiss-box-40-60-line">
					<?php echo $insights->get_top_authority_box() ?>
					<?php echo $insights->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'authority' === $insights->type && '' === $insights->extra ) { ?>
            <div class="hsiss-row">
                <div class="hsiss-box hsiss-box-40-60-line">
					<?php echo $insights->get_top_endpoint_box() ?>
					<?php echo $insights->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( ( 'summary' === $insights->type || 'domain' === $insights->type || 'authority' === $insights->type || 'endpoint' === $insights->type ) && '' === $insights->extra ) { ?>
			<?php echo $insights->get_main_chart() ?>
            <div class="hsiss-row">
                <div class="hsiss-box hsiss-box-33-33-33-line">
					<?php echo $insights->get_codes_box() ?>
					<?php echo $insights->get_security_box() ?>
					<?php echo $insights->get_method_box() ?>
                </div>
            </div>
			<?php if ( Role::SUPER_ADMIN === Role::admin_type() && 'all' === $insights->site) { ?>
                <div class="hsiss-row last-row">
					<?php echo $insights->get_sites_list() ?>
                </div>
			<?php } ?>
		<?php } ?>

		<?php if ( 'domains' === $insights->type && '' === $insights->extra ) { ?>
            <div class="hsiss-row">
	            <?php echo $insights->get_domains_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'authorities' === $insights->type && '' === $insights->extra ) { ?>
            <div class="hsiss-row">
				<?php echo $insights->get_authorities_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'endpoints' === $insights->type && '' === $insights->extra ) { ?>
            <div class="hsiss-row">
				<?php echo $insights->get_endpoints_list() ?>
            </div>
		<?php } ?>
		<?php if ( '' !== $insights->extra ) { ?>
            <div class="hsiss-row">
				<?php echo $insights->get_extra_list() ?>
            </div>
		<?php } ?>
	</div>
</div>
