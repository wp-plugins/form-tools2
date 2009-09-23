<?php

require_once("$g_root_dir/global/smarty/plugins/function.display_edit_submission_view_dropdown.php");
require_once("$g_root_dir/global/smarty/plugins/function.submission_dropdown.php");
require_once("$g_root_dir/global/smarty/plugins/function.submission_dropdown_multiple.php");
require_once("$g_root_dir/global/smarty/plugins/function.submission_radios.php");
require_once("$g_root_dir/global/smarty/plugins/function.submission_checkboxes.php");
require_once("$g_root_dir/global/smarty/plugins/function.display_file_field.php");

$submission_id = $query_string_info["submission_id"];
$tab_number = (isset($_GET["tab"])) ? $_GET["tab"] : 1;
$query_string_info["tab"] = $tab_number;

// get a list of all editable fields in the View. This is used both for security purposes
// for the update function and to determine whether the page contains any editable fields
$editable_field_ids = _ft_get_editable_view_fields($view_id);

// get the tabs for this View
$view_tabs = ft_get_view_tabs($view_id, true);

// handle POST requests
if (isset($_POST) && !empty($_POST))
{
  // add the view ID to the request hash, for use by the ft_update_submission function
  $request["view_id"] = $view_id;
  $request["editable_field_ids"] = $editable_field_ids;
  list($g_success, $g_message) = ft_update_submission($form_id, $submission_id, $request);

  // if required, remove a file or image
  $file_deleted = false;
  if (isset($_POST['delete_file_type']) && $_POST['delete_file_type'] == "file")
  {
    list($g_success, $g_message) = ft_delete_file_submission($form_id, $submission_id, $_POST['field_id']);
    $file_deleted = true;
  }

  // TODO this deprecated??
  else if (isset($_POST['email_user']) && !empty($_POST['email_user']))
  {
    $g_success = ft_send_email("user", $form_id, $submission_id);
    if ($g_success)
      $g_message = $LANG["notify_email_sent_to_user"];
  }
}

// check the user can view this submission
if (!$g_is_administrator && !ft_wp_check_client_may_view($ft_account_id, $form_id, $view_id))
{
  header("location: admin.php?page=formtools/pages/forms.php");
  exit;
}


$form_info       = ft_get_form($form_id);
$view_info       = ft_get_view($view_id);
$submission_info = ft_get_submission($form_id, $submission_id, $view_id);

// get the subset of fields (and IDs) from $submission_info that appear on the current tab (or tab-less page)
$submission_tab_fields    = array();
$submission_tab_field_ids = array();

for ($i=0; $i<count($submission_info); $i++)
{
  // if this view has tabs, ignore those fields that aren't on the current tab.
  if (count($view_tabs) > 0 && (!isset($submission_info[$i]["tab_number"]) || $submission_info[$i]["tab_number"] != $tab_number))
    continue;

  $curr_field_id = $submission_info[$i]["field_id"];

  $submission_tab_field_ids[] = $curr_field_id;
  $submission_tab_fields[]    = $submission_info[$i];
}

// get a list of editable fields on this tab
$editable_tab_fields = array_intersect($submission_tab_field_ids, $editable_field_ids);

$tabs = array();
while (list($key, $value) = each($view_tabs))
{
  $tabs[$key] = array("tab_label" => $value["tab_label"]);
}

// construct the page label
$edit_submission_page_label = $form_info["edit_submission_page_label"];
$common_placeholders = _ft_get_placeholder_hash($form_id, $submission_id);

$smarty = new Smarty();
$smarty->template_dir = "$g_root_dir/global/smarty/";
$smarty->compile_dir  = "$g_root_dir/themes/default/cache/";
$smarty->assign("LANG", $LANG);
$smarty->assign("eval_str", $edit_submission_page_label);
while (list($key, $value) = each($common_placeholders))
  $smarty->assign($key, $value);
