<?php
/**
 * @package Form_Tools
 * @author Encore Web Studios
 * @version 1.0.0
 */

/*
Plugin Name: Form Tools
Plugin URI: http://ft2.formtools.org/
Description: Form Tools is a generic form processor, storage and access script. This plugin lets you view and modify your form submissions through the Wordpress interface, rather than requiring you to log into two places.
Author: Encore Web Studios
Version: 1.0.0
Author URI: http://docs.formtools.org/wordpress_plugin/

Copyright 2009 Benjamin Keen (ben.keen@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

$g_root_dir = get_option("formtools_config_php_path");
if (!empty($g_root_dir))
{
  global $g_db_hostname, $g_db_username, $g_db_password, $g_db_name, $g_unicode, $g_db_ssl, $g_check_ft_sessions;
	$g_check_ft_sessions = false;

  @include_once("$g_root_dir/global/library.php");
}


if (!function_exists('wp_get_current_user'))
{
  function wp_get_current_user()
	{
    require (ABSPATH . WPINC . '/pluggable.php');
    global $current_user;
    get_currentuserinfo();
    return $current_user;
  }
}

// this appalling WP function loads all the user info into a $current_user global
wp_get_current_user();

if (is_object($current_user) && is_object($current_user->data))
  $wp_account_id = $current_user->data->ID;


$folder = dirname(__FILE__);
require_once("$folder/code/admin.php");
require_once("$folder/code/general.php");
require_once("$folder/code/submissions.php");
require_once("$folder/code/profiles.php");


// this adds the various menus
add_action('admin_menu', 'ft_wp_admin_menu');
add_action('edit_user_profile', 'ft_wp_edit_user_profile');
add_action('edit_user_profile_update', 'ft_wp_update_user_profile');
add_action('user_register', 'ft_wp_user_register');
