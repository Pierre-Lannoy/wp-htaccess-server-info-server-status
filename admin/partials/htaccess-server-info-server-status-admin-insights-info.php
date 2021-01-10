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

wp_enqueue_script( HSISS_ASSETS_ID );
wp_enqueue_style( HSISS_ASSETS_ID );

$url = site_url( 'server-info');

?>
<div class="wrap">
    <?php echo $insights->display() ?>
</div>
