# Vitalytics Mobile Authentication API Specification

**Version:** 1.0
**Base URL:** `https://your-vitalytics-server.com/api/v1/auth`
**Last Updated:** February 2026

---

## Overview

This API provides authentication endpoints for native mobile applications using Bearer token authentication (Laravel Sanctum). Tokens do not expire and remain valid until explicitly revoked via logout.

---

## Authentication

After login, include the token in all authenticated requests:

```
Authorization: Bearer {token}
```

---

## Endpoints

### 1. Login

Authenticate a user and receive an access token.

**Endpoint:** `POST /login`
**Authentication:** None

#### Request

```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "userpassword",
  "device_name": "iPhone 15 Pro"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string | Yes | User's email address |
| `password` | string | Yes | User's password |
| `device_name` | string | No | Device identifier for token management (defaults to "Mobile App") |

#### Success Response (200 OK)

```json
{
  "success": true,
  "token": "1|a1b2c3d4e5f6g7h8i9j0...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "email_verified_at": "2026-01-15T10:30:00+00:00",
    "is_admin": false,
    "has_health_access": true,
    "has_analytics_access": true,
    "accessible_products": ["myapp", "myapp"],
    "created_at": "2026-01-01T00:00:00+00:00"
  }
}
```

#### Error Responses

**401 Unauthorized - Invalid Credentials**
```json
{
  "success": false,
  "message": "The provided credentials are incorrect.",
  "code": "INVALID_CREDENTIALS"
}
```

**429 Too Many Requests - Rate Limited**
```json
{
  "success": false,
  "message": "Too many login attempts. Please try again in 45 seconds.",
  "code": "RATE_LIMITED",
  "retry_after": 45
}
```

**422 Unprocessable Entity - Validation Error**
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."],
    "password": ["The password field is required."]
  }
}
```

---

### 2. Get User Profile

Retrieve the authenticated user's profile.

**Endpoint:** `GET /user`
**Authentication:** Required

#### Request

```http
GET /api/v1/auth/user
Authorization: Bearer {token}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "email_verified_at": "2026-01-15T10:30:00+00:00",
    "is_admin": false,
    "has_health_access": true,
    "has_analytics_access": true,
    "accessible_products": ["myapp", "myapp"],
    "created_at": "2026-01-01T00:00:00+00:00"
  }
}
```

#### Error Response

**401 Unauthorized - Invalid/Missing Token**
```json
{
  "message": "Unauthenticated."
}
```

---

### 3. Logout

Revoke the current access token.

**Endpoint:** `POST /logout`
**Authentication:** Required

#### Request

```http
POST /api/v1/auth/logout
Authorization: Bearer {token}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Successfully logged out."
}
```

---

### 4. Logout from All Devices

Revoke all access tokens for the user (logs out everywhere).

**Endpoint:** `POST /logout-all`
**Authentication:** Required

#### Request

```http
POST /api/v1/auth/logout-all
Authorization: Bearer {token}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Successfully logged out from all devices."
}
```

---

### 5. Refresh Token

Rotate the current token (revokes old token, issues new one). Use this periodically for enhanced security.

**Endpoint:** `POST /refresh`
**Authentication:** Required

#### Request

```http
POST /api/v1/auth/refresh
Authorization: Bearer {token}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "token": "2|x9y8z7w6v5u4t3s2r1q0..."
}
```

> **Note:** After refresh, the old token is invalidated. Update stored token immediately.

---

### 6. Forgot Password

Request a password reset email.

**Endpoint:** `POST /forgot-password`
**Authentication:** None

#### Request

```http
POST /api/v1/auth/forgot-password
Content-Type: application/json

{
  "email": "user@example.com"
}
```

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "If an account exists with that email, a password reset link has been sent."
}
```

> **Note:** This endpoint always returns success to prevent email enumeration attacks. The user should check their email inbox.

#### Error Response

**429 Too Many Requests - Rate Limited**
```json
{
  "success": false,
  "message": "Too many password reset requests. Please try again in 180 seconds.",
  "code": "RATE_LIMITED",
  "retry_after": 180
}
```

---

### 7. Reset Password

Reset the user's password using the token from the email link.

**Endpoint:** `POST /reset-password`
**Authentication:** None

#### Request

```http
POST /api/v1/auth/reset-password
Content-Type: application/json

