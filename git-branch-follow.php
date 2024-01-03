<?php
/*
Plugin Name: Git Branch Follow
Description: Connect to GitHub repositories, automatically track changes across branches, and ensure your plugins and themes are always up-to-date. Simplify your WordPress management with this seamless integration.
Version: 0.0.1
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 8.0
Author: Vadim Volkov, Firmcatalyst
Author URI: https://firmcatalyst.com
License: GPL v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

define( 'FCGBF_DEV', true );
define( 'FCGBF_VER', get_file_data( __FILE__, [ 'ver' => 'Version' ] )[ 'ver' ] . ( FCGBF_DEV ? time() : '' ) );
define( 'FCGBF_REGISTER', __FILE__ );

define( 'FCGBF_SLUG', 'fcgbf' );
define( 'FCGBF_PREF', FCGBF_SLUG.'-' );

define( 'FCGBF_URL', plugin_dir_url( __FILE__ ) );
define( 'FCGBF_DIR', plugin_dir_path( __FILE__ ) );
define( 'FCGBF_BSN', plugin_basename(__FILE__) );

define( 'FCGBF_BRANCH', 'main' );

define( 'FCGBF_ENDPOINT', FCGBF_SLUG.'/v1' );


require_once FCGBF_DIR . 'inc/functions.php';
require_once FCGBF_DIR . 'inc/post-type.php';
require_once FCGBF_DIR . 'inc/fields.php';
require_once FCGBF_DIR . 'inc/meta-print.php';
require_once FCGBF_DIR . 'inc/meta-columns.php';
require_once FCGBF_DIR . 'inc/meta-front.php';
require_once FCGBF_DIR . 'inc/meta-save.php';
require_once FCGBF_DIR . 'inc/api-fetch.php';
require_once FCGBF_DIR . 'inc/install.php';
require_once FCGBF_DIR . 'inc/auto-updates.php';


/* future improvements
buttons to the list
	+refactor
schedule checks must have & updates optionally?
	check as normal
	updater: newer | any
mark mentioned in themes & plugins admin?
*/