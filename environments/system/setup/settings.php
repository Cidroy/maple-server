<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Maple Environment Control Panel</title>

		<link href="theme/css/bootstrap.min.css" rel="stylesheet">
		<link href="theme/css/bootstrap-theme.css" rel="stylesheet">
	</head>
	<body>
		<nav class="navbar navbar-default" style="margin-bottom:0px">
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="#">
						<img alt="Maple Logo" src="theme/images/logo.png" height="20" width="20" style="display:inline-block;">
						Maple Environment
					</a>
				</div>
				<a class="btn btn-default navbar-btn pull-right" href="?page=logout">Logout</a>
			</div>
		</nav>
		<div class="container" style="width:100%">
			<div class="row">
				<div class="col-sm-12 col-lg-3" style="background: rgb(24, 24, 24);     padding: 15px !important;">
					<ul class="nav nav-pills nav-stacked">
						<li role="presentation" <?php echo (isset($_REQUEST["page"])&&$_REQUEST["page"]=="home") || !isset($_REQUEST["page"]) ?"class='active'":"";  ?> ><a href="?page=home">Home</a></li>
						<li role="presentation" <?php echo isset($_REQUEST["page"])&&$_REQUEST["page"]=="install"?"class='active'":"";  ?> ><a href="?page=install">Install</a></li>
						<li role="presentation" <?php echo isset($_REQUEST["page"])&&$_REQUEST["page"]=="configure"?"class='active'":"";  ?> ><a href="?page=configure">Configure</a></li>
						<li role="presentation" <?php echo isset($_REQUEST["page"])&&$_REQUEST["page"]=="credentials"?"class='active'":"";  ?> ><a href="?page=credentials">Credentials</a></li>
					</ul>
				</div>
				<div class="col-sm-12 col-lg-9">
					<?php
						$page = isset($_REQUEST["page"])?$_REQUEST["page"]:"home";
						switch ($page) {
							case 'home': require_once "setting/home.php"; break;
							case 'install': require_once "setting/install.php"; break;
							case 'configure': require_once "setting/configure.php"; break;
							case 'credentials': require_once "setting/credentials.php"; break;
							case 'logout': require_once "setting/logout.php"; break;
							default: require_once "setting/404.php"; break;
						}
					?>
				</div>
			</div>
		</div>
		<script src="theme/js/jquery.min.js"></script>
		<script src="theme/js/bootstrap.min.js"></script>
	</body>
</html>
