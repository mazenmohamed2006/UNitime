#include <iostream>
#include <string>
#include <vector>
#include <algorithm>

// ========== TASK DATA STRUCTURES ========== //
// TODO: Define Task structure with id, title, description, progress, due_date, priority
// TODO: Create TaskAnalyzer class

// ========== TASK ANALYZER CLASS ========== //
class TaskAnalyzer {
private:
    // TODO: Declare private member variables
    std::vector<Task> tasks;
    
public:
    // TODO: Implement constructor
    TaskAnalyzer();
    
    // TODO: Implement loadTasks method to load task data
    void loadTasks(const std::vector<Task>& taskList);
    
    // TODO: Implement analyzeWorkload method to calculate workload score
    int analyzeWorkload();
    
    // TODO: Implement getOptimizedSchedule method for task prioritization
    std::vector<Task> getOptimizedSchedule();
    
    // TODO: Implement getTimeSuggestions method for time allocation
    std::vector<std::pair<int, int>> getTimeSuggestions();
    
private:
    // TODO: Implement helper methods for date calculations and scoring
    int calculateDaysUntil(const std::string& due_date);
    int calculateTaskScore(const Task& task);
};

// ========== MAIN PROGRAM ========== //
int main() {
    // TODO: Create sample tasks for testing
    // TODO: Initialize TaskAnalyzer
    // TODO: Load tasks into analyzer
    // TODO: Call analysis methods
    // TODO: Output results to console
    
    return 0;
}