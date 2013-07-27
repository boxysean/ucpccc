#include <iostream>
#include <iomanip>
#include <sstream>
#include <vector>
#include <map>
#include <numeric>
#include <algorithm>
#include <cstdlib>
#include <ctime>
#include <cmath>

using namespace std;

const int k_duration = 300;
const int k_missgap = 15;

const double k_dilation = 2.0;  // exponent at which solution times grow
const double k_flextime = 0.2;  // percentage of time distributed randomly

// helper to generate a random vector that sums to a given amount
vector<int> rvector(int n, int sum, double range = 0.5);

// writes a line for a run in our current judge format
void write(ostream &out, int t, string team, char problem, char verdict)
{
   int h = t / 60, m = t % 60;
   out << setw(2) << setfill('0') << h << ':';
   out << setw(2) << setfill('0') << m << ',';
   out << team << ',' << problem << ',';
   out << "C++," << problem << ".cc;" << verdict << endl;
}

// ---------------------------------------------------------------------------

struct problem
{
    char letter;
    int solved;
    int bads;
    vector<int> attempts;
    
    problem() : letter('X'), solved(-1), bads(0) {}
};

struct pstat
{
    int correct;
    int incorrect;
    int first;
    double mean;
    
    pstat() : correct(0), incorrect(0), first(k_duration), mean(0) {}
    
    void add(const problem &p)
    {
        if (p.solved != -1) {
            mean = (mean * correct + p.solved) / (correct + 1);
            ++correct;
            incorrect += p.bads;
            first = min(first, p.solved);
        }
    }
};
typedef map<char, pstat> cstat;

struct team 
{
    string name;
    string school;
    int score;
    int penalty;
    int mistakes;       // total number of wrong submissions by team
    
    vector<problem> problems;
    
    team(string n = "Team", int s = 0, int p = 0)
        : name(n), score(s), penalty(p), mistakes(0) {}
    
    void write_judgements()
    {
        ostringstream oss;
        oss << name;
        if (!school.empty()) oss << " (" << school << ")";
        string fullname = oss.str();
    
        // go through list of problems
        for (vector<problem>::iterator it = problems.begin(); 
            it != problems.end(); ++it)
        {
            // write wrong answers
            for (vector<int>::iterator jt = it->attempts.begin();
                jt != it->attempts.end(); ++jt)
                write(cout, *jt, fullname, it->letter, 'W');
            // write the accepted run
            if (it->solved != -1)
                write(cout, it->solved, fullname, it->letter, 'A');
        }
    }
    
    // go through the problems array and find mistakes
    void calculate_mistakes()
    {
        mistakes = 0;
        for (vector<problem>::iterator it = problems.begin(); 
            it != problems.end(); ++it)
        {
            mistakes += it->bads;
        }
    }
    
    // guess problem solution times from just a score and penalty
    bool guess_times(double dilation)
    {
        if (score == 0) return true;
    
        calculate_mistakes();
        int p = penalty - mistakes*20;
        
        // distribute the non-random part of the penalty according to curve
        double target = p * (1.0 - k_flextime);
        vector<double> curve(score);
        for (int i = 0; i < score; ++i)
            curve[i] = pow(i+1, dilation);
        while (accumulate(curve.begin(), curve.end(), 0.0) < target)
            for (int i = 0; i < score; ++i) curve[i] *= 1.04;
            
        // convert the curve to integer values
        vector<int> times(score);
        for (int i = 0; i < score; ++i)
            times[i] = curve[i];
            
        // add in the random component
        int difference = p - accumulate(times.begin(), times.end(), 0);
        vector<int> rv = rvector(score, difference);
        for (int i = 0; i < score; ++i)
            times[i] += rv[i];
            
        // resort the times and then put into problems array
        sort(times.begin(), times.end());
        
        // check for validity
        if (times.front() <= 0 || times.back() > k_duration)
            return false;
        
        if (problems.size() != score)
            problems = vector<problem>(score);
        for (int i = 0; i < score; ++i)
            problems[i].solved = times[i];
            
        return true;
    }
    
