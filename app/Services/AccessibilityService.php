<?php

namespace App\Services;

class AccessibilityService
{
    /**
     * Service Constructor.
     */
    public function __construct()
    {
        //
    }

    /**
     * Analyze accessibility issues in the provided HTML content.
     *
     * @param string $htmlContent
     * @return array
     */
    public function analyzeAccessibility(string $htmlContent): array
    {
        // Initialize an empty array to store issues dynamically
        $issues = [];

        // Assume full compliance initially
        $compliance_score = 100;

        // Split the HTML content by lines
        $lines = explode("\n", $htmlContent);

        // 1. Check for missing alt attributes in images
        preg_match_all('/<img[^>]*>/i', $htmlContent, $images);
        foreach ($images[0] as $img) {
            $lineNumber = $this->getLineNumber($lines, $img);
            if (strpos($img, 'alt="') === false && strpos($img, 'alt=""') === false) {
                $this->addIssue($issues, 'Missing alt attribute for image.', 'missing_alt', $lineNumber, $img);
                $compliance_score -= 5;
            }
        }

        // 2. Check for skipped heading levels (e.g., <h1> followed by <h3>)
        preg_match_all('/<h(\d)>.*?<\/h\1>/i', $htmlContent, $headings);
        for ($i = 1; $i < count($headings[1]); $i++) {
            if (intval($headings[1][$i]) > intval($headings[1][$i - 1]) + 1) {
                $lineNumber = $this->getLineNumber($lines, $headings[0][$i]);
                $this->addIssue($issues, 'Skipped heading levels.', 'skipped_headings', $lineNumber, $headings[0][$i]);
                $compliance_score -= 10;
            }
        }

        // 3. Check for color contrast between text and background
        preg_match_all('/color: *#[0-9a-fA-F]{6}/i', $htmlContent, $colors);
        foreach ($colors[0] as $color) {
            $lineNumber = $this->getLineNumber($lines, $color);
            if ($this->isLowContrastColor($color)) {
                $this->addIssue($issues, 'Low color contrast.', 'low_color_contrast', $lineNumber, $color);
                $compliance_score -= 5;
            }
        }

        // 4. Ensure interactive elements are accessible via keyboard
        preg_match_all('/<a[^>]*href="[^"]*"[^>]*>|<button[^>]*>.*?<\/button>/i', $htmlContent, $interactiveElements);
        foreach ($interactiveElements[0] as $element) {
            $lineNumber = $this->getLineNumber($lines, $element);
            if (strpos($element, 'tabindex="') === false) {
                $this->addIssue($issues, 'Missing tabindex for interactive elements.', 'missing_tabindex', $lineNumber, $element);
                $compliance_score -= 5;
            }
        }

        // 5. Ensure form fields have associated labels
        preg_match_all('/<input[^>]*>/i', $htmlContent, $formInputs);
        foreach ($formInputs[0] as $input) {
            $lineNumber = $this->getLineNumber($lines, $input);
            if (strpos($input, 'aria-labelledby') === false && strpos($input, 'id="') === false) {
                $this->addIssue($issues, 'Form field missing label.', 'missing_labels', $lineNumber, $input);
                $compliance_score -= 5;
            }
        }

        // 6. Ensure there are skip navigation links
        if (strpos($htmlContent, '<a href="#maincontent" class="skip-link">Skip to Content</a>') === false) {
            $this->addIssue($issues, 'Missing skip navigation link.', 'missing_skip_link', 1, '<a href="#maincontent" class="skip-link">Skip to Content</a>');
            $compliance_score -= 5;
        }

        // 7. Ensure that text can be resized up to 200% without breaking the layout
        preg_match_all('/font-size:\s*(\d+)px/i', $htmlContent, $fontSizes);
        foreach ($fontSizes[1] as $fontSize) {
            $lineNumber = $this->getLineNumber($lines, "font-size: $fontSize");
            if (intval($fontSize) < 16) {
                $this->addIssue($issues, 'Font size too small.', 'font_size_too_small', $lineNumber, "<p style='font-size: {$fontSize}px;'>Small text</p>");
                $compliance_score -= 5;
            }
        }

        // 8. Ensure that all links are identifiable by their text or aria-label.
        preg_match_all('/<a[^>]*>/i', $htmlContent, $links);
        foreach ($links[0] as $link) {
            $lineNumber = $this->getLineNumber($lines, $link);
            if (strpos($link, 'href=') === false) {
                $this->addIssue($issues, 'Broken link or missing href attribute.', 'broken_links', $lineNumber, $link);
                $compliance_score -= 5;
            }
        }

        // 9. Ensure that form elements have <label> with matching 'for' attributes
        preg_match_all('/<input[^>]+id="([^"]+)"[^>]*>/i', $htmlContent, $inputs);
        foreach ($inputs[1] as $inputId) {
            $lineNumber = $this->getLineNumber($lines, '<input id="' . $inputId . '"');
            if (strpos($htmlContent, '<label for="' . $inputId . '">') === false) {
                $this->addIssue($issues, 'Missing label for input element.', 'missing_input_labels', $lineNumber, "<input type='text' id='$inputId' />");
                $compliance_score -= 10;
            }
        }

        return [
            'compliance_score' => $compliance_score,
            'issues' => $issues
        ];
    }

