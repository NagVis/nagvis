<?PHP
/**
* Gets the User
*
* @return  String  String with Username
* @author  Lars Michelsen <lars@vertical-visions.de>
*/
function getUser() {
	if(isset($_SERVER['PHP_AUTH_USER']) && $_SERVER['PHP_AUTH_USER'] != '') {
		return $_SERVER['PHP_AUTH_USER'];
	} elseif(isset($_SERVER['REMOTE_USER']) && $_SERVER['REMOTE_USER'] != '') {
		return $_SERVER['REMOTE_USER'];
	} elseif(isset($_SESSION['nagvis_user']) && $_SESSION['nagvis_user'] != '') {
		// Support for session based authentication which is used in Ninja
		// This will be changed in 1.5 to module based authentication
		return $_SESSION['nagvis_user']; 
	} else {
		return '';
	}
}
?>
