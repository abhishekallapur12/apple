<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Add user</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<style>
{literal}
body {
	color: #566787;
	background: #f5f5f5;
	font-family: 'Varela Round', sans-serif;
	font-size: 13px;
	padding: 30px 0;
}
.card {
	padding: 20px 25px;
}
{/literal}
</style>
</head>
<body>

<div class="container">
	<div class="row justify-content-center">
		<div class="col-sm-6">
			{if !$first_user}<p><a href="view.php">&larr; Back to the user list</a></p>{/if}
			<div class="card">
				<h2>{if $first_user}Create the first <b>user</b>{else}Add <b>user</b>{/if}</h2>
				{if $errors}
				<div class="alert alert-danger">
					<ul class="mb-0">{foreach $errors as $message}<li>{$message}</li>{/foreach}</ul>
				</div>
				{/if}
				<form method="post" action="insert.php">
					<input type="hidden" name="csrf_token" value="{$csrf_token}" />
					<div class="form-group">
						<label for="name">Name</label>
						<input type="text" class="form-control" name="name" id="name" value="{$values.name}" maxlength="100" required />
					</div>
					<div class="form-group">
						<label for="age">Age</label>
						<input type="number" class="form-control" name="age" id="age" value="{$values.age}" min="0" max="150" required />
					</div>
					<div class="form-group">
						<label for="password">Password</label>
						<input type="password" class="form-control" name="password" id="password" autocomplete="new-password" minlength="8" required />
						<small class="form-text text-muted">At least 8 characters.</small>
					</div>
					<input type="submit" class="btn btn-primary" name="submit" value="Add Record" />
				</form>
			</div>
		</div>
	</div>
</div>

</body>
</html>
