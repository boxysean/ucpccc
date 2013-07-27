#include <iostream>
#include <iomanip>
#include <sstream>
#include <string>
#include <ctime>
#include <cstdlib>

using namespace std;

const int delay = 10;
const int last = 180;
const int rwindow = 90;

void write(ostream &out, int t, string team, char problem, char verdict)
{
    if (problem == 0) return;
   int h = t / 60, m = t % 60;
   out << setw(2) << setfill('0') << h << ':';
   out << setw(2) << setfill('0') << m << ',';
   out << team << ',' << problem << ',';
   out << "C++," << problem << ".cc;" << verdict << endl;
}

int main()
{
   istream &in = cin;
   ostream &out = cout;
   
//   char pindex[] = { 0, '1', 0, '2', '3', '4' };
   char pindex[] = { 0, '5', '6', '7', '8', 0 };

   string line;
   while (getline(in, line))
   {
      istringstream iss(line);
      
      string rank, name, prob;
      getline(iss, rank, '\t');
      getline(iss, name, '\t');
      getline(iss, prob, '\t');
      getline(iss, prob, '\t');
      
      for (int i = 0; i < 6; ++i)
      {
         char p = pindex[i];
      
         getline(iss, prob, '\t');
         istringstream jss(prob);
         
         int tries, time;
         char slash;
         
         if (jss >> tries >> slash)
         {
            if (jss >> time)
            {
               int start = time - (tries-1)*delay;
               for (int j = start; j < time; j += delay)
                  write(out, j, name, p, 'W');
               write(out, time, name, p, 'A');
            }
            else
            {
               int start = last - tries*delay - rand()%rwindow;
               for (int j = 0; j < tries; ++j)
                  write(out, start + j * delay,  name, p, 'W');
            }
         }
      }
   }

   return 0;
}
