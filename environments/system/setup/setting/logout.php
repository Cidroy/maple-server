<?php
	unset($_SESSION["maple-environment"]);
	session_commit();
	header("Location: ".ENVIRONMENT::url()->root().\ENVIRONMENT::$url_control_panel);
?>
