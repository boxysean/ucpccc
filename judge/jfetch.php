<?php
   // This file takes two GET variables, "file" and "name", and grabs the file
   // "file" from the server to be saved on the client as "name".  The purpose
   // is to rename for example A01.cc to A.cc when judge downloads the source.
   
   if (isset($_GET["file"])) 
   {
      $name = basename($_GET["name"]);
//      header("Content-type: application/force-download");
//      header("Content-Transfer-Encoding: Binary");
      header("Content-type: text/plain");
//      header("Content-length: ".filesize($file));
      header("Content-disposition: attachment; filename=\"".$name."\"");
      
      readfile($_GET["file"]);
   }
   else 
   {
      echo "No file selected";
   }
?> 