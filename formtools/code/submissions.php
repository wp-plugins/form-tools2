<?php

/**
 * Loads the required content for the <head> for the view submissions page.
 */
function ft_wp_load_submission_head()
{
  global $LANG, $g_root_url, $form_id;

  $confirm_delete_submissions_on_other_pages = ft_sanitize($LANG["confirm_delete_submissions_on_other_pages"]);

  $wp_actions_url = get_bloginfo("url") . "/wp-content/plugins/formtools/code/actions.php";

  $head_js =<<<EOF
  <link rel="stylesheet" type="text/css" media="all" href="$g_root_url/global/jscalendar/skins/aqua/theme.css" title="Aqua" />
  <script type="text/javascript" src="$g_root_url/global/jscalendar/calendar.js"></script>
  <script type="text/javascript" src="$g_root_url/global/jscalendar/calendar-setup.js"></script>
  <script type="text/javascript" src="$g_root_url/global/jscalendar/lang/calendar-en.js"></script>

  <script type="text/javascript">
  var g = {};
  g.actions_url = "$g_root_url";
  g.wp_actions_url = "$wp_actions_url";

  g.messages = [];
  g.messages["validation_select_submissions_to_delete"] = "{$LANG["validation_select_submissions_to_delete"]}";
  g.messages["confirm_delete_submission"] = "{$LANG["confirm_delete_submission"]}";
  g.messages["confirm_delete_submissions"] = "{$LANG["confirm_delete_submissions"]}";
  g.messages["phrase_select_all_X_results"] = "{$LANG["phrase_select_all_X_results"]}";
  g.messages["phrase_select_all_on_page"] = "{$LANG["phrase_select_all_on_page"]}";
  g.messages["phrase_all_X_results_selected"] = "{$LANG["phrase_all_X_results_selected"]}";
  g.messages["phrase_row_selected"] = "{$LANG["phrase_row_selected"]}";
  g.messages["phrase_rows_selected"] = "{$LANG["phrase_rows_selected"]}";

  var rules = [];
  rules.push("if:search_field!=submission_date,required,search_keyword,{$LANG["validation_please_enter_search_keyword"]}");
  rules.push("if:search_field=submission_date,required,search_date,{$LANG["validation_please_enter_search_date_range"]}");

  if (typeof ms == "undefined")
    ms = {};

  ms.all_submissions_on_page_selected = null; // boolean; set on page load
  ms.all_submissions_in_result_set_selected = false; // N/A for WP wordpress plugin
  ms.all_submissions_selected_omit_list = []; // N/A for WP wordpress plugin

  ms.delete_submissions = function()
  {
    var selected_ids_on_page = ms.get_selected_submissions();

    if (!selected_ids_on_page.length)
    {
      alert(g.messages["validation_select_submissions_to_delete"]);
      return;
    }
    else if (selected_ids_on_page.length == 1)
      var answer = confirm(g.messages["confirm_delete_submission"]);
    else
      var answer = confirm(g.messages["confirm_delete_submissions"]);

    if (answer)
      window.location = ms.delete_base_url + selected_ids_on_page.join(",");
  }

  ms.delete_submission = function(submission_id, target_webpage)
  {
    if (confirm(g.messages["confirm_delete_submission"]))
      window.location = target_webpage + "&delete=" + submission_id;

    return false;
  }

  ms.get_selected_submissions = function()
  {
    var selected_ids = [];
    for (var i=0; i<ms.page_submission_ids.length; i++)
    {
      var curr_id = ms.page_submission_ids[i];
      if (jQuery('#submission_cb_' + curr_id + ':checked').val() != null)
        selected_ids.push(curr_id);
    }

    return selected_ids;
  }

  ms.change_search_field = function(choice)
  {
    if (choice == "submission_date" || choice == "last_modified_date")
      jQuery("#search_dropdown_section").show();
    else
      jQuery("#search_dropdown_section").hide();
  }

  /**
   * Deletes a submission file.
   *
   * @param field_id
   */
  ms.delete_submission_file = function(field_id)
  {
    jQuery.getJSON(g.wp_actions_url, {
      action: "delete_submission_file",
      form_id: jQuery("#form_id").val(),
      submission_id: jQuery("#submission_id").val(),
      field_id: field_id,
      "return_vals[]": ["target_message_id:file_field_" + field_id + "_message_id", "field_id:" + field_id],
      force_delete: true
    },	ms.delete_submission_file_response);
  }

  /**
   * Handles the successful responses for the delete file feature. Whether or not the file was *actually*
   * deleted is a separate matter. If the file couldn't be delete, the user is provided the option of deleting
   * the database record anyway.
   */
  ms.delete_submission_file_response = function(info)
  {
    // if it was a success, remove the link from the page
    if (info.success == 1)
    {
      var field_id = info.field_id;
      jQuery("#field_" + field_id + "_link").html("");
      jQuery("#field_" + field_id + "_upload_field").show();
    }

    // display the message
    if (info.success)
    {
      jQuery("#message").replaceWith('<div style="background-color: rgb(255, 251, 204); padding-top: 10px; padding-bottom: 10px;" class="updated fade below-h2" id="message">'
        + '<div class="notify">' + info.message + '</div>'
        + '</div>');
    }
    else
    {
      jQuery("#message").replaceWith('<div padding-top: 10px; padding-bottom: 10px;" class="error below-h2" id="message">' + info.message	+ '</div>');
    }
  }
  </script>

  <style type="text/css">
  .widefat tbody th.check-column {
    padding:7px 0;
  }
  .pad_right_small { padding-right: 2px; }
  .pad_left_small { padding-left: 2px; }
  .pad_top_small { padding-top: 2px; }
  .pad_bottom_small { padding-bottom: 2px; }
  .pad_right { padding-right: 4px; }
  .pad_left { padding-left: 4px; }
  .pad_top { padding-top: 4px; }
  .pad_bottom { padding-bottom: 4px; }
  .pad_right_large { padding-right: 6px; }
  .pad_left_large { padding-left: 6px; }
  .pad_top_large { padding-top: 6px; }
  .pad_bottom_large { padding-bottom: 6px; }

  .margin_right_small { margin-right: 2px; }
  .margin_left_small { margin-left: 2px; }
  .margin_top_small { margin-top: 2px; }
  .margin_bottom_small { margin-top: 2px; }
  .margin_right { margin-right: 6px; }
  .margin_left { margin-left: 6px; }
  .margin_top { margin-top: 6px; }
  .margin_bottom { margin-bottom: 6px; }
  .margin_right_large { margin-right: 10px; }
  .margin_left_large { margin-left: 10px; }
  .margin_top_large { margin-top: 10px; }
  .margin_bottom_large { margin-bottom: 10px; }
  .submit input.red { color: #cc0000; font-weight: bold; }

  .tab_selected {
    border-top: 1px solid #cfcfcf;
    background-color: white;
    height: 21px;
    text-align: center;
    padding-bottom: 1px;
    font-weight: bold;
  }
  .tab_selected a:link,.tab_selected a:visited {
    text-decoration: none;
    color: black;
  }
  .tab_selected a:hover {
    text-decoration: underline;
    color: #336699;
  }
  .tab_not_selected {
    background-image: url($g_root_url/themes/default/images/tab_not_selected_bg.gif);
    background-repeat: repeat-x;
    height: 26px;
    text-align: center;
    font-weight: bold;
  }
  .tab_content {
    padding-top: 10px;
    padding-left: 18px;
    padding-right: 18px;
    padding-bottom: 10px;
    vertical-align: top;
    background-color: #ffffff;
    border-left: 1px solid #cfcfcf;
    border-right: 1px solid #cfcfcf;
    border-bottom: 1px solid #cfcfcf;
  }
  </style>
EOF;

  echo $head_js;
}


/**
 * This is the Wordpress counterpart of the date_range_search_dropdown Smarty function. That one
 * relies on functions that use sessions.
 */
function ft_wp_date_range_search_dropdown($params)
{
  global $LANG;

  $default_value = (isset($params["default"])) ? $params["default"] : "";
  $onchange      = (isset($params["onchange"])) ? $params["onchange"] : "";
  $style         = (isset($params["style"])) ? $params["style"] : "";
  $form_id = $params["form_id"];
  $view_id = $params["view_id"];

  $attributes = array(
    "id"   => $params["name_id"],
    "name" => $params["name_id"],
    "onchange" => $onchange,
    "style" => $style
      );

  $attribute_str = "";
  while (list($key, $value) = each($attributes))
  {
    if (!empty($value))
      $attribute_str .= " $key=\"$value\"";
  }

  $rows = array();
  $rows[] = "<option value=\"\">{$LANG["phrase_select_date_range"]}</option>";
  $rows[] = "<option value=\"1\" " . (($default_value == "1") ? "selected" : "") . ">{$LANG["phrase_last_day"]}</option>";
  $rows[] = "<option value=\"2\" " . (($default_value == "2") ? "selected" : "") . ">{$LANG["phrase_last_2_days"]}</option>";
  $rows[] = "<option value=\"3\" " . (($default_value == "3") ? "selected" : "") . ">{$LANG["phrase_last_3_days"]}</option>";
  $rows[] = "<option value=\"5\" " . (($default_value == "5") ? "selected" : "") . ">{$LANG["phrase_last_5_days"]}</option>";
  $rows[] = "<option value=\"7\" " . (($default_value == "7") ? "selected" : "") . ">{$LANG["phrase_last_week"]}</option>";
  $rows[] = "<option value=\"30\" " . (($default_value == "30") ? "selected" : "") . ">{$LANG["phrase_last_month"]}</option>";
  $rows[] = "<option value=\"365\" " . (($default_value == "365") ? "selected" : "") . ">{$LANG["phrase_last_year"]}</option>";
  $rows[] = "</optgroup>";

  $dd = "<select $attribute_str>" . join("\n", $rows) . "</select>";

  return $dd;
}

/**
 * Deletes an individual submission. If the $is_admin value isn't set (or set to FALSE), it checks
 * to see if the currently logged in user is allowed to delete the submission ID.
 *
 * @param integer $form_id
 * @param integer $view_id
 * @param integer $submission_id
 * @param boolean $is_admin TODO
 */
function ft_wp_delete_submission($form_id, $view_id, $submission_id, $is_admin = false)
{
  global $g_table_prefix, $LANG;

  $form_info = ft_get_form($form_id);
  $form_fields = ft_get_form_fields($form_id);

  $auto_delete_submission_files = $form_info["auto_delete_submission_files"];
  $file_delete_problems = array();
  $form_has_file_field = false;

  // send any emails
  ft_send_emails("on_delete", $form_id, $submission_id);

  // loop the form templates to find out if there are any file fields. If there are - and the user
  // configured it - delete any associated files
  foreach ($form_fields as $field_info)
  {
    $field_type = $field_info["field_type"];

    if ($field_type == "file" || $field_type == "image")
    {
      $form_has_file_field = true;

      // store the filename we're about to delete BEFORE deleting it. The reason being,
      // if the delete_file_submission function can't find the file, it updates the database record
      // (i.e. overwrites the file name with "") and returns a message indicating what happened.
      // If this wasn't done, in the event of a file being removed/renamed by another process, the
      // user could NEVER remove the filename from their interface. This seems the least inelegant
      // solution. By storing the filename here, we can display it to the user to explain what
      // happened.
      if ($auto_delete_submission_files == "no")
        continue;

      $submission_info = ft_get_submission_info($form_id, $submission_id);
      $filename = $submission_info[$field_info['col_name']];

      // if no filename was stored, it was empty - just continue
      if (empty($filename))
        continue;

      if ($field_type == "file")
        list($success, $message) = ft_delete_file_submission($form_id, $submission_id, $field_info['field_id']);

      if (!$success)
        $file_delete_problems[] = array($filename, $message);
    }
  }

  // now delete the submission
  mysql_query("
    DELETE FROM {$g_table_prefix}form_{$form_id}
    WHERE submission_id = $submission_id
      ");


  // don't even MENTION the file. This is a bug. It's possible that file fields don't get deleted propertl
  $success = true;
  $message = $LANG["notify_submission_deleted"];

  extract(ft_process_hooks("end", compact("form_id", "view_id", "submission_id", "is_admin"), array("success", "message")), EXTR_OVERWRITE);

  return array($success, $message);
}

