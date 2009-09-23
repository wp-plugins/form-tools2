<div class="wrap">
<h2>Form Tools</h2>

<?php if (!ft_wp_check_form_tools_path_found()) { ?>
<div id="error" class="error fade" style="background-color: pink;">
<p>
  <strong>
    In order to use this plugin, you need to first enter the path and URL to your Form Tools root folder below. Once you have entered
    these values this message will disappear.
  </strong>
</p>
</div>
<?php } ?>

<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>

<h3>Main Settings</h3>

<table class="form-table">
<tbody><tr valign="top">
  <th scope="row">Form Tools root dir</th>
  <td>
    <input type="text" name="formtools_config_php_path" value="<?php echo get_option('formtools_config_php_path'); ?>" style="width:100%" /><br />
    <span class="description">This field should contain the <b>$g_root_dir</b> field value, found in your Form Tools /global/config.php file</span>
  </td>
</tr>
<tr valign="top">
  <th scope="row">Form Tools root URL</th>
  <td>
    <input type="text" name="formtools_config_php_url" value="<?php echo get_option('formtools_config_php_url'); ?>" style="width:100%" /><br />
    <span class="description">This field should contain the <b>$g_root_url</b> field value, found in your Form Tools /global/config.php file</span>
  </td>
</tr>

<?php if (!ft_wp_check_form_tools_path_found()) { ?>
</table>
<input type="hidden" name="num_submissions_per_page" value="10" class="small-text" />

<?php } else { ?>

<tr valign="top">
  <th scope="row">Num submissions per page</th>
  <td><input type="text" name="num_submissions_per_page" value="<?php echo get_option('num_submissions_per_page'); ?>" class="small-text" /></td>
</tr>
</tbody></table>

<h3>User Settings</h3>

<table class="form-table">
<tbody><tr valign="top">
  <th scope="row">Default Wordpress account map</th>
  <td>

    <table cellspacing="0" cellpadding="1" class="widefat post fixed" style="width: 450px">
    <thead>
      <tr>
        <th width="150">Role</th>
        <th>Access Level</th>
      </tr>
    </thead>
    <?php
    $update_settings_str = "";
    $roles = get_editable_roles();
    while (list($role_name, $info) = each($roles))
    {
      $update_settings_str .= ",formtoolsaccess__{$role_name}";
      $dropdown = ft_wp_generate_role_dropdown("formtoolsaccess__{$role_name}", get_option("formtoolsaccess__{$role_name}"));

      echo <<<EOF
<tr>
  <td width="150">{$info["name"]}</td>
  <td>{$dropdown}</td>
</tr>
EOF;
    }
    ?>
    </table>

  </td>
</tr>
</tbody></table>

<?php } ?>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="formtools_config_php_path,formtools_config_php_url,num_submissions_per_page<?php echo $update_settings_str?>" />

<p class="submit">
  <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
</p>

</form>
</div>
