<?php

require_once("$g_root_dir/global/smarty/plugins/function.date_range_search_dropdown.php");
require_once("$g_root_dir/global/smarty/plugins/function.display_multi_select_field_values.php");

$search_field   = ft_load_field("search_field", "search_field", "");
$search_date    = ft_load_field("search_date", "search_date", "");
$search_keyword = ft_load_field("search_keyword", "search_keyword", "");

$search_fields = array(
  "search_field"   => $search_field,
  "search_date"    => $search_date,
  "search_keyword" => $search_keyword
);

if (isset($request["delete"]))
{
  // if delete actually a value, it's being fed a submission ID from the edit submission page
  // in order to delete it
  if (!empty($request["delete"]))
  {
    $ids = split(",", $_GET["delete"]);
    foreach ($ids as $id)
    {
      list($g_success, $g_message) = ft_wp_delete_submission($form_id, $view_id, $id, true);
    }
  }
}

// figure out the current page
$current_page = (isset($_GET["currpage"])) ? $_GET["currpage"] : 1;
if (isset($_GET["search"]))
{
  $query_string_info["currpage"] = 1;
  $current_page = 1;
}

$display_fields = ft_get_submission_field_info($view_info["fields"]);

// used to tell the search function which columns to return
$search_columns  = array();
foreach ($display_fields as $field_info)
  $search_columns[] = $field_info["col_name"];

// determine the sort order
if (isset($request["order"]))
{
  $order = $request["order"];
}
else
{
  $order = "{$view_info['default_sort_field']}-{$view_info['default_sort_field_order']}";
}

$results_per_page = get_option("num_submissions_per_page");

// perform the almighty search query
$results_info = ft_search_submissions($form_id, $view_id, $results_per_page, $current_page, $order, $search_columns, $search_fields);


$search_rows        = $results_info["search_rows"];
$search_num_results = $results_info["search_num_results"];
$view_num_results   = $results_info["view_num_results"];
$current_search = array(
    "form_id"          => $form_id,
    "results_per_page" => $results_per_page,
    "order"            => $order,
    "search_fields"    => $search_fields
      );

// store the list of all submission IDs for use by JS
$submission_ids = array();
for ($i=0; $i<count($search_rows); $i++)
  $submission_ids[] = $search_rows[$i]["submission_id"];
$submission_id_str = join(",", $submission_ids);


$delete_base_url = wp_ft_create_url($query_string_info);
echo <<<EOF
<script type="text/javascript">
ms.page_submission_ids = [$submission_id_str]; // the submission IDs on the current page
ms.selected_submission_ids = []; // N/A for WP plugin
ms.search_num_results = $search_num_results; // the total number of View-search results, regardless of page
ms.num_results_per_page = $results_per_page;
ms.form_id = $form_id;
ms.delete_base_url = "admin.php?$delete_base_url&delete=";

// ensures the search date field is displayed on page load (if necessary)
jQuery(function($) {
  if ($("#search_field").val() == "submission_date" || $("#search_field").val() == "last_modified_date")
    $("#search_dropdown_section").show();
});
</script>
EOF;

$count_query = mysql_query("SELECT count(*) as c FROM {$g_table_prefix}form_$form_id WHERE is_finalized = 'yes'");
$count = mysql_fetch_assoc($count_query);
$total_form_submissions = $count["c"];

$nav_base_url = wp_ft_create_url($query_string_info, "currpage");
$pagination = wp_ft_get_page_nav($search_num_results, $results_per_page, $current_page, $nav_base_url, "currpage");
$curr_search_fields = $current_search["search_fields"];

