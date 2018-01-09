<?php
	unset($_SESSION["www-environment"]);
	session_commit();
	header("Location: ".\maple\environments\eWWW::url("settings"));
?>
