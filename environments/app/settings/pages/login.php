<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Login - App Builder Control Panel</title>

		<link href="theme/css/bootstrap.min.css" rel="stylesheet">
		<link href="theme/css/bootstrap-theme.css" rel="stylesheet">
	</head>
	<body>
		<nav class="navbar navbar-default">
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="#">
						<img alt="Maple Logo" src="theme/images/logo.png" height="20" width="20" style="display:inline-block;">
						App Builder
					</a>
				</div>
			</div>
		</nav>
		<div class="container">
			<img alt="Maple Logo" src="theme/images/logo.png" height="200" width="200" class="center-block">
			<div class="row">
				<div class=" col-sm-12 col-lg-6 col-lg-offset-3">
					<div class="panel panel-primary">
						<div class="panel-heading text-center">App Builder Control Panel</div>
						<div class="panel-body">
							<form action="" method="post">
								<?php if(isset($_REQUEST["error"])): ?>
									<div class="callout callout-danger">
										<p class="text-error"><?php echo $_REQUEST["error"];?></p>
									</div>
								<?php endif;?>
								<div class="input-group">
									<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
									<input type="text" class="form-control" placeholder="Username" name="username" id="username" value="<?php echo isset($_REQUEST["username"])?$_REQUEST["username"]:""; ?>" aria-describedby="basic-addon1" autofocus="true" required="true">
								</div>
								<br>
								<div class="input-group">
									<span class="input-group-addon" id="basic-addon2.1"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>
									<input type="password" class="form-control" placeholder="Password" name="password" id="password" required="true" aria-describedby="basic-addon2.1">
								</div>
								<br>
								<input type="text" name="app-ajax-action" value="login" hidden="true">
								<button type="submit" class="btn btn-primary center-block">Login</button>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script src="theme/js/jquery.min.js"></script>
		<script src="theme/js/bootstrap.min.js"></script>
	</body>
</html>
