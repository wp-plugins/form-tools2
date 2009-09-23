<?php

/**
 * This page handles both the submissions listing page AND the edit submission page. The only difference is the query string: if
 * a submission_id parameter is passed, we're looking at the edit submission page. Obviously all the required validation is ran
 * on the pages to ensure the user can't look at something that they're not allowed to. The nice thing about this is that it
 * ensures the appropriate form is highlighted in the left nav at all times.
 */

$g_root_dir = get_option("formtools_config_php_path");
$g_root_url = get_option("formtools_config_php_url");
require_once("$g_root_dir/global/library.php");
require_once("$g_root_dir/global/smarty/plugins/function.form_view_fields_dropdown.php");

global $current_user;
get_currentuserinfo();
$wp_account_id = $current_user->data->ID;

// used to store all the various snippets of information that need to be stored for continuing to the next
$query_string_info = array();

$form_info = $_GET["page"];
$info = split("-", $form_info);
$form_id = $info[1];

// *** NOTE: if the form ID in the query string isn't correct or been hacked, no problem! Wordpress takes care
// of those permissions for us: the user just sees a blank page telling them they don't have permission ***

if (isset($_GET["form_id"]))
  $query_string_info["form_id"] = $_GET["form_id"];
if (isset($_GET["view_id"]))
  $query_string_info["view_id"] = $_GET["view_id"];
if (isset($_GET["page"]))
  $query_string_info["page"] = $_GET["page"];
if (isset($_GET["currpage"]))
  $query_string_info["currpage"] = $_GET["currpage"];
if (isset($_GET["order"]))
  $query_string_info["order"] = $_GET["order"];
if (isset($_GET["search_field"]))
  $query_string_info["search_field"] = $_GET["search_field"];
if (isset($_GET["search_date"]))
  $query_string_info["search_date"] = $_GET["search_date"];
if (isset($_GET["search_keyword"]))
  $query_string_info["search_keyword"] = $_GET["search_keyword"];
if (isset($_GET["submission_id"]))
  $query_string_info["submission_id"] = $_GET["submission_id"];


// get the date formatting for this user
$ft_account_id = ft_wp_get_ft_account_id($wp_account_id);
$account_settings = ft_get_account_info($ft_account_id);
$settings = ft_get_settings();

$g_is_administrator = (ft_wp_get_wpuser_role($ft_account_id) == "administrator") ? true : false;

$request = array_merge($_POST, $_GET);

if ($g_is_administrator)
  $form_views = ft_get_form_views($form_id);
else
  $form_views = ft_get_form_views($form_id, $ft_account_id);


if (isset($request["view_id"]))
  $view_id = $request["view_id"];
else
{
  $default_view_id = $form_views[0]["view_id"];
  $view_id = $default_view_id;

  // if this is a non-administrator, check that the user can see this View
  if (!$g_is_administrator)
  {
    $client_views = ft_get_client_form_views($ft_account_id);

    if (is_array($client_views[$form_id]) && !in_array($view_id, $client_views[$form_id]))
      $view_id = $client_views[$form_id][0];
  }
}


$form_info = ft_get_form($form_id);
$view_info = ft_get_view($view_id);

// if the user is just a submission, create the new
if (isset($_GET["add_submission"]) && $view_info["may_add_submissions"] == "yes")
{
  $submission_id = ft_create_blank_submission($form_id, true);
  $query_string_info["submission_id"] = $submission_id;
}


$theme_url = "$g_root_url/themes/default";

$this_folder = dirname(__FILE__);
if (!array_key_exists("submission_id", $query_string_info))
{
  require_once("$this_folder/_submission_list.php");
}
else
{
  require_once("$this_folder/_edit_submission.php");
}
