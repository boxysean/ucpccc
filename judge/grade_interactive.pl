#!/usr/bin/perl -w

################################################################################
# grade.pl (written by Chuong Do)
#
# This program is used to semi-automatically grade an interactive ACM contest
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
#
# This program takes 4 arguments:
#   Argument #1: SOURCE.{c,cc,java} = name of the program to be tested
#                - the program is assumed to read from STDIN
#                - the program is assumed to write to STDOUT
#                - any Java program should have a public class with the same
#                  name as the file, and a public static void main()
#
#   Argument #2: JUDGE_PROGRAM = an executable file containing the verification
#                program that will interact with the test program
#                - the judge program is assumed to read from STDIN
#                - the judge program is assumed to write to STDOUT
#                - the program will be called with a single argument containing
#                  the name of an input file
#
#   Argument #3: JUDGE_IN = name of the judge input file
#                - the name of a copy of this file will be passed
#                  as the first argument to the judge program
#
#   Argument #4: TIMELIMIT = number of seconds before aborting the programs
################################################################################

use strict;
use POSIX ":sys_wait_h";

my @LANGUAGES = ("c", "cc", "java");
my $TMP_DIR = "/tmp/acm";
my $COMMUNICATOR = "communicator";
my $PROBLEM;
my $EXT;
my $JUDGE_PROGRAM;
my $JUDGE_PROGRAM_SPLIT;
my $JUDGE_IN;
my $JUDGE_IN_SPLIT;
my $TIME_LIMIT;

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
    foreach my $item (@values) {
	return 1 if ($item eq $key);
    }
    return 0;
}

################################################################################
# GetProcessTree()
#
# Get list of processes in the process tree for a PID.  Includes the original
# PID.
################################################################################

