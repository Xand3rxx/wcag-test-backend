## About this repository

WCAG stands for Web Content Accessibility Guidelines. In simple terms, it's a set of rules that help make websites easier to use for people with disabilities, like those who are blind, deaf, or have difficulty using a mouse. These guidelines ensure that websites are accessible to everyone, no matter their abilities.

This API application features the following:

- Accept an HTML file upload.
- Analyze accessibility issues (e.g., `missing alt attributes`, `skipped heading levels`) using a rule-based algorithm.
- Return a JSON response with a compliance score and suggested fixes.

## Environment Setup

1. CD into the application root directory with your command prompt/terminal/git bash.
2. Run `cp .env.example .env` command to create a local environment configuration file.
3. Inside the `.env` file, setup database, mail and other configurations for `production` (optional for this project).
4. Run `composer install` to install the project dependencies in the `composer.json` file.
5. Run `php artisan key:generate` command to generates the application key.
6. Run `php artisan serve` or `php artisan serve --port=PORT_NUMBER` command to start a local development server.
7. Define additional routes in the `routes/api.php` file.
8. Run `composer dump-autoload` to generate new optimized autoload files (optional).


## Project Screenshots

![Screenshot 1](public/wcag-backend.png)
