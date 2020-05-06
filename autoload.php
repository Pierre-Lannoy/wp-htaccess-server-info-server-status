<?php
/**
 * Autoload for Apache Status & Info.
 *
 * @package Bootstrap
 * @author  Pierre Lannoy <https://pierre.lannoy.fr/>.
 * @since   1.0.0
 */

spl_autoload_register(
	function ( $class ) {
		$classname = $class;
		$filepath  = __DIR__ . '/';
		if ( strpos( $classname, 'Hsiss\\' ) === 0 ) {
			while ( strpos( $classname, '\\' ) !== false ) {
				$classname = substr( $classname, strpos( $classname, '\\' ) + 1, 1000 );
			}
			$filename = 'class-' . str_replace( '_', '-', strtolower( $classname ) ) . '.php';
			if ( strpos( $class, 'Hsiss\System\\' ) === 0 ) {
				$filepath = HSISS_INCLUDES_DIR . 'system/';
			}
			if ( strpos( $class, 'Hsiss\Plugin\Feature\\' ) === 0 ) {
				$filepath = HSISS_INCLUDES_DIR . 'features/';
			} elseif ( strpos( $class, 'Hsiss\Plugin\Integration\\' ) === 0 ) {
				$filepath = HSISS_INCLUDES_DIR . 'integrations/';
			} elseif ( strpos( $class, 'Hsiss\Plugin\\' ) === 0 ) {
				$filepath = HSISS_INCLUDES_DIR . 'plugin/';
			}
			if ( strpos( $class, 'Hsiss\Library\\' ) === 0 ) {
				$filepath = HSISS_VENDOR_DIR;
			}
			if ( strpos( $filename, '-public' ) !== false ) {
				$filepath = HSISS_PUBLIC_DIR;
			}
			if ( strpos( $filename, '-admin' ) !== false ) {
				$filepath = HSISS_ADMIN_DIR;
			}
			$file = $filepath . $filename;
			if ( file_exists( $file ) ) {
				include_once $file;
			}
		}
	}
);
