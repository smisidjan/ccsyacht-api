# Authentication Endpoints

## Login

Authenticate user and receive access token.

**Endpoint:** `POST /api/auth/login`

**Authentication:** None required

**Request Body:**
```json
{
  "email": "admin@ccsyacht.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "AuthorizeAction",
  "actionStatus": "CompletedActionStatus",
  "result": {
    "@type": "Person",
    "identifier": 1,
    "name": "Admin",
    "email": "admin@ccsyacht.com",
    "roles": ["admin"]
  },
  "token": "1|abc123...",
  "tokenType": "Bearer"
}
```

**Error Response (422):**
```json
{
  "message": "The provided credentials are incorrect.",
  "errors": {
    "email": ["The provided credentials are incorrect."]
  }
}
```

---

## Logout

Revoke current access token.

**Endpoint:** `POST /api/auth/logout`

**Authentication:** Bearer token required

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "CompletedActionStatus",
  "description": "Successfully logged out"
}
```

---

## Get Current User

Get authenticated user profile.

**Endpoint:** `GET /api/auth/me`

**Authentication:** Bearer token required

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "identifier": 1,
  "name": "Admin",
  "email": "admin@ccsyacht.com",
  "emailVerified": true,
  "dateCreated": "2024-01-01T00:00:00+00:00",
  "dateModified": "2024-01-01T00:00:00+00:00",
  "roles": ["admin"]
}
```

---

## Change Password

Change authenticated user's password.

**Endpoint:** `POST /api/auth/change-password`

**Authentication:** Bearer token required

**Request Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "UpdateAction",
  "actionStatus": "CompletedActionStatus",
  "description": "Password changed successfully"
}
```

**Error Response (422):**
```json
{
  "message": "The current password is incorrect.",
  "errors": {
    "current_password": ["The current password is incorrect."]
  }
}
```
