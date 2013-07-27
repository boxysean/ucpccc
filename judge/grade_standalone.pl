#!/usr/bin/perl -w

################################################################################
# grade.pl (written by Chuong Do)
#
# This program is used to semi-automatically grade an ACM contest
# submission.
#
# Return values:
#   -1 = ERROR RUNNING SCRIPT
#    0 = ACCEPT
#    1 = COMPILE ERROR
#    2 = TIME-LIMIT EXCEEDED
#    3 = RUN-TIME ERROR
#    4 = PRESENTATION ERROR
#    5 = WRONG ANSWER
################################################################################

use strict;

my @LANGUAGES = ("c", "cc", "java");
my $TMP_DIR = "/tmp/acm";
my $PROBLEM;
my $EXT;
my $JUDGE_IN;
my $JUDGE_OUT;
my $TIME_LIMIT;
my $DIFF = "diff";

################################################################################
# Trim()
#
# Remove whitespace from either end of a string.
################################################################################

sub Trim($)
{
    my $string = shift;
    $string =~ s/^\s+//;
    $string =~ s/\s+$//;
    return $string;
}

################################################################################
# FindElement()
#
# Determine if an element is in a list.
################################################################################

sub FindElement {
    my ($key, @values) = @_;
    foreach my $item (@values){
	if ($item eq $key){
	    return 1;
	}
    }
    return 0;
}

################################################################################
# TestProgram()
#
# Compile and test a program.
################################################################################

