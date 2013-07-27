<?php
   include("config.php");
   include("common.php");
?>

<html>

<head>
<?php print "<title>$g_pagetitle - Confirmation</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Submission Confirmation</h1>
</div>

<?php navigation("confirm"); ?>

<hr>
<div align="center">

<?php
   // if team is logged in, we get the ID from the session
   $team       = $_SESSION["teamid"];
   $problem    = $_POST["problem"];
   $language   = $_POST["language"];
   $source     = $_FILES["source"]["name"];
   $tempfile   = $_FILES["source"]["tmp_name"];
   $time       = date("h:i");

   // authenticate the team
   if (isset($_SESSION["teamid"]) && $_SESSION["password"] == $g_teams[$team])
   {
      if ($source && $language)
      {
         $tssubmit = $tsstart = $tsend = time();
         
         // get the contest time from the config file
         if ($fp = fopen($g_configfile, "r"))
         {
            flock($fp, LOCK_SH);
            
            // discard title and contact
            fgets($fp); fgets($fp);
            
            // next two lines are date and time
            $cdate = trim(fgets($fp));
            $ctime = trim(fgets($fp));
            list($tsstart, $tsend) = contesttime($cdate, $ctime);
            fclose($fp);
         }
         
         // check contest start and end time
         if ($tsstart <= $tssubmit && $tssubmit < $tsend)
         {         
      
            // create the team directory if necessary
            $teamdir = $g_submitpath.$team;
            if (!file_exists($teamdir)) 
            {
               mkdir($teamdir);
               chmod($teamdir, 0770);
               
               // make team "output" and "diff" directories for judge
//               mkdir($teamdir . "/output");
//               chmod($teamdir . "/output", 0777);
//               mkdir($teamdir . "/diff");
//               chmod($teamdir . "/diff", 0777);
               //system("cp ".$teamdir."/../input/*.in $teamdir");
            }

            // find the next available filename for this team/problem
            $number = 1;
            do {
               $shortfile = sprintf("P%s_%02d%s", $problem, $number,            
                                   $g_extension[$language]);
               $teamfile = $teamdir . "/" . $shortfile;
               //print "Trying file $teamfile.<br>\n";
               $number++;
            } while (file_exists($teamfile));

            $error = false;

            if (move_uploaded_file($tempfile, $teamfile))
            {
               chmod($teamfile, 0660);
               
               // remember the team's preferred language
               $_SESSION["language"] = $language;

               // write the submission to log file
               if ($fp = fopen($g_submitfile, "a"))
               {
                  flock($fp, LOCK_EX);
                  $tdelta = intervaltostr($tssubmit - $tsstart);
                  fputs($fp, "$tdelta,$team,$problem,$language,$shortfile\n");
                  fclose($fp);

                  print "<table border=\"0\">\n";
                  print "<tr align=\"center\"><th colspan=\"2\">We are pleased to confirm your submission:</th></tr>\n";
                  print "<tr><td>Team:</td><td>$team</td></tr>\n";
                  print "<tr><td>Problem:</td><td>$problem</td></tr>\n";
                  print "<tr><td>Language:</td><td>$language</td></tr>\n";
                  print "<tr><td>Source:</td><td>$source</td></tr>\n";
                  print "<tr><td>Time:</td><td>$tdelta</td></tr>\n";
                  print "</table>\n";
                  
                  print "<p><i>Note: Do not refresh this page unless you intend to submit the same file again!</i></p>\n";
                   
                   // log the submitter's ip, for security tracking
                   if ($g_logIPs && ($gp = fopen($g_iplogfile, "a")))
                   {
                       flock($gp, LOCK_EX);
                       $ip=$_SERVER['REMOTE_ADDR'];
                       fputs($gp, "$tdelta,$team,$problem,$language,$shortfile;$ip\n");
                       fclose($gp);
                   }
               }
               else
               {
                  $error = true;
               }
            }
            // couldn't move the uploaded file for some reason?
            else
            {
               $error = true;
            }

            if ($error)
            {
               print "<p>Something bad happened, and I don't know what.<br>\n";
               print "Please try your submission again.</p>\n";
            }
         }
         // submission out of time range
         else
         {
            print "<p>Sorry $team, the contest is not running!</p>\n";
         }
      }
      else
      {
         print "<p>Sorry $team, you must have screwed something up!<br>\n";
         if ($language)
            print "(Check that you selected the right source file.)<br>\n";
         else
            print "(Make sure you select the right langauge.)<br>\n";
            
         print "<p>Unable to confirm your submission.</p>\n";
      }
   }
   // authentication failed!
   else
   {
      print "<p><i>You must be logged in to submit and view runs.</i></p>\n";
      print "<p>Unable to confirm your submission.</p>\n";
   }
?>

</div>

<?php footer(); ?>

</body>

</html>
