<?php 
   // -----------------------------------------------------------------------

   function navigation($from)
   {
      print "<hr><div align=\"center\"><p>\n";
      print "[ ";
      
      if ($from != "index")
         print "<a href=\"index.php\">Contest Page</a>";
      else print "Contest Page";
      print " | ";

      if ($from != "scores")
         print "<a href=\"scores.php\">Scoreboard</a>";
      else print "Scoreboard";
      print " | ";

      if ($from != "submit")
         print "<a href=\"submit.php\">Submissions</a>";
      else print "Submissions";
      print " | ";
      
      if ($from != "clarify")
         print "<a href=\"clarify.php\">Clarifications</a>";
      else print "Clarifications";
      print " | ";

      if ($from != "help")
         print "<a href=\"help.php\">Help</a>";
      else print "Help";
      print " | ";
      
      // toggle between displaying "Log In" and "Log Out"
      if (empty($_SESSION["teamid"]))
      {
         if ($from != "login")
            print "<a href=\"login.php\">Log In</a>";
         else print "Log In";
      }
      else
      {
         print "<a href=\"login.php\">Log Out</a>";
      }
      
      print " ]\n";
      print "</p></div>\n";      
   }
   
   function jnavigation($from)
   {
      print "<hr><div align=\"center\"><p>\n";
      print "[ ";
      
      if ($from != "judge")
         print "<a href=\"judge.php\">Judgements</a>";
      else print "Judgements";
      print " | ";

      if ($from != "jclarify")
         print "<a href=\"jclarify.php\">Clarifications</a>";
      else print "Clarifications";
      print " | ";

      if ($from != "jscores")
         print "<a href=\"jscores.php\">Scoreboard</a>";
      else print "Scoreboard";
      print " | ";
      
      if ($from != "jconfig")
         print "<a href=\"jconfig.php\">Configuration</a>";
      else print "Configuration";
      print " | ";
      
      print "<a href=\"..\" target=\"new\">Contest Page</a>";

      print " ]\n";
      print "</p></div>\n";      
   }   

   function footer()
   {
      print "<hr><p><small>\n";
      print "Ultra Cool Programming Contest Control Centre v1.8<br>\n";
      print "Copyright (c) 2005-2010 by Sonny Chan<br>\n";
      print "</small></p>\n";
   }

   // -----------------------------------------------------------------------

   function contesttime($cdate, $ctime)
   {
      $dstamp = strtotime($cdate);
      list($cstart, $clhours, $clminutes) = sscanf($ctime, "%s + %d:%d");
      $sstamp = strtotime($cstart, $dstamp);
      $estamp = $sstamp + $clhours*60*60 + $clminutes*60;
      return array($sstamp, $estamp);
   }

   function intervaltostr($interval)
   {
      $seconds = $interval % 60; $interval /= 60;
      $minutes = $interval % 60; $interval /= 60;
      $hours = $interval;
      $istr = sprintf("%02d:%02d", $hours, $minutes);
      return $istr;
   }

   // -----------------------------------------------------------------------

   class Contest
   {
      var $cname;
      var $cdate;
      var $ctime;
      var $chost;
      
      var $tstart;
      var $tend;
      var $tnow;
      var $tfreeze;
      
      var $firstletter;
      var $showtitles;
      
      var $pletters = array();
      var $pnames = array();
      var $purls = array();
      
      var $okay;
      
      function Contest($problemfile, $problempath)
      {
         // Don't know why this include is necessary... remove if possible
         include("config.php");  
         
         $this->tnow = time();
         
         if ($fp = fopen($problemfile, "r"))
         {
            flock($fp, LOCK_SH);
            
            // read page title and contest host contact
            fgets($fp);
            $this->chost = trim(fgets($fp));            
         
            // read contest date and time from file
            $datestr = trim(fgets($fp));
            $timestr = trim(fgets($fp));
            
            // convert contest date and time into time stamps and strings
            list($this->tstart, $this->tend) = contesttime($datestr, $timestr);
            $this->cdate = date("l, F d, Y", $this->tstart);
            $this->ctime = date("H:i", $this->tstart) . " to " . date("H:i T", $this->tend);
            
            // read scoreboard freeze, first letter, and show titles
            list($freeze, $this->firstletter, $showtitles) = explode(";", trim(fgets($fp)));
            $this->tfreeze = $this->tend - $freeze*60;
            $this->showtitles = $showtitles ? True : False;        
            
            // read problem set name         
            $this->cname = trim(fgets($fp));
            
            // read problem names and URLs into their arrays
            $letter = $this->firstletter;
            while ($line = fgets($fp))
            {
               $line = explode(";", $line);
               $ptitle = ($this->showtitles || $this->tnow >= $this->tstart) ? 
                         htmlspecialchars(trim($line[0])) : "?????";
               $name = "$letter - " . $ptitle;
               $url  = $problempath . trim($line[1]);
               
               $this->pletters[] = $letter;
               $this->pnames[$letter] = $name;
               $this->purls[$letter] = $url;
               
               ++$letter;
            }
            
            fclose($fp);
            $this->okay = True;
         }
         else
         {
            print "Cannot open $problemfile for data!";
            $this->okay = False;
         }
      }
      
      function problemlink($letter)
      {
         $name = $this->pnames[$letter];
         $url = $this->purls[$letter];
         if ($this->tnow >= $this->tstart)
            return "<a href=\"$url\">$name</a>";
         else
            return $name;
      }
      
      function problemlonglink($letter)
      {
         $name = $this->pnames[$letter];
         $url = $this->purls[$letter];
         if ($this->tnow >= $this->tstart)
            return "<a href=\"$url\">Problem $name</a>";
         else
            return "Problem " . $name;
      }
      
      function problemshortlink($letter)
      {
         $url = $this->purls[$letter];
         if ($this->tnow >= $this->tstart)
            return "<a href=\"$url\">$letter</a>";
         else
            return $letter;
      }

      function jproblemlink($letter)
      {
         $name = $this->pnames[$letter];
         $url = $this->purls[$letter];
         if ($this->tnow >= $this->tstart)
            return "<a href=\"../$url\">$name</a>";
         else
            return $name;
      }
      
      function jproblemshortlink($letter)
      {
         $url = $this->purls[$letter];
         if ($this->tnow >= $this->tstart)
            return "<a href=\"../$url\">$letter</a>";
         else
            return $letter;
      }
   }

   // -----------------------------------------------------------------------

   class Run
   {
      var $time;
      var $team;
      var $problem;
      var $language;
      var $file;
      var $verdict;
      
      function Run($key)
      {
         list($this->time, $this->team, $this->problem, $this->language, $this->file) = explode(",", trim($key));
         $this->verdict = "U";
      }
      
      function key()
      {
         return "$this->time,$this->team,$this->problem,$this->language,$this->file";
      }
      
      function judgement()
      {
         return "$this->time,$this->team,$this->problem,$this->language,$this->file;$this->verdict\n";
      }
   }

   // -----------------------------------------------------------------------

   class TeamScore
   {
      var $name;
      var $id;
      var $solved = array();
      var $attime = array();
      var $booboo = array();
      var $total = 0;
      var $penalty = 0;
      
      function TeamScore($team, $id = "")
      {
         $this->name = $team;
         $this->id = $id;
      }
      
      // report the result of a judgement to this team's record
      // assumes all reports are done in chronological order
      function report($problem, $time, $verdict)
      {
         if (in_array($problem, $this->solved))
            return;
            
         switch ($verdict)
         {
            // unjuged or submission error, ignore
            case "U":
            case "E":
               break;
            // accepted, add penalty points
            case "A":
               $this->solved[] = $problem;
               $this->attime[$problem] = $time;
               $this->total++;
               $this->penalty += $this->attime[$problem] + $this->booboo[$problem] * 20;
               break;
            // default - assume it's wrong
            default:
               $this->booboo[$problem] += 1;
               break;
         }
      }
      
      function problemstat($problem)
      {
         $stat = "&nbsp;";
         
         if (in_array($problem, $this->solved))
         {
            $stat = $this->attime[$problem];
            if (array_key_exists($problem, $this->booboo))
               $stat = $stat . "+" . ($this->booboo[$problem] * 20);
         }
         else if (array_key_exists($problem, $this->booboo))
         {
            $stat = "(-" . $this->booboo[$problem] . ")";
         }
         
         return $stat;
      }
      
      function key()
      {
         return sprintf("%02d%05d%s", 99 - $this->total, $this->penalty, $this->name);
      }
   }

   // -----------------------------------------------------------------------

   class Clarification
   {
      var $id;
      var $from;
      var $problem;
      var $responded = False;
      var $fresh = False;
      var $question;
      var $answer;
      
      function Clarification($team, $p, $q)
      {
         $this->id = time();
         $this->from = $team;
         $this->problem = $p;
         $this->question = $q;
      }
      
      function read($fp)
      {
         $stamp = (int) trim(fgets($fp));
         $team = trim(fgets($fp));
         $p = trim(fgets($fp));
         $r = (boolean) trim(fgets($fp));
         $q = "";
         $a = "";
         if (trim(fgets($fp)) == "{")
            for ($line = fgets($fp); trim($line) != "}"; $line = fgets($fp))
               $q = $q . $line;
         if ($r && trim(fgets($fp)) == "{")
            for ($line = fgets($fp); trim($line) != "}"; $line = fgets($fp))
               $a = $a . $line;
         
         $c = new Clarification($team, $p, $q);
         $c->id = $stamp;
         $c->responded = $r;
         $c->answer = $a;
         
         return $c;
      }

      function write($fp)
      {
         if ($fp)
         {
            fputs($fp, $this->id . "\n");
            fputs($fp, $this->from . "\n");
            fputs($fp, $this->problem . "\n");
            fputs($fp, $this->responded . "\n");
            fputs($fp, "{\n" . $this->question . "\n}\n");
            if ($this->responded)
               fputs($fp, "{\n" . $this->answer . "\n}\n");
         }
      }
      
   }

   // -----------------------------------------------------------------------
?>
