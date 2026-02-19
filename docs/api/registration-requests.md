# Registration Request Endpoints

## Submit Registration Request (Self-Register)

Submit a self-registration request (public endpoint).

**Endpoint:** `POST /api/auth/register`

**Authentication:** None required

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "message": "I would like to join the platform."
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Full name |
| email | string | Yes | Email address |
| message | string | No | Optional message explaining why they want to join |

**Response (201):**
```json
{
  "@context": "https://schema.org",
  "@type": "RegisterAction",
  "identifier": 1,
  "agent": {
    "@type": "Person",
    "name": "John Doe",
    "email": "john@example.com"
  },
  "description": "I would like to join the platform.",
  "actionStatus": "PotentialActionStatus",
  "dateCreated": "2024-01-01T00:00:00+00:00"
}
```

**Note:** This sends a notification email to all admins, hoofdgebruikers, and uitnodigingsbeheerders.

---

## List Registration Requests

Get paginated list of registration requests.

**Endpoint:** `GET /api/registration-requests`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder`

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| status | string | No | Filter by status: `pending`, `approved`, `rejected` |
| page | integer | No | Page number for pagination |

**Response (200):**
```json
{
  "data": [
    {
      "@context": "https://schema.org",
      "@type": "RegisterAction",
      "identifier": 1,
      "agent": {
        "@type": "Person",
        "name": "John Doe",
        "email": "john@example.com"
      },
      "description": "I would like to join the platform.",
      "actionStatus": "PotentialActionStatus",
      "dateCreated": "2024-01-01T00:00:00+00:00",
      "dateProcessed": null,
      "processedBy": null,
      "rejectionReason": null
    }
  ],
  "links": {...},
  "meta": {...}
}
```

---

## Get Registration Request

Get registration request details.

**Endpoint:** `GET /api/registration-requests/{registrationRequest}`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder`

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "RegisterAction",
  "identifier": 1,
  "agent": {
    "@type": "Person",
    "name": "John Doe",
    "email": "john@example.com"
  },
  "description": "I would like to join the platform.",
  "actionStatus": "PotentialActionStatus",
  "dateCreated": "2024-01-01T00:00:00+00:00",
  "dateProcessed": null,
  "processedBy": null,
  "rejectionReason": null
}
```

---

## Process Registration Request

Approve or reject a registration request.

**Endpoint:** `POST /api/registration-requests/{registrationRequest}/process`

**Authentication:** Bearer token required

**Required Roles:** `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder`

### Approve Request

**Request Body:**
```json
{
  "action": "approve",
  "role": "uitnodigingsbeheerder"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| action | string | Yes | Must be `approve` |
| role | string | Yes (for approve) | Role to assign: `admin`, `hoofdgebruiker`, `uitnodigingsbeheerder` |

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "ApproveAction",
  "actionStatus": "CompletedActionStatus",
  "description": "Registration request approved successfully.",
  "result": {
    "@context": "https://schema.org",
    "@type": "RegisterAction",
    "identifier": 1,
    "actionStatus": "CompletedActionStatus",
    "dateProcessed": "2024-01-02T00:00:00+00:00",
    "processedBy": {
      "@type": "Person",
      "identifier": 1,
      "name": "Admin"
    }
  }
}
```

**Note:** On approval, the user receives an email with their temporary password.

### Reject Request

**Request Body:**
```json
{
  "action": "reject",
  "rejection_reason": "We are not accepting new users at this time."
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| action | string | Yes | Must be `reject` |
| rejection_reason | string | No | Optional reason for rejection |

**Response (200):**
```json
{
  "@context": "https://schema.org",
  "@type": "RejectAction",
  "actionStatus": "CompletedActionStatus",
  "description": "Registration request rejected.",
  "result": {
    "@context": "https://schema.org",
    "@type": "RegisterAction",
    "identifier": 1,
    "actionStatus": "FailedActionStatus",
    "dateProcessed": "2024-01-02T00:00:00+00:00",
    "rejectionReason": "We are not accepting new users at this time."
  }
}
```

**Note:** On rejection, the user receives an email notification.

---

## Error Response (400) - Already Processed

```json
{
  "@context": "https://schema.org",
  "@type": "Action",
  "actionStatus": "FailedActionStatus",
  "error": "This registration request has already been processed."
}
```