    /**
     * Helper function to add an issue dynamically to the correct group.
     *
     * @param array $issues
     * @param string $issue
     * @param string $category
     * @param int $line
     * @param string $htmlSnippet
     * @return void
     */
    private function addIssue(array &$issues, string $issue, string $category, int $line, $htmlSnippet)
    {
        // If the category doesn't exist, initialize it as an empty array
        if (!isset($issues[$category])) {
            $issues[$category] = [
                'issue' => $issue,
                'line' => $line,
                'details' => []
            ];
        }

        // Add the new issue to the category's details
        $issues[$category]['details'][] = [
            'suggested_fix' => $this->getSuggestedFix($category),
            'faulted_html' => $htmlSnippet,
            'sample_html' => $this->getSampleHTML($category)
        ];

        // This ensures categories with no issues are excluded from the final list
        if (empty($issues[$category]['details'])) {
            unset($issues[$category]);
        }
    }

    /**
     * Helper function to get the line number of a match.
     *
     * @param array $lines
     * @param string $match
     * @return int
     */
    private function getLineNumber(array $lines, string $match): int
    {
        foreach ($lines as $lineNumber => $line) {
            if (strpos($line, $match) !== false) {
                // Line number is 1-based
                return $lineNumber + 1;
            }
        }

        // If no match is found
        return 0;
    }

    /**
     * Helper function to get a sample HTML structure for each issue.
     *
     * @param string $category
     * @return string
     */
    private function getSampleHTML(string $category): string
    {
        return match ($category) {
            'missing_alt' => '<img src="image.jpg" alt="Description of image" />',
            'skipped_headings' => '<h1>Main Heading</h1><h2>Sub Heading</h2>',
            'low_color_contrast' => '<p style="color: #f0f0f0; background-color: #ffffff;">Low contrast text</p>',
            'missing_tabindex' => '<button>Click Me</button>',
            'missing_labels' => '<input type="text" id="name" /><label for="name">Name</label>',
            'missing_skip_link' => '<a href="#maincontent" class="skip-link">Skip to Content</a>',
            'font_size_too_small' => '<p style="font-size: 12px;">Small text</p>',
            'broken_links' => '<a href="#">Broken Link</a>',
            'missing_input_labels' => '<input type="text" id="email" /><label for="email">Email</label>',
            default => '<!-- No sample available -->',
        };
    }

    /**
     * Helper function to get a suggested fix based on the category.
     *
     * @param string $category
     * @return string
     */
    private function getSuggestedFix(string $category): string
    {
        return match ($category) {
            'missing_alt' => 'Add an alt attribute to the image.',
            'skipped_headings' => 'Ensure headings follow a logical order (e.g., <h1>, <h2>, <h3>).',
            'low_color_contrast' => 'Ensure sufficient contrast between text and background colors.',
            'missing_tabindex' => 'Ensure all interactive elements are accessible using keyboard navigation.',
            'missing_labels' => 'Ensure all form fields have associated labels using the <label> tag or aria-labelledby attribute.',
            'missing_skip_link' => 'Add a "Skip to Content" link at the top of the page for easier navigation.',
            'font_size_too_small' => 'Ensure text size is at least 16px or resizable.',
            'broken_links' => 'Ensure all links have a valid href attribute.',
            'missing_input_labels' => 'Ensure all input elements have a corresponding label with a matching "for" attribute.',
            default => 'No suggested fix available.',
        };
    }

    /**
     * Check if the color is of low contrast.
     *
     * @param string $color
     * @return bool
     */
    private function isLowContrastColor(string $color): bool
    {
        // Check if the color is too light or too dark for accessibility
        $rgb = sscanf($color, "#%02x%02x%02x");
        $brightness = (0.2126 * $rgb[0]) + (0.7152 * $rgb[1]) + (0.0722 * $rgb[2]);
        return $brightness > 200;
    }
}
