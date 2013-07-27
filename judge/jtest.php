<?php
   chdir("../");
   include("config.php");
   include("common.php");
?>

<html>

<head>
<?php print "<title>$g_pagetitle - System Test</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Host System Test</h1>
</div>

<hr>
<div align="left">

<pre>
<?php 
   // print out general information
   print "Welcome to the UCPCCC host system test & diagnostics.\n";
   print "PHP 4.4 or higher is required to run the contest control system.\n";
   print "If you had to type a user/password to get to this page, htaccess is enabled (GOOD).\n\n";
   
   print "Host is " . $_SERVER["SERVER_SOFTWARE"] . "\n";
   print "Client is " . $_SERVER["HTTP_USER_AGENT"] . "\n\n";
   
   // check configuration files
   print "Checking read/write for configuration file $g_configfile... ";
   if ($fp = fopen($g_configfile, "r+")) { print "OK\n"; fclose($fp); }
   else print "FAILED\n";
   print "Checking read/write for configuration file $g_teamfile... ";
   if ($fp = fopen($g_teamfile, "r+")) { print "OK\n"; fclose($fp); }
   else print "FAILED\n";

   // check contest files
   print "Checking read/write for contest file $g_submitfile... ";
   if ($fp = fopen($g_submitfile, "a+")) { print "OK\n"; fclose($fp); }
   else print "FAILED\n";
   print "Checking read/write for contest file $g_judgefile... ";
   if ($fp = fopen($g_judgefile, "r+")) { print "OK\n"; fclose($fp); }
   else print "FAILED\n";
   print "\n";
   
   // check file and directory creation, modification, lock
   $testdir = $g_submitpath . "test";
   if (file_exists($testdir)) rmdir($testdir);
   print "Creating test directory $testdir... ";
   if (mkdir($testdir))
   {
      $perms = substr(sprintf('%o', fileperms($testdir)), -4);
      print "OK (permissions $perms)\n";
      
      // change permissions
      print "Changing permissions for $testdir... ";
      if (chmod($testdir, 0770)) 
      {
         $perms = substr(sprintf('%o', fileperms($testdir)), -4);
         print "OK (permissions $perms)\n";
      }
      else print "FAILED\n";
      
      $testfile = $testdir . "/testfile.txt";
      print "Creating test file $testfile... ";
      if ($fp = fopen($testfile, "w"))
      {
         // create a test file
         $perms = substr(sprintf('%o', fileperms($testfile)), -4);
         print "OK (permissions $perms)\n";
         fclose($fp);         
      }
      else print "FAILED!\n";
      
      // change permissions
      print "Changing permissions for $testfile... ";
      if (chmod($testfile, 0660)) 
      {
         $perms = substr(sprintf('%o', fileperms($testfile)), -4);
         print "OK (permissions $perms)\n";
      }
      else print "FAILED\n";
      
      // open and lock
      print "Opening $testfile... ";
      if ($fp = fopen($testfile, "w+"))
      {
         print "OK\n";
         print "Acquiring exclusive lock for $testfile... ";
         if (flock($fp, LOCK_EX)) print "OK\n";
         else print "FAILED\n  (Contest system should still run fine without file locks)\n";
         fclose($fp);
      }
      else print "FAILED\n";
      
      // remove file
      print "Removing file $testfile... ";
      if (unlink($testfile)) print "OK\n";
      else print "FAILED\n";
   }
   else print "FAILED!\n";
   
   print "Removing directory $testdir... ";
   if (rmdir($testdir)) print "OK\n";
   else print "FAILED!\n";
   print "\n";
   
   
            
   // test system command execution
   print "Executing system command... ";
   if ($result = exec("id")) print "OK\nWeb server is running as $result.\n";
   else print "FAILED!\n  (The only consequence of failure is that ZIP archives cannot be uploaded)\n";

?>
</pre>

</div>

<?php footer(); ?>

</body>

</html>