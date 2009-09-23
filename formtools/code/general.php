<?php


/**
 * Loads a theme opening page - as stored in the "theme" session key. This is loaded for every
 * page in the Form Tools UI.
 *
 * Note: if the page isn't found in the current theme, it defaults to the
 * "default" theme. This is important (and handy!): it means that only the default theme
 * needs to contain every file. Other themes can just define whatever pages they want to override
 * and omit the others.
 *
 * @param string $template the location of the template file, relative to the theme folder
 * @param array $page_vars a hash of information to provide to the template
 * @param string $g_theme an optional parameter, letting you override the default template
 */
function ft_wp_display_page($template, $page_vars)
{
  global $g_root_dir, $g_root_url, $g_success, $g_message, $g_link, $g_smarty_debug, $g_debug, $LANG,
    $g_smarty, $g_smarty_use_sub_dirs;

  $theme = "default";

  // common variables. These are sent to EVERY templates
  $g_smarty->template_dir = "$g_root_dir/themes/$theme";
  $g_smarty->compile_dir  = "$g_root_dir/themes/$theme/cache";

  // check the compile directory has the write permissions
  if (!is_writable($g_smarty->compile_dir))
    ft_handle_error("The theme cache folder doesn't have write-permissions. Please update the <b>{$g_smarty->compile_dir}</b> to have full read-write permissions (777 on unix).", "");

  $g_smarty->use_sub_dirs = $g_smarty_use_sub_dirs;
  $g_smarty->assign("LANG", $LANG);
  $settings = ft_get_settings();
  $g_smarty->assign("settings", $settings);
  $g_smarty->assign("g_root_dir", $g_root_dir);
  $g_smarty->assign("g_root_url", $g_root_url);
  $g_smarty->assign("g_debug", $g_debug);
  $g_smarty->assign("same_page", $_SERVER["PHP_SELF"]);
  $g_smarty->assign("query_string", $_SERVER["QUERY_STRING"]);
  $g_smarty->assign("dir", $LANG["special_text_direction"]);
  $g_smarty->assign("g_success", $g_success);
  $g_smarty->assign("g_message", $g_message);

  if (isset($page_vars["page_url"]))
  {
    $parent_page_url = ft_get_parent_page_url($page_vars["page_url"]);
    $g_smarty->assign("nav_parent_page_url", $parent_page_url);
  }

  // check the "required" vars are at least set so they don't produce warnings when smarty debug is enabled
  if (!isset($page_vars["head_string"])) $page_vars["head_string"] = "";
  if (!isset($page_vars["head_title"]))  $page_vars["head_title"] = "";
  if (!isset($page_vars["head_js"]))     $page_vars["head_js"] = "";
  if (!isset($page_vars["page"]))        $page_vars["page"] = "";

  // if we need to include custom JS messages in the page, add it to the generated JS. Note: even if the js_messages
  // key is defined but still empty, the ft_generate_js_messages function is called, returning the "base" JS - like
  // the JS version of g_root_url. Only if it is not defined will that info not be included.
  $js_messages = (isset($page_vars["js_messages"])) ? ft_wp_generate_js_messages($page_vars["js_messages"]) : "";

  if (!empty($page_vars["head_js"]) || !empty($js_messages))
    $page_vars["head_js"] = "<script type=\"text/javascript\">\n//<![CDATA[\n{$page_vars["head_js"]}\n$js_messages\n//]]>\n</script>";

  if (!isset($page_vars["head_css"]))
    $page_vars["head_css"] = "";
  else if (!empty($page_vars["head_css"]))
    $page_vars["head_css"] = "<style type=\"text/css\">\n{$page_vars["head_css"]}\n</style>";

  $g_smarty->assign("modules_dir", "$g_root_url/modules");

  // theme-specific vars
  $g_smarty->assign("images_url", "$g_root_url/themes/$theme/images");
  $g_smarty->assign("theme_url", "$g_root_url/themes/$theme");
  $g_smarty->assign("theme_dir", "$g_root_dir/themes/$theme");

  // now add the custom variables for this template, as defined in $page_vars
  foreach ($page_vars as $key=>$value)
    $g_smarty->assign($key, $value);

//  extract(ft_process_hooks("main", compact("g_smarty", "template", "page_vars"), array("g_smarty")), EXTR_OVERWRITE);

  $g_smarty->display($template);

  ft_db_disconnect($g_link);
}

/**
 * This is called on the main Form Tools plugin page. It performs a simple check to
 *
 */
function ft_wp_check_form_tools_path_found()
{
  global $g_root_url;
  return !empty($g_root_url);
}

