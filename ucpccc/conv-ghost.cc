// ---------------------------------------------------------------------------
// Program to generate random-ish ghost teams from known total problems solved
// and known total penalty, but no details given.
//
// February 24, 2005 - Sonny Chan
// ---------------------------------------------------------------------------

#include <iostream>
#include <iomanip>
#include <sstream>
#include <string>
#include <valarray>
#include <cstdlib>
#include <ctime>

using namespace std;

void write(ostream &out, int t, string team, char problem, char verdict)
{
   int h = t / 60, m = t % 60;
   out << setw(2) << setfill('0') << h << ':';
   out << setw(2) << setfill('0') << m << ',';
   out << team << ',' << problem << ',';
   out << "C++," << problem << ".cc;" << verdict << endl;
}

const int first = 15;
const int last = 285;
const int limit = 299;

int main()
{
   istream &in = cin;
   ostream &out = cout;
   
   srand(time(0));
   
   string line;
   while (getline(in, line))
   {
      istringstream iss(line);

      string rank, name, place;
      getline(iss, rank, '\t');
      getline(iss, name, '\t');
      getline(iss, place, '\t');
      
      int total, penalty;
      iss >> total >> penalty;
      
      int unit, sum, final, delta = 0;
      sum = (total + 1) * total / 2;
      unit = penalty / sum;
      
      valarray<int> times(total);
      for (int i = 0; i < total; ++i) {
         int perturb = (rand() % unit) - unit/2; 
         times[i] = unit * (i+1) + perturb;
         final = unit * (i+1);
      }
      
      if (final > last) {
         delta = final - last + unit;
         unit = (penalty - total*delta) / sum;
         for (int i = 0; i < total; ++i) {
            int perturb = (rand() % unit) - unit/2; 
            times[i] = unit * (i+1) + delta + perturb;
         }         
      }
      
      for (int i = 0; i < total; ++i)
         times[i] = min(max(times[i], first), limit);
      
      delta = penalty - times.sum();
      
//      cout << name << ' ' << penalty << ' ' << unit << ' ' << delta << endl;
      
      if (delta > 0) {
         for (int i = 0, j = 0; i < delta; ++j)
            if (times[j%total] < limit) { ++times[j%total]; ++i; }
      }
      else if (delta < 0) {
         for (int i = 0, j = 0; i < -delta; ++j)
            if (times[i%total] > first) { --times[i%total]; ++i; }
      }
      
      for (int i = 0; i < total; ++i)
         write(out, times[i], name, 'J'+char(i), 'A');
   }

   return 0;
}
