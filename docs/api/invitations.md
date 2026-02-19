# Invitation Endpoints

## List Invitations

Get paginated list of invitations.

**Endpoint:** `GET /api/invitations`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status: `pending`, `accepted`, `declined`, `expired` |
| page | integer | No | Page number for pagination |

**Response (200):**
```json
{
  "data": [
    {
      "@context": "https://schema.org",
      "@type": "InviteAction",
      "identifier": 1,
      "recipient": {
        "@type": "Person",
        "email": "newuser@example.com"
      },
      "agent": {
        "@type": "Person",
        "identifier": 1,
        "name": "Admin",
        "email": "admin@ccsyacht.com"
      },
      "actionStatus": "PotentialActionStatus",
      "role": "uitnodigingsbeheerder",
      "dateCreated": "2024-01-01T00:00:00+00:00",
      "expires": "2024-01-08T00:00:00+00:00",
      "isExpired": false
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

## Send Invitation

Send an invitation to a new user.

**Endpoint:** `POST /api/invitations`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder`

**Request Body:**
```json
{
  "email": "newuser@example.com",
  "role": "uitnodigingsbeheerder"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| email | string | Yes | Email address of the user to invite |
| role | string | Yes | Role to assign: `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder` |

**Response (201):**
```json
{
  "@context": "https://schema.org",
  "@type": "InviteAction",
  "identifier": 1,
  "recipient": {
    "@type": "Person",
    "email": "newuser@example.com"
  },
  "actionStatus": "PotentialActionStatus",
  "role": "uitnodigingsbeheerder",
  "dateCreated": "2024-01-01T00:00:00+00:00",
  "expires": "2024-01-08T00:00:00+00:00"
}
```

---

## Get Invitation by Token

Get invitation details by token (public endpoint for invited users).

**Endpoint:** `GET /api/invitations/{token}`

**Authentication:** None required

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "InviteAction",
  "identifier": 1,
  "recipient": {
    "@type": "Person",
    "email": "newuser@example.com"
  },
  "role": "uitnodigingsbeheerder",
  "actionStatus": "PotentialActionStatus",
  "isValid": true,
  "isExpired": false,
  "expires": "2024-01-08T00:00:00+00:00",
  "invitedBy": "Admin"
}
```

---

## Accept Invitation

Accept an invitation and create user account.

**Endpoint:** `POST /api/invitations/accept`

**Authentication:** None required

**Request Body:**
```json
{
  "token": "abc123...",
  "name": "John Doe",
  "password": "password123",
  "password_confirmation": "password123"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| token | string | Yes | Invitation token |
| name | string | Yes | Full name of the user |
| password | string | Yes | Password (min 8 characters) |
| password_confirmation | string | Yes | Password confirmation |

**Response (201):**
```json
{
  "@context": "https://schema.org",
  "@type": "AuthorizeAction",
  "actionStatus": "CompletedActionStatus",
  "result": {
    "@type": "Person",
    "identifier": 2,
    "name": "John Doe",
    "email": "newuser@example.com",
    "roles": ["uitnodigingsbeheerder"]
  },
  "token": "2|xyz789...",
  "tokenType": "Bearer"
}
```

**Error Response (400) - Expired:**
```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "FailedActionStatus",
  "error": "This invitation has expired."
}
```

---

## Decline Invitation

Decline an invitation.

**Endpoint:** `POST /api/invitations/decline`

**Authentication:** None required

**Request Body:**
```json
{
  "token": "abc123..."
}
```

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "RejectAction",
  "actionStatus": "CompletedActionStatus",
  "description": "Invitation declined successfully."
}
```

---

## Resend Invitation

Resend an invitation email and extend expiry.

**Endpoint:** `POST /api/invitations/{invitation}/resend`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder`

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "SendAction",
  "actionStatus": "CompletedActionStatus",
  "description": "Invitation resent successfully."
}
```

**Error Response (400):**
```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "FailedActionStatus",
  "error": "Can only resend pending invitations."
}
```

---

## Cancel Invitation

Cancel a pending invitation.

**Endpoint:** `DELETE /api/invitations/{invitation}`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder`

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "CancelAction",
  "actionStatus": "CompletedActionStatus",
  "description": "Invitation cancelled successfully."
}
```

**Error Response (400):**
```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "FailedActionStatus",
  "error": "Can only cancel pending invitations."
}
```
