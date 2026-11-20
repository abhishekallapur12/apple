{config_load file="test.conf" section="setup"}
{include file="header.tpl" title="Login"}
{if $flash}<p><strong>{$flash}</strong></p>{/if}
{if $error}<p style="color:#b00020">{$error}</p>{/if}
<form method="post" action="login.php">
	<input type="hidden" name="csrf_token" value="{$csrf_token}" />
	<table width="550" border="1">
		<tr>
			<td colspan="2" align="center"><strong>Login</strong></td>
		</tr>
		<tr>
			<td><label for="name">Name</label></td>
			<td><input type="text" name="name" id="name" value="{$name}" autocomplete="username" required /></td>
		</tr>
		<tr>
			<td><label for="password">Password</label></td>
			<td><input type="password" name="password" id="password" autocomplete="current-password" required /></td>
		</tr>
		<tr>
			<td></td>
			<td><input type="submit" name="submit" value="Login" /></td>
		</tr>
	</table>
</form>
{include file="footer.tpl"}