{
  "email": "user@example.com",
  "token": "a1b2c3d4e5f6...",
  "password": "newSecurePassword123",
  "password_confirmation": "newSecurePassword123"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `email` | string | Yes | User's email address |
| `token` | string | Yes | Reset token from email link |
| `password` | string | Yes | New password (min 8 characters) |
| `password_confirmation` | string | Yes | Must match `password` |

#### Success Response (200 OK)

```json
{
  "success": true,
  "message": "Your password has been reset. Please login with your new password."
}
```

> **Note:** All existing tokens are revoked when password is reset. User must login again.

#### Error Responses

**422 Unprocessable Entity - Invalid Token**
```json
{
  "success": false,
  "message": "This password reset token is invalid or has expired.",
  "code": "PASSWORD_RESET_FAILED"
}
```

**422 Unprocessable Entity - Validation Error**
```json
{
  "message": "The password field confirmation does not match.",
  "errors": {
    "password": ["The password field confirmation does not match."]
  }
}
```

---

## Password Reset Flow

The password reset uses a web-based flow:

1. App calls `POST /forgot-password` with user's email
2. User receives email with link: `https://your-vitalytics-server.com/reset-password/{token}?email={email}`
3. **Option A (Recommended):** Open link in Safari/WebView, user resets password on web, then returns to app to login
4. **Option B:** Parse the token from the URL (via deep link) and call `POST /reset-password` from the app

For Option B, register a URL scheme or universal link to intercept the reset URL.

---

## Rate Limiting

| Endpoint | Limit | Window |
|----------|-------|--------|
| `POST /login` | 5 attempts | Per email+IP combination |
| `POST /forgot-password` | 3 requests | 5 minutes per email |

When rate limited, the response includes `retry_after` (seconds until reset).

---

## Token Storage

Store the token securely using iOS Keychain:

```swift
import Security

class TokenStorage {
    private static let service = "app.vitalytics.auth"
    private static let account = "access_token"

    static func save(token: String) -> Bool {
        let data = token.data(using: .utf8)!

        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecValueData as String: data
        ]

        SecItemDelete(query as CFDictionary)
        let status = SecItemAdd(query as CFDictionary, nil)
        return status == errSecSuccess
    }

    static func retrieve() -> String? {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account,
            kSecReturnData as String: true,
            kSecMatchLimit as String: kSecMatchLimitOne
        ]

        var result: AnyObject?
        let status = SecItemCopyMatching(query as CFDictionary, &result)

        guard status == errSecSuccess,
              let data = result as? Data,
              let token = String(data: data, encoding: .utf8) else {
            return nil
        }
        return token
    }

    static func delete() -> Bool {
        let query: [String: Any] = [
            kSecClass as String: kSecClassGenericPassword,
            kSecAttrService as String: service,
            kSecAttrAccount as String: account
        ]
        let status = SecItemDelete(query as CFDictionary)
        return status == errSecSuccess || status == errSecItemNotFound
    }
}
```

---

## Swift Implementation Example

### API Client

```swift
import Foundation

enum AuthError: Error {
    case invalidCredentials
    case rateLimited(retryAfter: Int)
    case networkError(Error)
    case invalidResponse
    case unauthorized
}

struct AuthResponse: Codable {
    let success: Bool
    let token: String?
    let user: User?
    let message: String?
    let code: String?
    let retryAfter: Int?

    enum CodingKeys: String, CodingKey {
        case success, token, user, message, code
        case retryAfter = "retry_after"
    }
}

struct User: Codable {
    let id: Int
    let name: String
    let email: String
    let emailVerifiedAt: String?
    let isAdmin: Bool
    let hasHealthAccess: Bool
    let hasAnalyticsAccess: Bool
    let accessibleProducts: [String]
    let createdAt: String

    enum CodingKeys: String, CodingKey {
        case id, name, email
        case emailVerifiedAt = "email_verified_at"
        case isAdmin = "is_admin"
        case hasHealthAccess = "has_health_access"
        case hasAnalyticsAccess = "has_analytics_access"
        case accessibleProducts = "accessible_products"
        case createdAt = "created_at"
    }
}

class VitalyticsAuth {
    static let shared = VitalyticsAuth()

    private let baseURL = "https://your-vitalytics-server.com/api/v1/auth"
    private var currentUser: User?

    var isLoggedIn: Bool {
        return TokenStorage.retrieve() != nil
    }

    var user: User? {
        return currentUser
    }

    // MARK: - Login

    func login(email: String, password: String, deviceName: String? = nil) async throws -> User {
        let url = URL(string: "\(baseURL)/login")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        var body: [String: String] = [
            "email": email,
            "password": password
        ]
        if let deviceName = deviceName {
            body["device_name"] = deviceName
        } else {
            body["device_name"] = UIDevice.current.name
        }

        request.httpBody = try JSONEncoder().encode(body)

        let (data, response) = try await URLSession.shared.data(for: request)

        guard let httpResponse = response as? HTTPURLResponse else {
            throw AuthError.invalidResponse
        }

        let authResponse = try JSONDecoder().decode(AuthResponse.self, from: data)

        switch httpResponse.statusCode {
        case 200:
            guard let token = authResponse.token, let user = authResponse.user else {
                throw AuthError.invalidResponse
            }
            _ = TokenStorage.save(token: token)
            currentUser = user
            return user

        case 401:
            throw AuthError.invalidCredentials

        case 429:
            throw AuthError.rateLimited(retryAfter: authResponse.retryAfter ?? 60)

        default:
            throw AuthError.invalidResponse
        }
    }

    // MARK: - Logout

    func logout() async throws {
        guard let token = TokenStorage.retrieve() else { return }

        let url = URL(string: "\(baseURL)/logout")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        _ = try? await URLSession.shared.data(for: request)

        _ = TokenStorage.delete()
        currentUser = nil
    }

    // MARK: - Get User

    func fetchUser() async throws -> User {
        guard let token = TokenStorage.retrieve() else {
            throw AuthError.unauthorized
        }

        let url = URL(string: "\(baseURL)/user")!
        var request = URLRequest(url: url)
        request.httpMethod = "GET"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        let (data, response) = try await URLSession.shared.data(for: request)

        guard let httpResponse = response as? HTTPURLResponse else {
            throw AuthError.invalidResponse
        }

        if httpResponse.statusCode == 401 {
            _ = TokenStorage.delete()
            currentUser = nil
            throw AuthError.unauthorized
        }

        let authResponse = try JSONDecoder().decode(AuthResponse.self, from: data)

        guard let user = authResponse.user else {
            throw AuthError.invalidResponse
        }

        currentUser = user
        return user
    }

    // MARK: - Forgot Password

    func forgotPassword(email: String) async throws {
        let url = URL(string: "\(baseURL)/forgot-password")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        let body = ["email": email]
        request.httpBody = try JSONEncoder().encode(body)

        let (data, response) = try await URLSession.shared.data(for: request)

        guard let httpResponse = response as? HTTPURLResponse else {
            throw AuthError.invalidResponse
        }

        if httpResponse.statusCode == 429 {
            let authResponse = try JSONDecoder().decode(AuthResponse.self, from: data)
            throw AuthError.rateLimited(retryAfter: authResponse.retryAfter ?? 180)
        }
    }

    // MARK: - Refresh Token

    func refreshToken() async throws -> String {
        guard let token = TokenStorage.retrieve() else {
            throw AuthError.unauthorized
        }

        let url = URL(string: "\(baseURL)/refresh")!
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("Bearer \(token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Accept")

        let (data, response) = try await URLSession.shared.data(for: request)

        guard let httpResponse = response as? HTTPURLResponse else {
            throw AuthError.invalidResponse
        }

        if httpResponse.statusCode == 401 {
            _ = TokenStorage.delete()
            throw AuthError.unauthorized
        }

        let authResponse = try JSONDecoder().decode(AuthResponse.self, from: data)

        guard let newToken = authResponse.token else {
            throw AuthError.invalidResponse
        }

        _ = TokenStorage.save(token: newToken)
        return newToken
    }
}
```

### Usage Example

```swift
// Login
Task {
    do {
        let user = try await VitalyticsAuth.shared.login(
            email: "user@example.com",
            password: "password123"
        )
        print("Logged in as: \(user.name)")
    } catch AuthError.invalidCredentials {
        showAlert("Invalid email or password")
    } catch AuthError.rateLimited(let retryAfter) {
        showAlert("Too many attempts. Try again in \(retryAfter) seconds.")
    } catch {
        showAlert("Login failed: \(error.localizedDescription)")
    }
}

// Check login state on app launch
Task {
    if VitalyticsAuth.shared.isLoggedIn {
        do {
            let user = try await VitalyticsAuth.shared.fetchUser()
            // Navigate to main app
        } catch AuthError.unauthorized {
            // Token invalid, show login screen
        }
    }
}

// Logout
Task {
    try await VitalyticsAuth.shared.logout()
    // Navigate to login screen
}

// Forgot password
Task {
    do {
        try await VitalyticsAuth.shared.forgotPassword(email: "user@example.com")
        showAlert("Check your email for reset instructions")
    } catch AuthError.rateLimited(let retryAfter) {
        showAlert("Please wait \(retryAfter) seconds before requesting again")
    }
}
```

---

## Error Handling Summary

| HTTP Status | Code | Meaning |
|-------------|------|---------|
| 200 | - | Success |
| 401 | `INVALID_CREDENTIALS` | Wrong email/password |
| 401 | `Unauthenticated.` | Missing/invalid token |
| 422 | - | Validation error (check `errors` object) |
| 422 | `PASSWORD_RESET_FAILED` | Invalid/expired reset token |
| 429 | `RATE_LIMITED` | Too many requests |

---

## Checklist for iOS Implementation

- [ ] Implement login with email/password
- [ ] Store token securely in Keychain
- [ ] Add Authorization header to all authenticated requests
- [ ] Handle 401 responses by clearing token and showing login
- [ ] Implement logout (clear local token + call API)
- [ ] Implement forgot password flow
- [ ] Handle rate limiting with retry UI
- [ ] Check for existing token on app launch
- [ ] Fetch user profile to validate token on launch

---

## Contact

For API issues or questions, contact the backend team.
