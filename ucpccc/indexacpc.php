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
<h1>Alberta Collegiate Programming Contest</h1>
<h2><i>Live Contest Page</i></h2>
<table border="0"><tr>
   <td width="128" align="center"><img src="images/ua.gif"></td>
   <td width="128" align="center"><img src="images/CVCrest.jpg"></td>
   <td width="128" align="center"><img src="images/new-uofL-logo.jpg"></td>
</tr></table>
<p>This contest is a joint effort of the University of Alberta, the University
of Calgary, and the University of Lethbridge.<br> For more information, please
visit the main <a href="http://ugweb.cs.ualberta.ca/~acpc/">ACPC site</a>.</p>
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

      print "<table border=\"0\" width=\"280\">\n";
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
         if (file_exists($g_problempath . ".htaccess"))
            rename($g_problempath . ".lock", $g_problempath . ".htaccess");
      }
   }
?>
<p>Current date and time:<br>
<i><?=date("r")?></i></p>

<hr>
<table border="0" cellpadding="10"><tr>
<td align="right">
Prizes and funding for this contest is provided by the 
<a href="http://www.icore.ca">Alberta Informatics Circle of Research Excellence.</a>
</td>
<td>
<a href="http://www.icore.ca"><img src="images/icore_logo.jpg" border="0"></a>
</td>
</tr></table>

</div>

<?php footer(); ?>

</body>

</html>
