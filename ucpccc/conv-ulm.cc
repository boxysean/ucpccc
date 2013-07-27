#include <iostream>
#include <vector>
#include <string>
#include <sstream>

using namespace std;

struct submission
{
   int h, m;
   string team, language;
   char problem, verdict;
};

istream &operator>>(istream &stream, submission &s)
{
   string input, cell;
   getline(stream, input);
   istringstream iss(input);
   
   getline(iss, cell, '\t');
   
   // get the time
   char colon;
   getline(iss, cell, '\t');
   istringstream jss(cell);
   jss >> s.h >> colon >> s.m;
   
   // get the team
   getline(iss, s.team, '\t');
   
   // get the problem
   getline(iss, cell, '\t');
   s.problem = cell[0];
   
   // get the language
   getline(iss, s.language, '\t');
   
   // get the verdict
   iss >> cell;
   s.verdict = cell[0];
   
   return stream;
}

ostream &operator<<(ostream &stream, const submission &s)
{
   stream << s.h << ':' << s.m << ',' << s.team << ',' << s.problem << ','
          << s.language << ',' << s.problem << '.' << s.language << ';'
          << s.verdict << endl;
}

int main()
{
   vector<submission> v;
   submission s;
   
   while (cin >> s) v.push_back(s);
   
   for (vector<submission>::reverse_iterator it = v.rbegin(); it != v.rend(); ++it)
      cout << *it;
   
   return 0;
}