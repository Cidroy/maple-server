<?php
	unset($_SESSION["app-environment"]);
	session_commit();
	header("Location: ".\maple\environments\eAPP::url("settings"));
?>
