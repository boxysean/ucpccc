<?php
   include("config.php");
   include("common.php");

   // process the POST data right away and send refresh header to avoid repost
   $team       = $_SESSION["teamid"];
   $problem    = $_POST["problem"];
   $text       = $_POST["request"];

   if ($problem && $text)
   {
      if ($fp = fopen($g_creqfile, "a"))
      {
         flock($fp, LOCK_EX);
         fputs($fp, "----------------------------------------\n");
         $clar = new Clarification($team, $problem, stripcslashes(rtrim($text)));
         $clar->write($fp);
         fclose($fp);
         $_SESSION["logged"] = True;
         
         // refresh the page to prevent double POST
         header("Location: clarify.php");
         exit("Clarification logged.");
      }
      else
         $_SESSION["logged"] = False;
   }
?>

<html>

<head>
<?php print "<title>$g_pagetitle - Clarifications</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>View Clarifications</h1>
</div>

<?php navigation("clarify"); ?>
   
<hr>
<div align="center">

<?php
   $contest = new Contest($g_configfile, $g_problempath);

   // if a clarification request was logged, print an acknowledgement here
   if (isset($_SESSION["logged"]))
   {   
      print "<p><b><big>Clarification Request Result:</big></b></p>\n";
   
      if ($_SESSION["logged"])
      {
         print "<p><i>Your request has been successfully logged.<br>\n";
         print "Please be patient and wait for your response to appear below.</i></p>\n";         
      }
      else
      {
         print "<p>Error processing your request!</p>\n";
      }         
      print "<hr>\n";
      
      unset($_SESSION["logged"]);
   }

   // now print out the existing clarifications
   if (file_exists($g_clarfile)) {
      if ($fp = fopen($g_clarfile, "r"))
      {
         flock($fp, LOCK_SH);
         print "<p><b><big>$setname</big></b></p>\n";
         print "<table border=\"1\" width=\"75%\" cellspacing=\"0\" cellpadding=\"6\">\n";
         print "<tr bgcolor=\"#EEEEEE\"><th width=\"25%\">Problem</th>";
         print "<th>Clarification</th></tr>\n";         
         while ($line = fgets($fp))
            if ($line{0} == "-")
            {
               $c = Clarification::read($fp);
               $question = nl2br($c->question);
               $answer = nl2br($c->answer);
               
               if ($c->responded)
               {
                  print "<tr><td>$c->problem</td>\n";
                  print "<td>\n";
                  if (trim($c->question)) print "$question<br>\n";
                  print "<i>$answer</i></td>\n";
                  print "</td></tr>\n";
               }
            }
         print "</table><br>\n";
         fclose($fp);
      }
      else {
         print "<p>Unable to open clarifications file!</p>\n";
      }
   }
   else {
      print "<p><big><i>There are no clarifications at this time.</i></big></p>\n";
   }   
?>

</div>

<?php
// print out a form for requesting clarifications if a team is logged on
if ($team)
{
// ----- HTML -----
print <<<END
<hr>
<div align="center">

<p><b><big>Request Clarification</big></b></p>

<form name="view" method="post" action="clarify.php">
<table border="0" width="400">
<tr>
   <td>Problem:</td>
   <td><select name="problem">
      <option value="General">General</option>
END;
// ----- END -----
         foreach ($contest->pnames as $name)
            print "<option value=\"$name\">$name</option>";
// ----- HTML -----
print <<<END
   </select></td>
</tr>
<tr><td colspan="2" align="center">
   <textarea name="request" rows="5" cols="44">(type your question in this box)</textarea>
</td></tr>
<tr align="center"><td colspan="2"><input type="submit" value="Bug the Judge..."></td></tr>
</table>
</form>

</div>
END;
// ----- END -----
}
?>

<?php footer(); ?>

</body>

</html>
