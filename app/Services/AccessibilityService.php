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
        $complianceScore = 100;

        // Split the HTML content by lines
        $lines = explode("\n", $htmlContent);

        // Call each check method dynamically and aggregate the results
        foreach ($this->testMethods() as $method) {
            $complianceScore -= $this->$method($htmlContent, $issues, $lines);
        }

        return [
            'compliance_score' => $complianceScore,
            'issues' => $issues
        ];
    }

    /**
     * Check for missing alt attribute in images
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkMissingAltAttribute(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/<img[^>]*>/i', $htmlContent, $images);
        $scoreDeducted = 0;

        foreach ($images[0] as $img) {
            $lineNumber = $this->getLineNumber($lines, $img);
            if (strpos($img, 'alt="') === false && strpos($img, 'alt=""') === false) {
                $this->addIssue($issues, 'Missing alt attribute for image', 'missing_alt', $lineNumber, $img);
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for skipped heading levels (e.g., <h1> followed by <h3>)
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkSkippedHeadings(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/<h(\d)>.*?<\/h\1>/i', $htmlContent, $headings);
        $scoreDeducted = 0;

        for ($i = 1; $i < count($headings[1]); $i++) {
            if (intval($headings[1][$i]) > intval($headings[1][$i - 1]) + 1) {
                $lineNumber = $this->getLineNumber($lines, $headings[0][$i]);
                $this->addIssue($issues, 'Skipped heading levels', 'skipped_headings', $lineNumber, $headings[0][$i]);
                $scoreDeducted += 10;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for low color contrast
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkLowColorContrast(string $htmlContent, array &$issues, array $lines): int
    {
        // Regex to find color and background-color in the content, matching both hex and rgb/rgba
        preg_match_all('/(?:color|background-color):\s*(#[a-fA-F0-9]{6}|rgb\([^\)]+\)|rgba\([^\)]+\))/i', $htmlContent, $matches);

        $scoreDeducted = 0;

        // Loop through all colors found and compare them
        for ($i = 0; $i < count($matches[1]); $i++) {
            // We get both the color and background-color (pairwise comparison)
            $textColor = $matches[1][$i];
            $bgColor = $matches[1][($i + 1) % count($matches[1])]; // Cycle through next one as background color

            // Convert colors to RGB format
            $rgbTextColor = $this->hexToRgb($textColor);
            $rgbBgColor = $this->hexToRgb($bgColor);

            // If they are not in hex format, try to convert rgb/rgba to hex
            if (!$rgbTextColor) {
                $rgbTextColor = $this->rgbToRgb($textColor);
            }
            if (!$rgbBgColor) {
                $rgbBgColor = $this->rgbToRgb($bgColor);
            }

            // Check if contrast ratio is too low
            if ($this->isLowContrast($rgbTextColor, $rgbBgColor)) {
                // Add issue and deduct score
                $lineNumber = $this->getLineNumber($lines, $textColor);
                $this->addIssue($issues, 'Low color contrast', 'low_color_contrast', $lineNumber, "Color: $textColor, Background: $bgColor");
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for missing tabindex for interactive elements (e.g., buttons, links)
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkMissingTabIndex(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/<a[^>]*href="[^"]*"[^>]*>|<button[^>]*>.*?<\/button>/i', $htmlContent, $interactiveElements);
        $scoreDeducted = 0;

        foreach ($interactiveElements[0] as $element) {
            $lineNumber = $this->getLineNumber($lines, $element);
            if (strpos($element, 'tabindex="') === false) {
                $this->addIssue($issues, 'Missing tabindex for interactive elements', 'missing_tabindex', $lineNumber, $element);
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for missing labels for form fields
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkMissingLabels(string $htmlContent, array &$issues, array $lines): int
    {
        // Get all input elements
        preg_match_all('/<input[^>]*>/i', $htmlContent, $formInputs);
        $scoreDeducted = 0;
        $processedInputs = [];

        foreach ($formInputs[0] as $input) {
            $lineNumber = $this->getLineNumber($lines, $input);

            // Skip if this input has already been processed
            if (in_array($input, $processedInputs)) {
                continue;
            }

            // Mark this input as processed
            $processedInputs[] = $input;

            // Check if the input has an associated label (either by 'for' attribute or 'aria-labelledby')
            if (!$this->hasAssociatedLabel($input, $htmlContent)) {
                if (!isset($issues['missing_labels'])) {
                    $issues['missing_labels'] = [];
                }
                $issues['missing_labels'][] = [
                    'issue' => 'Form field missing label',
                    'line' => $lineNumber,
                    'details' => $input
                ];
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for missing skip navigation link
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkMissingSkipLink(string $htmlContent, array &$issues, array $lines): int
    {
        $scoreDeducted = 0;
        if (strpos($htmlContent, '<a href="#maincontent" class="skip-link">Skip to Content</a>') === false) {
            $this->addIssue($issues, 'Missing skip navigation link', 'missing_skip_link', 1, '<a href="#maincontent" class="skip-link">Skip to Content</a>');
            $scoreDeducted += 5;
        }

        return $scoreDeducted;
    }

    /**
     * Check for font size being too small
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkFontSizeTooSmall(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/font-size:\s*(\d+)px/i', $htmlContent, $fontSizes);
        $scoreDeducted = 0;

        foreach ($fontSizes[1] as $fontSize) {
            $lineNumber = $this->getLineNumber($lines, "font-size: $fontSize");
            if (intval($fontSize) < 16) {
                $this->addIssue($issues, 'Font size too small', 'font_size_too_small', $lineNumber, "<p style='font-size: {$fontSize}px;'>Small text</p>");
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for broken links
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkBrokenLinks(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/<a href="([^"]+)"/i', $htmlContent, $links);
        $scoreDeducted = 0;

        foreach ($links[1] as $link) {
            $lineNumber = $this->getLineNumber($lines, $link);
            if ($this->isBrokenLink($link)) {
                $this->addIssue($issues, 'Broken link or missing href attribute', 'broken_links', $lineNumber, "<a href='$link'>Broken Link</a>");
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for missing input labels
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkMissingInputLabels(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/<input[^>]*>/i', $htmlContent, $inputs);  // Get all input tags
        $scoreDeducted = 0;
        $processedInputs = [];  // Track which inputs have been checked

        foreach ($inputs[0] as $input) {
            $lineNumber = $this->getLineNumber($lines, $input);

            // Check if input has been processed already
            if (in_array($input, $processedInputs)) {
                continue; // Skip if this input has already been checked
            }

            // Mark this input as processed
            $processedInputs[] = $input;

            // Check if the input has an associated label
            if (!$this->hasAssociatedLabel($input, $htmlContent)) {
                $this->addIssue($issues, 'Missing label for input element', 'missing_input_labels', $lineNumber, $input);
                $scoreDeducted += 10;  // Deduct 10 points for missing label
            }
        }

        return $scoreDeducted;
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
     * Calculate the contrast ratio between two luminance values.
     *
     * @param float $l1 Luminance of the lighter color
     * @param float $l2 Luminance of the darker color
     * @return float
     */
    private function calculateContrastRatio(float $l1, float $l2): float
    {
        return ($l1 + 0.05) / ($l2 + 0.05);
    }

    /**
     * Calculate the luminance of a color.
     *
     * @param int $r Red component
     * @param int $g Green component
     * @param int $b Blue component
     * @return float
     */
    private function calculateLuminance(int $r, int $g, int $b): float
    {
        // Normalize RGB values and apply the luminance formula
        $rgb = [$r, $g, $b];
        foreach ($rgb as &$color) {
            $color /= 255;
            $color = ($color <= 0.03928) ? $color / 12.92 : pow(($color + 0.055) / 1.055, 2.4);
        }

        return 0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2];
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
            'low_color_contrast' => '<p style="color: #000000; background-color: #ffffff;">Good contrast text</p>',
            'missing_tabindex' => '<button tabindex="0">Click Me</button>',
            'missing_labels' => '<input type="text" id="name" /><label for="name">Name</label>',
            'missing_skip_link' => '<a href="#maincontent" class="skip-link">Skip to Content</a>',
            'font_size_too_small' => '<p style="font-size: 16px;">Text with appropriate size</p>',
            'broken_links' => '<a href="https://google.com">Valid Link</a>',
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
     * Check if an input element has an associated label
     *
     * @param string $input The input element HTML
     * @param string $htmlContent The full HTML content
     * @return bool
     */
    private function hasAssociatedLabel(string $input, string $htmlContent): bool
    {
        // Check if the input has an 'id' and a <label> with a matching 'for' attribute
        if (preg_match('/id="([^"]+)"/i', $input, $matches)) {
            $inputId = $matches[1];
            // Check if there's a matching <label> with for="inputId"
            if (preg_match('/<label[^>]*for="' . preg_quote($inputId, '/') . '"[^>]*>/i', $htmlContent)) {
                return true; // Found a matching <label> with for="inputId"
            }
        }

        // Check if the input has 'aria-labelledby' attribute
        if (strpos($input, 'aria-labelledby') !== false) {
            return true; // The input has an aria-labelledby attribute, consider it labeled
        }

        // If no label or aria-labelledby is found, return false
        return false;
    }

    /**
     * Convert Hex color to RGB array
     *
     * @param string $hexColor
     * @return array|null
     */
    private function hexToRgb(string $hexColor): ?array
    {
        // If it's already in hex form like #ffffff, convert to RGB
        if (preg_match('/^#([a-fA-F0-9]{6})$/', $hexColor, $matches)) {
            $r = hexdec($matches[1][0] . $matches[1][1]);
            $g = hexdec($matches[1][2] . $matches[1][3]);
            $b = hexdec($matches[1][4] . $matches[1][5]);
            return [$r, $g, $b];
        }
        return null;
    }

    /**
     * Helper function to check if the link is broken
     *
     * @param string $url
     * @return bool
     */
    private function isBrokenLink(string $url): bool
    {
        // Implement a check for broken links (simplified for example)
        return $url === '#';
    }

    /**
     * Check if the color contrast between two colors is below the required threshold.
     *
     * @param array $rgbTextColor
     * @param array $rgbBgColor
     * @return bool
     */
    private function isLowContrast(array $rgbTextColor, array $rgbBgColor): bool
    {
        // Calculate luminance for both colors using the WCAG formula
        $luminanceText = $this->calculateLuminance($rgbTextColor[0], $rgbTextColor[1], $rgbTextColor[2]);
        $luminanceBg = $this->calculateLuminance($rgbBgColor[0], $rgbBgColor[1], $rgbBgColor[2]);

        // Calculate contrast ratio
        $contrastRatio = $this->calculateContrastRatio($luminanceText, $luminanceBg);

        // The WCAG threshold for normal text is 4.5:1
        return $contrastRatio < 4.5;
    }

    /**
     * Convert rgb(r,g,b) or rgba(r,g,b,a) to RGB array
     *
     * @param string $rgbString
     * @return array|null
     */
    private function rgbToRgb(string $rgbString): ?array
    {
        // Handle RGB or RGBA formats like rgb(255, 0, 0) or rgba(255, 0, 0, 0.5)
        if (preg_match('/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*\d+(\.\d+)?)?\)/', $rgbString, $matches)) {
            return [(int)$matches[1], (int)$matches[2], (int)$matches[3]];
        }
        return null;
    }

    /**
     * Functions for analyzing and improving the accessibility of HTML content
     */
    private function testMethods(): array
    {
        return [
            'checkMissingAltAttribute',
            'checkSkippedHeadings',
            'checkLowColorContrast',
            'checkMissingTabIndex',
            'checkMissingLabels',
            'checkMissingSkipLink',
            'checkFontSizeTooSmall',
            'checkBrokenLinks',
            'checkMissingInputLabels'
        ];
    }
}
