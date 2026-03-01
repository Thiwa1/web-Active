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
        $this->testRecommendationPotentialMatch();
        $this->testRecommendationBoundaries();
        $this->testEmptyCV();
        $this->testEmptyJobDescription();

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

    private function testRecommendationPotentialMatch() {
        $jobDescription = "PHP MySQL JavaScript HTML CSS";
        $cvText = "PHP";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 4);

        // Keywords: php, mysql, javascript, html, css (5)
        // Matches: php (1)
        // matchRatio = 1/5 = 0.2
        // keywordScore = min(100, 0.2 * 2 * 100) = 40
        // Exp score: 2 / 4 * 100 = 50
        // finalScore = 40 * 0.7 + 50 * 0.3 = 28 + 15 = 43
        $this->assertEqual(43, (int)$result['score'], "Recommendation Potential Match - Score");
        $this->assertEqual("Potential Match", $result['recommendation'], "Recommendation Potential Match - Recommendation");
    }

    private function testRecommendationBoundaries() {
        // We want to test exact boundary scores: 80, 60, 40 if possible
        // To get exactly 80:
        // keywordScore = 100
        // experienceScore: finalScore = 100 * 0.7 + X * 0.3 = 80 => 70 + 0.3X = 80 => 0.3X = 10 => X = 33.33
        // Instead of exact math, let's just observe the boundary behavior using manual scores if possible, but scoreCV computes it.
        // Let's craft specific values.

        // Target final score = 80
        // kw=100 (70), exp=33.33 (10) -> score 80
        // Let's get exp score = 33.33 => candidate=1, required=3 -> exp=33.33
        // finalScore = 70 + 10 = 80
        $jobDescription80 = "PHP developer"; // 2 keywords: php, developer
        $cvText80 = "PHP developer"; // keyword=100
        $result80 = $this->recruiter->scoreCV($cvText80, $jobDescription80, 1, 3);
        $this->assertEqual(80, (int)$result80['score'], "Recommendation Boundary 80 - Score");
        $this->assertEqual("Good Match", $result80['recommendation'], "Recommendation Boundary 80 - Recommendation (<= 80 is Good)");

        // Target final score = 60
        // kw=0 (0), exp=200? No, exp max is 100.
        // Let's try kw=60 (42), exp=60 (18) -> 60.
        // To get kw=60: ratio = 0.3 => 3 matches out of 10 keywords.
        // Let's do kw=0, exp=200 -> wait, if exp=200, score is 0.3*200 = 60, but is exp capped?
        // "experienceScore = ($candidateExperienceYears / $requiredExperienceYears) * 100"
        // Wait, if candidate > required, it sets to 100. So exp max is 100.
        // Let's try kw=50 (35), exp=83.33 (25) -> 60.
        // Let's try kw=71.4 (50), exp=33.3 (10) -> 60.
        // Actually, we can just look at score 60:
        // 5 keywords, 1 match => ratio 0.2 => kw = 40. kwScore = 40. 40 * 0.7 = 28.
        // We need 32 from exp => exp * 0.3 = 32 => exp = 106.6 (capped at 100).
        // Let's try 5 keywords, 2 matches => ratio 0.4 => kw = 80. 80 * 0.7 = 56.
        // We need 4 from exp => exp * 0.3 = 4 => exp = 13.33 => candidate=2, required=15.
        // finalScore = 56 + 4 = 60.
        $jobDescription60 = "PHP MySQL JavaScript HTML CSS";
        $cvText60 = "PHP MySQL"; // kw = 80
        $result60 = $this->recruiter->scoreCV($cvText60, $jobDescription60, 2, 15); // exp = 13.33
        $this->assertEqual(60, (int)$result60['score'], "Recommendation Boundary 60 - Score");
        $this->assertEqual("Potential Match", $result60['recommendation'], "Recommendation Boundary 60 - Recommendation (<= 60 is Potential)");

        // Target final score = 40
        // 5 keywords, 1 match => kw = 40. 40 * 0.7 = 28.
        // We need 12 from exp => exp * 0.3 = 12 => exp = 40 => candidate=4, required=10.
        // finalScore = 28 + 12 = 40.
        $jobDescription40 = "PHP MySQL JavaScript HTML CSS";
        $cvText40 = "PHP"; // kw = 40
        $result40 = $this->recruiter->scoreCV($cvText40, $jobDescription40, 4, 10); // exp = 40
        $this->assertEqual(40, (int)$result40['score'], "Recommendation Boundary 40 - Score");
        $this->assertEqual("Low Match", $result40['recommendation'], "Recommendation Boundary 40 - Recommendation (<= 40 is Low)");
    }

    private function testEmptyCV() {
        $jobDescription = "PHP developer MySQL";
        $cvText = "";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 2);

        // Keywords: php, developer, mysql
        // Matches: 0 -> keywordScore: 0
        // Exp score: 2 >= 2 -> 100
        // finalScore = 0 * 0.7 + 100 * 0.3 = 30
        $this->assertEqual(30, (int)$result['score'], "Empty CV - Score");
        $this->assertEqual("Low Match", $result['recommendation'], "Empty CV - Recommendation");
    }

    private function testEmptyJobDescription() {
        $jobDescription = "";
        $cvText = "PHP developer MySQL";
        $result = $this->recruiter->scoreCV($cvText, $jobDescription, 2, 2);

        $this->assertEqual(0, (int)$result['score'], "Empty Job Description - Score");
        $this->assertEqual("Job description too short", $result['recommendation'], "Empty Job Description - Recommendation");
    }
}

$test = new AiRecruiterTest();
$test->run();
