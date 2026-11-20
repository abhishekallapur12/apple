{config_load file="test.conf" section="setup"}
{include file="header.tpl" title="Edit user"}
<p><a href="view.php">&larr; Back to the user list</a></p>
{if $errors}
<ul style="color:#b00020">
	{foreach $errors as $message}<li>{$message}</li>{/foreach}
</ul>
{/if}
<form method="post" action="edit.php">
	<input type="hidden" name="csrf_token" value="{$csrf_token}" />
	<input type="hidden" name="id" value="{$user_id}" />
	<table width="550" border="1">
		<tr>
			<td colspan="2" align="center"><strong>Edit Record</strong></td>
		</tr>
		<tr>
			<td><label for="name">Name</label></td>
			<td><input type="text" name="name" id="name" value="{$values.name}" maxlength="100" required /></td>
		</tr>
		<tr>
			<td><label for="age">Age</label></td>
			<td><input type="number" name="age" id="age" value="{$values.age}" min="0" max="150" required /></td>
		</tr>
		<tr>
			<td><label for="password">New password</label></td>
			<td>
				<input type="password" name="password" id="password" autocomplete="new-password" minlength="8" />
				<br /><small>Leave empty to keep the current password.</small>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" name="submit" value="Save changes" /></td>
		</tr>
	</table>
</form>
{include file="footer.tpl"}
