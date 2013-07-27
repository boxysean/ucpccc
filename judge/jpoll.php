<?php
   chdir("../");
   include("config.php");
   include("common.php");
?>

<html>

<head>
<meta http-equiv="refresh" content="60;URL=jpoll.php">
<title>New Submission Polling</title>
</head>

<body>

<div align="center">

<?php
   $runs = array();
   $fresh = 0;

   // read the judgements file to set what judgements have been made
   if ($fp = fopen($g_judgefile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
      {
         if (trim($line) == "FINAL")
            continue;
      
         list($key, $verdict) = explode(";", trim($line));
         $runs[$key] = True;
      }
      fclose($fp);
   }
   else
   {
      print "<p>Unable to locate judgements.</p>\n";
   }

   // then open the submissions file to see what submissions are unjudged
   if ($fp = fopen($g_submitfile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
         if (!array_key_exists(trim($line), $runs))
            $fresh++;
      
      fclose($fp);
   }
   else
   {
      print "<p>Unable to locate submissions.</p>\n";
   }
   
   // TODO: Possibly poll for new clarifications as well here.

   // print the number of new submissions without judgement yet
   if ($fresh) {
      print "<font color=\"red\"><b><blink>$fresh Pending Submission(s)</blink></b></font><br>\n";
      print "<small>(refresh page if not shown)</small>\n";
   }
?>

</div>

</body>

</html>
