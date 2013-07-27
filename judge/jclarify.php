<?php
   chdir("../");
   include("config.php");
   include("common.php");

   // process the POST data right away if any, and send a refresh header
   $contest = new Contest($g_configfile, $g_problempath);
   $clarifications = array();
   $jkey = 1337;

   // read existing clarifications into clarifications array
   if (file_exists($g_clarfile) && $fp = fopen($g_clarfile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
         if ($line{0} == "-") 
         {
            $c = Clarification::read($fp);
            $clarifications[$c->id] = $c;
         }
      fclose($fp);
   }

   // read clarification requests, marking any new ones
   if (file_exists($g_creqfile) && $fp = fopen($g_creqfile, "r"))
   {
      flock($fp, LOCK_SH);
      while ($line = fgets($fp))
         if ($line{0} == "-") 
         {
            $c = Clarification::read($fp);
            if (!array_key_exists($c->id, $clarifications))
            {
               $c->fresh = True;
               $clarifications[$c->id] = $c;
            }
         }
      fclose($fp);
   }

   // process new clarification responses if any
   if ($_POST["clarify"] && ($fp = fopen($g_clarfile, "w")))
   {
      flock($fp, LOCK_EX);
   
      // update the clarifications in the array
      foreach (array_keys($clarifications) as $key) 
      {
         if ($_POST[$key . "q"])
         {
            $clarifications[$key]->question = stripcslashes(rtrim($_POST[$key . "q"]));
            $clarifications[$key]->answer   = stripcslashes(rtrim($_POST[$key . "a"]));
            $clarifications[$key]->responded = $_POST[$key];
            $clarifications[$key]->fresh    = False;
            fputs($fp, "----------------------------------------\n");
            $clarifications[$key]->write($fp);
         }
      }
      
      // check for a new judge clarification
      if ($_POST[$jkey])
      {
         $q = stripcslashes(rtrim($_POST[$jkey . "q"]));
         $a = stripcslashes(rtrim($_POST[$jkey . "a"]));
         $newclar = new Clarification("Judge", $_POST["jproblem"], $q);
         $newclar->id = time();
         $newclar->answer = $a;
         $newclar->responded = True;
         fputs($fp, "----------------------------------------\n");
         $newclar->write($fp);
         
         $clarifications[$newclar->id] = $newclar;
      }

      fclose($fp);
      
      $_SESSION["clarified"] = True;
      header("Location: jclarify.php");
      exit("Updated new clarifications.");
   }
?>

<html>

<head>
<?php print "<title>$g_pagetitle - Judge Clarifications</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Answer Clarifications</h1>
</div>

<?php jnavigation("jclarify"); ?>

<hr>
<div align="center">

<!-- NOTE: Browser refresh is now handled properly
<p>
<i>Warning: Do not use your browser&apos;s refresh button to reload this page!</i><br>
Doing so may cause post data to be resent. Instead, please use the button below.<br>
<a href="jclarify.php"><img src="../images/refresh.png" border="0"></a>
</p>
-->

<?php
   print "<p><b><big>$contest->cname</big></b></p>\n";
   if (isset($_SESSION["clarified"]))
   {
      print "<p><font color=\"green\">Updated your new answers.</font></p>\n";
      unset($_SESSION["clarified"]);
   }
?>

<form name="clarifications" method="post" action="jclarify.php">
<p><input type="submit" name="clarify" value="Answer the Questions"></p>
<p>Please check the box on the right labelled * to indicate that the 
clarification should be displayed to contestants.</p>
<table border="1" width="960" cellspacing="0">
<tr bgcolor="#EEEEEE">
   <th>Problem / Team</th>
   <th>Question</th>
   <th>Answer</th>
   <th>*</th>
</tr>

<?php
   // add option for new clarification by judge
   $jclar = new Clarification("Judge", "General", "");
   $jclar->id = $jkey;
   $clarifications[$jkey] = $jclar;
   
   // order clarifications so that the newest are at the top
   $clarifications = array_reverse($clarifications, true);
   foreach ($clarifications as $c)
   {
      $qkey = $c->id . "q";
      $akey = $c->id . "a";
      $checked = $c->responded ? "checked=\"checked\"" : "";

      if ($c->id == $jkey)
      {
         print "<tr bgcolor=\"#EDF3FE\">\n";
         print "   <td>New Clarification:<br><select name=\"jproblem\">\n";
         print "      <option value=\"General\">General</option>\n";
         foreach ($contest->pnames as $name)
            print "      <option value=\"$name\">$name</options>\n";
         print "   </select></td>\n";
      }
      else
      {
         // colour code so that new clarifications are red, unanswered yellow
         if ($c->fresh)       print "<tr bgcolor=\"#FFCCCC\">\n";
         else if ($checked)   print "<tr>\n";
         else                 print "<tr bgcolor=\"#FFF7CC\">\n";

         $plink = $c->problem == "General" ? "General" : $contest->jproblemlink($c->problem{0});
         print "   <td>$plink<br><i>($c->from)</i></td>\n";
      }
      print "   <td align=\"center\" width=\"360\"><textarea name=\"$qkey\" rows=\"5\" cols=\"44\">$c->question</textarea></td>\n";
      print "   <td align=\"center\" width=\"360\"><textarea name=\"$akey\" rows=\"5\" cols=\"44\">$c->answer</textarea></td>\n";
      print "   <td align=\"center\"><input name=\"$c->id\" type=\"checkbox\" $checked /></td>\n";
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
