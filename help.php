<?php
   include("config.php");
   include("common.php");
?>

<html>

<head>
<?php print "<title>$g_pagetitle - Help</title>\n"; ?>
</head>

<body>

<div align="center">
<h1>Help Page</h1>
</div>

<?php navigation("help"); ?>

<hr>
<div align="left">

<h2>Introduction</h2>

<p>   The UofC Programming Contest Control Centre is a tool for hosting an 
<a href="http://icpc.baylor.edu">ACM ICPC</a>-style programming contest on the
web.  Only a basic web browser (with cookies enabled) is required to access the
contest system.

<p>   In order to participate in the running contest, you must have a valid team
ID and password.  If you do not have an ID and password, please contact the
contest host to obtain one.

<h2>Contest Problems</h2>

<p>   Links to each of the contest problems can be found on the main page.  Note
that these links are only active once the contest has begun, and are accessible
to the general public as well.  The contest date and time (with time zone) is 
also posted on this page.

<h2>Contest Scoreboard</h2>

<p>   The &quot;Scoreboard&quot; navigation tab takes you to a page displaying 
the current, live standings of contest.  This page is automatically refreshed 
every 5 minutes.

<p>   On the scoreboard, formally participating teams names are shown in
emphasized bold type, while guest and ghost teams are shown in regular typeface.
Only teams that have submitted solutions will be shown on the scoreboard.

<p>   A number appearing in a cell in the scoreboard indicates the number of
minutes a team took to solve a particular problem.  The number following a +
sign indicates additional penalty minutes a team incurred.  A negative number
in parentheses incidates the number of failed attempts to solve a particular
problem.

<h2>Submitting Solutions</h2>

<p>   The &quot;Submissions&quot; navigation tab will provide you with a form
you can use to submit a solution to a problem.  You must be logged in to the
system with a valid team ID and password in order to submit your solutions.

<p>   In the Source field, choose the file which contains the source code for
your solution.  Please note that all the source code for any one solution must
be contained within the same file!  Be sure to select the correct language of 
the source, or you may receive a nasty compilation error.  The accepted
languages are currently C, C++, and Java.

<h2>Checking Submission Status</h2>

<p>   You will also be able to check the status of any solutions you submitted
at the bottom of the &quot;Submissions&quot; page.  If you are logged in to the
system, a table displaying all submissions received from your team will
automatically be shown.  Checking the judge results of your submissions is 
important, as it can give you valuable information if your solution failed.

<p>   The various results for a submitted solution are listed below.  Note that
not all possible responses may be used by the judge for a particular contest.

<p><table cellpadding="4">
<tr>   <td width="180">&nbsp;&nbsp;&quot;Not Yet Judged&quot;&nbsp;&nbsp;</td>
      <td>   The contest judge has yet to run your solution against the test
            data.</td></tr>

<tr>   <td>&nbsp;&nbsp;&quot;Accepted&quot;&nbsp;&nbsp;</td>
      <td>   Your solution is correct.</td></tr>

<tr>   <td>&nbsp;&nbsp;&quot;Run-Time Error&quot;&nbsp;&nbsp;</td>
      <td>   Execution of your solution on the test data resulted in a program
            crash or similar behaviour.</td></tr>
            
<tr>   <td>&nbsp;&nbsp;&quot;Time Limit Exceeded&quot;&nbsp;&nbsp;</td>
      <td>   Your program failed to terminate within the allotted execution time.
            </td></tr>
            
<tr>   <td>&nbsp;&nbsp;&quot;Incorrect Output&quot;&nbsp;&nbsp;</td>
      <td>   Execution of your solution on the test data does not produce the
            correct answer.</td></tr>
            
<tr>   <td>&nbsp;&nbsp;&quot;Presentation Error&quot;&nbsp;&nbsp;</td>
      <td>   Your solution appears to produce the correct answer to the problem,
            but there is an error in the formatting, capitalization, spelling,
            punctuation, etc. of the output.</td></tr>
            
<tr>   <td>&nbsp;&nbsp;&quot;Compile Error&quot;&nbsp;&nbsp;</td>
      <td>   Your program source fails to compile on the judge system using the
            compiler for the specified language.</td></tr>
            
<tr>   <td>&nbsp;&nbsp;&quot;Submission Error&quot;&nbsp;&nbsp;</td>
      <td>   There was an unknown or unanticipated problem with your submission.
            Check that you are following the right conventions for formatting
            and uploading your source files, using the correct input/output, and
            that you are solving the right problem.  It may be necessary to
            contact the contest host or judge if you cannot resolve the problem.
            You are not normally penalized for a submission error.
</table>

<h2>Contest or Problem Clarifications</h2>

<p>   The &quot;Clarifications&quot; navigation tab allows you to view any
additional clarifications or information about the problems or the contest that
the judge has posted.  You may also use this page to submit a clarification
request.  It is important to check this page often during a contest, especially
if you are having difficulties with a certain problem or the contest system.

<p>   Note that whenever a clarification is answered, it is answered for all
teams to see.  Clarifications are only intended to be used when there is some
genuine inconsistency or ambiguity in a problem that needs to be resolved, or
if certain special instructions for the contest are required but were omitted.
It is not by any means a way for you to get the judge to explain a problem if
you do not understand it.

<p>   Once you submit a clarification request, you will no longer be able to
see it.  Do not worry -- it has been saved!  The clarification will only appear
if and when the judge has chosen to answer it.

<h2>About the UCPCCC</h2>

<p>   The University of Calgary Programming Contest Control Centre was developed
beginning in 2005 for the purpose of training the University of Calgary and 
other Canadian programming teams.  It was designed as a super light-weight, easy
to use, and universally accessible front end for running small programming
contests.

<p>   If you have any questions, comments, or suggestions about the system,
please contact the author, Sonny Chan, at <a href="mailto:sonnyc@gmail.com">
sonnyc@gmail.com</a>.  The author would also like to thank Kelly Poon and Allan
Hart for their contributions to the development of the contest system, and
Chuong Do for providing a very nice automatic judging script back end.

</div>

<?php footer(); ?>

</body>

</html>
