//////////////////////////////////////////////////////////////////////
// Communicator.cc
//
// Program for facilitating communication between a judging program
// and a test program.  This program takes two arguments, a
// JUDGE_PROGRAM and a TEST_PROGRAM.
//
// Steps:
// (1) Create two communication pipes.
//
// (2) Fork off two child processes for executing JUDGE_PROGRAM and 
//     TEST_PROGRAM.
//
// (3) Retrieve exit codes from each program.
//     - Any non-zero return value from the TEST_PROGRAM is assumed
//       to be a runtime error (although we can catch abnormal
//       termination of C/C++ programs, Java programs don't fail
//       abnormally -- they return an error code)
//     - A 0 return value from the JUDGE_PROGRAM means ACCEPTED
//     - A non-zero return from the JUDGE_PROGRAM means WRONG ANSWER
//
// (4) Return overall exit code.
//     - A 0 return value means ACCEPTED
//     - A 1 return value means WRONG_ANSWER
//     - A 2 return value means TEST_PROGRAM runtime error
//     - A 3 return value means JUDGE_PROGRAM runtime error
//     - A 4 return value means communication layer failed
//////////////////////////////////////////////////////////////////////

#include <iostream>
#include <string>
#include <cstring>
#include <sys/types.h>
#include <sys/wait.h>

using namespace std;

const int ACCEPTED = 0;
const int WRONG_ANSWER = 1;
const int TEST_RUNTIME_ERROR = 2;
const int JUDGE_RUNTIME_ERROR = 3;
const int COMMUNICATOR_FAILURE = 4;
const int FAILURE = 4;

int main(int argc, char **argv) {

  if (argc == 1) {
    cerr << "Usage: " << argv[0] << " -judge JUDGE_PROGRAM [JUDGE_ARGS...] -test TEST_PROGRAM [TEST_ARGS...]" << endl;
    return COMMUNICATOR_FAILURE;
  }

  // parse arguments
  char *judge_args[2000];
  char *test_args[2000];
  int num_judge_args = 0;
  int num_test_args = 0;

  int current = 0;
  for (int i = 1; i < argc; i++) {
    if (!strcmp(argv[i], "-judge")) {
      current = 1;
    } else if (!strcmp(argv[i], "-test")) {
      current = 2;
    } else if (current == 1) {
      judge_args[num_judge_args++] = argv[i];
    } else if (current == 2) {
      test_args[num_test_args++] = argv[i];
    } else {
      cerr << "COMMUNICATOR ERROR: incorrect arguments" << endl;
      return COMMUNICATOR_FAILURE;
    }
  }
  
  judge_args[num_judge_args] = NULL;
  test_args[num_test_args] = NULL;

  if (current != 2) {
    cerr << "COMMUNICATOR ERROR: insufficient arguments" << endl;
    return COMMUNICATOR_FAILURE;
  }

  // organization of pipes
  //
  // test_input[0] = testing program reads from here
  // test_input[1] = judging program writes here
  // judge_input[0] = judging program reads from here
  // judge_input[1] = testing program writes here

  int test_input[2];
  int judge_input[2];

  if (pipe(test_input)) {
    cerr << "COMMUNICATOR: couldn't create test program input pipe." << endl; 
    return COMMUNICATOR_FAILURE; 
  }
  
  if (pipe(judge_input)) {
    cerr << "COMMUNICATOR: couldn't create judge program input pipe" << endl; 
    return COMMUNICATOR_FAILURE; 
  }
  
  int test_pid;
  int judge_pid;
  
  // test program
  if (!(test_pid = fork())) {
    close(STDIN_FILENO);
    close(STDOUT_FILENO);
    dup2(test_input[0], STDIN_FILENO);
    dup2(judge_input[1], STDOUT_FILENO);
    execvp(test_args[0], test_args);
    cerr << "COMMUNICATOR: test program failed to run" << endl;
    return COMMUNICATOR_FAILURE;
  } 
  
  // judge program
  if (!(judge_pid = fork())) {
    close(STDIN_FILENO);
    close(STDOUT_FILENO);
    dup2(judge_input[0], STDIN_FILENO);
    dup2(test_input[1], STDOUT_FILENO);
    execvp(judge_args[0], judge_args);
    cerr << "COMMUNICATOR: judge program failed to run" << endl;
    return COMMUNICATOR_FAILURE;
  } 

  // wait for either of the two programs to finish, then allow 2 seconds for the other to finish
  int judge_status, judge_finished = false;
  int test_status, test_finished = false;
  int wait_counter = 0;

  while ((!judge_finished || !test_finished) && wait_counter < 2) {
    sleep(1);
    if (!judge_finished && waitpid(judge_pid, &judge_status, WNOHANG)) judge_finished = true;
    if (!test_finished && waitpid(test_pid, &test_status, WNOHANG)) test_finished = true;
    if (judge_finished || test_finished) wait_counter++;
  }

  // check for judge program runtime error
  if (judge_finished && !WIFEXITED(judge_status)) {
    cerr << "COMMUNICATOR: judge program terminated abnormally" << endl;
    kill(test_pid, SIGKILL);
    return JUDGE_RUNTIME_ERROR;
  }

  // if judge program finished without test program finishing, wait for
  // test program to finish
  if (!test_finished) {
    cerr << "COMMUNICATOR: test program did not terminate, waiting..." << endl;
    while (!test_finished) {
      sleep(1);
      if (!test_finished && waitpid(test_pid, &test_status, WNOHANG)) test_finished = true;
    }    
  }  

  // check for test program runtime error
  if (test_finished && !WIFEXITED(test_status) || WEXITSTATUS(test_status)) {
    cerr << "COMMUNICATOR: test program terminated abnormally" << endl;
    kill(judge_pid, SIGKILL);
    return TEST_RUNTIME_ERROR;
  }

  // if test program finished without judge finishing then assume that
  // the test program aborted early
  if (!judge_finished) {
    cerr << "COMMUNICATOR: test program aborted early" << endl;
    kill(judge_pid, SIGKILL);
    return WRONG_ANSWER;
  }
  
  // wrong answer
  if (WEXITSTATUS(judge_status)) {
    cerr << "COMMUNICATOR: test program produced wrong answer" << endl;
    return WRONG_ANSWER; 
  }
  
  // accept!
  return ACCEPTED;
}
