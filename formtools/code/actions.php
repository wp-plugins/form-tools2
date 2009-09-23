<?php

/**
 * Actions.php
 *
 * This file handles all server-side responses for Ajax requests. As of 2.0.0, it returns information
 * in JSON format to be handled by JS.
 */

// -------------------------------------------------------------------------------------------------

$curr_folder = dirname(__FILE__);
require("$curr_folder/../../../../wp-blog-header.php");

// the action to take and the ID of the page where it will be displayed (allows for
// multiple calls on same page to load content in unique areas)
$request = array_merge($_GET, $_POST);
$action  = $request["action"];

// Find out if we need to return anything back with the response. This mechanism allows us to pass any information
// between the Ajax submit function and the Ajax return function. Usage:
//   "return_vals[]=question1:answer1&return_vals[]=question2:answer2&..."
$return_val_str = "";
if (isset($request["return_vals"]))
{
  $vals = array();
  foreach ($request["return_vals"] as $pair)
  {
    list($key, $value) = split(":", $pair);
    $vals[] = "$key: \"$value\"";
  }
  $return_val_str = ", " . join(", ", $vals);
}

if (!is_user_logged_in())
{
  echo "{ success: false, message: \"NOT logged in\"{$return_val_str} }";
  exit;
}

switch ($action)
{
  // called by the administrator or client on the Edit Submission page. Note that we pull the submission ID
  // and the form ID from sessions rather than have them explictly passed by the JS. This is a security precaution -
  // it prevents a potential hacker exploiting this function here. Instead they'd have to set the sessions by another
  // route which is trickier
  case "delete_submission_file":
    $form_id       = $request["form_id"];
    $submission_id = $request["submission_id"];
    $field_id      = $request["field_id"];
    $force_delete  = ($request["force_delete"] == "true") ? true : false;

    if (empty($form_id) || empty($submission_id))
    {
      echo "{ success: false, message: \"{$LANG["notify_invalid_session_values_re_login"]}\" } ";
      exit;
    }

    list($success, $message) = ft_delete_file_submission($form_id, $submission_id, $field_id, $force_delete);
    $success = ($success) ? 1 : 0;
    $message = ft_sanitize($message);
    echo "{ success: $success, message: \"$message\"{$return_val_str} }";
    break;
}
