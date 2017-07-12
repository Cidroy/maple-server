<h2 class="page-title text-primary">Change Credentials</h2>
<form action="<?php echo ENVIRONMENT::url()->root(false).ENVIRONMENT::$url_control_panel; ?>" method="post">
	<?php if(isset($_REQUEST["error"])):?>
		<div class="callout callout-danger">
			<h4>An Error Occured : <label class="text-primary"><?php echo $_REQUEST["error"] ?></label></h4>
		</div>
	<?php endif;?>
	<div class="panel">
		<div class="panel-body">
			<h4>Old Credentials</h4>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon1.1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
				<input type="text" class="form-control" placeholder="Current Username" name="username" id="username" aria-describedby="basic-addon1.1" autofocus="true" required="true">
			</div>
			<br>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon1.2"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>
				<input type="password" class="form-control" placeholder="Current Password" name="password" id="password" required="true" aria-describedby="basic-addon1.2">
			</div>
			<hr>
			<h4>New Credentials</h4>
			<div class="callout">
				<ul>
					<li>If you do not intend to change username, leave the field empty</li>
					<li>If you do not intend to change password, leave the fields empty</li>
					<li>If you do not intend to change settings url, leave the fields empty</li>
				</ul>
			</div>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon2.1"><span class="glyphicon glyphicon-user" aria-hidden="true"></span></span>
				<input type="text" class="form-control" placeholder="New Username" name="new-username" id="new-username" aria-describedby="basic-addon2.1">
			</div>
			<br>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon2.2"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>
				<input type="password" class="form-control" placeholder="New Password" name="new-password" id="new-password" aria-describedby="basic-addon2.2">
			</div>
			<br>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon2.3"><span class="glyphicon glyphicon-lock" aria-hidden="true"></span></span>
				<input type="password" class="form-control" placeholder="Confirm Password" name="confirm-password" id="confirm-password" aria-describedby="basic-addon2.3">
			</div>
			<br>
			<div class="input-group">
				<span class="input-group-addon" id="basic-addon2.4"><span class="glyphicon glyphicon-globe" aria-hidden="true"></span></span>
				<span class="input-group-addon" id="basic-addon2.5"><?php echo ENVIRONMENT::url()->root(); ?></span>
				<input type="text" class="form-control" placeholder="Settings Url" name="url" aria-describedby="basic-addon2.4">
			</div>
		</div>
		<div class="panel-footer">
			<input type="text" name="environment-ajax-action" value="change-credentials" hidden="true">
			<button type="submit" class="btn btn-success">Change</button>
		</div>
	</div>
</form>
