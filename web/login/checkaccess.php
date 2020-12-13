<?php
if ($_SESSION[$securitysec] != True) {
 Header("Location: /humiwatch/login/?redirecting=" . $_SERVER['PHP_SELF']);
}
?>