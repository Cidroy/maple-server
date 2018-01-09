<?php if(MAPLE::is_loggedin()):?>
<li><a href='#user_settings'><i class="material-icons left">perm_identity</i><?php echo MAPLE::UserDetail('NAME')?></a></li>
<?php else:?>
<li><a href="<?php echo URL::name("login","login",["redirect_to"=>URL::http("%CURRENT%")])?>"><i class="material-icons left">person_pin</i>Login</a></li>
<?php endif;?>