reset($common_placeholders);
$edit_submission_page_label = $smarty->fetch("eval.tpl");

$tab_has_editable_fields = count($editable_tab_fields) > 0;

?>
  <div class="wrap">

    <h2>
      <a href="?page=formtools/pages/forms.php"><?php echo $LANG["word_forms"]?></a> &raquo;
      <?php
      $form_url = "admin.php?" . wp_ft_create_url($query_string_info, "submission_id");
      ?>
      <a href="<?php echo $form_url; ?>"><?php echo $form_info["form_name"]?></a> &raquo;
      <?php echo $edit_submission_page_label;?>
    </h2>

    <?php
    if (isset($g_message) && !empty($g_message))
    {
      if ($g_success) {
    ?>
      <div id="message" class="updated fade" style="background-color: rgb(255, 255, 224); padding-top: 10px; padding-bottom: 10px">
        <?php
        ft_wp_display_message($g_success, $g_message);
        ?>
      </div>
    <?php
      }
      else
      {
      ?>
      <div id="message" class="error" style="padding-top: 10px; padding-bottom: 10px">
        <?php
        ft_wp_display_message($g_success, $g_message);
        ?>
      </div>
      <?php
      }
    }
    else
    {
      echo "<div style=\"padding-bottom: 10px\" id=\"message\"> </div>";
    }

    if (count($tabs) > 0)
    {
      $folder = dirname(__FILE__);
      require_once("$folder/_tabset_open.php");
    }
    $update_form_url = "admin.php?" . wp_ft_create_url($query_string_info);
    ?>

        <form action="<?php echo $update_form_url?>" method="post" name="edit_submission_form" enctype="multipart/form-data">
          <input type="hidden" name="form_id" id="form_id" value="<?php echo $form_id?>" />
          <input type="hidden" id="submission_id" value="<?php echo $submission_id?>" />

          <?php if (count($submission_tab_fields) > 0) { ?>
            <table class="list_table" cellpadding="1" cellspacing="1" border="0" width="100%" style="margin-top:10px">
          <?php } ?>

          <?php
          // loop through the submission and display all the contents
          foreach ($submission_tab_fields as $submission_field)
          {
            $field_id = $submission_field["field_id"];
            $field_ids[] = $field_id;
          ?>

            <tr>
              <td width="200" class="pad_left_small" valign="top"><?php echo $submission_field["field_title"]?></td>
              <td>

              <?php
              if ($submission_field["field_type"] == "select")
              {
                $info = array(
                  "name" => $submission_field["col_name"],
                  "field_id" => $field_id,
                  "selected" => $submission_field["content"],
                  "is_editable" => $submission_field["is_editable"]
                );
                smarty_function_submission_dropdown($info, $g_smarty);
              }
              else if ($submission_field["field_type"] == "radio-buttons")
              {
                $info = array(
                  "name" => $submission_field["col_name"],
                  "field_id" => $field_id,
                  "selected" => $submission_field["content"],
                  "is_editable" => $submission_field["is_editable"]
                );
                ft_wp_submission_radios($info, $g_smarty);
              }
              else if ($submission_field["field_type"] == "checkboxes")
              {
                $info = array(
                  "name" => $submission_field["col_name"],
                  "field_id" => $field_id,
                  "selected" => $submission_field["content"],
                  "is_editable" => $submission_field["is_editable"]
                );
                ft_wp_submission_checkboxes($info, $g_smarty);
              }
              else if ($submission_field["field_type"] == "multi-select")
              {
                $info = array(
                  "name" => $submission_field["col_name"],
                  "field_id" => $field_id,
                  "selected" => $submission_field["content"],
                  "is_editable" => $submission_field["is_editable"],
                  "style" => "height: 80px"
                );
                ft_wp_submission_dropdown_multiple($info, $g_smarty);
              }
              else if ($submission_field["field_type"] == "file")
              {
              ?>
                <span id="field_<?php echo $field_id?>_link" <?php if ($submission_field["content"] == "") echo 'style="display:none"'; ?>>
                  <?php
                  $info = array(
                    "field_id" => $field_id,
                    "filename" => $submission_field["content"]
                  );
                  echo smarty_function_display_file_field($info, $smarty);

                  if ($submission_field["is_editable"] == "yes")
                  {
                    $delete_file = mb_strtoupper($LANG["phrase_delete_file"]);
                    echo "&nbsp;<input type=\"button\" value=\"$delete_file\" onclick=\"ms.delete_submission_file({$field_id})\" />";
                  }
                  ?>
                </span>

                <span id="field_<?php echo $field_id?>_upload_field" <?php if ($submission_field["content"] != "") echo 'style="display:none"'; ?>>
                  <?php
                  if ($submission_field["is_editable"] == "yes")
                  {
                    echo "&nbsp;<input type=\"file\" name=\"{$submission_field["col_name"]}\" />";
                  }
                  ?>
                </span>

                <span id="file_field_<?php echo $field_id?>_message_id"></span>
              <?php
              }
              else if ($submission_field["field_type"] == "system")
              {
                if ($submission_field["col_name"] == "submission_id")
                {
                  echo "<b>{$submission_field["content"]}</b>";
                }
                else if ($submission_field["col_name"] == "ip_address")
                {
                  if ($submission_field["is_editable"] == "yes")
                  {
                    echo "<input type=\"text\" style=\"width: 100px;\" name=\"{$submission_field["col_name"]}\" value=\"{$submission_field["content"]}\" />";
                  }
                  else
                  {
                    echo $submission_field["content"];
                  }
                }
                else if ($submission_field["col_name"] == "submission_date")
                {
                  if ($submission_field["is_editable"] == "yes")
                  {
                  ?>
                    <table cellspacing="0" cellpadding="0">
                    <tr>
                      <td><input type="text" style="width: 125px;" name="<?php echo $submission_field["col_name"]?>" id="<?php echo $submission_field["col_name"]; ?>" value="<?php echo $submission_field["content"];?>" /></td>
                      <td><img src="<?php echo $theme_url;?>/images/calendar_icon.gif" id="date_image_<?php echo $field_id;?>" style="cursor:pointer" /></td>
                    </tr>
                    </table>
                    <script type="text/javascript">
                    Calendar.setup({
                       inputField     :    "<?php echo $submission_field["col_name"]; ?>",
                       showsTime      :    true,
                       timeFormat     :    "24",
                       ifFormat       :    "%Y-%m-%d %H:%M:00",
                       button         :    "date_image_<?php echo $field_id?>",
                       align          :    "tr",
                       singleClick    :    true
                    });
                    </script>
                  <?php
                  }
                  else
                  {
                    echo date($account_settings["date_format"], ft_convert_datetime_to_timestamp($submission_field["content"]));
                  }
                }
                else if ($submission_field["col_name"] == "last_modified_date")
                {
                  if ($submission_field["is_editable"] == "yes")
                  {
                  ?>
                    <table cellspacing="0" cellpadding="0">
                    <tr>
                      <td><input type="text" style="width: 125px;" name="<?php echo $submission_field["col_name"]?>"
                        id="<?php echo $submission_field["col_name"]?>" value="<?php echo $submission_field["content"]?>" /></td>
                      <td><img src="<?php echo $theme_url?>/images/calendar_icon.gif" id="date_image_<?php echo $field_id?>" style="cursor:pointer" /></td>
                    </tr>
                    </table>
                    <script type="text/javascript">
                    Calendar.setup({
                       inputField     :    "{$submission_field["col_name"]}",
                       showsTime      :    true,
                       timeFormat     :    "24",
                       ifFormat       :    "%Y-%m-%d %H:%M:00",
                       button         :    "date_image_<?php echo $field_id?>",
                       align          :    "tr",
                       singleClick    :    true
                    });
                    </script>
                  <?php
                  }
                  else
                  {
                    echo date($account_settings["date_format"], ft_convert_datetime_to_timestamp($submission_field["content"]));
                  }
                }
              }
              else if ($submission_field["field_type"] == "wysiwyg")
              {
                if ($submission_field["is_editable"] == "yes")
                {
                  echo "<textarea name=\"{$submission_field["col_name"]}\" id=\"field_<?php echo $field_id?>_wysiwyg\" style=\"width: 100%; height: 160px\">{$submission_field["content"]}</textarea>";
                }
                else
                {
                  echo $submission_field["content"];
                }
              }
              else if ($submission_field["field_type"] == "password")
              {
                if ($submission_field["is_editable"] == "yes")
                {
                  $content = htmlspecialchars($submission_field["content"]);
                  echo "<input type=\"password\" name=\"{$submission_field["col_name"]}\" value=\"{$content}\" style=\"width: 150px;\" />";
                }
              }
              else
              {
                if ($submission_field["is_editable"] == "yes")
                {
                  $escaped_content = htmlspecialchars($submission_field["content"]);
                  if ($submission_field["field_size"] == "tiny")
                  {
                    echo "<input type=\"text\" name=\"{$submission_field["col_name"]}\" value=\"$escaped_content\" style=\"width: 50px;\" />";
                  }
                  else if ($submission_field["field_size"] == "small")
                  {
                    echo "<input type=\"text\" name=\"{$submission_field["col_name"]}\" value=\"$escaped_content\" style=\"width: 150px;\" />";
                  }
                  else if ($submission_field["field_size"] == "medium")
                  {
                    echo "<input type=\"text\" name=\"{$submission_field["col_name"]}\" value=\"$escaped_content\" style=\"width: 99%;\" />";
                  }
                  else if ($submission_field["field_size"] == "large" || $submission_field["field_size"] == "very_large")
                  {
                    echo "<textarea name=\"{$submission_field["col_name"]}\" style=\"width: 99%; height: 80px\">{$submission_field["content"]}</textarea>";
                  }
                }
                else
                {
                  echo $submission_field["content"];
                }
              }
              ?>
            </td>
          </tr>
          <?php
          }
          ?>

          <?php if (count($submission_tab_fields) > 0) { ?>
            </table>
          <?php } ?>

          <input type="hidden" name="field_ids" value="<?php echo join(",", $field_ids)?>" />

          <?php
          // if there are no fields in this tab, display a message to let the user know
          if (count($submission_tab_fields) == 0)
          {
            echo "<div>{$LANG["notify_no_fields_in_tab"]}</div>";
          }
          ?>
          <br />

          <div style="position:relative">
            <span style="float:right">
              <?php
              // show the list of whatever email templates can be send from this page
              // {display_email_template_dropdown form_id=$form_id view_id=$view_id submission_id=$submission_id}
              ?>
             </span>

            <?php
            // only show the update button if there are editable fields in the tab
            if (count($submission_tab_fields) > 0 && $tab_has_editable_fields)
            {
              $word_update = mb_strtoupper($LANG["word_update"]);
              echo "<input type=\"submit\" class=\"button-primary\" name=\"update\" value=\"{$word_update}\" />";
            }
            if ($view_info["may_delete_submissions"] == "yes")
            {
              $word_delete = mb_strtoupper($LANG["word_delete"]);
              $target_webpage = "admin.php?page=form_id-{$form_id}&view_id=$view_id";
              echo "&nbsp;<span class=\"submit\"><input type=\"button\" name=\"delete\" value=\"{$word_delete}\" class=\"red\" onclick=\"return ms.delete_submission({$submission_id}, '$target_webpage')\" /></span>";
            }
            ?>
          </div>

        </form>

        <?php
        if (count($tabs) > 0)
        {
           echo "</td></tr></table>";
        }
        ?>

      </div>

      <br />

  </div>