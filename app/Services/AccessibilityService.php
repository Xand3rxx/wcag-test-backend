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

        // Ensure score stays within valid range (0-100)
        $complianceScore = max(0, min(100, $complianceScore));

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
            // Check for alt attribute with any quote style or as boolean attribute
            $hasAlt = preg_match('/\salt[=\s>]/i', $img) || 
                      preg_match('/\salt="[^"]*"/i', $img) || 
                      preg_match("/\salt='[^']*'/i", $img);
            
            if (!$hasAlt) {
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
        preg_match_all('/<h(\d)[^>]*>.*?<\/h\1>/is', $htmlContent, $headings);
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
        // Find elements with both color and background-color defined
        preg_match_all('/style=["\'][^"\']*color:\s*(#[a-fA-F0-9]{3,6}|rgb\([^\)]+\)|rgba\([^\)]+\))[^"\']*background-color:\s*(#[a-fA-F0-9]{3,6}|rgb\([^\)]+\)|rgba\([^\)]+\))[^"\']*["\']|style=["\'][^"\']*background-color:\s*(#[a-fA-F0-9]{3,6}|rgb\([^\)]+\)|rgba\([^\)]+\))[^"\']*color:\s*(#[a-fA-F0-9]{3,6}|rgb\([^\)]+\)|rgba\([^\)]+\))[^"\']*["\']/i', $htmlContent, $matches, PREG_SET_ORDER);

        $scoreDeducted = 0;

        foreach ($matches as $match) {
            // Get text color and background color from either match pattern
            $textColor = !empty($match[1]) ? $match[1] : $match[4];
            $bgColor = !empty($match[2]) ? $match[2] : $match[3];

            if (empty($textColor) || empty($bgColor)) {
                continue;
            }

            // Convert colors to RGB format
            $rgbTextColor = $this->parseColor($textColor);
            $rgbBgColor = $this->parseColor($bgColor);

            // Skip if we couldn't parse either color
            if ($rgbTextColor === null || $rgbBgColor === null) {
                continue;
            }

            // Check if contrast ratio is too low
            if ($this->isLowContrast($rgbTextColor, $rgbBgColor)) {
                $lineNumber = $this->getLineNumber($lines, $textColor);
                $this->addIssue($issues, 'Low color contrast', 'low_color_contrast', $lineNumber, "Color: $textColor, Background: $bgColor");
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for missing keyboard accessibility on custom interactive elements
     * Note: Native <a>, <button>, <input>, <select>, <textarea> are already focusable
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkMissingTabIndex(string $htmlContent, array &$issues, array $lines): int
    {
        // Find elements with click handlers that aren't natively focusable
        preg_match_all('/<(div|span|li|tr|td|img|p)[^>]*(onclick|ng-click|@click|\(click\))[^>]*>/i', $htmlContent, $customInteractive);
        $scoreDeducted = 0;

        foreach ($customInteractive[0] as $element) {
            $lineNumber = $this->getLineNumber($lines, $element);
            // Check if element has tabindex or role with keyboard support
            $hasKeyboardAccess = preg_match('/tabindex=["\'][^"\']*["\']/i', $element) ||
                                 preg_match('/role=["\'](button|link|menuitem)["\'].*tabindex/i', $element);
            
            if (!$hasKeyboardAccess) {
                $this->addIssue($issues, 'Interactive element not keyboard accessible', 'missing_tabindex', $lineNumber, $element);
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
        // Get all input, select, and textarea elements (excluding hidden and submit/button types)
        preg_match_all('/<(input|select|textarea)[^>]*>/i', $htmlContent, $formInputs);
        $scoreDeducted = 0;
        $processedInputs = [];

        foreach ($formInputs[0] as $input) {
            // Skip hidden inputs and button/submit types
            if (preg_match('/type=["\']?(hidden|submit|button|reset|image)["\']?/i', $input)) {
                continue;
            }

            $lineNumber = $this->getLineNumber($lines, $input);

            // Skip if this input has already been processed
            if (in_array($input, $processedInputs)) {
                continue;
            }

            // Mark this input as processed
            $processedInputs[] = $input;

            // Check if the input has an associated label
            if (!$this->hasAssociatedLabel($input, $htmlContent)) {
                $this->addIssue($issues, 'Form field missing label', 'missing_labels', $lineNumber, $input);
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
        
        // Check for skip link patterns: href starting with # and containing skip-related text or class
        $hasSkipLink = preg_match(
            '/<a[^>]*href=["\']#[^"\']*["\'][^>]*>.*?(skip|jump|main|content).*?<\/a>/is',
            $htmlContent
        ) || preg_match(
            '/<a[^>]*class=["\'][^"\']*skip[^"\']*["\'][^>]*>/i',
            $htmlContent
        );
        
        if (!$hasSkipLink) {
            $this->addIssue($issues, 'Missing skip navigation link', 'missing_skip_link', 1, '<a href="#maincontent" class="skip-link">Skip to Content</a>');
            $scoreDeducted += 5;
        }

        return $scoreDeducted;
    }

    /**
     * Check for font size being too small (less than 14px for body text)
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkFontSizeTooSmall(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/font-size:\s*(\d+(?:\.\d+)?)(px|pt|em|rem)/i', $htmlContent, $fontSizes, PREG_SET_ORDER);
        $scoreDeducted = 0;

        foreach ($fontSizes as $match) {
            $size = floatval($match[1]);
            $unit = strtolower($match[2]);
            
            // Convert to approximate px for comparison
            $pxSize = match($unit) {
                'pt' => $size * 1.333,
                'em', 'rem' => $size * 16,
                default => $size
            };

            // WCAG recommends minimum 14px for body text
            if ($pxSize < 14) {
                $lineNumber = $this->getLineNumber($lines, $match[0]);
                $this->addIssue($issues, 'Font size too small', 'font_size_too_small', $lineNumber, "<p style='{$match[0]};'>Small text</p>");
                $scoreDeducted += 5;
            }
        }

        return $scoreDeducted;
    }

    /**
     * Check for broken or placeholder links
     *
     * @param string $htmlContent
     * @param array &$issues
     * @param array $lines
     * @return int
     */
    public function checkBrokenLinks(string $htmlContent, array &$issues, array $lines): int
    {
        preg_match_all('/<a[^>]*href=["\']([^"\']*)["\'][^>]*>/i', $htmlContent, $links);
        $scoreDeducted = 0;

        foreach ($links[1] as $index => $link) {
            if ($this->isBrokenLink($link)) {
                $lineNumber = $this->getLineNumber($lines, $links[0][$index]);
                $this->addIssue($issues, 'Broken link or placeholder href', 'broken_links', $lineNumber, $links[0][$index]);
                $scoreDeducted += 5;
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
    private function addIssue(array &$issues, string $issue, string $category, int $line, $htmlSnippet): void
    {
        // If the category doesn't exist, initialize it
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
    }

    /**
     * Calculate the contrast ratio between two luminance values.
     * Always returns ratio >= 1 by putting lighter color on top.
     *
     * @param float $l1 Luminance of first color
     * @param float $l2 Luminance of second color
     * @return float
     */
    private function calculateContrastRatio(float $l1, float $l2): float
    {
        $lighter = max($l1, $l2);
        $darker = min($l1, $l2);
        return ($lighter + 0.05) / ($darker + 0.05);
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
     * Parse a color string to RGB array
     *
     * @param string $color
     * @return array|null
     */
    private function parseColor(string $color): ?array
    {
        // Try hex format first
        $rgb = $this->hexToRgb($color);
        if ($rgb !== null) {
            return $rgb;
        }
        
        // Try rgb/rgba format
        return $this->rgbToRgb($color);
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
            'missing_tabindex' => '<div onclick="..." tabindex="0" role="button">Click Me</div>',
            'missing_labels' => '<label for="name">Name</label><input type="text" id="name" />',
            'missing_skip_link' => '<a href="#maincontent" class="skip-link">Skip to Content</a>',
            'font_size_too_small' => '<p style="font-size: 16px;">Text with appropriate size</p>',
            'broken_links' => '<a href="https://example.com">Valid Link</a>',
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
            'missing_alt' => 'Add an alt attribute to the image. Use alt="" for decorative images.',
            'skipped_headings' => 'Ensure headings follow a logical order (e.g., <h1>, <h2>, <h3>).',
            'low_color_contrast' => 'Ensure contrast ratio is at least 4.5:1 for normal text, 3:1 for large text.',
            'missing_tabindex' => 'Add tabindex="0" and appropriate role to make custom interactive elements keyboard accessible.',
            'missing_labels' => 'Add a <label> with matching "for" attribute, or use aria-label/aria-labelledby.',
            'missing_skip_link' => 'Add a "Skip to Content" link at the top of the page for keyboard users.',
            'font_size_too_small' => 'Ensure body text size is at least 14px (16px recommended).',
            'broken_links' => 'Replace placeholder href with a valid URL or use a button for actions.',
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
        // Check for aria-label attribute (directly on input)
        if (preg_match('/aria-label=["\'][^"\']+["\']/i', $input)) {
            return true;
        }

        // Check for aria-labelledby attribute
        if (preg_match('/aria-labelledby=["\'][^"\']+["\']/i', $input)) {
            return true;
        }

        // Check for title attribute (fallback labeling method)
        if (preg_match('/title=["\'][^"\']+["\']/i', $input)) {
            return true;
        }

        // Check if the input has an 'id' and a <label> with a matching 'for' attribute
        if (preg_match('/id=["\']([^"\']+)["\']/i', $input, $matches)) {
            $inputId = $matches[1];
            // Check if there's a matching <label> with for="inputId"
            if (preg_match('/<label[^>]*for=["\']' . preg_quote($inputId, '/') . '["\'][^>]*>/i', $htmlContent)) {
                return true;
            }
        }

        // Check if input is wrapped inside a <label> element
        if (preg_match('/<label[^>]*>[^<]*' . preg_quote($input, '/') . '/i', $htmlContent)) {
            return true;
        }

        return false;
    }

    /**
     * Convert Hex color to RGB array (supports 3 and 6 digit hex)
     *
     * @param string $hexColor
     * @return array|null
     */
    private function hexToRgb(string $hexColor): ?array
    {
        // 6-digit hex
        if (preg_match('/^#([a-fA-F0-9]{6})$/', $hexColor, $matches)) {
            $hex = $matches[1];
            return [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2))
            ];
        }
        
        // 3-digit hex (shorthand)
        if (preg_match('/^#([a-fA-F0-9]{3})$/', $hexColor, $matches)) {
            $hex = $matches[1];
            return [
                hexdec($hex[0] . $hex[0]),
                hexdec($hex[1] . $hex[1]),
                hexdec($hex[2] . $hex[2])
            ];
        }
        
        return null;
    }

    /**
     * Helper function to check if the link is broken or a placeholder
     *
     * @param string $url
     * @return bool
     */
    private function isBrokenLink(string $url): bool
    {
        $url = trim($url);
        
        // Check for common placeholder/broken patterns
        return $url === '#' ||
               $url === '' ||
               $url === '#!' ||
               stripos($url, 'javascript:') === 0 ||
               $url === 'javascript:void(0)' ||
               $url === 'javascript:void(0);' ||
               $url === 'javascript:;';
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

        // Calculate contrast ratio (always >= 1 due to max/min in calculateContrastRatio)
        $contrastRatio = $this->calculateContrastRatio($luminanceText, $luminanceBg);

        // The WCAG AA threshold for normal text is 4.5:1
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
        if (preg_match('/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)(?:\s*,\s*[\d.]+)?\s*\)/i', $rgbString, $matches)) {
            return [(int)$matches[1], (int)$matches[2], (int)$matches[3]];
        }
        return null;
    }

    /**
     * Methods for analyzing HTML content accessibility
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
            // Note: checkMissingInputLabels removed - consolidated into checkMissingLabels
        ];
    }
}
