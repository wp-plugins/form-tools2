<?php


function ft_wp_edit_user_profile()
{
  $page_vars = array();
  include_once(realpath(dirname(__FILE__) . "/../pages/profile.php"));
}


function ft_wp_update_user_profile()
{
  $user_id = $_POST["user_id"];
  $form_tools_access = $_POST["form_tools_access"];
  update_usermeta($user_id, 'form_tools_access', $form_tools_access);
}


/**
 * Called when a new user is registered. This sets their default Form Tools access level to
 * whatever is specified by their role type, found in the Form Tools settings page.
 */
function ft_wp_user_register($user_id)
{
  $role = ft_wp_get_wpuser_role($user_id);

  $formtools_account_id = "";
  if (!empty($role))
  {
    // now get the Form Tools account ID associated with this role type
    $access_level = "formtoolsaccess__{$role}";
    $formtools_account_id = get_option($access_level);
  }

  update_usermeta($user_id, 'form_tools_access', $formtools_account_id);
}