    // guess where the failed attempts to solve the problems went
    void guess_bads(cstat &stats)
    {
        for (vector<problem>::iterator it = problems.begin(); 
            it != problems.end(); ++it)
        {
            // check first if it's solved and has mistakes
            if (it->solved != -1 && it->bads > 0) {
                int first = stats[it->letter].first;
                int start = it->solved - it->bads * k_missgap;
                if (it->solved != first) start = max(start, first);
                start += -k_missgap/2 + rand()%k_missgap;
                start = max(start, min(k_missgap, first));
                start = min(start, it->solved - 5);
                vector<int> rv = rvector(it->bads, it->solved-start);
                vector<int> mv(it->bads);
                for (int j = 0, t = it->solved; j < it->bads; ++j) {
                    t -= rv[j];
                    mv[j] = t;
                }
                reverse(mv.begin(), mv.end());
                it->attempts = mv;
            }
            // also do mistakes for any unsolved problems
            else if (it->bads > 0) {
                int first = stats[it->letter].first;
                int start = (2*stats[it->letter].mean + k_duration) / 3;
                start += -k_missgap + rand()%(2*k_missgap);
                start = max(start, min(k_missgap, first));
                start = min(start, k_duration - k_missgap);
                int last = max(start + k_missgap/2, k_duration - rand()%(k_missgap/2));
                vector<int> rv = rvector(it->bads, last-start);
                vector<int> mv(it->bads);
                for (int j = 0, t = last; j < it->bads; ++j) {
                    t -= rv[j];
                    mv[j] = t;
                }
                reverse(mv.begin(), mv.end());
                it->attempts = mv;                
            }
        }
    }
};



// ---------------------------------------------------------------------------
// debugging output: prints problems and teams in a readable format

ostream &operator<<(ostream &stream, const problem &p)
{
    stream << '\t' << p.letter << ' ' << p.solved;
    if (!p.attempts.empty()) {
        stream << "\t(" << p.attempts[0];
        for (int i = 1; i < p.attempts.size(); ++i)
            stream << ' ' << p.attempts[i];
        stream << ")";
    }
    stream << endl;
    return stream;
}

ostream &operator<<(ostream &stream, const team &t)
{
    stream << t.name << " (" << t.school << ")" << endl;
    stream << "  " << t.score << " " << t.penalty << endl;
    for (int i = 0; i < t.problems.size(); ++i)
        stream << t.problems[i];
    return stream;
}

// ---------------------------------------------------------------------------

// random number from -1 to 1, with a distribution favouring 0
double xrand()
{
    double x = (rand() % 1000) / 1000.0;
    return (rand() % 2) ? -x*x : x*x;
}

vector<int> rvector(int n, int sum, double range)
{
    if (n == 1) return vector<int>(n, sum);

    double mean = double(sum / n);
    double r = mean * range;
    
    // generate a vector of doubles naively first
    vector<double> naive(n, mean);
    for (int i = 0; i < n; ++i)
        naive[i] += xrand() * r;
        
    // scale back to the right sum and truncate to int
    vector<int> result;
    double s = sum / accumulate(naive.begin(), naive.end(), 0.0);
    for (int i = 0; i < n; ++i)
        result.push_back(naive[i] * s);
        
    // distribute the "change" to make a perfect sum
    int change = sum - accumulate(result.begin(), result.end(), 0);
    for (; change; --change) ++result[rand()%n];
    
    return result;
}

// ---------------------------------------------------------------------------
// routines for reading and parsing a scoreboard to generate a list of teams

string trim(string s)
{
    if (s.empty()) return s;
    string::iterator lt = s.begin();
    string::reverse_iterator rt = s.rbegin();
    while (*lt == ' ') ++lt;
    while (*rt == ' ') ++rt;
    return string(lt, rt.base());
}

vector<team> read_cepc()
{
    vector<team> teams;

    string rank, name, school, members, time, solved;
    while (getline(cin, rank, '\t'))
    {
        getline(cin, name, '\t');
        getline(cin, school, '\t');
        getline(cin, members, '\t');
        getline(cin, time, '\t');
        getline(cin, solved);
               
        team t(trim(name));
        t.school = trim(school);

        istringstream iss(time);
        int h, m, s; char colon;
        iss >> h >> colon >> m >> colon >> s;
        t.penalty = h*60 + m;

        istringstream jss(solved);
        int total = 0; string p;
        while (jss >> p) {
            ++total;
            istringstream kss(p);
            char letter, star; int bads = 0;
            kss >> letter;
            while (kss >> star) ++bads;
            problem prob;
            prob.letter = letter;
            prob.bads = bads;
            t.problems.push_back(prob);
        }
        t.score = total;
        
        teams.push_back(t);
    }
    
    return teams;
}

