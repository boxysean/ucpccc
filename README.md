This guide contains a brief set of steps to help you get a contest up and running quickly.  For a more comprehensive instructions on the configuration and use of the system, please refer to the Contest Administrator's Manual (`manual.pdf`) found in the `ucpccc` directory.

QUICK START
-----------

1.  Clone this repo into a new folder on the web host where the contest pages will be served from.

2.  Run the setup shell script (`setup`) in the judge directory to correctly configure the permissions of the contest system's files and directories.  The script will also set a judge password for you.

3.  Go to the configuration page at http://<contestURL>/judge/jconfig.php to configure your contest, problems, and teams.  You will need to enter a user name of "judge" and the password from step 2.  Be sure to upload files for the problem statements, and save the changes when done.

4.  Direct contestants to the contest URL and run the contest!

CREDITS
-------

The Ultra Cool Programming Contest Control Centre was authored by Sonny Chan and released to the public with his blessings.
