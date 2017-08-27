<?php
namespace maple\cms;

/**
 * Login Handler
 * @since 1.0
 * @package Maple CMS Login
 * @author Rubixcode
 */
class LOGIN{
	/**
	 * Add Navbar Elements
	 * @filter user|navbar-actions
	 * @filter-handler pre-render|head
	 */
	public static function f_add_navbar_elements(){
		if(USER::loggedin()){
			if(SECURITY::permission("maple/cms","dashboard") && URL::http("%CURRENT%")!=URL::http("%ADMIN%"))
				UI::navbar()->add_link("Dashboard",URL::http("%ADMIN%"),"dashboard");
			#TODO : change to add_html -> template dropdown with actions
			UI::navbar()->add_link(USER::details("name"),URL::name("maple/login","profile|view"),"people");
		} else {
			if(SECURITY::permission("maple/login","login") && URL::http("%CURRENT%")!=URL::name("maple/login","page|login"))
				UI::navbar()->add_link("Login",URL::name("maple/login","page|login")."/?redirect_to=".URL::http("%CURRENT%"),"login");
			if(SECURITY::permission("maple/login","sign-up") && URL::http("%CURRENT%")==URL::name("maple/login","page|login"))
				UI::navbar()->add_link("Register",URL::name("maple/login","page|sign-up"),"login");
		}
	}

	/**
	 * Login Page
	 * @router maple/login:page|login
	 * @return string html
	 */
	public static function r_login(){
		MAPLE::has_content(true);
		return "login page";
	}
}

?>
