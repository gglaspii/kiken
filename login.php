<?php
session_start();
session_destroy();
require("session.php");
?>
<html>
<head>
</head>
<body>
<form name="login" method="POST">
Login : <input type="text" name="login"><br>
Pass : <input type="password" name="passwd"><br>
<input type="submit" name="login_submit" value="OK">
</form>
</body>
</html>