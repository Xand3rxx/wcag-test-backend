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
        // Test HTML with a missing alt attribute
        $htmlContent = '<html><body><img src="image.jpg" /></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkMissingAltAttribute method
        $scoreDeducted = $this->accessibilityService->checkMissingAltAttribute($htmlContent, $issues, $lines);

        // Assert that 5 points are deducted
        $this->assertEquals(5, $scoreDeducted);

        // Assert that the issue is detected for missing alt attribute
        $this->assertArrayHasKey('missing_alt', $issues);
        $this->assertCount(1, $issues['missing_alt']['details']);
        $this->assertEquals('Missing alt attribute for image', $issues['missing_alt']['issue']);
    }

    #[Test]
    public function it_detects_skipped_heading_levels(): void
    {
        // Test HTML with skipped heading levels (e.g., <h1> followed by <h3>)
        $htmlContent = '<html><body><h1>Main Heading</h1><h3>Sub Heading</h3></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkSkippedHeadings method
        $scoreDeducted = $this->accessibilityService->checkSkippedHeadings($htmlContent, $issues, $lines);

        // Assert that 10 points are deducted
        $this->assertEquals(10, $scoreDeducted);

        // Assert that the issue is detected for skipped heading levels
        $this->assertArrayHasKey('skipped_headings', $issues);
        $this->assertCount(1, $issues['skipped_headings']['details']);
        $this->assertEquals('Skipped heading levels', $issues['skipped_headings']['issue']);
    }

    #[Test]
    public function it_detects_missing_tabindex_for_interactive_elements(): void
    {
        // Test HTML with a missing tabindex on a button
        $htmlContent = '<html><body><button>Click Me</button></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkMissingTabIndex method
        $scoreDeducted = $this->accessibilityService->checkMissingTabIndex($htmlContent, $issues, $lines);

        // Assert that 5 points are deducted
        $this->assertEquals(5, $scoreDeducted);

        // Assert that the issue is detected for missing tabindex
        $this->assertArrayHasKey('missing_tabindex', $issues);
        $this->assertCount(1, $issues['missing_tabindex']['details']);
        $this->assertEquals('Missing tabindex for interactive elements', $issues['missing_tabindex']['issue']);
    }

    #[Test]
    public function it_detects_missing_labels_for_form_fields(): void
    {
        // Test HTML with an input element missing a label
        $htmlContent = '<html><body><input type="text" id="name" /></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkMissingLabels method
        $scoreDeducted = $this->accessibilityService->checkMissingLabels($htmlContent, $issues, $lines);

        // Assert that 5 points are deducted because the input is missing an associated label
        $this->assertEquals(5, $scoreDeducted);

        // Assert that the issue is detected for missing labels
        $this->assertArrayHasKey('missing_labels', $issues);
        $this->assertCount(1, $issues['missing_labels']);
        $this->assertEquals('Form field missing label', $issues['missing_labels'][0]['issue']);
    }

    #[Test]
    public function it_detects_missing_skip_navigation_link(): void
    {
        // Test HTML missing a skip navigation link
        $htmlContent = '<html><body><p>Some content here</p></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkMissingSkipLink method
        $scoreDeducted = $this->accessibilityService->checkMissingSkipLink($htmlContent, $issues, $lines);

        // Assert that 5 points are deducted
        $this->assertEquals(5, $scoreDeducted);

        // Assert that the issue is detected for missing skip link
        $this->assertArrayHasKey('missing_skip_link', $issues);
        $this->assertCount(1, $issues['missing_skip_link']['details']);
        $this->assertEquals('Missing skip navigation link', $issues['missing_skip_link']['issue']);
    }

    #[Test]
    public function it_detects_font_size_too_small(): void
    {
        // Test HTML with a small font size
        $htmlContent = '<html><body><p style="font-size: 12px;">Small text</p></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkFontSizeTooSmall method
        $scoreDeducted = $this->accessibilityService->checkFontSizeTooSmall($htmlContent, $issues, $lines);

        // Assert that 5 points are deducted
        $this->assertEquals(5, $scoreDeducted);

        // Assert that the issue is detected for font size too small
        $this->assertArrayHasKey('font_size_too_small', $issues);
        $this->assertCount(1, $issues['font_size_too_small']['details']);
        $this->assertEquals('Font size too small', $issues['font_size_too_small']['issue']);
    }

    #[Test]
    public function it_detects_broken_links(): void
    {
        // Test HTML with a broken link
        $htmlContent = '<html><body><a href="#">Broken Link</a></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkBrokenLinks method
        $scoreDeducted = $this->accessibilityService->checkBrokenLinks($htmlContent, $issues, $lines);

        // Assert that 5 points are deducted
        $this->assertEquals(5, $scoreDeducted);

        // Assert that the issue is detected for broken links
        $this->assertArrayHasKey('broken_links', $issues);
        $this->assertCount(1, $issues['broken_links']['details']);
        $this->assertEquals('Broken link or missing href attribute', $issues['broken_links']['issue']);
    }

    #[Test]
    public function it_detects_missing_input_labels(): void
    {
        // Test HTML with an input element missing a matching label
        $htmlContent = '<html><body><input type="text" id="email" /></body></html>';

        // Initialize the issues array and split the HTML content into lines
        $issues = [];
        $lines = explode("\n", $htmlContent);

        // Call the checkMissingInputLabels method
        $scoreDeducted = $this->accessibilityService->checkMissingInputLabels($htmlContent, $issues, $lines);

        // Assert that 10 points are deducted because the input is missing an associated label
        $this->assertEquals(10, $scoreDeducted);

        // Assert that the issue is detected for missing input labels
        $this->assertArrayHasKey('missing_input_labels', $issues);
        $this->assertCount(1, $issues['missing_input_labels']['details']);
        $this->assertEquals('Missing label for input element', $issues['missing_input_labels']['issue']);
    }

    #[Test]
    public function it_analyzes_full_html_content(): void
    {
        // Test full analysis with multiple issues
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

        // Verify structure
        $this->assertArrayHasKey('compliance_score', $result);
        $this->assertArrayHasKey('issues', $result);
        
        // Score should be less than 100 due to issues
        $this->assertLessThan(100, $result['compliance_score']);
    }

    #[Test]
    public function it_returns_full_score_for_compliant_html(): void
    {
        // Test HTML that is fully compliant with all accessibility checks
        $htmlContent = '<html><body>
            <a href="#maincontent" class="skip-link" tabindex="0">Skip to Content</a>
            <img src="image.jpg" alt="Description of the image" />
            <h1>Main Heading</h1>
            <h2>Sub Heading</h2>
            <label for="name">Name</label>
            <input type="text" id="name" aria-labelledby="name" tabindex="0" />
            <button tabindex="0">Click Me</button>
            <a href="https://example.com" tabindex="0">Valid Link</a>
            <p style="font-size: 16px;">Normal text</p>
        </body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        // Verify structure
        $this->assertArrayHasKey('compliance_score', $result);
        $this->assertArrayHasKey('issues', $result);
        
        // Score should be 100 for compliant HTML
        $this->assertEquals(100, $result['compliance_score']);
    }
}
