<?php
   include("config.php");
   include("common.php");
?>

<html>

<head>
<?php print "<title>$g_pagetitle</title>\n"; ?>
</head>

<body>

<div align="center">
<h1><?=$g_pagetitle?></h1>
<h2><i>Online Training Arena</i></h2>
<p><img src="images/icpc_logo.png"></p>
</div>

<?php navigation("index"); ?>

<hr>
<div align="center">

<?php   
   // just make sure the submitfile and judgefile are there
   if (!file_exists($g_submitfile))
   {
      $fp = fopen($g_submitfile, "w");
      fclose($fp);
      chmod($g_submitfile, 0660);
   }
   if (!file_exists($g_judgefile))
   {
      $fp = fopen($g_judgefile, "w");
      fclose($fp);
      chmod($g_judgefile, 0660);
   }
   
   $contest = new Contest($g_configfile, $g_problempath);
   if ($contest->okay)
   {
      print "<p>Contest of $contest->cdate<br>\n";
      print "<i>$contest->ctime</i></p>\n";

      print "<table border=\"0\" width=\"320\">\n";
      print "<tr><th>$contest->cname</th></tr>\n";
      
      foreach ($contest->pletters as $letter)
      {
         $link = $contest->problemlonglink($letter);
         print "<tr><td>$link</td></tr>\n";
      }
      print "</table>\n";
      
      print "<p><i>For your questions, comments, and issues,<br>\n";
      print "please contact your host today at</i><br>\n";
      print "<a href=\"mailto:$contest->chost\">$contest->chost</a></p>\n";      

      // unlock (or relock) the problem set if necessary
      if ($contest->tnow > $contest->tstart)
      {
         if (file_exists($g_problempath . ".htaccess"))
            rename($g_problempath . ".htaccess", $g_problempath . ".lock");
      }
      else
      {
         if (file_exists($g_problempath . ".lock"))
            rename($g_problempath . ".lock", $g_problempath . ".htaccess");
      }
   }
?>
<p>Current date and time:<br>
<i><?=date("r")?></i></p>
</div>

<?php footer(); ?>

</body>

</html>
