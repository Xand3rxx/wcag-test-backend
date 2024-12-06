<?php

use PHPUnit\Framework\TestCase;

class AccessibilityServiceTest extends TestCase
{
    private $accessibilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessibilityService = new \App\Services\AccessibilityService();
    }

    #[Test]
    public function test_it_detects_missing_alt_attribute()
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
    public function test_it_detects_skipped_heading_levels()
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
    public function test_it_detects_missing_tabindex_for_interactive_elements()
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
    public function test_it_detects_missing_labels_for_form_fields()
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
        $this->assertCount(1, $issues['missing_labels']);  // Only one issue should be present
        $this->assertEquals('Form field missing label', $issues['missing_labels'][0]['issue']);
    }

    #[Test]
    public function test_it_detects_missing_skip_navigation_link()
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
    public function test_it_detects_font_size_too_small()
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
    public function test_it_detects_broken_links()
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

    // #[Test]
    public function test_it_detects_missing_input_labels()
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

        $this->assertEquals('Missing label for input element', $issues['missing_input_labels']['issue']);
    }
}
