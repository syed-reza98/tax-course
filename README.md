# Tax Course Creation Platform

A web application for creating structured, multi-module courses with support for nested content items. Built with Laravel 12 (backend) and standard frontend technologies (HTML, CSS, JavaScript, jQuery).

## Features

- Create courses with multiple modules
- Add unlimited nested content to modules
- Dynamic form handling with add/remove actions
- Frontend and backend validation
- RESTful API for course management
- Responsive modern UI
- Database storage with proper relationships
- Test coverage and automated checks
- Error handling and validation
- Performance optimizations (eager loading where appropriate)

## Technology Stack

- Backend: Laravel 12 (PHP 8.3.x)
- Frontend: HTML5, CSS3, JavaScript, jQuery 3.7.1
- Database: SQLite (configurable for MySQL/PostgreSQL)
- Testing: PHPUnit with Laravel testing utilities

## Quick Start / Installation

1. Clone the repository:
```bash
git clone https://github.com/syed-reza98/tax-course.git
cd tax-course
```

2. Install PHP dependencies and frontend tooling:
```bash
composer install
npm install
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Run database migrations:
```bash
php artisan migrate
```

5. Build frontend assets (if applicable) and start the development server:
```bash
npm run build   # optional, depending on project setup
php artisan serve
```

6. Open the app at http://localhost:8000

## Usage (UI)

- Navigate to the course creation page.
- Enter course title and description.
- Add modules with titles and descriptions.
- Add content items to each module and use the "+ Add Nested" button to create nested content.
- Submit the form to persist the course and its structure.

## API Endpoints

- GET /api/courses — List all courses
- POST /api/courses — Create a new course
- GET /api/courses/{id} — Retrieve a specific course
- PUT /api/courses/{id} — Update a course
- DELETE /api/courses/{id} — Delete a course

Example: create a course via curl
```bash
curl -X POST http://localhost:8000/api/courses \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Tax Fundamentals",
    "description": "Introduction to taxation",
    "modules": [{
      "title": "Module 1",
      "description": "Getting started",
      "contents": [{
        "title": "What is Tax?",
        "type": "text",
        "body": "Tax is a mandatory financial charge...",
        "children": [{
          "title": "Types of Taxes",
          "type": "text",
          "body": "Income tax, sales tax..."
        }]
      }]
    }]
  }'
```

## Testing & Formatting

Run the test suite:
```bash
php artisan test
```

Format code:
```bash
./vendor/bin/pint
```

## Database Structure

Courses table:
- id, title, description, timestamps

Modules table:
- id, course_id, title, description, order, timestamps

Contents table:
- id, module_id, parent_id (for nesting), title, body, type (text, video, document, quiz), order, timestamps

## License

This project uses the MIT license. The Laravel framework is licensed under MIT as well.

