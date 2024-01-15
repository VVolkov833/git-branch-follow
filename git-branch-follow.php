<?php
/*
Plugin Name: Git Branch Follow
Description: Connect to GitHub repositories, automatically track changes across branches, and ensure your plugins and themes are always up-to-date. Simplify your WordPress management with this seamless integration.
Version: 1.1.1
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Author: Vadim Volkov, Firmcatalyst
Author URI: https://firmcatalyst.com
License: GPL v3 or later
License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

namespace FC\GitBranchFollow;
defined( 'ABSPATH' ) || exit;

define( 'FCGBF_DEV', false );
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
require_once FCGBF_DIR . 'inc/api-webhook.php';
require_once FCGBF_DIR . 'inc/auto-updates.php';
require_once FCGBF_DIR . 'inc/install.php';


/* future improvements
++ extend the type logic to '3'
should I cancel the checks for repositoreis without auto-updates?
add webooks support
buttons to the list
	+refactor
	+improve the columns ux
mark mentioned in themes & plugins admin
	also add UPPERCASE TEXT FILE TO THEME/PLUGIN DIR THAT THE CHANGES ARE CONTROLLED BY GIT
		DIRECT-CHANGES-IN-THIS-FOLDER-OVERRIDES
			delete on uninstall
lines marked with ++
on some fails restoration can be scheduled
add notice to the editor to existing reps, that the changes will be overridden
allow to select an older commit and disable updates at the same time
		'https://api.github.com/repos/'.$args['rep_author'].'/'.$args['rep_name'].'/commits?per_page=20&sha='.$args['rep_branch']
		'https://api.github.com/repos/'.$args['rep_author'].'/'.$args['rep_name'].'/zipball/{commit_sha}
add other popular git reps
hide negative values of scheduled events
clear/postphone the update scheduled after the update is successful
*/