// the sort query string uses anything already in $query_string_info EXCEPT "order"
$sort_base_url = "admin.php?" . wp_ft_create_url($query_string_info, "order");
?>
  <div class="wrap">

    <h2><a href="?page=formtools/pages/forms.php"><?php echo $LANG["word_forms"]?></a> &raquo; <?php echo $form_info["form_name"]?></h2>

    <?php
    if (count($form_views) > 1)
    {
      $view_base_url = "admin.php?" . wp_ft_create_url($query_string_info, "view_id");
      ?>
      <select onchange="window.location='<?php echo $view_base_url; ?>&currpage=1&view_id=' + this.value" style="float:right">
        <optgroup label="<?php echo $LANG["word_views"]?>">
        <?php
        foreach ($form_views as $view_info)
        {
          $view_name = $view_info["view_name"];
          $selected = ($view_id == $view_info["view_id"]) ? "selected" : "";
          echo "<option value=\"{$view_info["view_id"]}\" $selected>$view_name</option>";
        }
        ?>
        </optgroup>
      </select>
    <?php } ?>

  <?php
  // if there is at least one submission in this form (and not necessary in this current search or View),
  // always display the search form
  if ($total_form_submissions == 0)
  {
    echo "<p>{$LANG["text_no_submissions_found"]}</p>";

    if ($view_info["may_add_submissions"] == "yes")
      echo "<input type=\"button\" id=\"add_submission\" value=\"{$LANG["word_add"]}\" onclick=\"window.location='admin.php?page=form_id-{$form_id}&view_id={$view_id}&add_submission'\" />";
  }
  else
  {
  ?>

  <?php if (isset($g_message) && !empty($g_message)) { ?>
  <div id="message" class="updated fade" style="background-color: rgb(255, 255, 224); padding-top: 10px; padding-bottom: 10px">
    <?php
    ft_display_message($g_success, $g_message);
    ?>
  </div>
  <?php } ?>

  <div id="search_form">

    <form action="admin.php" method="get" name="search_form" onsubmit="return rsv.validate(this, rules)">
      <?php
      unset($query_string_info["search_field"]);
      unset($query_string_info["search_keyword"]);
      while (list($key, $value) = each($query_string_info))
      {
        $value = htmlspecialchars($value);
        echo "<input type=\"hidden\" name=\"$key\" value=\"$value\" />";
      }
      ?>

      <input type="hidden" name="search" value="1" />

      <table cellspacing="0" cellpadding="0" id="search_form_table">
      <tr>
        <td class="blue" width="70"><?php echo $LANG["word_search"]?></td>
        <td>

          <table cellspacing="2" cellpadding="0">
          <tr>
            <td>
              <?php
              $info = array();
              $info["form_id"] = $form_id;
              $info["view_id"] = $view_id;
              $info["name_id"] = "search_field";
              $info["blank_option_value"] = "all";
              $info["blank_option_text"] = $LANG["phrase_all_fields"];
              $info["onchange"] = "ms.change_search_field(this.value)";
              $info["onkeyup"] = "ms.change_search_field(this.value)";
              $info["default"] = $curr_search_fields["search_field"];
              _ft_cache_form_stats();
              echo smarty_function_form_view_fields_dropdown($info, $g_smarty);
              ?>
            </td>
            <td>
              <div id="search_dropdown_section"
                <?php
                if ($curr_search_fields["search_field"] != "submission_date" && $curr_search_fields["search_field"] != "last_modified_date")
                  echo 'style="display: none"';
                ?>>
                <?php
                $info = array();
                $info["name_id"] = "search_date";
                $info["view_id"] = $view_id;
                $info["form_id"] = $form_id;
                $info["default"] = $curr_search_fields["search_date"];
                echo ft_wp_date_range_search_dropdown($info);
                ?>
              </div>
            </td>
          </tr>
          </table>

        </td>
        <td width="25"><?php echo $LANG["word_for"]?></td>
        <td>
          <input type="text" style="width: 120px" name="search_keyword" value="<?php echo htmlspecialchars($curr_search_fields["search_keyword"])?>" />
        </td>
        <td>
          <input type="submit" name="search" value="<?php echo $LANG["word_search"]?>" />
          <input type="button" name="" value="<?php echo $LANG["phrase_show_all"]?>" onclick="window.location='admin.php?page=form_id-<?php echo $form_id?>&reset=1'"
            <?php if ($search_num_results < $view_num_results) echo 'class="bold"'; ?> />
        </td>
      </tr>
      </table>

    </form>

  </div>

  <?php if ($search_num_results == 0) { ?>
    <p>
      <?php echo $LANG["text_no_search_results"]; ?>
    </p>
  <?php } else { ?>

    <div style="float:right">
      <?php echo $pagination; ?>
    </div>

    <div style="padding-top: 5px;">
      <?php if ($view_info["may_delete_submissions"] == "yes") { ?>
        <input type="button" value="Delete Selected" class="red" onclick="ms.delete_submissions()" />
      <?php } ?>

      <?php if ($view_info["may_add_submissions"] == "yes") { ?>
        <input type="button" id="add_submission" value="<?php echo $LANG["word_add"]?>" onclick="window.location='admin.php?page=form_id-<?php echo $form_id?>&view_id=<?php echo $view_id?>&add_submission'" />
      <?php } ?>
    </div>

    <form name="current_form" action="<?php echo $same_page?>" method="post">

    <table class="submissions_table widefat post fixed" id="submissions_table" cellpadding="1" cellspacing="1" border="0" width="100%">
    <thead>
    <tr>
      <th id="cb" align="center" class="manage-column column-cb check-column" scope="col"><input type="checkbox" /></th>
      <?php
      foreach ($display_fields as $field)
      {
        if ($field["is_sortable"] == "yes")
        {
          // determine the column sorting (if included in query string, reverse)
          $up_down = "";
          if ($order == "{$field["col_name"]}-DESC")
          {
            $order_col = "&order={$field["col_name"]}-ASC";
          }
          else if ($order == "{$field["col_name"]}-ASC")
          {
            $order_col = "&order={$field["col_name"]}-DESC";
          }
          else
          {
            $order_col = "&order={$field["col_name"]}-DESC";
          }
          ?>
          <th nowrap><a href="<?php echo "{$sort_base_url}{$order_col}"; ?>"><?php echo $field["field_title"];?></a></th>
          <?php
        }
        else
          echo "<th>{$field["field_title"]}</th>";
      }
      ?>
      <th width="50" style="text-align:center">
        <?php
        if ($view_info["may_edit_submissions"] == "yes")
          echo strtoupper($LANG["word_edit"]);
        else
          echo strtoupper($LANG["word_view"]);
        ?>
      </th>
    </tr>
    </thead>
    <tfoot>
    <tr>
      <th id="cb" align="center" class="manage-column column-cb check-column" scope="col"><input type="checkbox" /></th>
      <?php
      foreach ($display_fields as $field)
      {
        if ($field["is_sortable"] == "yes")
        {
          // determine the column sorting (if included in query string, reverse)
          $up_down = "";
          if ($order == "{$field["col_name"]}-DESC")
          {
            $order_col = "&order={$field["col_name"]}-ASC";
          }
          else if ($order == "{$field["col_name"]}-ASC")
          {
            $order_col = "&order={$field["col_name"]}-DESC";
          }
          else
          {
            $order_col = "&order={$field["col_name"]}-DESC";
          }
          ?>
          <th nowrap><a href="<?php echo "{$sort_base_url}{$order_col}"; ?>"><?php echo $field["field_title"];?></a></th>
          <?php
        }
        else
          echo "<th>{$field["field_title"]}</th>";
      }
      ?>
      <th width="50" style="text-align:center">
        <?php
        if ($view_info["may_edit_submissions"] == "yes")
          echo strtoupper($LANG["word_edit"]);
        else
          echo strtoupper($LANG["word_view"]);
        ?>
      </th>
    </tr>
    </tfoot>

    <?php
    foreach ($search_rows as $search_row)
    {
      $submission_id = $search_row["submission_id"];
      ?>
      <tr id="submission_row_<?php echo $submission_id?>">
        <th class="manage-column column-cb check-column" scope="col">
          <input type="checkbox" id="submission_cb_<?php echo $submission_id?>" name="submissions[]" value="<?php echo $submission_id?>" />
        </th>
      <?php

        // for each search row, loop through the display fields and display the appropriate content for the submission field
        foreach ($display_fields as $curr_field)
        {
          $field_id   = $curr_field["field_id"];
          $field_type = $curr_field["field_info"]["field_type"];
          $col_name   = $curr_field["col_name"];
          $nowrap_rightpad = "";
          $td_class   = "";
          $cell_value = "";

          // select and radio buttons show the appropriate display value
          if ($field_type == "select" || $field_type == "radio-buttons")
          {
            $val = $search_row[$col_name];

            foreach ($curr_field["field_info"]["options"] as $option)
            {
              if ($option["option_value"] == $val)
                $cell_value = $option["option_name"];
            }
          }
          else if ($field_type == "checkboxes" || $field_type == "multi-select")
          {
            $info = array();
            $info["values"] = $search_row[$col_name];
            $info["options"] = $curr_field["field_info"]["options"];
            $cell_value = smarty_function_display_multi_select_field_values($info, $g_smarty);

            // this helper function displays the values of a multi-select field (checkboxes / multi-select dropdown)
            // {display_multi_select_field_values options=$curr_field.field_info.options values=$value var_name="cell_value"}
          }
          else if ($field_type == "system")
          {
            if ($col_name == "submission_id")
            {
              $td_class   = "submission_id";
              $cell_value = $submission_id;
            }
            else if ($col_name == "submission_date")
            {
              $td_class   = "dates";
              $cell_value = date($account_settings["date_format"], ft_convert_datetime_to_timestamp($search_row["submission_date"]));
            }
            else if ($col_name == "last_modified_date")
            {
              $td_class = "dates";
              $cell_value = date($account_settings["date_format"], ft_convert_datetime_to_timestamp($search_row["last_modified_date"]));
            }
            else if ($col_name == "ip_address")
            {
              $td_class   = "ip_address";
              $cell_value = $search_row["ip_address"];
            }

            // only make system fields as wide as they need to be
            $nowrap_rightpad = "nowrap pad_right_small";
          }
          else
            $cell_value = $search_row[$col_name];

          $cell_value = ft_trim_string(htmlspecialchars($cell_value), 80);
          echo "<td class=\"{$td_class}\">{$cell_value}</td>";
        }

        $edit_base_url = "admin.php?" . wp_ft_create_url($query_string_info);

        echo "<td align=\"center\"><a href=\"$edit_base_url&submission_id={$submission_id}\">";

        if ($view_info["may_edit_submissions"] == "yes")
          echo mb_strtoupper($LANG["word_edit"]);
        else
          echo $LANG["word_view"];

      echo "</a></td></tr>";
    }
    ?>
    </table>

    <div style="float:right"><?php echo $pagination; ?></div>

    <div style="padding-top: 5px;">
      <?php if ($view_info["may_delete_submissions"] == "yes") { ?>
        <input type="button" value="Delete Selected" class="red" onclick="ms.delete_submissions()" />
      <?php } ?>

      <?php if ($view_info["may_add_submissions"] == "yes") { ?>
        <input type="button" id="add_submission" value="<?php echo $LANG["word_add"]?>" onclick="window.location='admin.php?page=form_id-<?php echo $form_id?>&view_id=<?php echo $view_id?>&add_submission'" />
      <?php } ?>
    </div>


    </form>


    <?php } ?>

  <?php } ?>

  </div>