function ft_wp_generate_role_dropdown($name, $selected_value)
{
  $html = "<select name=\"{$name}\">
             <option value=\"\"";

  if ($selected_value == "")
    $html .= " selected";

  $html .= ">No Access</option>
           <optgroup label=\"Administrator\">";

  $admin_info = ft_get_admin_info();
  $html .= "<option value=\"{$admin_info["account_id"]}\"";

  if ($selected_value == $admin_info["account_id"])
    $html .= " selected";

  $html .= ">&#8212; {$admin_info["first_name"]} {$admin_info["last_name"]}</option>\n";

  $html .= "</optgroup><optgroup label=\"Clients\">";

  $accounts = ft_search_clients();
  foreach ($accounts as $account_info)
  {
    $account_type = $account_info["account_type"];
    $admin_str = ($account_type == "admin") ? " - ADMIN" : "";
    $account_id = $account_info["account_id"];

    $html .= "<option value=\"$account_id\"";

    if ($selected_value == $account_id)
        $html .= " selected";

    $html .= ">&#8212; {$account_info["first_name"]} {$account_info["last_name"]} ({$account_info["account_id"]})$admin_str</option>\n";
  }
  $html .= "</optgroup></select>";

  return $html;
}


function ft_wp_get_wpuser_role($user_id)
{
  global $wpdb;

  $user = get_userdata($user_id);
  if (is_object($user))
  {
    $capabilities = $user->{$wpdb->prefix . 'capabilities'};

    if (!empty($capabilities))
    {
      $keys = array_keys($capabilities);
      return $keys[0];
    }
  }

  return "";
}


// how memorable! This returns the mapped Form Tools account ID for a particular Wordpress ID
function ft_wp_get_ft_account_id($user_id)
{
  $role = ft_wp_get_wpuser_role($user_id);
  $access_level = "formtoolsaccess__{$role}";
  return get_option($access_level);
}


/**
 * Displays basic &lt;&lt; 1 2 3 >> navigation for lists, each linking to the current page.
 *
 * This uses the pagination.tpl template, found in the theme's root folder.
 *
 * @param integer $num_results The total number of results found.
 * @param integer $num_per_page The max number of results to list per page.
 * @param integer $current_page The current page number being examined (defaults to 1).
 * @param string $pass_along_str The string to include in nav links.
 * @param string $page_str The string used in building the page nav to indicate the page number
 */
function wp_ft_get_page_nav($num_results, $num_per_page, $current_page = 1, $pass_along_str = "", $page_str = "page")
{
  global $g_root_dir, $g_root_url, $LANG;

  $g_max_nav_pages = 8;

  $theme = "default";
  $current_page = ($current_page < 1) ? 1 : $current_page;
  $same_page = $_SERVER["PHP_SELF"];

  // display the total number of results found
  $range_start = ($current_page - 1) * $num_per_page + 1;
  $range_end   = $range_start + $num_per_page - 1;
  $range_end   = ($range_end > $num_results) ? $num_results : $range_end;
  $total_pages = ceil($num_results / $num_per_page);

  // piece together additional query string values
  $query_str = "";
  if (!empty($pass_along_str))
     $query_str = "&{$pass_along_str}";

  // determine the first and last pages to show page nav links for
  $half_total_nav_pages  = floor($g_max_nav_pages / 2);
  $first_page = ($current_page > $half_total_nav_pages) ? $current_page - $half_total_nav_pages : 1;
  $last_page  = (($current_page + $half_total_nav_pages) < $total_pages) ? $current_page + $half_total_nav_pages : $total_pages;

  $pagination_html = "<div class=\"tablenav\"><div class=\"tablenav-pages\"><span class=\"displaying-num\">Displaying $range_start to $range_end of $num_results</span> ";

  if ($total_pages > 1)
  {
    // if we're not on the first page, provide a "<<" (previous page) link
    if ($current_page != 1)
    {
      $previous_page = $current_page-1;
      $pagination_html .= "<a href=\"{$same_page}?{$page_str}={$previous_page}{$query_str}\" class=\"previous page-numbers\">&laquo;</a> ";
    }

    for ($i=$first_page; $i<=$last_page; $i++)
    {
      if ($i == $current_page)
        $pagination_html .= "<span class=\"page-numbers current\">$i</span> ";
      else
        $pagination_html .= "<a class=\"page-numbers\" href=\"{$same_page}?{$page_str}={$i}{$query_str}\">$i</a> ";
    }

    if ($current_page < $total_pages)
    {
      $next_page = $current_page+1;
      $pagination_html .= "<a href=\"{$same_page}?{$page_str}={$next_page}{$query_str}\" class=\"next page-numbers\">&raquo;</a>";
    }
  }

  $pagination_html .= "</div></div>";

  return $pagination_html;
}



