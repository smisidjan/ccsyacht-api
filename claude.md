# CCS Yacht Backend Project

## Project Overview
Laravel 12 backend API for CCS Yacht management system.

## Tech Stack
- **Framework:** Laravel 12
- **PHP Version:** 8.2+
- **Database:** PostgreSQL
- **Authentication:** Laravel Sanctum (API tokens)
- **Authorization:** Spatie Laravel Permission
- **API Format:** JSON with schema.org compliance

## Coding Standards

### API Responses
- All API responses must return JSON
- Use Laravel API Resources for response formatting
- Follow schema.org standards for data schemas
- Always include appropriate HTTP status codes

### Authentication & Authorization
- Use Laravel Sanctum for API token authentication
- Three roles in the system:
  1. **admin** - Full access, auto-created on setup
  2. **hoofdgebruiker** - Full access (main user/owner)
  3. **uitnodigingsbeheerder** - Can manage invitations (accept/send)

### User Management Flow
1. **Invitation Flow:**
   - Admin/Hoofdgebruiker/Uitnodigingsbeheerder invites user via email
   - User receives email with invitation link
   - User can accept or decline
   - On accept: user creates account and can login

2. **Self-Registration Flow:**
   - New user requests account
   - Request sent to admin/hoofdgebruiker/uitnodigingsbeheerder
   - On approval: user receives confirmation email
   - On rejection: user receives rejection email

### Database Conventions
- Use snake_case for table and column names
- Always use migrations for schema changes
- Use foreign key constraints
- Soft deletes where appropriate

### Code Organization
- Controllers in `app/Http/Controllers/Api/`
- Form Requests in `app/Http/Requests/`
- API Resources in `app/Http/Resources/`
- Notifications in `app/Notifications/`
- Policies in `app/Policies/`

### Laravel Conventions
- Follow Laravel documentation and best practices
- Use built-in Laravel packages where possible
- Use Form Requests for validation
- Use Policies for authorization
- Use Notifications for emails
- Use Observers for model events if needed

### Testing
- Write feature tests for all API endpoints
- Use Laravel's built-in testing tools
- Test both happy paths and error cases

## Docker Setup (Recommended)

### Quick Start

```bash
# First time setup
make install

# Start the application
docker-compose up
```

### Services

| Service | URL | Description |
|---------|-----|-------------|
| API | http://localhost:8000 | Laravel API |
| Swagger | http://localhost:8000/api/documentation | API documentation |
| Mailpit | http://localhost:8025 | Email testing UI |
| PostgreSQL | localhost:5432 | Database |
| Redis | localhost:6379 | Cache & Queue |

### Docker Commands (via Makefile)

```bash
make build      # Build containers
make up         # Start all containers
make down       # Stop all containers
make restart    # Restart containers
make logs       # View logs
make shell      # Open shell in app container
make migrate    # Run migrations
make seed       # Seed database
make fresh      # Fresh migration with seed
make test       # Run tests
make swagger    # Generate Swagger docs
make tinker     # Open Laravel Tinker
```

### Docker Commands (direct)

```bash
# Run artisan commands
docker-compose exec app php artisan migrate

# Run composer
docker-compose exec app composer install

# View logs
docker-compose logs -f app
```

## Commands (without Docker)
- `php artisan serve` - Start development server
- `php artisan migrate` - Run migrations
- `php artisan db:seed` - Seed database (creates admin user)
- `php artisan test` - Run tests
- `composer dev` - Run development environment

## Environment
- Copy `.env.example` to `.env` for local development
- Configure mail settings for invitation/notification emails
- Configure database connection for PostgreSQL

### Docker Environment Variables

The following can be customized in `.env`:

```bash
APP_PORT=8000           # API port
DB_PORT=5432            # PostgreSQL port
REDIS_PORT=6379         # Redis port
MAILPIT_UI_PORT=8025    # Mailpit web UI
MAILPIT_SMTP_PORT=1025  # Mailpit SMTP
```

## API Documentation

### Documentation Location
- **Swagger UI:** Available at `/api/documentation` when running
- **Markdown Docs:** Located in `docs/api/` folder
- **OpenAPI Spec:** Generated from controller annotations

### Documentation Rules (IMPORTANT)

When creating, modifying, or deleting an API endpoint, you MUST update BOTH:

1. **OpenAPI Annotations** in the controller:
   - Add/update `#[OA\Get]`, `#[OA\Post]`, `#[OA\Put]`, `#[OA\Delete]` attributes
   - Include all parameters, request body, and response schemas
   - Use proper tags for grouping

2. **Markdown Documentation** in `docs/api/`:
   - Update the relevant endpoint documentation file
   - Include endpoint URL, method, authentication requirements
   - Document request/response examples
   - List all parameters and their types

### OpenAPI Annotation Template

```php
use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/endpoint',
    summary: 'Short description',
    description: 'Detailed description',
    tags: ['Tag Name'],
    security: [['bearerAuth' => []]], // If authentication required
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['field1', 'field2'],
            properties: [
                new OA\Property(property: 'field1', type: 'string'),
                new OA\Property(property: 'field2', type: 'integer')
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation error')
    ]
)]
```

### Regenerating Swagger Documentation

After making changes to OpenAPI annotations, regenerate the documentation:

```bash
php artisan l5-swagger:generate
```

### Documentation File Structure

```
docs/
└── api/
    ├── README.md              # Overview and index
    ├── authentication.md      # Auth endpoints
    ├── invitations.md         # Invitation endpoints
    ├── registration-requests.md # Registration request endpoints
    └── users.md               # User management endpoints
```

When adding a new feature area, create a new markdown file and link it in README.md.
