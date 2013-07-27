<?php
   include("config.php");
   include("common.php");
?>

<html>

<head>
<?php print "<title>$g_pagetitle - Log In</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Team Log In</h1>
</div>

<?php
   // must do a quick pre-authentication so that navigation bar shows correctly
   $team       = $_POST["team"];
   $password   = $_POST["password"];

   // authenticate the team
   if ($team && $password)
   {
      if (array_key_exists($team, $g_teams) && $g_teams[$team] == $password) {
         $_SESSION["teamid"] = $team;
         $_SESSION["password"] = $password;
      }
   }
   // otherwise log the team out
   else if (isset($_SESSION["teamid"]))
   {
      $team = $_SESSION["teamid"];
      unset($_SESSION["teamid"]);
      unset($_SESSION["password"]);
      unset($_SESSION["language"]);
      $loggedout = true;
   }
?>

<?php navigation("login"); ?>

<hr>
<div align="center">

<?php


   // if on the POST, a team and password was entered
   if ($team && $password)
   {
      // authenticate the team
      if (array_key_exists($team, $g_teams) && $g_teams[$team] == $password)
      {
         //$_SESSION["teamid"] = $team;
         print "<p><b><big>Now logged in as $team.</big></b></p>\n";            
      }
      else
      {
         print "<font color=\"red\">\n";
         if (array_key_exists($team, $g_teams))
            print "<p>Incorrect password!</p>\n";
         else
            print "<p>Unknown team name!</p>\n";
         print "</font>\n";
      }
   }
   // otherwise, we are attempting to log out
   else if ($loggedout)
   {
      print "<p><b><big>Team $team now logged out.</big></b></p>\n";            
   }
   
   if (empty($_SESSION["teamid"]))
   {
      print "<p><i>Warning: Team name is <b>case-sensitive</b>!!!</i></p>\n";
      print "<form name=\"login\" method=\"post\" action=\"login.php\">\n";
      print "<table border=\"0\" width=\"400\">\n";
      print "<tr><td>Team:</td><td><input type=\"text\" name=\"team\" /></td></tr>\n";
      print "<tr><td>Password:</td><td><input type=\"password\" name=\"password\" /></td></tr>\n";
      print "<tr align=\"center\"><td colspan=\"2\"><input type=\"submit\" value=\"Log Me In!\" /></td></tr>\n";
      print "</table>\n";
      print "</form>\n"; 
   }

?>

</div>

<?php footer(); ?>

</body>

</html>