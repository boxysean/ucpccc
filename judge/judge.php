<?php
   chdir("../");
   include("config.php");
   include("common.php");

   $contest = new Contest($g_configfile, $g_problempath);
   $runs = array();

   // load submissions from submission file into runs array
   if ($fp = fopen($g_submitfile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
         $runs[trim($line)] = new Run($line);
      fclose($fp);
   }
   else
   {
      $_SESSION["error"] = "<p>Unable to locate submissions.</p>\n";
   }

   // whether or not "finalize scoreboard" is checked
   $fchecked = "";

   // read the existing judgements into the runs array
   if ($fp = fopen($g_judgefile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
      {
         if (trim($line) == "FINAL")
         {
            $fchecked = "checked=\"checked\"";
            continue;
         }
      
         list($key, $verdict) = explode(";", trim($line));
         if (array_key_exists($key, $runs))
            $runs[$key]->verdict = $verdict;
         else
            $buffer .= $line;
      }
      fclose($fp);
   }
   else
   {
      $_SESSION["error"] = "<p>Unable to locate judgements.</p>\n";
   }

   // process the POST data right away if any, and send a refresh header
   if ($_POST["judge"] && ($fp = fopen($g_judgefile, "r+")))
   {
      // if finalize scoreboard was checked, write "FINAL" at top of file
      flock($fp, LOCK_EX);
      if ($_POST["final"]) {
         fputs($fp, "FINAL\n");
         $fchecked = "checked=\"checked\"";
      }
      
      foreach (array_keys($runs) as $key)
      {
         $pkey = str_replace(".", "_", $key);
         if ($_POST[$pkey]) {
            $xkey = $pkey . "x";
            if ($_POST[$pkey] != $_POST[$xkey])
               $runs[$key]->verdict = $_POST[$pkey];
         }
      }

      fputs($fp, $buffer);
      foreach ($runs as $run)
         fputs($fp, $run->judgement());
         
      fclose($fp);
      
      $_SESSION["judged"] = True;
      header("Location: judge.php");
      exit("Updated new judgements.");
   }
?>

<html>

<head>
<?php print "<title>$g_pagetitle - Judge Verdicts</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Hand Down Judgments!</h1>
</div>

<?php jnavigation("judge"); ?>

<hr>
<div align="center">

<!--
<p>
<i>Warning: Do not use your browser&apos;s refresh button to reload this page!</i><br>
Doing so may cause post data to be resent. Instead, please use the button below.<br>
<a href="judge.php"><img src="../images/refresh.png" border="0"></a>
</p>
-->

<?php
// print "<p>Contest of $contest->cdate<br>\n";
// print "<i>$contest->ctime</i></p>\n";

   if ($_SESSION["error"])
   {
      print $_SESSION["error"];
      unset($_SESSION["error"]);
   }
?>

<iframe src="jpoll.php" width="240" height="50" frameborder="0">
</iframe>

<?php
   print "<p><b><big>$contest->cname</big></b></p>\n";
   if (isset($_SESSION["judged"]))
   {
      print "<p><font color=\"green\">Updated your new verdicts.</font></p>\n";
      unset($_SESSION["judged"]);
   }
?>

<form name="judge" method="post" action="judge.php">
<p><input type="submit" name="judge" value="Judge Them!"/></p>
<?php
   if ($contest->tnow > $contest->tend)
      print "<p><input type=\"checkbox\" name=\"final\" $fchecked>Finalise Scores (cannot be undone)</input></p>\n";
?>
<table border="1" width="800" cellspacing="0">
<tr bgcolor="#EEEEEE">
   <th width="60">Time</th>
   <th>Team</th>
   <th>Problem</th>
   <th width="80">Language</th>
   <th width="80">File</th>
   <th width="160">Verdict</th>
</tr>

<?php
   $runs = array_reverse($runs, true);
   foreach ($runs as $run)
   {
      $pkey = str_replace(".", "_", $run->key());
      $xkey = $pkey . "x";
      $plink = $contest->jproblemlink($run->problem);
//      $flink = "<a href=\"../$g_submitpath$run->team/$run->file\">$run->file</a>";
      $fname = $run->problem . $g_extension[$run->language];
      $flink = "<a href=\"jfetch.php?file=" . 
               urlencode("../$g_submitpath$run->team/$run->file") . 
               "&name=$fname\">$fname</a>";
      $pending = $run->verdict == "U";

      // if the run is pending judgement, highlight it in red
      if ($pending) print "<tr bgcolor=\"#FFCCCC\">\n";
      else if ($index++ % 2) print "<tr bgcolor=\"#EDF3FE\">\n";
      else print "<tr>\n";
      print "   <td align=\"center\">$run->time</td>\n";
      print "   <td>$run->team</td>\n";
      print "   <td>$plink</td>\n";
      print "   <td align=\"center\">$run->language</td>\n";
      print "   <td align=\"center\">$flink</td>\n";

      print "   <td align=\"center\"><select name=\"$pkey\">\n";
      foreach (array_keys($g_verdicts) as $v)
      {
         $vstr = $g_verdicts[$v];
         $sel = $run->verdict == $v ? "selected=\"selected\"" : "";
         print "      <option value=\"$v\" $sel>$vstr</option>\n";
      }
      print "   </select></td>\n";
      print "   <input type=\"hidden\" name=\"$xkey\" value=\"$run->verdict\">\n";
      print "</tr>\n";
   }
?>

</table>
</form>

<p>Current date and time:<br>
<i><?=date("r")?></i></p>
</div>

<?php footer(); ?>

</body>

</html>
