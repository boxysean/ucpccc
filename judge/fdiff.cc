// --------------------------------------------------------------------------
// Program to perform a line-by-line difference of floating point numbers
// allowing for a given tolerance (both absolute and relative).  There can
// be zero or more floating-point numbers per line.  Behaves somewhat similar
// to the UNIX diff command.
// --------------------------------------------------------------------------

#include <iostream>
#include <fstream>
#include <sstream>
#include <string>
#include <cmath>

using namespace std;

const double epsilon = 10e-6;
const char *whitespace = " \t\r\n";

string trim(const string &s)
{

    size_t begin = s.find_first_not_of(whitespace);
    size_t end = s.find_last_not_of(whitespace);

    if(( string::npos == begin ) || ( string::npos == end))
        return string();
    else
        return s.substr( begin, end-begin+1 );
}

bool test(string a, string b)
{
    if (a == b) return true;
    
    istringstream ain(a), bin(b);
    
    // check any initial 
    double da, db;
    while (ain >> da) {
        if (bin >> db) {
            double d = abs(da - db);
            double re = (da != 0) ? (d / da) : d;
            if (d > epsilon && re > epsilon)
                return false;
        }
        else return false;
    }
    
    // check the remainder of the strings
    string ax, bx;
    getline(ain, ax);
    getline(bin, bx);
    
    // ignore whitespace
    return trim(ax) == trim(bx);
}

int main(int argc, char *argv[])
{
    char *file1 = 0, *file2 = 0;
    
    for (int i = 1; i < argc; ++i) {
        if (argv[i][0] == '-') continue;
        if (file1) {
            if (file2)  break;
            else        file2 = argv[i];
        }
        else file1 = argv[i];
    }
    
    if (file1 == 0 || file2 == 0) {
        cout << "Insufficient arguments!" << endl;
        return 1;
    }
    
    int different = 0;
    
    // start line by line comparison
    
    ifstream in1(file1);
    ifstream in2(file2);
    
    string line1, line2;
    int line = 1;
    while (getline(in1, line1))
    {
        if (getline(in2, line2)) {
            if (!test(line1, line2))
            {
                cout << line << "< " << line1 << endl;
                cout << line << "> " << line2 << endl;
                ++different;
            }
        }
        else if (!line1.empty()) {
            cout << line << "< " << line1 << endl;
            ++different;
        }
        ++line;
    }
    while (getline(in2, line2))
    {
        if (!line2.empty()) {
            cout << line << "> " << line2 << endl;
            ++different;
        }
        ++line;
    }
    
    in1.close();
    in2.close();

    return different;
}
