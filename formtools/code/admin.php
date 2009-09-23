<?php

function ft_wp_admin_menu()
{
  global $g_root_url, $wp_account_id;

  // this adds the "Form Tools" submenu item under the main Settings nav menu
  add_options_page('Form Tools Options', 'Form Tools', 8, 'formtools/pages/options.php');

  if (!empty($g_root_url))
  {
    $ft_account_id = ft_wp_get_ft_account_id($wp_account_id);

    if (!empty($ft_account_id))
    {
      $g_is_administrator = (ft_wp_get_wpuser_role($ft_account_id) == "administrator") ? true : false;

      if ($g_is_administrator)
        $forms = ft_get_forms();
      else
        $forms = ft_get_client_forms($ft_account_id);

      if (count($forms))
      {
        $menu_page = add_menu_page("Forms", "Forms", 0, "formtools/pages/forms.php");

        for ($i=0; $i<count($forms); $i++)
        {
          if ($forms[$i]["is_complete"] == "no")
            continue;

          $form_name = $forms[$i]["form_name"];
          $form_id   = $forms[$i]["form_id"];
          $submissions_page = add_submenu_page("formtools/pages/forms.php", $form_name, $form_name, 0, "form_id-$form_id", "ft_wp_display_submissions_page");

          // this adds the required <head> content to the page
          add_action('admin_head-'. $submissions_page, 'ft_wp_load_submission_head');
        }
      }
    }
  }
}

/**
 * Displays the main submission listing page.
 */
function ft_wp_display_submissions_page()
{
  global $g_smarty, $LANG, $g_table_prefix;

  $template = realpath(dirname(__FILE__) . "/../pages/submissions.php");
  include_once($template);
}
