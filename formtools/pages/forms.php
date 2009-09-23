<?php
$g_root_dir = get_option("formtools_config_php_path");
@include_once("$g_root_dir/global/library.php");

$order     = ft_load_field("order", "form_sort_order", "form_name-ASC");
$num_forms = ft_get_form_count();

$ft_account_id = ft_wp_get_ft_account_id($wp_account_id);
$g_is_administrator = (ft_wp_get_wpuser_role($ft_account_id) == "administrator") ? true : false;

if ($g_is_administrator)
  $forms = ft_search_forms("", true, array());
else
  $forms = ft_get_client_forms($ft_account_id);

$clients   = ft_get_client_list();
$settings = ft_get_settings();

// ALL forms will be displayed on this page
$settings["num_forms_per_page"] = 10000;

// compile template info
$page_vars = array();
$page_vars["num_forms"] = $num_forms;
$page_vars["forms"] = $forms;
$page_vars["order"] = $order;

$theme_url = "";
?>
<div class="wrap">
  <h2>Forms</h2>

  <?php if ($num_forms == 0) { ?>
    <div><?=$LANG["text_no_forms"]?></div>
  <?php } else { ?>

    <?php if (count($forms) == 0) { ?>

      <div class="notify yellow_bg">
        <div style="padding: 8px">
          <?=$LANG["text_no_forms_found"]?>
        </div>
      </div>

    <?php } else { ?>


      <p>
        This lists all forms in the Form Tools database. Click on the VIEW links to view that form's submissions.
      </p>

      <form action="<?=$_SERVER["PHP_SELF"]?>" method="post">

      <?php
      $table_group_id = 1;
      for ($i=0; $i<count($forms); $i++)
      {
        $count = $i+1;

        $form_info = $forms[$i];
        $form_id = $form_info["form_id"];

        // if it's the first row or the start of a new table, open the table & display the headings *}
        if ($count == 1 || ($count != 1 && (($count-1) % $settings["num_forms_per_page"] == 0)))
        {
          $style = "display: none";

          if ($table_group_id == "1")
            $style = "display: block";

       ?>

          <div id="page_<?=$table_group_id?>" style="<?=$style?>">

          <table class="widefat post fixed" width="100%" cellpadding="0" cellspacing="1">
          <thead>
            <tr>
              <th class="manage-column column-title" scope="col" style="width: 40px"><?=$LANG["word_id"]?></th>
              <th><?=$LANG["word_form"]?></th>
              <th><?=$LANG["phrase_form_url"]?></th>
              <th width="70"><?=$LANG["word_status"]?></th>
              <th width="90"><?=mb_strtoupper($LANG["word_submissions"])?></th>
            </tr>
          </thead>
          <tfoot>
            <tr>
              <th class="manage-column column-title" scope="col" style="width: 40px"><?=$LANG["word_id"]?></th>
              <th><?=$LANG["word_form"]?></th>
              <th><?=$LANG["phrase_form_url"]?></th>
              <th width="70"><?=$LANG["word_status"]?></th>
              <th width="90"><?=mb_strtoupper($LANG["word_submissions"])?></th>
            </tr>
          </tfoot>

         <?php } ?>

          <tr>
            <td><?=$form_id?></td>
            <td><?=$form_info["form_name"]?></td>
            <td><a href="<?=$form_info["form_url"]?>" target="_blank"><?=$form_info["form_url"]?></a></td>
            <td>

              <?php
              $status = "<span style=\"color: green\">{$LANG["word_online"]}</span>";
              if ($form_info["is_active"] == "no")
                $status = "<span style=\"color: orange\">{$LANG["word_offline"]}</span>";

              if ($form_info["is_complete"] == "no")
              {
                $status = "<span style=\"color: red\">{$LANG["word_incomplete"]}</span>";
                $file   = "add/step1.php";
              }
              else
                $file = "edit.php";

              echo $status;
              ?>

            </td>
            <td style="text-align:center">

              <?php
              $text = mb_strtoupper($LANG["word_view"]);
              if ($form_info["is_complete"] == "yes")
                echo "<a href=\"admin.php?page=form_id-$form_id\">$text</a>";
              ?>

            </td>
          </tr>

        <?php
        if ($count != 1 && ($count % $settings["num_forms_per_page"]) == 0)
        {
          echo "</table></div>";
          $table_group_id++;
        }

      }

      // if the table wasn't closed, close it!
      if ((count($forms) % $settings["num_forms_per_page"]) != 0)
        echo "</table></div>";

    }
    ?>

    </form>

  <?php } ?>

</div>
