<?php if(MAPLE::is_loggedin()):?>
<li class="no-padding">
	<ul class="collapsible" data-collapsible="accordion">
		<li>
			<a class="collapsible-header">
				<i class="material-icons left">perm_identity</i>
				<?php echo MAPLE::UserDetail('NAME')?>
			</a>
			<div class="collapsible-body">
				<ul>
					<li><a href='<?php echo LOGIN::LogoutUrl()?>' class="blue-text maple-logout">Logout</a></li>
				</ul>
			</div>
		</li>
	</ul>
</li>
<?php else:?>
<li><a href="<?php echo URL::name("login","login",["redirect_to"=>URL::http("%CURRENT%")])?>"><i class="material-icons left">person_pin</i>Login</a></li>
<?php endif;?>
