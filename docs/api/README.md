# CCS Yacht API Documentation

## Overview

This is the API documentation for the CCS Yacht management system. All endpoints return JSON responses following schema.org standards.

## Base URL

```
/api
```

## Authentication

The API uses Bearer token authentication via Laravel Sanctum. Include the token in the Authorization header:

```
Authorization: Bearer {token}
```

## Swagger UI

Interactive API documentation is available at:
```
/api/documentation
```

## Endpoints

- [Authentication](authentication.md)
- [Invitations](invitations.md)
- [Registration Requests](registration-requests.md)
- [Users](users.md)

## Roles

| Role | Description | Permissions |
|------|-------------|-------------|
| `admin` | Administrator | Full access to all features |
| `hoofdgebruiker` | Main user | Full access to all features |
| `uitnodigingsbeheerder` | Invitation manager | Can manage invitations and registration requests |

## Response Format

All responses follow schema.org standards:

```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "identifier": 1,
  "name": "John Doe",
  "email": "john@example.com"
}
```

## Error Responses

Errors follow this format:

```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "FailedActionStatus",
  "error": "Error message here"
}
```

## HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |
