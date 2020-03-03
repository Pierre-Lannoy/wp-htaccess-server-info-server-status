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

wp_enqueue_script( 'hsiss-moment-with-locale' );
wp_enqueue_script( 'hsiss-daterangepicker' );
wp_enqueue_script( 'hsiss-switchery' );
wp_enqueue_script( 'hsiss-chartist' );
wp_enqueue_script( 'hsiss-chartist-tooltip' );
wp_enqueue_script( 'hsiss-jvectormap' );
wp_enqueue_script( 'hsiss-jvectormap-world' );
wp_enqueue_script( HSISS_ASSETS_ID );
wp_enqueue_style( HSISS_ASSETS_ID );
wp_enqueue_style( 'hsiss-daterangepicker' );
wp_enqueue_style( 'hsiss-switchery' );
wp_enqueue_style( 'hsiss-tooltip' );
wp_enqueue_style( 'hsiss-chartist' );
wp_enqueue_style( 'hsiss-chartist-tooltip' );
wp_enqueue_style( 'hsiss-jvectormap' );


?>

<div class="wrap">
	<div class="hsiss-dashboard">
		<div class="hsiss-row">
			<?php echo $analytics->get_title_bar() ?>
		</div>
        <div class="hsiss-row">
	        <?php echo $analytics->get_kpi_bar() ?>
        </div>
        <?php if ( 'summary' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="hsiss-row">
                <div class="hsiss-box hsiss-box-40-60-line">
                    <?php echo $analytics->get_top_domain_box() ?>
                    <?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'domain' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="hsiss-row">
                <div class="hsiss-box hsiss-box-40-60-line">
					<?php echo $analytics->get_top_authority_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( 'authority' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="hsiss-row">
                <div class="hsiss-box hsiss-box-40-60-line">
					<?php echo $analytics->get_top_endpoint_box() ?>
					<?php echo $analytics->get_map_box() ?>
                </div>
            </div>
		<?php } ?>
		<?php if ( ( 'summary' === $analytics->type || 'domain' === $analytics->type || 'authority' === $analytics->type || 'endpoint' === $analytics->type ) && '' === $analytics->extra ) { ?>
			<?php echo $analytics->get_main_chart() ?>
            <div class="hsiss-row">
                <div class="hsiss-box hsiss-box-33-33-33-line">
					<?php echo $analytics->get_codes_box() ?>
					<?php echo $analytics->get_security_box() ?>
					<?php echo $analytics->get_method_box() ?>
                </div>
            </div>
			<?php if ( Role::SUPER_ADMIN === Role::admin_type() && 'all' === $analytics->site) { ?>
                <div class="hsiss-row last-row">
					<?php echo $analytics->get_sites_list() ?>
                </div>
			<?php } ?>
		<?php } ?>

		<?php if ( 'domains' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="hsiss-row">
	            <?php echo $analytics->get_domains_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'authorities' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="hsiss-row">
				<?php echo $analytics->get_authorities_list() ?>
            </div>
		<?php } ?>
		<?php if ( 'endpoints' === $analytics->type && '' === $analytics->extra ) { ?>
            <div class="hsiss-row">
				<?php echo $analytics->get_endpoints_list() ?>
            </div>
		<?php } ?>
		<?php if ( '' !== $analytics->extra ) { ?>
            <div class="hsiss-row">
				<?php echo $analytics->get_extra_list() ?>
            </div>
		<?php } ?>
	</div>
</div>