/**
 * Generates a Wordpress-friendly URL for the module.
 */
function wp_ft_create_url($query_string_info, $omit_key = "")
{
  if (!empty($omit_key))
  {
    if (is_array($omit_key))
    {
      foreach ($omit_key as $key)
      {
        unset($query_string_info[$key]);
      }
    }
    else
    {
      unset($query_string_info[$omit_key]);
    }
  }

  $query_string_pairs = array();
  while (list($key, $value) = each($query_string_info))
    $query_string_pairs[] = "$key=$value";

  return join("&", $query_string_pairs);
}


/**
 * The Wordpress version of ft_load_field except that it uses database user account cache to
 * store the data.
function ft_wp_load_field($wp_account_id, $field_name, $db_session_name, $default_value = "")
{
  $field = $default_value;

  $current_val = get_usermeta($wp_account_id, $db_session_name);

  if (isset($_GET[$field_name]))
  {
    $field = $_GET[$field_name];
    update_usermeta($wp_account_id, $db_session_name, $field);
  }
  else if (isset($_POST[$field_name]))
  {
    $field = $_POST[$field_name];
    update_usermeta($wp_account_id, $db_session_name, $field);
  }
  else if (!empty($current_val))
  {
    $field = $current_val;
  }

  return $field;
}
*/


function ft_wp_extract_form_settings($query_string)
{
  $vals = split("&", $query_string);

  $settings = array();
  foreach ($vals as $pairs)
  {
    $curr_pair = split("=", $pairs);
    $settings[$curr_pair[0]] = $curr_pair[1];
  }

  return $settings;
}


/*
 * Overrides the default Smarty plugin, since it needs a style attribute to define the height.
 */
function ft_wp_submission_dropdown_multiple($params, &$smarty)
{
  global $g_multi_val_delimiter;

  if (empty($params["name"]))
  {
    $smarty->trigger_error("assign: missing 'name' parameter. This is used to give the select field a name value.");
    return;
  }
  if (empty($params["field_id"]))
  {
    $smarty->trigger_error("assign: missing 'field_id' parameter. This is used to give the select field a field_id value.");
    return;
  }

  $name        = $params["name"];
  $field_id    = $params["field_id"];
  $is_editable = (isset($params["is_editable"])) ? $params["is_editable"] : "yes";
  $selected_vals = (isset($params["selected"])) ? explode("$g_multi_val_delimiter", $params["selected"]) : array();

  $option_info = ft_get_field_options($field_id);

  $dd_str = "<select name=\"{$name}[]\" multiple style=\"height: 80px\">";
  foreach ($option_info as $option)
  {
    $dd_str .= "<option value='{$option['option_value']}'";
    if (in_array($option['option_value'], $selected_vals))
      $dd_str .= " selected";

    $dd_str .= ">{$option['option_name']}</option>\n";
  }
  $dd_str .= "</select>";

  if ($is_editable == "no")
    echo $params["selected"];
  else
    echo $dd_str;
}


function ft_wp_submission_radios($params, &$smarty)
{
  global $LANG;

  if (empty($params["name"]))
  {
    $smarty->trigger_error("assign: missing 'name' parameter. This is used to give the select field a name value.");
    return;
  }
  if (empty($params["field_id"]))
  {
    $smarty->trigger_error("assign: missing 'field_id' parameter. This is used to give the select field a field_id value.");
    return;
  }

  $name        = $params["name"];
  $field_id    = $params["field_id"];
  $selected    = (isset($params["selected"])) ? $params["selected"] : "";
  $is_editable = (isset($params["is_editable"])) ? $params["is_editable"] : "yes";

  $field_info    = ft_get_form_field($field_id, true);
  $field_group_id = $field_info["field_group_id"];
  $options = $field_info["options"];

  if (empty($field_group_id))
  {
    echo "<div class=\"medium_grey\">{$LANG["notify_no_assigned_field_option_group"]}</div>";
    return;
  }

  $group_info = ft_get_field_option_group($field_group_id);
  $orientation = $group_info["field_orientation"];
  $pagebreak   = ($orientation == "vertical") ? "<br />" : "";

  $count = 1;
  $selected_value = "";
  $dd_str = "";
  foreach ($options as $option)
  {
    // generate a unique ID for this option (used for the label)
    $id = "{$name}_$count";

    $dd_str .= "<input type=\"radio\" name=\"$name\" value=\"{$option['option_value']}\" id=\"$id\"";
    if ($option['option_value'] == $selected)
    {
      $dd_str .= " checked";
      $selected_value = $option['option_name'];
    }
    $dd_str .= ">&nbsp;<label for=\"$id\">{$option['option_name']}</label>$pagebreak\n";

    $count++;
  }

  if ($is_editable == "no")
    echo $selected_value;
  else
    echo $dd_str;
}


