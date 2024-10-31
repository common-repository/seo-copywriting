<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.seocopy.com
 * @since      1.0.0
 *
 * @package    seocopy
 * @subpackage seocopy/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    seocopy
 * @subpackage seocopy/includes
 * @author     seocopy <support@seocopy.com>
 */
class seocopy_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
        add_option('seo_copy_do_activation_redirect', true);
	}

}
