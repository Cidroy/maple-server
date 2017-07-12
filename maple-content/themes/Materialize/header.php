<html lang="<?php UI::language()->get();?>" >
<head>
	<meta name="viewport" content="width=device-width">
	<?php echo TEMPLATE::head(); ?>
	<link rel="stylesheet" href="<?php echo URL::http(__DIR__."/css/materialize.min.css")?>" >
	<link rel="stylesheet" href="<?php echo URL::http(__DIR__."/css/style.css")?>" >
	<link rel="stylesheet" href="<?php echo URL::http(__DIR__."/fonts/icons/material-icons.css")?>" >
	<!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> -->
</head>
<body <?php TEMPLATE::body_class();?>>
	<?php
		// filter body_start_content : do contents when body has started
		echo MAPLE::do_filters("body_start_content");
		// filter pre_nav_content : do contents when body has started
		echo MAPLE::do_filters("pre_nav_content");
	?>
		<nav>
			<div class="nav-wrapper red">
				<a href="<?php echo URL::http("%ROOT%")?>" class="brand-logo"><?php echo SITE::Name()?></a>
				<a href="#" data-activates="navbar-content" class="button-collapse"><i class="material-icons">menu</i></a>
				<ul class="right hide-on-med-and-down">
					<?php foreach (TEMPLATE::navbar_element() as $name => $url):?>
					<li><a href="<?php echo $url?>" <?php foreach (TEMPLATE::navbar_element_args($name) as $key => $value) { echo "$key='$value'"; }?> ><?php echo $name?></a></li>
					<?php endforeach;?>
					<?php echo $navbar_user = FILE::parse_read(ROOT.THEME_USE.'elements/navbar-user.php')?>
				</ul>
				<ul class="right hide-on-med-and-up">
					<li><a onclick="location.reload()"><i class="material-icons">refresh</i></a></li>
					<li><a onclick="window.history.back()"><i class="material-icons">arrow_back</i></a></li>
				</ul>
				<ul class="side-nav" id="navbar-content">
					<li>
						<div class="userView">
						<img class="background" src="<?php echo URL::http("%DATA%image/sidebar-image.jpg")?>">
						<a href="<?php URL::http("%ROOT%")?>"><span class="white-text name"><?php echo SITE::Name()?></span></a>
						</div>
					</li>
					<?php foreach (TEMPLATE::navbar_element() as $name => $url):?>
					<li><a href="<?php echo $url?>" <?php foreach (TEMPLATE::navbar_element_args($name) as $key => $value) { echo "$key='$value'"; }?> ><?php echo $name?></a></li>
					<?php endforeach;?>
					<?php echo MAPLE::do_filters('admin_sidebar_list'); ?>
					<?php echo FILE::parse_read(ROOT.THEME_USE.'elements/navbar-user-min.php');?>
				</ul>
			</div>
			<?php echo TEMPLATE::navbar_nonfluid_elements();?>
		</nav>
		<?php
		// filter post_nav_content
		echo MAPLE::do_filters('post_nav_content');
		?>
	<div class="wrapper" style="">
