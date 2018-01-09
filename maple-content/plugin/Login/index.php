<?php
define('LOGIN',__DIR__.'/');

{
	LOGIN::add_navbar();
	UI::content()->add('LOGIN::select_function',URL::http("%CURRENT%"),10,false);
	if(MAPLE::is_loggedin()) USER::AddContentHooks();
}?>
