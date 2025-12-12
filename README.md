# WCAG Accessibility Compliance Analysis API

A Laravel-based API for analyzing HTML content against WCAG 2.1 accessibility guidelines.

## Overview

This system analyzes uploaded HTML files for accessibility compliance issues and provides:
- A compliance score (0-100)
- Detailed list of accessibility issues found
- Suggested fixes for each issue detected

## Requirements

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm (for frontend assets)
- Docker (optional, for containerized deployment)

## Quick Start

### Local Development

1. **Clone the repository:**
   ```bash
   git clone https://github.com/Xand3rxx/wcag-test-backend.git
   cd wcag-test-backend
   ```

2. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run the development server:**
   ```bash
   php artisan serve
   ```

5. **Access the API at:** `http://localhost:8000/api`

### Docker Deployment

1. **Build and run containers:**
   ```bash
    docker-compose up -d --build
    ```

2. **Access the API at:** `http://localhost:9600/api`

3. **Stop containers:**
   ```bash
docker-compose down
```

## API Endpoints

### Analyze HTML

```
POST /api/analyze-html
Content-Type: multipart/form-data
```

**Request Body:**
| Field | Type | Description |
|-------|------|-------------|
| file  | File | HTML file to analyze (max 256KB) |

**Response:**
```json
{
  "statusCode": 200,
  "success": true,
  "data": {
    "compliance_score": 85,
    "issues": {
      "missing_alt": {
        "issue": "Missing alt attribute for image",
        "line": 15,
        "details": [
          {
            "suggested_fix": "Add an alt attribute to the image.",
            "faulted_html": "<img src=\"image.jpg\" />",
            "sample_html": "<img src=\"image.jpg\" alt=\"Description of image\" />"
          }
        ]
      }
    }
  },
  "message": "Ok"
}
```

### Health Check

```
GET /up
```

Returns application health status.

## Accessibility Checks

The API performs the following WCAG compliance checks:

| Check | Deduction | WCAG Guideline |
|-------|-----------|----------------|
| Missing alt attributes | 5 points | 1.1.1 Non-text Content |
| Skipped heading levels | 10 points | 1.3.1 Info and Relationships |
| Low color contrast | 5 points | 1.4.3 Contrast (Minimum) |
| Missing tabindex | 5 points | 2.1.1 Keyboard |
| Missing form labels | 5 points | 1.3.1 Info and Relationships |
| Missing skip link | 5 points | 2.4.1 Bypass Blocks |
| Font size too small | 5 points | 1.4.4 Resize Text |
| Broken links | 5 points | 2.4.4 Link Purpose |
| Missing input labels | 10 points | 4.1.2 Name, Role, Value |

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Frontend                              │
│              (HTML File Upload Interface)                    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                    Laravel Backend                           │
├─────────────────────────────────────────────────────────────┤
│  HTMLFileUploadRequest → AccessibilityController            │
│                              │                               │
│                              ▼                               │
│                    AccessibilityService                      │
│                    (WCAG Analysis Engine)                    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    JSON Response
                (Score + Issues + Fixes)
```

## Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test file
vendor/bin/phpunit --filter AccessibilityServiceTest
```

### Test Results

```
   PASS  Tests\Unit\AccessibilityServiceTest
  ✓ it detects missing alt attribute
  ✓ it detects skipped heading levels
  ✓ it detects missing tabindex for interactive elements
  ✓ it detects missing labels for form fields
  ✓ it detects missing skip navigation link
  ✓ it detects font size too small
  ✓ it detects broken links
  ✓ it detects missing input labels
  ✓ it analyzes full html content
  ✓ it returns full score for compliant html

  Tests:    10 passed (40 assertions)
```

## Production Deployment

### Environment Configuration

For production, ensure these environment variables are set:

```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=<your-generated-key>
LOG_CHANNEL=stack
LOG_STACK=stderr
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com
```

### Optimization Commands

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Install production dependencies
composer install --no-dev --optimize-autoloader
```

### Docker Production

The included Docker configuration is production-ready with:
- Nginx with rate limiting and security headers
- PHP-FPM with opcache enabled
- Health check endpoint
- Optimized caching

## Project Structure

```
├── app/
│   ├── Exceptions/Handler.php      # Custom exception handling
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── AccessibilityController.php
│   │   └── Requests/
│   │       └── HTMLFileUploadRequest.php
│   └── Services/
│       └── AccessibilityService.php  # Core analysis engine
├── config/
│   └── cors.php                      # CORS configuration
├── docker/
│   ├── build.sh                      # Docker entrypoint
│   ├── nginx.conf                    # Nginx configuration
│   └── www.conf                      # PHP-FPM configuration
├── routes/
│   └── api.php                       # API routes
├── tests/
│   └── Unit/
│       └── AccessibilityServiceTest.php
├── docker-compose.yml
├── Dockerfile
└── phpunit.xml
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Screenshot

![WCAG Backend API](public/wcag-backend.png)
