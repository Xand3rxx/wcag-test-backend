<?php

namespace Tests\Unit;

use App\Services\AccessibilityService;
use PHPUnit\Framework\TestCase;

class AccessibilityServiceTest extends TestCase
{
    /**
     * @var AccessibilityService
     */
    protected $accessibilityService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accessibilityService = new AccessibilityService();
    }

    /** @test */
    public function it_detects_missing_alt_attributes()
    {
        $htmlContent = '<html><body><img src="image.jpg"></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(95, $result['compliance_score']);
        $this->assertArrayHasKey('missing_alt', $result['issues']);
        $this->assertCount(1, $result['issues']['missing_alt']);
        $this->assertEquals('Missing alt attribute for image.', $result['issues']['missing_alt'][0]['issue']);
    }

    /** @test */
    public function it_detects_skipped_heading_levels()
    {
        $htmlContent = '<html><body><h1>Main Heading</h1><h3>Sub Heading</h3></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(90, $result['compliance_score']);
        $this->assertArrayHasKey('skipped_headings', $result['issues']);
        $this->assertCount(1, $result['issues']['skipped_headings']);
        $this->assertEquals('Skipped heading levels.', $result['issues']['skipped_headings'][0]['issue']);
    }

    /** @test */
    public function it_detects_low_color_contrast()
    {
        $htmlContent = '<html><body><p style="color: #f0f0f0; background-color: #ffffff;">Low contrast text</p></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(95, $result['compliance_score']);
        $this->assertArrayHasKey('low_color_contrast', $result['issues']);
        $this->assertCount(1, $result['issues']['low_color_contrast']);
        $this->assertEquals('Low color contrast.', $result['issues']['low_color_contrast'][0]['issue']);
    }

    /** @test */
    public function it_detects_missing_tabindex_for_interactive_elements()
    {
        $htmlContent = '<html><body><button>Click Me</button></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(95, $result['compliance_score']);
        $this->assertArrayHasKey('missing_tabindex', $result['issues']);
        $this->assertCount(1, $result['issues']['missing_tabindex']);
        $this->assertEquals('Missing tabindex for interactive elements.', $result['issues']['missing_tabindex'][0]['issue']);
    }

    /** @test */
    public function it_detects_missing_labels_for_form_fields()
    {
        $htmlContent = '<html><body><input type="text" id="name"></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(95, $result['compliance_score']);
        $this->assertArrayHasKey('missing_labels', $result['issues']);
        $this->assertCount(1, $result['issues']['missing_labels']);
        $this->assertEquals('Form field missing label.', $result['issues']['missing_labels'][0]['issue']);
    }

    /** @test */
    public function it_detects_missing_skip_link()
    {
        $htmlContent = '<html><body><h1>Welcome to the Page</h1></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(95, $result['compliance_score']);
        $this->assertArrayHasKey('missing_skip_link', $result['issues']);
        $this->assertCount(1, $result['issues']['missing_skip_link']);
        $this->assertEquals('Missing skip navigation link.', $result['issues']['missing_skip_link'][0]['issue']);
    }

    /** @test */
    public function it_detects_font_size_too_small()
    {
        $htmlContent = '<html><body><p style="font-size: 12px;">Small text</p></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(95, $result['compliance_score']);
        $this->assertArrayHasKey('font_size_too_small', $result['issues']);
        $this->assertCount(1, $result['issues']['font_size_too_small']);
        $this->assertEquals('Font size too small.', $result['issues']['font_size_too_small'][0]['issue']);
    }

    /** @test */
    public function it_detects_broken_links()
    {
        $htmlContent = '<html><body><a href="#">Broken Link</a></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(95, $result['compliance_score']);
        $this->assertArrayHasKey('broken_links', $result['issues']);
        $this->assertCount(1, $result['issues']['broken_links']);
        $this->assertEquals('Broken link or missing href attribute.', $result['issues']['broken_links'][0]['issue']);
    }

    /** @test */
    public function it_detects_missing_input_labels()
    {
        $htmlContent = '<html><body><input type="text" id="email"></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(90, $result['compliance_score']);
        $this->assertArrayHasKey('missing_input_labels', $result['issues']);
        $this->assertCount(1, $result['issues']['missing_input_labels']);
        $this->assertEquals('Missing label for input element.', $result['issues']['missing_input_labels'][0]['issue']);
    }

    /** @test */
    public function it_returns_full_compliance_for_valid_html()
    {
        $htmlContent = '<html><body><img src="image.jpg" alt="Description of image"><h1>Main Heading</h1><p style="color: #000000; background-color: #ffffff;">Normal text</p></body></html>';

        $result = $this->accessibilityService->analyzeAccessibility($htmlContent);

        $this->assertEquals(100, $result['compliance_score']);
        $this->assertEmpty($result['issues']);
    }
}