sub TestProgram {
    my ($compile_command, $run_command) = @_;
    my $basename = $PROBLEM;

    # First, attempt to compile the program.

    print STDERR "--== Compiling ==--\n";
    my $compile_succeeded = 0;

    while (1) {
	if (system ($compile_command) == 0) {
	    $compile_succeeded = 1;
	    last;
	} else {
	    if ($EXT eq "java") {
		`javac $TMP_DIR/$basename.$EXT 2>&1` =~ /class (.*) is public/;
		last if ($basename eq $1);
		`mv $TMP_DIR/$basename.$EXT $TMP_DIR/$1.$EXT`;
		print STDERR "--== Detected new public class name: $1 ==--\n";
		$basename = $1;
		$compile_command = "javac $TMP_DIR/$basename.$EXT 2> /dev/null";
		$run_command = "java -classpath $TMP_DIR $basename < $JUDGE_IN > $TMP_DIR/$basename.out";
	    } else {
		last;
	    }
	}
    }
    if (!$compile_succeeded) {
	print STDERR "\nREJECT: compile error\n\n";
	return "CE";
    }
 
    # Next, fork off a child process running the program.

    my $child_pid;

    print STDERR "--== Running ==--\n";
    if (!defined($child_pid = fork)) {
	print STDERR "ERROR: Unable to call fork()!\n";
	exit(-1);
    }

    # If we're the child process, run the program and save the error code.

    if (!$child_pid) {
	my $ERROR_CODE = system($run_command);
	`echo "$ERROR_CODE" > $TMP_DIR/errorcode`;
	exit 0;
    }

    # If we're the parent process, wait until time limit is exceeded or until
    # an error code is returned.

    my $time_limit_exceeded = 0;

    while (1) {
	sleep 1;

	# Extract the time elapsed for the program
	
	chomp (my $time_string = `ps --ppid $child_pid -o etime | tail -n 1 | awk '{print \$1}'`);
	my $time_elapsed = -1;
	if ($time_string ne "ELAPSED") {
	    $time_elapsed = 0;
	    while (length($time_string) > 0) {
		$time_elapsed = $time_elapsed * 60 + substr($time_string, 0, 2);
		$time_string = substr($time_string, 2);
		if (length($time_string) > 0) {
		    $time_string = substr($time_string, 1);
		}
	    }
	}

	if ($time_elapsed >= 0) {
	    print STDERR $time_elapsed." seconds elapsed.\n";
	}

	# Stop if time-limit exceeded

	if ($time_elapsed > $TIME_LIMIT) {
	    $time_limit_exceeded = 1;

	    # Get list of descendant processes

	    my @pids = ($child_pid);
	    my @descendants = ($child_pid);

	    while (@descendants) {

		# Form list of descendants
		
		my $descendant_list = "";
		foreach my $descendant (@descendants) {
		    $descendant = Trim($descendant);
		    $descendant_list .= $descendant." ";
		}
		$descendant_list = Trim($descendant_list);

		# Get list of children of descendants
		
		chomp (@descendants = `ps --ppid "$descendant_list" -o pid`);
		@descendants = @descendants[1..$#descendants];
		foreach my $descendant (@descendants) {
		    chomp $descendant;
		    push(@pids, $descendant);
		}
            }

	    # Kill all descendant processes
	    
	    foreach my $descendant (@pids) {
		kill 9, $descendant;
	    }

	    last;
	}
	
	# Stop if error code returned.

	if (-e "$TMP_DIR/errorcode") { last; }
    }

    # If time limit exceeded, return TL

    if ($time_limit_exceeded) {
	print STDERR "\nREJECT: time-limit exceeded\n\n";
	`rm -f $TMP_DIR/errorcode`;
	return "TL";
    }

    # If run-time error encountered, return RE

    my $error_code = `cat $TMP_DIR/errorcode`;
    `rm -f $TMP_DIR/errorcode`;
    if ($error_code != 0) {
	print STDERR "\nREJECT: runtime error\n\n";
	return "RE";
    }
	
    print STDERR "--== Comparing ==--\n";
    
    # If diff passes, return AC

    if (system ("$DIFF $TMP_DIR/$basename.out $JUDGE_OUT > /dev/null") == 0) {
	print STDERR "\nACCEPT\n\n";
	return "AC";
    } else {
	
	# If diff -w -B passes, return PE

	if (system ("$DIFF $TMP_DIR/$basename.out $JUDGE_OUT -w -B > /dev/null") == 0) {
	    print STDERR "\nREJECT: presentation error\n\n";
	    return "PE";
	} else {

	    # Otherwise, return WA

	    print STDERR "\nREJECT: wrong answer (but might be presentation error)\n\n";
	    print STDERR "--== Diff ==--\n\n";
	    print STDERR `$DIFF $TMP_DIR/$basename.out $JUDGE_OUT`;
	    print STDERR "\n";
	    return "WA";
	}
    }
}

################################################################################
# PrintBanner()
#
# Print banner.
################################################################################

sub PrintBanner {
    my ($msg) = @_;
    print STDERR "\n";
    print STDERR "################################################################################\n";
    print STDERR "# $msg\n";
    print STDERR "################################################################################\n\n";
}

################################################################################
# main()
#
# Main program.
################################################################################

sub main {

    my $FILENAME = $ARGV[0]; chomp $FILENAME;
    
    PrintBanner("Grading Script -- Running for $FILENAME");

    if (@ARGV != 4 && @ARGV != 5) {
	print STDERR "USAGE: grade_standalone.pl SOURCE.{c,cc,java} JUDGE_IN JUDGE_OUT TIMELIMIT <DIFF_PROGRAM>\n\n";
	exit(-1);
    }

    # Create temp directory
    
    if (system ("rm -rf $TMP_DIR") != 0) {
	print STDERR "ERROR: Unable to remove old temp directory.\n";
	exit(-1);
    }

    if (system ("mkdir $TMP_DIR") != 0) {
	print STDERR "ERROR: Unable to create new temp directory.\n";
	exit(-1);
    }

    # Get arguments

    $JUDGE_IN = $ARGV[1];
    $JUDGE_OUT = $ARGV[2];
    $TIME_LIMIT = $ARGV[3];
    if (@ARGV == 5) {
    $DIFF = $ARGV[4];
    }
    
    # Parse filename
    
    if ($FILENAME =~ /(\w+)\.(\w+)/) {
	$PROBLEM = $1;
	$EXT = $2;
    } else {
	print STDERR "ERROR: Could not parse filename.\n\n";
	exit(-1);
    }

    print STDERR "PROBLEM:        $PROBLEM\n";
    print STDERR "EXT:            $EXT\n";
    print STDERR "JUDGE_IN:       $JUDGE_IN\n";
    print STDERR "JUDGE_OUT:      $JUDGE_OUT\n";
    print STDERR "TIME_LIMIT:     $TIME_LIMIT\n";
    print STDERR "DIFF_PROGRAM:   $DIFF\n";

    # Error-checking
    
    if (!FindElement ($EXT, @LANGUAGES)) {
	print STDERR "ERROR: Unknown language \"$EXT\".\n\n";
	exit(-1);
    }
    
    if (!(-e $FILENAME)) {
	print STDERR "ERROR: File $FILENAME not found!\n\n";
	exit(-1);
    }
    
    # Prepare data for run
    
    `cp $FILENAME $TMP_DIR/$PROBLEM.$EXT`;

    my $response;
    
    if ($EXT eq "c") {
	$response = TestProgram ("gcc -lm -O2 -o $TMP_DIR/$PROBLEM $TMP_DIR/$PROBLEM.$EXT 2> /dev/null",
				 "$TMP_DIR/$PROBLEM < $JUDGE_IN > $TMP_DIR/$PROBLEM.out");
    } elsif ($EXT eq "cc") {
	$response = TestProgram ("g++ -lm -O2 -o $TMP_DIR/$PROBLEM $TMP_DIR/$PROBLEM.$EXT 2> /dev/null",
				 "$TMP_DIR/$PROBLEM < $JUDGE_IN > $TMP_DIR/$PROBLEM.out");
    } elsif ($EXT eq "java") {
	$response = TestProgram ("javac $TMP_DIR/$PROBLEM.$EXT 2> /dev/null",
				 "java -classpath $TMP_DIR $PROBLEM < $JUDGE_IN > $TMP_DIR/$PROBLEM.out");
    }

    if ($response eq "AC") {
	exit(0);
    } elsif ($response eq "CE") {
	exit(1);
    } elsif ($response eq "TL") {
	exit(2);
    } elsif ($response eq "RE") {
	exit(3);
    } elsif ($response eq "PE") {
	exit(4);
    } elsif ($response eq "WA") {
	exit(5);
    } else {
	exit(-1);
    }

    # Not used

    if (0) {
	# Decide if result should be reported
	
	my $choice;
	do {
	    print STDERR "Do you wish to report a result now? (y/n) ";
	    $choice = <STDIN>; chomp $choice;
	} while ($choice ne "y" && $choice ne "n");
	
	`touch $FILENAME.graded`;
	
	# Check if grading should be overriden.
	
	if ($choice eq "y") {
	    do {
		print STDERR "Do you wish to override the automatic grader? (y/n) ";
		$choice = <STDIN>; chomp $choice;
	    } while ($choice ne "y" && $choice ne "n");
	    
	    if ($choice eq "y") {
		do {
		    print STDERR "Enter response code: (AC/CE/RE/TL/WA/PE) ";
		    $response = <STDIN>; chomp $response;
		} while ($response ne "AC" && $response ne "CE" &&
			 $response ne "RE" && $response ne "TL" &&
			 $response ne "WA" && $response ne "PE");
	    }
	    
	    # print STDERR `$CONTEST_DIR/scripts/report.pl $ARGV[0] $response`;
	} else {
	    print STDERR "\n";
	}
    }
}

main;
