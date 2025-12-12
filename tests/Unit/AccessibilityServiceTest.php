<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Services\AccessibilityService;

class AccessibilityServiceTest extends TestCase
{
    private AccessibilityService $accessibilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessibilityService = new AccessibilityService();
    }

    #[Test]
    public function it_detects_missing_alt_attribute(): void
    {
        $htmlContent = '<html><body><img src="image.jpg" /></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingAltAttribute($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
        $this->assertArrayHasKey('missing_alt', $issues);
        $this->assertCount(1, $issues['missing_alt']['details']);
        $this->assertEquals('Missing alt attribute for image', $issues['missing_alt']['issue']);
    }

    #[Test]
    public function it_accepts_empty_alt_for_decorative_images(): void
    {
        $htmlContent = '<html><body><img src="decorative.jpg" alt="" /></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingAltAttribute($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
        $this->assertArrayNotHasKey('missing_alt', $issues);
    }

    #[Test]
    public function it_detects_skipped_heading_levels(): void
    {
        $htmlContent = '<html><body><h1>Main Heading</h1><h3>Sub Heading</h3></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkSkippedHeadings($htmlContent, $issues, $lines);

        $this->assertEquals(10, $scoreDeducted);
        $this->assertArrayHasKey('skipped_headings', $issues);
        $this->assertCount(1, $issues['skipped_headings']['details']);
    }

    #[Test]
    public function it_accepts_proper_heading_hierarchy(): void
    {
        $htmlContent = '<html><body><h1>Main</h1><h2>Sub</h2><h3>Detail</h3></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkSkippedHeadings($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }

    #[Test]
    public function it_detects_missing_keyboard_accessibility_on_custom_elements(): void
    {
        // Custom div with onclick but no tabindex - this is the actual accessibility issue
        $htmlContent = '<html><body><div onclick="doSomething()">Click Me</div></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingTabIndex($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
        $this->assertArrayHasKey('missing_tabindex', $issues);
    }

    #[Test]
    public function it_does_not_penalize_native_focusable_elements(): void
    {
        // Native buttons and links are already focusable - no tabindex needed
        $htmlContent = '<html><body>
            <button>Click Me</button>
            <a href="https://example.com">Link</a>
        </body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingTabIndex($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
        $this->assertArrayNotHasKey('missing_tabindex', $issues);
    }

    #[Test]
    public function it_detects_missing_labels_for_form_fields(): void
    {
        $htmlContent = '<html><body><input type="text" id="name" /></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingLabels($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
        $this->assertArrayHasKey('missing_labels', $issues);
    }

    #[Test]
    public function it_accepts_inputs_with_aria_label(): void
    {
        $htmlContent = '<html><body><input type="text" aria-label="Search" /></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingLabels($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }

    #[Test]
    public function it_accepts_inputs_with_matching_label(): void
    {
        $htmlContent = '<html><body><label for="email">Email</label><input type="text" id="email" /></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingLabels($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }

    #[Test]
    public function it_skips_hidden_and_button_inputs(): void
    {
        $htmlContent = '<html><body>
            <input type="hidden" name="csrf" value="token" />
            <input type="submit" value="Submit" />
            <input type="button" value="Click" />
        </body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingLabels($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }

    #[Test]
    public function it_detects_missing_skip_navigation_link(): void
    {
        $htmlContent = '<html><body><p>Some content here</p></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingSkipLink($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
        $this->assertArrayHasKey('missing_skip_link', $issues);
    }

    #[Test]
    public function it_accepts_skip_link_with_skip_class(): void
    {
        $htmlContent = '<html><body><a href="#main" class="skip-link">Skip to Content</a></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkMissingSkipLink($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }

    #[Test]
    public function it_detects_font_size_too_small(): void
    {
        // 12px is below the 14px threshold
        $htmlContent = '<html><body><p style="font-size: 12px;">Small text</p></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkFontSizeTooSmall($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
        $this->assertArrayHasKey('font_size_too_small', $issues);
    }

    #[Test]
    public function it_accepts_adequate_font_sizes(): void
    {
        $htmlContent = '<html><body><p style="font-size: 16px;">Normal text</p></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkFontSizeTooSmall($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }

    #[Test]
    public function it_detects_broken_links(): void
    {
        $htmlContent = '<html><body><a href="#">Broken Link</a></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkBrokenLinks($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
        $this->assertArrayHasKey('broken_links', $issues);
    }

    #[Test]
    public function it_detects_javascript_void_links(): void
    {
        $htmlContent = '<html><body><a href="javascript:void(0)">Bad Link</a></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkBrokenLinks($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
    }

    #[Test]
    public function it_accepts_valid_links(): void
    {
        $htmlContent = '<html><body><a href="https://example.com">Valid Link</a></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkBrokenLinks($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }

    #[Test]
    public function it_analyzes_full_html_content(): void
    {
        $htmlContent = '<!DOCTYPE html>
<html>
<head><title>Test</title></head>
<body>
    <img src="test.jpg" />
    <h1>Heading 1</h1>
    <h3>Heading 3</h3>
    <p style="font-size: 10px;">Small text</p>
</body>
</html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertArrayHasKey('compliance_score', $result);
        $this->assertArrayHasKey('issues', $result);
        $this->assertLessThan(100, $result['compliance_score']);
    }

    #[Test]
    public function it_clamps_score_to_zero_for_highly_non_compliant_html(): void
    {
        // HTML with many issues that would exceed 100 points deduction
        $htmlContent = '<html><body>
            <img src="1.jpg" /><img src="2.jpg" /><img src="3.jpg" />
            <img src="4.jpg" /><img src="5.jpg" /><img src="6.jpg" />
            <img src="7.jpg" /><img src="8.jpg" /><img src="9.jpg" />
            <img src="10.jpg" /><img src="11.jpg" /><img src="12.jpg" />
            <h1>Title</h1><h4>Skipped</h4><h6>Skipped more</h6>
            <input type="text" /><input type="email" /><input type="tel" />
            <a href="#">Broken</a><a href="#">Also broken</a>
            <p style="font-size: 8px;">Tiny</p>
            <p style="font-size: 10px;">Small</p>
        </body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertGreaterThanOrEqual(0, $result['compliance_score']);
        $this->assertLessThanOrEqual(100, $result['compliance_score']);
    }

    #[Test]
    public function it_returns_full_score_for_compliant_html(): void
    {
        // Fully accessible HTML
        $htmlContent = '<html><body>
            <a href="#main" class="skip-to-main">Skip to main content</a>
            <img src="image.jpg" alt="A descriptive alt text" />
            <h1>Main Heading</h1>
            <h2>Sub Heading</h2>
            <label for="name">Name</label>
            <input type="text" id="name" />
            <button>Submit</button>
            <a href="https://example.com">Valid Link</a>
            <p style="font-size: 16px;">Normal text</p>
        </body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertArrayHasKey('compliance_score', $result);
        $this->assertEquals(100, $result['compliance_score']);
    }

    #[Test]
    public function it_detects_low_color_contrast(): void
    {
        // Gray on gray - low contrast
        $htmlContent = '<html><body><p style="color: #777777; background-color: #888888;">Low contrast</p></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkLowColorContrast($htmlContent, $issues, $lines);

        $this->assertEquals(5, $scoreDeducted);
        $this->assertArrayHasKey('low_color_contrast', $issues);
    }

    #[Test]
    public function it_accepts_high_contrast_colors(): void
    {
        // Black on white - excellent contrast
        $htmlContent = '<html><body><p style="color: #000000; background-color: #ffffff;">High contrast</p></body></html>';

        $issues = [];
        $lines = explode("\n", $htmlContent);

        $scoreDeducted = $this->accessibilityService->checkLowColorContrast($htmlContent, $issues, $lines);

        $this->assertEquals(0, $scoreDeducted);
    }
}