sub GetProcessTree {
    my $child_pid = shift;
    my @process_tree = ();

    my @descendants = ($child_pid);
    while (@descendants) {

	# Construct list of descendants
	
	my $descendant_list = "";
	foreach my $descendant (@descendants) {
	    $descendant = Trim($descendant);
	    $descendant_list .= $descendant." ";
	    push(@process_tree, $descendant);
	}
	$descendant_list = Trim($descendant_list);

	# Get children of descendants
	    
	chomp(@descendants = `ps --ppid "$descendant_list" -o pid`);
	chomp(@descendants = @descendants[1..$#descendants]);
    }
    
    return @process_tree;
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
		$run_command = "$TMP_DIR/$COMMUNICATOR -judge $JUDGE_PROGRAM_SPLIT $JUDGE_IN_SPLIT -test java -classpath $TMP_DIR $basename";
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

    # If we're the child process, run the program.

    if (!$child_pid) {
	if (!(exec $run_command)) {
	    print STDERR "ERROR: Unable to call exec()!\n";
	    exit(-1);
	}
    }

    # If we're the parent process, wait until time limit is exceeded or until
    # an error code is returned.

    my $time_limit_exceeded = 0;
    my $terminated_normally = 0;
    my $exit_code = 0;

    while (1) {
	sleep 1;      
	
	# Compute elapsed time (Doesn't handle programs that take longer than 1 day)
	
	chomp(my $time_string = `ps --pid $child_pid -o etime | tail -n 1 | awk '{print \$1}'`);
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

	# Break if child is finished.

	if (waitpid($child_pid, WNOHANG)) { 
	    $terminated_normally = 1 if (WIFEXITED($?));
	    $exit_code = WEXITSTATUS($?);
	    last;
	}

	# Stop if time-limit exceeded

	if ($time_elapsed > $TIME_LIMIT) {
	    $time_limit_exceeded = 1;
	    my @pids = GetProcessTree($child_pid);

	    foreach my $descendant (@pids) {
		kill 9, $descendant;
	    }

	    last;
	}
    }

    # If time limit exceeded, return TL

    if ($time_limit_exceeded) {
	print STDERR "\nREJECT: time-limit exceeded\n\n";
	return "TL";
    }

    # If run-time error encountered, return RE
    
    if (!$terminated_normally) {
	print STDERR "\nERROR: communication runtime error\n\n";
	return "??";
    }

    # Check judge response

    if ($exit_code == 0) {
	print STDERR "\nACCEPT\n\n";
	return "AC";	
    } elsif ($exit_code == 1) {
	print STDERR "\nREJECT: wrong answer\n\n";
	return "WA";
    } elsif ($exit_code == 2) {
	print STDERR "\nREJECT: runtime error\n\n";
	return "RE";
    } elsif ($exit_code == 3) {
	print STDERR "\nERROR: judge runtime error\n\n";
	return "??";
    } elsif ($exit_code == 4) {
	print STDERR "\nERROR: communication error\n\n";
	return "??";
    } else {
	print STDERR "\nERROR: unknown communication response\n\n";
	return "??";
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

    if (@ARGV != 4) {
	print STDERR "USAGE: grade_interactive.pl SOURCE.{c,cc,java} JUDGE_PROGRAM JUDGE_IN TIMELIMIT\n\n";
	exit(-1);
    }

    # Create temp directory
    
    if (system("rm -rf $TMP_DIR") != 0) {
	print STDERR "ERROR: Unable to remove old temp directory.\n";
	exit(-1);
    }
    
    if (system("mkdir $TMP_DIR") != 0) {
	print STDERR "ERROR: Unable to create new temp directory.\n";
	exit(-1);
    }

    # Get arguments

    $JUDGE_PROGRAM = $ARGV[1]; chomp $JUDGE_PROGRAM;
    $JUDGE_IN = $ARGV[2];
    $TIME_LIMIT = $ARGV[3];
    
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
    print STDERR "JUDGE_PROGRAM:  $JUDGE_PROGRAM\n";
    print STDERR "JUDGE_IN:       $JUDGE_IN\n";
    print STDERR "TIME_LIMIT:     $TIME_LIMIT\n\n";

    # Error-checking
    
    if (!FindElement($EXT, @LANGUAGES)) {
	print STDERR "ERROR: Unknown language \"$EXT\".\n\n";
	exit(-1);
    }
    
    if (!(-e $FILENAME)) {
	print STDERR "ERROR: File $FILENAME not found!\n\n";
	exit(-1);
    }
    
    # Prepare data for run
        
    my @splitname = split(/\//, $JUDGE_PROGRAM);
    $JUDGE_PROGRAM_SPLIT = $splitname[-1];
       @splitname = split(/\//, $JUDGE_IN);
    $JUDGE_IN_SPLIT = $splitname[-1];
    `cp $FILENAME $TMP_DIR/$PROBLEM.$EXT`;
    `cp $COMMUNICATOR $TMP_DIR/$COMMUNICATOR`;
    `cp $JUDGE_PROGRAM $TMP_DIR/$JUDGE_PROGRAM_SPLIT`;
    `cp $JUDGE_IN $TMP_DIR/$JUDGE_IN_SPLIT`;

    if ($JUDGE_PROGRAM_SPLIT =~ /(\w+)\.(\w+)/) {
        my $EXTJ = $2;
        if ($EXTJ eq "java") {
            `javac $TMP_DIR/$JUDGE_PROGRAM_SPLIT`;
            chdir $TMP_DIR;
            $JUDGE_PROGRAM_SPLIT = "java -classpath $TMP_DIR ".$1;
        }
    } else {
        $JUDGE_PROGRAM_SPLIT = $TMP_DIR."/".$JUDGE_PROGRAM_SPLIT;
    }

    my $response;
    
    if ($EXT eq "c") {
	$response = TestProgram("gcc -lm -O2 -o $TMP_DIR/$PROBLEM $TMP_DIR/$PROBLEM.$EXT 2> /dev/null",
				"$TMP_DIR/$COMMUNICATOR -judge $JUDGE_PROGRAM_SPLIT $JUDGE_IN_SPLIT -test $TMP_DIR/$PROBLEM");
    } elsif ($EXT eq "cc") {
	$response = TestProgram("g++ -lm -O2 -o $TMP_DIR/$PROBLEM $TMP_DIR/$PROBLEM.$EXT 2> /dev/null",
				"$TMP_DIR/$COMMUNICATOR -judge $JUDGE_PROGRAM_SPLIT $JUDGE_IN_SPLIT -test $TMP_DIR/$PROBLEM");
    } elsif ($EXT eq "java") {
	$response = TestProgram("javac $TMP_DIR/$PROBLEM.$EXT 2> /dev/null",
				"$TMP_DIR/$COMMUNICATOR -judge $JUDGE_PROGRAM_SPLIT $JUDGE_IN_SPLIT -test java -classpath $TMP_DIR $PROBLEM");
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

    if (system("rm -rf $TMP_DIR") != 0) {
	print STDERR "ERROR: Unable to remove old temp directory.\n";
	exit(-1);
    }
}

main;
