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
    } else {
      return '';
    }
  }
?>
