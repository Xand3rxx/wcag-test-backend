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
        // Array vairable to hold the accessibility issues detected
        $issues = [];

        // Assume full compliance initially
        $compliance_score = 100;

        // 1. Check for missing alt attributes in images
        preg_match_all('/<img[^>]*>/i', $htmlContent, $images);
        foreach ($images[0] as $img) {
            if (strpos($img, 'alt="') === false && strpos($img, 'alt=""') === false) {
                $issues[] = [
                    'issue' => 'Missing alt attribute for image.',
                    'suggested_fix' => 'Add an alt attribute to the image.'
                ];
                $compliance_score -= 5;
            }
        }

        // 2. Check for skipped heading levels (e.g., <h1> followed by <h3>)
        preg_match_all('/<h(\d)>.*?<\/h\1>/i', $htmlContent, $headings);
        for ($i = 1; $i < count($headings[1]); $i++) {
            if (intval($headings[1][$i]) > intval($headings[1][$i - 1]) + 1) {
                $issues[] = [
                    'issue' => 'Skipped heading levels.',
                    'suggested_fix' => 'Ensure headings follow a logical order (e.g., <h1>, <h2>, <h3>).'
                ];
                $compliance_score -= 10;
            }
        }

        // 3. Check for color contrast between text and background
        preg_match_all('/color: *#[0-9a-fA-F]{6}/i', $htmlContent, $colors);
        foreach ($colors[0] as $color) {
            if ($this->isLowContrastColor($color)) {
                $issues[] = [
                    'issue' => 'Low color contrast.',
                    'suggested_fix' => 'Ensure sufficient contrast between text and background colors.'
                ];
                $compliance_score -= 5;
            }
        }

        // 4. Ensure interactive elements are accessible via keyboard
        preg_match_all('/<a[^>]*href="[^"]*"[^>]*>|<button[^>]*>.*?<\/button>/i', $htmlContent, $interactiveElements);
        foreach ($interactiveElements[0] as $element) {
            if (strpos($element, 'tabindex="') === false) {
                $issues[] = [
                    'issue' => 'Missing tabindex for interactive elements.',
                    'suggested_fix' => 'Ensure all interactive elements are accessible using keyboard navigation.'
                ];
                $compliance_score -= 5;
            }
        }

        // 5. Ensure form fields have associated labels
        preg_match_all('/<input[^>]*>/i', $htmlContent, $formInputs);
        foreach ($formInputs[0] as $input) {
            if (strpos($input, 'aria-labelledby') === false && strpos($input, 'id="') === false) {
                $issues[] = [
                    'issue' => 'Form field missing label.',
                    'suggested_fix' => 'Ensure all form fields have associated labels using the <label> tag or aria-labelledby attribute.'
                ];
                $compliance_score -= 5;
            }
        }

        // 6. Ensure there are skip navigation links
        if (strpos($htmlContent, '<a href="#maincontent" class="skip-link">Skip to Content</a>') === false) {
            $issues[] = [
                'issue' => 'Missing skip navigation link.',
                'suggested_fix' => 'Add a "Skip to Content" link at the top of the page for easier navigation.'
            ];
            $compliance_score -= 5;
        }

        // 7. Ensure that text can be resized up to 200% without breaking the layout
        if (strpos($htmlContent, 'font-size:') !== false) {
            preg_match_all('/font-size:\s*(\d+)px/i', $htmlContent, $fontSizes);
            foreach ($fontSizes[1] as $fontSize) {
                if (intval($fontSize) < 16) {
                    $issues[] = [
                        'issue' => 'Font size too small.',
                        'suggested_fix' => 'Ensure text size is at least 16px or resizable.'
                    ];
                    $compliance_score -= 5;
                }
            }
        }

        // 8. Ensure that all links are identifiable by their text or aria-label.
        preg_match_all('/<a[^>]*>/i', $htmlContent, $links);
        foreach ($links[0] as $link) {
            if (strpos($link, 'href=') === false) {
                $issues[] = [
                    'issue' => 'Broken link or missing href attribute.',
                    'suggested_fix' => 'Ensure all links have a valid href attribute.'
                ];
                $compliance_score -= 5;
            }
        }

        // 9. Ensure that form elements have <label> with matching 'for' attributes
        preg_match_all('/<input[^>]+id="([^"]+)"[^>]*>/i', $htmlContent, $inputs);
        foreach ($inputs[1] as $inputId) {
            if (strpos($htmlContent, '<label for="' . $inputId . '">') === false) {
                $issues[] = [
                    'issue' => 'Missing label for input element.',
                    'suggested_fix' => 'Ensure all input elements have a corresponding label with a matching "for" attribute.'
                ];
                $compliance_score -= 10;
            }
        }

        return [
            'compliance_score' => $compliance_score,
            'issues' => $issues
        ];
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
