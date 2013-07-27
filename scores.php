<?php
   include("config.php");
   include("common.php");
?>

<html>

<head>
<meta http-equiv="refresh" content="300;URL=scores.php">
<?php print "<title>$g_pagetitle - Scoreboard</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Contest Scoreboard</h1>
</div>

<?php navigation("scores"); ?>

<hr>
<div align="center">

<?php
   // check to see if we want to show invisible teams
   $invisible = in_array($_SESSION["teamid"], $g_invisible);
   $showhidden = $invisible || $_GET["guest"];
   
   $okay = False;
   $final = False;
   $contest = new Contest($g_configfile, $g_problempath);
   if ($contest->okay)
   {
      if ($fp = fopen($g_judgefile, "r"))
      {
         flock($fp, LOCK_SH);
         $okay = True;
         $scores = array();
         
         while ($line = fgets($fp))
         {
            if (trim($line) == "FINAL")
            {
               $final = True;
               continue;
            }
            
            list($temp, $verdict) = explode(";", trim($line));
            list($time, $team, $prob, $lang, $file) = explode(",", $temp);
            list($hours, $minutes) = explode(":", $time);
            $time = $hours * 60 + $minutes;
            
            // throw out score reports that haven't happened yet
            if ($contest->tnow < $contest->tend && 
                $contest->tnow < $contest->tstart + $time * 60 + $g_judgelag)
               continue;
            
            // throw out score reports that are after scoreboard freeze
            if (!$final && $contest->tstart + $time * 60 > $contest->tfreeze)
               continue;
            
            if (!array_key_exists($team, $scores))
            {
               $teamname = $team;
               if (array_key_exists($team, $g_alias))
                  $teamname = $g_alias[$team];
               
               if (in_array($team, $g_official))
                  $teamname = "<b><i>".$teamname."</i></b>";
               
               $scores[$team] = new TeamScore($teamname, $team);
            }
            
            $scores[$team]->report($prob, $time, $verdict);
         }
         
         fclose($fp);
      }   
         
      print "<p>Contest of $contest->cdate<br>\n";
      print "<i>$contest->ctime</i></p>\n";
      
      $tnow = time();
      $tremain = (int) (($contest->tend - $tnow) / 60);
      
      if ($tnow > $contest->tstart) {
         if ($final)
            print "<p><b><i>Final Scoreboard</i></b></p>\n";
         else {
            if ($tremain > 0)
               print "<p><b><i>$tremain</i></b> minutes remaining</p>\n";
            else if ($tremain == 0)
               print "<p><b><i>Last minute!</i></b></p>\n";
            else
               print "<p><b><i>Contest is over... final judgements pending.</i></b></p>\n";
               
            if ($contest->tend > $contest->tfreeze && $tnow > $contest->tfreeze)
               print "<p><i>(scoreboard is frozen)</i></p>\n";
         }
      }
      
      print "<p><b><big>$contest->cname</big></b></p>\n";
      
      print "<table border=\"1\" width=\"95%\" cellspacing=\"0\">\n";
      print "<tr bgcolor=\"#EEEEEE\"><th width=\"40\">Rank</th>";
      print "<th>Team</th>";
      
      foreach ($contest->pletters as $letter)
      {
         $link = $contest->problemshortlink($letter);
         print "<th width=\"60\">$link</th>";
      }
      print "<th>Total</th><th>Penalty</th></tr>\n";
   }

   if ($okay)
   {
      foreach ($scores as $ts)
         $sorted[$ts->key()] = $ts;
         
      if ($sorted)
      {
         ksort($sorted);

         $rank = 0;
         foreach ($sorted as $ts)
         {
            if (!$showhidden && in_array($ts->id, $g_invisible))
               continue;
            
            ++$rank;
            if ($rank == 1) print "<tr bgcolor=\"#FFF7CC\">\n";
            else if ($rank % 2) print "<tr bgcolor=\"#EDF3FE\">\n";
            print "<td align=\"center\">$rank</td>";
            print "<td>$ts->name</td>";
            foreach ($contest->pletters as $letter)
            {
               $stat = $ts->problemstat($letter);
               print "<td align=\"center\">$stat</td>";
            }
            print "<td align=\"center\">$ts->total</td>";
            print "<td align=\"center\">$ts->penalty</td>";
            print "</tr>\n";      
         }
      }
      print "</table>\n";
      print "<p>Only official teams are shown in <b><i>emphasized</i></b> typeface.</p>\n";
      
      print "<blockquote><p align=\"left\">\n";
      $whoswho = array();
      foreach (array_keys($scores) as $team)
      {
         if (array_key_exists($team, $g_members))
            $whoswho[] = $scores[$team]->name . " is " . $g_members[$team] . "<br>\n";
      }
      sort($whoswho);
      foreach ($whoswho as $line) print $line;
      print "</p></blockquote>\n";
   }
   else
   {
      print "Cannot open $g_submitfile for data!";
   }
?>
<p>Current date and time:<br>
<i><?=date("r")?></i></p>
</div>

<?php footer(); ?>

</body>

</html>
