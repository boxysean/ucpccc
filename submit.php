<?php
   include("config.php");
   include("common.php");
?>

<html>

<head>
<?php print "<title>$g_pagetitle - Submit</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Submissions</h1>
</div>

<?php
   navigation("submit");

   $contest = new Contest($g_configfile, $g_problempath);
?>

<hr>
<div align="center">

<?php

// check if they're logged in
if (empty($_SESSION["teamid"]))
   print "<p><i>You must be logged in to submit and view runs.</i></p>\n";
// re-authenticate the team, in case the password changed or they have been deleted
else if ($_SESSION["password"] != $g_teams[$_SESSION["teamid"]])
{
   print "<p><i>Team configuration has changed.  Please log in again.</i></p>\n";
   unset($_SESSION["teamid"]);
   unset($_SESSION["password"]);
}
else
{
   // check that the contest is still running
   if ($contest->tnow < $contest->tstart)
   {
      print "<p><i>Waiting for contest to start.</i></p>\n";      
   }   
   else if ($contest->tnow >= $contest->tend)
   {
      print "<p><i>Contest is over.</i></p>\n";      
   }
   else
   {
// ----- HTML -----
print <<<END
<p><b><big>Submit Solution</big></b></p>

<form name="submit" method="post" action="confirm.php" enctype="multipart/form-data">

<table border="0" width="400">
<tr>
   <td>Problem:</td>
   <td><select name="problem">
END;
// ----- END -----
      foreach ($contest->pnames as $name)
      {
         $letter = $name{0};
         print "<option value=\"$letter\">$name</option>";
      }
      $nlanguages = count($g_extension);
// ----- HTML -----
print <<<END
   </select></td>
</tr>
<tr>
   <td>Language:</td>
   <td><select name="language" size = "$nlanguages">
END;
// ----- END -----
      foreach (array_keys($g_extension) as $value) {
         $selected = $_SESSION["language"] == $value ? "selected" : "";
         print "      <option $selected value=\"$value\">$value</option>\n";
      }
// ----- HTML -----
print <<<END
   </select></td>
</tr>
<tr><td>Source:</td><td><input type="file" name="source"></td></tr>
<tr align="center"><td colspan="2"><input type="submit" value="Submit!!!"></td></tr>
</table>

</form>
END;
// ----- END -----
   }

   
   print "<hr>\n";

   $team = $_SESSION["teamid"];
   $runs = array();

   if ($fp = fopen($g_submitfile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
      {
         if (trim($line) == "FINAL") continue;
         $run = new Run($line);
         if ($team == $run->team) $runs[trim($line)] = $run;
      }
      fclose($fp);
   }
   else
   {
      print "<p>Unable to locate submissions.</p>\n";
   }

   if ($fp = fopen($g_judgefile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
      {
         list($key, $verdict) = explode(";", trim($line));
         $run = new Run($key);
         if ($team == $run->team && array_key_exists($key, $runs))
            $runs[$key]->verdict = $verdict;
      }
      fclose($fp);
   }

   
   print "<p><b><big>Runs received from team $team</big></b></p>\n";

   print "<table border=\"1\" width=\"480\" cellspacing=\"0\">\n";
   print "<tr bgcolor=\"#EEEEEE\"><th>Time</th><th>Problem</th><th>Language</th><th>Verdict</th></tr>\n";            

   foreach ($runs as $run)
   {
      $verdict = array_key_exists($run->verdict, $g_verdicts) ?
                     $g_verdicts[$run->verdict] : "Unknown";
      if ($run->verdict == "E") 
         $verdict = $verdict . " <i>(Please contact judge!)</i>";
      
      // colour code the results
      if ($run->verdict == "U")        print "<tr>";
      else if ($run->verdict == "A")   print "<tr bgcolor=\"#CCFFCC\">";
      else                             print "<tr bgcolor=\"#FFCCCC\">";      
      print "<td>$run->time</td><td>$run->problem</td><td>$run->language</td><td>$verdict</td></tr>\n";
   }
   print "</table><br>\n";

}
?>

</div>

<?php footer(); ?>

</body>

</html>
