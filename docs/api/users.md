# User Endpoints

## List Users

Get paginated list of users.

**Endpoint:** `GET /api/users`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| role | string | No | Filter by role: `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder` |
| search | string | No | Search by name or email |
| page | integer | No | Page number for pagination |

**Response (200):**
```json
{
  "data": [
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
  ],
  "links": {...},
  "meta": {...}
}
```

---

## Get User

Get user details.

**Endpoint:** `GET /api/users/{user}`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`

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

## Update User

Update user details.

**Endpoint:** `PUT /api/users/{user}`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "role": "hoofdgebruiker"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | No | User's full name |
| email | string | No | User's email address |
| role | string | No | New role: `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder` |

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "Person",
  "identifier": 2,
  "name": "John Doe",
  "email": "john@example.com",
  "emailVerified": true,
  "dateCreated": "2024-01-01T00:00:00+00:00",
  "dateModified": "2024-01-02T00:00:00+00:00",
  "roles": ["hoofdgebruiker"]
}
```

---

## Delete User

Delete a user.

**Endpoint:** `DELETE /api/users/{user}`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`

**Restrictions:**
- Cannot delete your own account
- Cannot delete the last admin user

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "DeleteAction",
  "actionStatus": "CompletedActionStatus",
  "description": "User deleted successfully."
}
```

**Error Response (400) - Self Delete:**
```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "FailedActionStatus",
  "error": "You cannot delete your own account."
}
```

**Error Response (400) - Last Admin:**
```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "FailedActionStatus",
  "error": "Cannot delete the last admin user."
}
```
