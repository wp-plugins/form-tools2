<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
  <td>

    <table border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>

      <?php
      $images_url = "$g_root_url/themes/default/images";
      $curr_tab_key = "";
      $base_tab_url = "admin.php?" . wp_ft_create_url($query_string_info, "tab");

      // loop through $tabs
      $row = 1;
      foreach ($tabs as $tab_info)
      {
        $tab_label = $tab_info["tab_label"];

        // we show a tab as enabled if:
        // (a) the page var is the same as the current tab key (e.g. page=main in the query string), OR
        // (b) if curr_tab.pages is specified as an array, and $page is included in the array (used for "sub-pages" in tabs) OR
        // (c) $tab_number is specified and it's equal to $curr_tab_key
        if ($tab_number == $row) {
        ?>
          <td width="10" height="26"><img src="<?php echo $images_url?>/left_tab_selected.gif" width="10" height="26" alt=""></td>
          <td class="tab_selected nowrap" width="80"><div class="pad_left pad_right nowrap"><a href="<?php echo "$base_tab_url&tab=$row"; ?>" style="display:block"><?php echo $tab_label?></a></div></td>
          <td width="10" height="26"><img src="<?php echo $images_url?>/right_tab_selected.gif" width="10" height="26" alt=""></td>
        <?php
        } else {
        ?>
          <td width="10" height="26"><img src="<?php echo $images_url?>/left_tab_not_selected.gif" width="10" height="26" alt=""></td>
          <td class="tab_not_selected" width="80"><div class="pad_left pad_right nowrap"><a href="<?php echo "$base_tab_url&tab=$row"; ?>" style="display:block"><?php echo $tab_label?></a></div></td>
          <td width="10" height="26"><img src="<?php echo $images_url?>/right_tab_not_selected.gif" width="10" height="26" alt=""></td>
        <?php
        }
        echo '<td width="1" height="26" style="border-bottom: 1px solid #cfcfcf"> </td>';

        $row++;
      }
      ?>

      <td height="26" style="border-bottom: 1px solid #cfcfcf;">&nbsp;</td>
    </tr>
    </table>

  </td>
</tr>
<tr>
  <td class="tab_content">
