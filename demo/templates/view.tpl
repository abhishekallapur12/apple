<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>Users</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha384-ZvpUoO/+PpLXR1lu4jmpXWu80pZlYUAfxl5NsBMWOEPSjUn/6Z/hRTt8+pR6L4N2" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
<style>
{literal}
body {
	color: #566787;
	background: #f5f5f5;
	font-family: 'Varela Round', sans-serif;
	font-size: 13px;
}
.table-responsive {
	margin: 30px 0;
}
.table-wrapper {
	background: #fff;
	padding: 20px 25px;
	border-radius: 3px;
	min-width: 1000px;
	box-shadow: 0 1px 1px rgba(0,0,0,.05);
}
.table-title {
	background: #435d7d;
	color: #fff;
	padding: 16px 30px;
	min-width: 100%;
	margin: -20px -25px 10px;
	border-radius: 3px 3px 0 0;
}
.table-title h2 {
	margin: 5px 0 0;
	font-size: 24px;
}
.table-title .btn {
	color: #fff;
	float: right;
	font-size: 13px;
	border: none;
	min-width: 50px;
	border-radius: 2px;
	outline: none !important;
	margin-left: 10px;
}
.table-title .btn i {
	float: left;
	font-size: 21px;
	margin-right: 5px;
}
.table-title .btn span {
	float: left;
	margin-top: 2px;
}
.table-title .who {
	float: right;
	margin: 8px 0 0 10px;
}
.table-title .who button {
	color: #fff;
	text-decoration: underline;
	padding: 0;
}
table.table tr th, table.table tr td {
	border-color: #e9e9e9;
	padding: 12px 15px;
	vertical-align: middle;
}
table.table tr th:first-child {
	width: 60px;
}
table.table tr th:last-child {
	width: 100px;
}
table.table-striped tbody tr:nth-of-type(odd) {
	background-color: #fcfcfc;
}
table.table-striped.table-hover tbody tr:hover {
	background: #f5f5f5;
}
table.table td a, table.table td button.delete {
	font-weight: bold;
	display: inline-block;
	text-decoration: none;
	outline: none !important;
	vertical-align: middle;
}
table.table td a.edit {
	color: #FFC107;
}
table.table td button.delete {
	color: #F44336;
	background: none;
	border: none;
	padding: 0;
	cursor: pointer;
}
table.table td i {
	font-size: 19px;
	margin: 0 5px;
}
.pagination {
	float: right;
	margin: 0 0 5px;
}
.hint-text {
	float: left;
	margin-top: 10px;
	font-size: 13px;
}
/* Custom checkbox */
.custom-checkbox {
	position: relative;
}
.custom-checkbox input[type="checkbox"] {
	opacity: 0;
	position: absolute;
	margin: 5px 0 0 3px;
	z-index: 9;
}
.custom-checkbox label:before {
	content: '';
	width: 18px;
	height: 18px;
	margin-right: 10px;
	display: inline-block;
	vertical-align: text-top;
	background: white;
	border: 1px solid #bbb;
	border-radius: 2px;
	box-sizing: border-box;
	z-index: 2;
}
.custom-checkbox input[type="checkbox"]:checked + label:after {
	content: '';
	position: absolute;
	left: 6px;
	top: 3px;
	width: 6px;
	height: 11px;
	border: solid #fff;
	border-width: 0 3px 3px 0;
	z-index: 3;
	transform: rotateZ(45deg);
}
.custom-checkbox input[type="checkbox"]:checked + label:before {
	border-color: #03A9F4;
	background: #03A9F4;
}
{/literal}
</style>
<script>
{literal}
$(document).ready(function () {
	// Activate tooltips
	$('[data-toggle="tooltip"]').tooltip();

	// Select / deselect all tick boxes
	var checkbox = $('table tbody input[type="checkbox"]');
	$("#selectAll").click(function () {
		checkbox.prop("checked", this.checked);
	});
	checkbox.click(function () {
		if (!this.checked) {
			$("#selectAll").prop("checked", false);
		}
	});
});
{/literal}
</script>
</head>
<body>

<div class="container-xl">
	<div class="table-responsive">
		<div class="table-wrapper">
			<div class="table-title">
				<div class="row">
					<div class="col-sm-6">
						<h2>Manage <b>Users</b></h2>
					</div>
					<div class="col-sm-6">
						<form method="post" action="logout.php" class="who">
							<input type="hidden" name="csrf_token" value="{$csrf_token}" />
							Welcome {$current_user.name} &middot; <button type="submit" class="btn btn-link">Log out</button>
						</form>
						<form id="bulk-delete" method="post" action="delete.php" onsubmit="return confirm('Delete the selected users?');">
							<input type="hidden" name="csrf_token" value="{$csrf_token}" />
							<button type="submit" class="btn btn-danger"><i class="material-icons">&#xE15C;</i> <span>Delete selected</span></button>
						</form>
						<a href="insert.php" class="btn btn-success"><i class="material-icons">&#xE147;</i> <span>Add New User</span></a>
					</div>
				</div>
			</div>

			{if $flash}<div class="alert alert-info">{$flash}</div>{/if}

			<table class="table table-striped table-hover">
				<thead>
					<tr>
						<th>
							<span class="custom-checkbox">
								<input type="checkbox" id="selectAll">
								<label for="selectAll"></label>
							</span>
						</th>
						<th>ID</th>
						<th>Name</th>
						<th>Age</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
				{foreach $users as $u}
					<tr>
						<td>
							{if $u.id != $current_user.id}
							<span class="custom-checkbox">
								<input type="checkbox" id="user-{$u.id}" name="ids[]" value="{$u.id}" form="bulk-delete">
								<label for="user-{$u.id}"></label>
							</span>
							{/if}
						</td>
						<td>{$u.id}</td>
						<td>{$u.name}</td>
						<td>{$u.age}</td>
						<td>
							<a href="edit.php?id={$u.id}" class="edit"><i class="material-icons" data-toggle="tooltip" title="Edit">&#xE254;</i></a>
							{if $u.id != $current_user.id}
							<form method="post" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this user?');">
								<input type="hidden" name="csrf_token" value="{$csrf_token}" />
								<input type="hidden" name="ids[]" value="{$u.id}" />
								<button type="submit" class="delete"><i class="material-icons" data-toggle="tooltip" title="Delete">&#xE872;</i></button>
							</form>
							{/if}
						</td>
					</tr>
				{foreachelse}
					<tr><td colspan="5">No users yet. <a href="insert.php">Add the first one</a>.</td></tr>
				{/foreach}
				</tbody>
			</table>

			<div class="clearfix">
				<div class="hint-text">Showing <b>{$shown}</b> of <b>{$total}</b> users &middot; page {$page} of {$pages}</div>
				<ul class="pagination">
					{if $page > 1}<li class="page-item"><a class="page-link" href="view.php?page={$page - 1}">Previous</a></li>{/if}
					{if $page < $pages}<li class="page-item"><a class="page-link" href="view.php?page={$page + 1}">Next</a></li>{/if}
				</ul>
			</div>
		</div>
	</div>
</div>

</body>
</html>
