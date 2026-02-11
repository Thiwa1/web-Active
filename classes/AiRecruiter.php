<?php

class AiRecruiter {

    /**
     * Scores a candidate based on the match between their profile/CV and the job description.
     */
    public function scoreCV($cvText, $jobDescription, $candidateExperienceYears = 0, $requiredExperienceYears = 0) {
        $cvText = strtolower(strip_tags($cvText));
        $jobDescription = strtolower(strip_tags($jobDescription));

        // 1. Extract Keywords from Job Description
        $keywords = $this->extractKeywords($jobDescription);

        if (empty($keywords)) {
            return ['score' => 0, 'matches' => [], 'recommendation' => 'Job description too short'];
        }

        // 2. Calculate Keyword Matches
        $matches = [];
        $matchCount = 0;
        foreach ($keywords as $word) {
            // Check for whole word match to avoid partials (e.g. "cat" in "category")
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $cvText)) {
                $matches[] = $word;
                $matchCount++;
            }
        }

        // Base score from keyword matching (weighted 70%)
        // If they match 50% of keywords, that's a good score. Curve it.
        $matchRatio = $matchCount / count($keywords);
        $keywordScore = min(100, ($matchRatio * 2) * 100);

        // 3. Experience Score (weighted 30%)
        $experienceScore = 0;
        if ($requiredExperienceYears > 0) {
            if ($candidateExperienceYears >= $requiredExperienceYears) {
                $experienceScore = 100;
            } else {
                $experienceScore = ($candidateExperienceYears / $requiredExperienceYears) * 100;
            }
        } else {
            // If no explicit requirement, assume 1 year is good enough for entry level
            $experienceScore = ($candidateExperienceYears >= 1) ? 100 : 50;
        }

        // Final Weighted Score
        $finalScore = ($keywordScore * 0.7) + ($experienceScore * 0.3);
        $finalScore = round(min(100, $finalScore));

        // Recommendation
        $recommendation = "Low Match";
        if ($finalScore > 80) $recommendation = "Excellent Match";
        elseif ($finalScore > 60) $recommendation = "Good Match";
        elseif ($finalScore > 40) $recommendation = "Potential Match";

        return [
            'score' => $finalScore,
            'matches' => array_slice($matches, 0, 5), // Top 5
            'recommendation' => $recommendation
        ];
    }

    private function extractKeywords($text) {
        // Stop words list
        $stopWords = [
            'the', 'and', 'is', 'in', 'at', 'of', 'for', 'with', 'a', 'an', 'to', 'we', 'are', 'you', 'will', 'be', 'or', 'as', 'on', 'our', 'your', 'this', 'that', 'from', 'by', 'have', 'has', 'can', 'should', 'work', 'job', 'team', 'role', 'looking', 'skills', 'experience', 'knowledge', 'ability', 'must', 'required'
        ];

        // Remove special chars and split
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $words = explode(' ', $text);

        $keywords = [];
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stopWords) && !is_numeric($word)) {
                $keywords[] = $word;
            }
        }

        return array_unique($keywords);
    }

    /**
     * Estimates salary based on Category, Experience and Skills
     */
    public function estimateSalary($cvText, $experienceYears, $jobCategory = '') {
        $cvTextLower = strtolower(strip_tags($cvText));

        // Base Salary Map (Monthly LKR) - Very rough estimates
        $categoryBase = [
            'IT' => 60000,
            'Software' => 75000,
            'Management' => 80000,
            'Marketing' => 45000,
            'Accounting' => 50000,
            'Engineering' => 65000,
            'Sales' => 35000,
            'Clerical' => 30000,
            'Driver' => 40000,
            'Teacher' => 35000
        ];

        // Find best match for category base
        $baseSalary = 40000; // Default
        foreach ($categoryBase as $key => $val) {
            if (stripos($jobCategory, $key) !== false) {
                $baseSalary = $val;
                break;
            }
        }

        // Experience Multiplier (Approx 20% per year for first 5 years, then 10%)
        $expMult = 1;
        if ($experienceYears <= 5) {
            $expMult += ($experienceYears * 0.20);
        } else {
            $expMult += (5 * 0.20) + (($experienceYears - 5) * 0.10);
        }

        // Education/Skill Bonuses (Flat additions to multiplier)
        $bonus = 0;
        if (strpos($cvTextLower, 'phd') !== false) $bonus += 0.5;
        elseif (strpos($cvTextLower, 'master') !== false || strpos($cvTextLower, 'mba') !== false) $bonus += 0.3;
        elseif (strpos($cvTextLower, 'degree') !== false || strpos($cvTextLower, 'bsc') !== false || strpos($cvTextLower, 'bachelor') !== false) $bonus += 0.15;

        // Seniority
        if (strpos($cvTextLower, 'senior') !== false || strpos($cvTextLower, 'lead') !== false || strpos($cvTextLower, 'manager') !== false) {
            $bonus += 0.2;
        }

        $totalMultiplier = $expMult + $bonus;
        $estimatedSalary = $baseSalary * $totalMultiplier;

        // Round to nearest 5000
        return round($estimatedSalary / 5000) * 5000;
    }
}
?>