vector<team> read_ncpc()
{
    vector<team> teams;

    string rank, name, school, country, score, penalty;
    while (getline(cin, rank, '\t'))
    {
        getline(cin, name, '\t');
        getline(cin, school, '\t');
        getline(cin, country, '\t');
        getline(cin, score, '\t');
        getline(cin, penalty, '\t');
               
        team t(trim(name));
        t.school = trim(school);
        t.score = atoi(score.c_str());
        t.penalty = atoi(penalty.c_str());
        
        // read 10 problems, the last of which is terminated by newline
        for (char letter = 'A'; letter <= 'J'; ++letter)
        {
            string p;
            if (letter == 'J')  getline(cin, p);
            else                getline(cin, p, '\t');
            
            istringstream jss(p);
            int attempts, solved;
            if (jss >> attempts) {
                problem prob;
                prob.letter = letter;
                if (jss >> solved) {
                    prob.solved = solved;
                    prob.bads = attempts-1;
                }
                else prob.bads = attempts;
                t.problems.push_back(prob);
            }
        }
        
        teams.push_back(t);
    }
    
    return teams;
}

vector<team> read_asia()
{
    vector<team> teams;

    string rank, urank, name, school, score, penalty;
    while (getline(cin, rank, '\t'))
    {
        getline(cin, urank, '\t');
        getline(cin, name, '\t');
        getline(cin, school, '\t');
        getline(cin, score, '\t');
        getline(cin, penalty);
               
        team t(trim(name));
        t.school = trim(school);
        t.score = atoi(score.c_str());
        t.penalty = atoi(penalty.c_str());
        
        for (int i = 0; i < t.score; ++i) {
        	problem prob;
        	prob.letter = 'K' + i;
        	t.problems.push_back(prob);
        }
        
        teams.push_back(t);
    }
    
    return teams;
}

vector<team> read_pc2(int n = 8)
{
    vector<team> teams;

    string rank, name, school, score, penalty, junk;
    while (getline(cin, rank, '\t'))
    {
        getline(cin, name, '(');
        getline(cin, school, ')');
        getline(cin, junk, '\t');
        getline(cin, score, '\t');
        getline(cin, penalty, '\t');
               
        team t(trim(name));
        t.school = trim(school);
        t.score = atoi(score.c_str());
        t.penalty = atoi(penalty.c_str());
        
        for (int i = 0; i < n; ++i) {
            string p;
            if (i == n-1)   getline(cin, p);
            else            getline(cin, p, '\t');
            
            istringstream jss(p);
            int attempts, solved;
            char slash;
            if (jss >> attempts >> slash) {
                if (attempts == 0) continue;
                problem prob;
                prob.letter = 'A' + i;
                if (jss >> solved) {
                    prob.solved = solved;
                    prob.bads = attempts-1;
                }
                else prob.bads = attempts;
                t.problems.push_back(prob);
            }
        }
        
        teams.push_back(t);
    }
    
    return teams;
}

// ---------------------------------------------------------------------------

cstat compute_stats(vector<team> &teams)
{
    cstat result;
    for (vector<team>::iterator it = teams.begin(); it != teams.end(); ++it)
    {
        for (vector<problem>::iterator jt = it->problems.begin();
            jt != it->problems.end(); ++jt)
        {
            result[jt->letter].add(*jt);
        }
    }
    return result;
}

int main()
{
    // seed random number generator
    srand(time(0));

    // read teams from scoreboard
    vector<team> teams = read_pc2(8);
/*
    // process the teams
    for (vector<team>::iterator it = teams.begin(); it != teams.end(); ++it)
    {
        // this scoreboard doesn't have times for each problem, so guess them
        double dilation = k_dilation + 0.5;
        while (!it->guess_times(dilation)) dilation -= 0.2;        
    }
*/
    cstat stats = compute_stats(teams);

    // do bad runs after computing stats
    for (vector<team>::iterator it = teams.begin(); it != teams.end(); ++it)
    {
        it->guess_bads(stats);
        it->write_judgements();
    }

    return 0;
}

// ---------------------------------------------------------------------------