function ft_wp_submission_checkboxes($params, &$smarty)
{
  global $LANG, $g_multi_val_delimiter;

  if (empty($params["name"]))
  {
    $smarty->trigger_error("assign: missing 'name' parameter. This is used to give the select field a name value.");
    return;
  }
  if (empty($params["field_id"]))
  {
    $smarty->trigger_error("assign: missing 'field_id' parameter. This is used to give the select field a field_id value.");
    return;
  }

  $name          = $params["name"];
  $field_id      = $params["field_id"];
  $selected_vals = (isset($params["selected"])) ? explode("$g_multi_val_delimiter", $params["selected"]) : array();
  $field_info    = ft_get_form_field($field_id, true);
  $field_group_id = $field_info["field_group_id"];
  $options = $field_info["options"];

  if (empty($field_group_id))
  {
    echo "<div class=\"medium_grey\">{$LANG["notify_no_assigned_field_option_group"]}</div>";
      return;
  }

  $group_info = ft_get_field_option_group($field_group_id);
  $orientation = $group_info["field_orientation"];
  $pagebreak     = ($orientation == "vertical") ? "<br />" : "";
  $is_editable = (isset($params["is_editable"])) ? $params["is_editable"] : "yes";

  $count = 1;
  $selected_values = array();
  $dd_str = "";
  foreach ($options as $option)
  {
    // generate a unique ID for this option (used for the label)
    $id = "{$name}_$count";

    $dd_str .= "<input type=\"checkbox\" name=\"{$name}[]\" value=\"{$option['option_value']}\" id=\"$id\"";
    if (in_array($option['option_value'], $selected_vals))
    {
      $selected_values[] = $option['option_name'];
      $dd_str .= " checked";
    }
    $dd_str .= ">&nbsp;<label for=\"$id\">{$option['option_name']}</label>$pagebreak\n";

    $count++;
  }

  if ($is_editable == "no")
    echo join($g_multi_val_delimiter, $selected_values);
  else
    echo $dd_str;
}


/**
 * A handy, generic function used throughout the site to output messages to the user - the content
 * of which are returned by the various functions. It can handle multiple messages (notifications
 * and/or errors) by passing in arrays for each of the two parameters.
 *
 * Ultimately, one of the goals is to move to complete consistency in the ways the various functions
 * handle their return values. Specifically, an array with the following indexes:<br/>
 *    [0] T/F (or an array of T/F values),<br/>
 *    [1] error/success message string (or an array of strings)<br/>
 *    [2] other information, e.g. new IDs (optional).
 *
 *
 * @param boolean $results This parameter can be EITHER a boolean or an array of booleans if you
 *          need to display multiple messages at once.
 * @param boolean $messages The message to output, or an array of messages. The indexes of each
 *          corresponds to the success/failure boolean in the $results parameter.
 */
function ft_wp_display_message($results, $messages)
{
  global $LANG;

  // if there are no messages, just return
  if (empty($messages))
    return;

  $notifications = array();
  $errors        = array();

  if (is_array($results))
  {
    for ($i=0; $i<=count($results); $i++)
    {
      if     ($results[$i])  $notifications[] = $messages[$i];
      elseif (!$results[$i]) $errors[]        = $messages[$i];
    }
  }
  else
  {
    if     ($results)  $notifications[] = $messages;
    elseif (!$results) $errors[]        = $messages;
  }

  // display notifications
  if (!empty($notifications))
  {
    if (count($notifications) > 1)
    {
      array_walk($notifications, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
      $display_str = join("<br />", $notifications);
    }
    else
      $display_str = $notifications[0];

    echo "<div class='notify'>$display_str</div>";
  }

  // display errors
  if (!empty($errors))
  {
    // if there were notifications displayed, add a little padding to separate the two sections
    if (!empty($notifications)) { echo "<br />"; }

    if (count($errors) > 1)
    {
      array_walk($errors, create_function('&$el','$el = "&bull;&nbsp; " . $el;'));
      $display_str = join("<br />", $errors);
      $title_str = $LANG["word_errors"];
    }
    else
    {
      $display_str = $errors[0];
      $title_str = $LANG["word_error"];
    }

    echo $display_str;
  }
}

function ft_wp_check_client_may_view($client_id, $form_id, $view_id)
{
  $permissions = ft_get_client_form_views($client_id);

  if (!array_key_exists($form_id, $permissions))
    return false;
  else
  {
    if (!empty($view_id) && !in_array($view_id, $permissions[$form_id]))
      return false;
  }

  return true;
}

