<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Setup|Security - Maple Environment</title>

		<link href="theme/css/bootstrap.min.css" rel="stylesheet">
		<link href="theme/css/bootstrap-theme.css" rel="stylesheet">
	</head>
	<body>
		<div class="container">
			<h1 class="text-success text-center">Welcome to Maple Environment!</h1>
			<div class="callout callout-primary">
				<h4>Why am I here?</h4>
				<p>
					Since this is the first time you have started the environment you will need to set a few things up.<br>
					Please  fill the forms, this will only take a couple of minutes to get you started.<br>
					Lets go!
				</p>
			</div>
			<div class="row">
				<div class="panel panel-primary">
					<div class="panel-heading">Environment login info</div>
					<div class="panel-body">
						<form action="" method="post">
							<?php if(isset($_REQUEST["error"])): ?>
								<div class="callout callout-danger">
									<p class="text-error"><?php echo $_REQUEST["error"];?></p>
								</div>
							<?php endif;?>
							<div class="input-group">
								<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
								<input type="text" class="form-control" placeholder="Username" name="username" value="<?php echo isset($_REQUEST["username"])?$_REQUEST["username"]:""; ?>" aria-describedby="basic-addon1" autofocus="true" required="true">
							</div>
							<br>
							<div class="input-group">
								<span class="input-group-addon" id="basic-addon2.1"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>
								<input type="password" class="form-control" placeholder="Password" name="password" required="true" aria-describedby="basic-addon2.1">
							</div>
							<br>
							<div class="input-group">
								<span class="input-group-addon" id="basic-addon2.2"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>
								<input type="password" class="form-control" placeholder="Confirm Password" name="confirm-password" required="true" aria-describedby="basic-addon2.2">
							</div>
							<br>
							<div class="input-group">
								<span class="input-group-addon" id="basic-addon3"><span class="glyphicon glyphicon-globe" aria-hidden="true"></span></span>
								<span class="input-group-addon" id="basic-addon3.1"><?php echo ENVIRONMENT::url()->root(); ?></span>
								<input type="text" class="form-control" placeholder="Settings Url" name="url" required="true" value="<?php echo isset($_REQUEST["url"])?$_REQUEST["url"]:""; ?>" aria-describedby="basic-addon3">
							</div>
							<br>
							<input type="text" name="environment-ajax-action" value="first-boot" hidden="true">
							<button type="submit" class="btn btn-primary center-block">Next</button>
						</form>
					</div>
				</div>
				<div class="panel panel-info">
					<div class="panel-heading">What is all this?</div>
					<div class="panel-body">
						<p>
							Maple Environment features the possibility to intigrate many other CMS environment, and needs a control system.
							To access the environment control panel and security you need to assign a username and password.
							This is required to access the control panel.
							Note that each Environment will have its own security measures, this credential can not access them and they are seperate.
							<br>
							<strong>Settings Url - </strong>
							This is the url that you need to enter in order to access the Maple Environment Control Panel.
						</p>
					</div>
				</div>
			</div>
		</div>
		<script src="theme/js/jquery.min.js"></script>
		<script src="theme/js/bootstrap.min.js"></script>
	</body>
</html>
