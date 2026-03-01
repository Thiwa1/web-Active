<?php

require_once __DIR__ . '/../classes/AiRecruiter.php';

class AiRecruiterTest {
    private $recruiter;
    private $passed = 0;
    private $failed = 0;

    public function __construct() {
        $this->recruiter = new AiRecruiter();
    }

    public function run() {
        echo "Running AiRecruiter Tests...\n\n";

        $this->testHappyPath();
        $this->testNoKeywords();
        $this->testNoMatch();
        $this->testPartialMatch();
        $this->testExperienceScoringExact();
        $this->testExperienceScoringLess();
        $this->testExperienceScoringMore();
        $this->testExperienceScoringNoExplicitRequirement();
        $this->testExperienceScoringNoExplicitRequirementLowExp();
        $this->testCaseInsensitivityAndHtmlTags();
        $this->testPartialWordMatch();

        $this->testEstimateSalaryDefault();
        $this->testEstimateSalaryCategoryMatch();
        $this->testEstimateSalaryExperienceMultiplier();
        $this->testEstimateSalaryEducationBonus();
        $this->testEstimateSalarySeniorityBonus();
        $this->testEstimateSalaryCombined();
        $this->testEstimateSalaryRounding();

        echo "\nTest Summary:\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        if ($this->failed > 0) {
            echo "\nFAILURE: Some tests failed.\n";
            exit(1);
        } else {
            echo "\nSUCCESS: All tests passed.\n";
            exit(0);
        }
    }

    private function assertEqual($expected, $actual, $testName) {
        if ($expected === $actual) {
            echo "✅ PASS: $testName\n";
            $this->passed++;
        } else {
            echo "❌ FAIL: $testName\n";
            echo "   Expected: " . print_r($expected, true) . "\n";
            echo "   Actual:   " . print_r($actual, true) . "\n";
            $this->failed++;
        }
    }

    private function testHappyPath() {
        $jobDescription = "We are looking for a PHP developer with MySQL and JavaScript experience.";
        $cvText = "I am a PHP developer. I have worked with MySQL and JavaScript extensively.";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 3, 2);

        // Keywords expected to be extracted from job description (ignoring stop words):
        // "php", "developer", "mysql", "javascript", "experience" (wait, "experience" is a stop word!)
        // Let's check the stop words in AiRecruiter: 'the', 'and', 'is', 'in', 'at', 'of', 'for', 'with', 'a', 'an', 'to', 'we', 'are', 'you', 'will', 'be', 'or', 'as', 'on', 'our', 'your', 'this', 'that', 'from', 'by', 'have', 'has', 'can', 'should', 'work', 'job', 'team', 'role', 'looking', 'skills', 'experience', 'knowledge', 'ability', 'must', 'required'
        // So keywords: "php", "developer", "mysql", "javascript". (4 keywords)
        // Match in CV: "php", "developer", "mysql", "javascript". (4 matches)
        // matchRatio = 4 / 4 = 1. keywordScore = min(100, 1 * 2 * 100) = 100.
        // experienceScore: 3 >= 2 -> 100.
        // finalScore = 100 * 0.7 + 100 * 0.3 = 100.
        // recommendation: Excellent Match

        $this->assertEqual(100, (int)$result['score'], "Happy Path - Score");
        $this->assertEqual("Excellent Match", $result['recommendation'], "Happy Path - Recommendation");
    }

    private function testNoKeywords() {
        $jobDescription = "To be or as an in of at by"; // All stop words or < 3 chars
        $cvText = "Some text";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 5, 5);

        $this->assertEqual(0, (int)$result['score'], "No Keywords - Score");
        $this->assertEqual("Job description too short", $result['recommendation'], "No Keywords - Recommendation");
    }

    private function testNoMatch() {
        $jobDescription = "PHP developer MySQL";
        $cvText = "Python engineer PostgreSQL";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 2);

        // Keywords: php, developer, mysql
        // Matches: 0 -> keywordScore: 0
        // Exp score: 2 >= 2 -> 100
        // finalScore = 0 * 0.7 + 100 * 0.3 = 30
        $this->assertEqual(30, (int)$result['score'], "No Match - Score");
        $this->assertEqual("Low Match", $result['recommendation'], "No Match - Recommendation");
    }

    private function testPartialMatch() {
        $jobDescription = "PHP MySQL JavaScript HTML CSS";
        $cvText = "PHP and MySQL expert";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 4);

        // Keywords: php, mysql, javascript, html, css (5)
        // Matches: php, mysql (2)
        // matchRatio = 2/5 = 0.4
        // keywordScore = min(100, 0.4 * 2 * 100) = 80
        // Exp score: 2 / 4 * 100 = 50
        // finalScore = 80 * 0.7 + 50 * 0.3 = 56 + 15 = 71
        $this->assertEqual(71, (int)$result['score'], "Partial Match - Score");
        $this->assertEqual("Good Match", $result['recommendation'], "Partial Match - Recommendation");
    }

    private function testExperienceScoringExact() {
        $jobDescription = "PHP developer";
        $cvText = "PHP developer";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 5, 5);

        // Keywords: php, developer (2)
        // Matches: 2 -> keywordScore = 100
        // Exp score: 5 >= 5 -> 100
        // finalScore = 100
        $this->assertEqual(100, (int)$result['score'], "Experience Scoring Exact - Score");
    }

    private function testExperienceScoringLess() {
        $jobDescription = "PHP developer";
        $cvText = "PHP developer";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 3, 5);

        // keywordScore = 100
        // Exp score: 3 / 5 * 100 = 60
        // finalScore = 100 * 0.7 + 60 * 0.3 = 70 + 18 = 88
        $this->assertEqual(88, (int)$result['score'], "Experience Scoring Less - Score");
    }

    private function testExperienceScoringMore() {
        $jobDescription = "PHP developer";
        $cvText = "PHP developer";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 10, 2);

        // keywordScore = 100
        // Exp score: 10 >= 2 -> 100
        // finalScore = 100
        $this->assertEqual(100, (int)$result['score'], "Experience Scoring More - Score");
    }

    private function testExperienceScoringNoExplicitRequirement() {
        $jobDescription = "PHP developer";
        $cvText = "PHP developer";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 0);

        // keywordScore = 100
        // Exp score: Req = 0, candidate = 2 >= 1 -> 100
        // finalScore = 100
        $this->assertEqual(100, (int)$result['score'], "Experience No Explicit Req (High Exp) - Score");
    }

    private function testExperienceScoringNoExplicitRequirementLowExp() {
        $jobDescription = "PHP developer";
        $cvText = "PHP developer";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 0.5, 0);

        // keywordScore = 100
        // Exp score: Req = 0, candidate = 0.5 < 1 -> 50
        // finalScore = 100 * 0.7 + 50 * 0.3 = 70 + 15 = 85
        $this->assertEqual(85, (int)$result['score'], "Experience No Explicit Req (Low Exp) - Score");
    }

    private function testCaseInsensitivityAndHtmlTags() {
        $jobDescription = "<p>Looking for a <b>PHP</b> Developer</p>";
        $cvText = "<h1>php DEVELOPER</h1>";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 2);

        // Keywords: php, developer
        // Matches: php, developer
        // finalScore = 100
        $this->assertEqual(100, (int)$result['score'], "Case Insensitivity & HTML Tags - Score");
    }

    private function testPartialWordMatch() {
        $jobDescription = "cat dog";
        $cvText = "category dogmatic";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 2);

        // Keywords: cat, dog
        // Matches in CV: none (because of \b matching)
        // keywordScore = 0
        // Exp score: 100
        // finalScore = 0 * 0.7 + 100 * 0.3 = 30
        $this->assertEqual(30, (int)$result['score'], "Partial Word Match (Negative) - Score");
    }

    private function testEstimateSalaryDefault() {
        $result = $this->recruiter->estimateSalary("Just some text", 0, "Unknown");
        $this->assertEqual(40000.0, (float)$result, "Estimate Salary - Default");
    }

    private function testEstimateSalaryCategoryMatch() {
        $result = $this->recruiter->estimateSalary("Just some text", 0, "Software");
        $this->assertEqual(75000.0, (float)$result, "Estimate Salary - Category Match");
    }

    private function testEstimateSalaryExperienceMultiplier() {
        // 2.5 years: mult = 1 + 2.5 * 0.20 = 1.5 -> 40000 * 1.5 = 60000
        $result1 = $this->recruiter->estimateSalary("text", 2.5, "Unknown");
        $this->assertEqual(60000.0, (float)$result1, "Estimate Salary - Exp Mult <= 5");

        // 6 years: mult = 1 + (5 * 0.20) + (1 * 0.10) = 2.1 -> 40000 * 2.1 = 84000
        // 84000 / 5000 = 16.8 -> round is 17 -> 17 * 5000 = 85000
        $result2 = $this->recruiter->estimateSalary("text", 6, "Unknown");
        $this->assertEqual(85000.0, (float)$result2, "Estimate Salary - Exp Mult > 5");
    }

    private function testEstimateSalaryEducationBonus() {
        // PhD (+0.5): 1.5 -> 60000
        $result1 = $this->recruiter->estimateSalary("I have a PhD in computer science", 0, "Unknown");
        $this->assertEqual(60000.0, (float)$result1, "Estimate Salary - PhD Bonus");

        // Master (+0.3): 1.3 -> 40000 * 1.3 = 52000 -> 52000 / 5000 = 10.4 -> 10 * 5000 = 50000
        $result2 = $this->recruiter->estimateSalary("I have a Master degree", 0, "Unknown");
        $this->assertEqual(50000.0, (float)$result2, "Estimate Salary - Master Bonus");

        // Degree (+0.15): 1.15 -> 40000 * 1.15 = 46000 -> 46000 / 5000 = 9.2 -> 9 * 5000 = 45000
        $result3 = $this->recruiter->estimateSalary("I have a BSc in IT", 0, "Unknown");
        $this->assertEqual(45000.0, (float)$result3, "Estimate Salary - Degree Bonus");
    }

    private function testEstimateSalarySeniorityBonus() {
        // Senior (+0.2): 1.2 -> 40000 * 1.2 = 48000 -> 48000 / 5000 = 9.6 -> 10 * 5000 = 50000
        $result = $this->recruiter->estimateSalary("Senior developer", 0, "Unknown");
        $this->assertEqual(50000.0, (float)$result, "Estimate Salary - Seniority Bonus");
    }

    private function testEstimateSalaryCombined() {
        // Category Software (75000)
        // Experience 10 years (1 + 1 + 0.5 = 2.5)
        // Education PhD (+0.5)
        // Seniority Lead (+0.2)
        // totalMultiplier = 2.5 + 0.5 + 0.2 = 3.2
        // Base 75000. 75000 * 3.2 = 240000
        $result = $this->recruiter->estimateSalary("PhD lead developer", 10, "Software");
        $this->assertEqual(240000.0, (float)$result, "Estimate Salary - Combined");
    }

    private function testEstimateSalaryRounding() {
        // Test a specific value that requires rounding
        // base 40000, 1 year exp (1.2 multiplier) = 48000 -> 50000
        $result1 = $this->recruiter->estimateSalary("Just a standard text", 1, "Unknown");
        $this->assertEqual(50000.0, (float)$result1, "Estimate Salary - Rounding Up");

        // base 40000, 0 exp, degree (1.15 multiplier) = 46000 -> 45000
        $result2 = $this->recruiter->estimateSalary("BSc degree", 0, "Unknown");
        $this->assertEqual(45000.0, (float)$result2, "Estimate Salary - Rounding Down");
    }
}

$test = new AiRecruiterTest();
$test->run();
