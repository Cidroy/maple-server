<?php
/**
 * This is the most generic template file in a WordPress or Maple Framework theme.
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 * @package Maple Framework
 * @subpackage Materialize
 */
?>
<?php //BREADCRUMBS::Display();?>
<?php
	TEMPLATE::Content();
	if(!MAPLE::$content) echo TEMPLATE::Render("maple","error/404",[])
?>
<?php MAPLE::Display_Error();?>
<noscript>
	TODO : a good no script popup!
	TODO : a good no cookie popup
	TODO : anti iexplorer popup
</noscript>
