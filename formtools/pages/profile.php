<h3>Form Tools</h3>

<table class="form-table">
<tbody>
<tr>
	<th><label for="description">Map Access Level to FT account</label></th>
	<td>
	  <?php
		$user_id = $_GET["user_id"];
	  echo ft_wp_generate_role_dropdown("form_tools_access", get_usermeta($user_id, "form_tools_access"));
	  ?>
  </td>
</tr>
</tbody>
</table>
