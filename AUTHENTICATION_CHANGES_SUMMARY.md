# Authentication System Changes Summary

This document summarizes all the changes made to implement a complete authentication system in the Symfony project, including API endpoints, email verification, reCAPTCHA integration, and password reset functionality.

## 1. src/Controller/ApiAuthController.php (New File)

**Purpose**: Handles REST API authentication endpoints for login, registration, and user info retrieval.

**Key Features Added**:
- `login()` method: Validates credentials, checks email verification, returns JWT-like session data
- `register()` method: Creates new users, sends verification email via Brevo
- `me()` method: Returns current user information if authenticated

**Lines Added**: Entire file (approximately 120 lines)

## 2. src/Entity/Utilisateur.php

**Purpose**: User entity with authentication fields including verification and password reset tokens.

**Changes Made**:

### Added Properties:
- Line ~50: `private ?string $verificationToken = null;`
- Line ~55: `private ?\DateTimeImmutable $verificationTokenExpiresAt = null;`
- Line ~60: `private ?bool $isVerified = false;`
- Line ~65: `private ?string $resetToken = null;`
- Line ~70: `private ?\DateTime $resetTokenExpiresAt = null;`

### Added Getters and Setters:
- Lines ~150-200: Added getter/setter methods for all new properties
- `getVerificationToken()`, `setVerificationToken()`
- `getVerificationTokenExpiresAt()`, `setVerificationTokenExpiresAt()`
- `isVerified()`, `setIsVerified()`
- `getResetToken()`, `setResetToken()`
- `getResetTokenExpiresAt()`, `setResetTokenExpiresAt()`

**What it does**: Extends the user entity to support email verification workflow and secure password reset tokens with expiration.

## 3. src/Controller/AuthController.php

**Purpose**: Manages web-based authentication with email verification, reCAPTCHA, and password reset.

**Changes Made**:

### Added Methods:

#### `login()` method (Lines ~30-120):
- Handles POST login requests
- Validates email format and required fields
- Verifies reCAPTCHA token
- Checks password (supports both plain text and bcrypt hashed)
- Ensures account is verified
- Sets session variables on success

#### `register()` method (Lines ~125-180):
- Handles user registration
- Validates input and reCAPTCHA
- Prevents admin role registration
- Sends verification email via Brevo
- Redirects with success message

#### `verifyEmail()` method (Lines ~185-200):
- Verifies email using token
- Updates user verification status
- Redirects to login

#### `forgotPassword()` method (Lines ~202-235):
- Generates secure reset token
- Sends password reset email
- Always shows success message (security by obscurity)

#### `resetPassword()` method (Lines ~237-280):
- Validates reset token and expiration
- Handles password reset form
- Updates password with bcrypt hashing
- Clears reset tokens after successful reset

### Added Helper Method:
#### `verifyRecaptcha()` method (Lines ~285-305):
- Validates reCAPTCHA tokens with Google API
- Returns boolean verification result

**What it does**: Provides complete web authentication flow with security measures (reCAPTCHA, email verification, secure password reset).

## 4. templates/client/login.html.twig

**Purpose**: Login page with reCAPTCHA and forgot password link.

**Changes Made**:

### Added reCAPTCHA Widget:
- Line ~20: `<div class="g-recaptcha" data-sitekey="{{ recaptcha_site_key }}"></div>`

### Added Forgot Password Link:
- Lines ~25-27:
```html
<p style="text-align:center; margin-top:10px; font-size:14px;">
    <a href="{{ path('forgot_password') }}" style="color:#0b7a8f; font-weight:600;">Mot de passe oublié ?</a>
</p>
```

### Updated Render Calls:
- All render calls now pass `'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? ''`

**What it does**: Enhances login security with bot protection and provides access to password recovery.

## 5. templates/client/register.html.twig

**Purpose**: Registration page with reCAPTCHA integration.

**Changes Made**:

### Added reCAPTCHA Widget:
- Line ~20: `<div class="g-recaptcha" data-sitekey="{{ recaptcha_site_key }}"></div>`

### Updated Render Calls:
- All render calls now pass `'recaptcha_site_key' => $_ENV['GOOGLE_RECAPTCHA_SITE_KEY'] ?? ''`

**What it does**: Prevents automated registrations with reCAPTCHA verification.

## 6. templates/client/forgot_password.html.twig (New File)

**Purpose**: Form for requesting password reset emails.

**Key Features**:
- Email input field
- Error/success message display (with defined checks)
- Consistent styling with other auth pages
- Link back to login

**Lines Added**: Entire file (approximately 60 lines)

**What it does**: Allows users to initiate password reset by entering their email address.

## 7. templates/client/reset_password.html.twig (New File)

**Purpose**: Form for setting new passwords after reset.

**Key Features**:
- Password and confirm password fields
- Password visibility toggles
- Error display (handles array of errors)
- Hidden token field
- Link back to login

**Lines Added**: Entire file (approximately 80 lines)

**What it does**: Securely allows users to set new passwords using reset tokens.

## 8. Database Schema Updates

**Changes Made**:

### Added Columns to `utilisateur` table:
- `verification_token` VARCHAR(255) DEFAULT NULL
- `verification_token_expires_at` DATETIME DEFAULT NULL
- `is_verified` TINYINT(1) DEFAULT 0
- `reset_token` VARCHAR(255) DEFAULT NULL
- `reset_token_expires_at` DATETIME DEFAULT NULL

**Executed Command**: `php bin/console doctrine:schema:update --force`

**What it does**: Adds necessary database fields to support email verification and password reset workflows.

## 9. Environment Configuration (.env.local)

**Keys Added**:
- `GOOGLE_RECAPTCHA_SITE_KEY` - For reCAPTCHA widget display
- `GOOGLE_RECAPTCHA_SECRET` - For server-side verification
- `BREVO_API_KEY` - For email sending via Brevo/Sendinblue

**What it does**: Configures external service integrations for security and email functionality.

## Security Features Implemented

1. **reCAPTCHA v2**: Prevents automated attacks on login and registration
2. **Email Verification**: Ensures valid email addresses and prevents spam accounts
3. **Password Hashing**: Uses bcrypt for secure password storage
4. **Token-based Reset**: Secure password recovery with time-limited tokens
5. **Session Management**: Manual session handling for authentication state
6. **Input Validation**: Server-side validation for all user inputs
7. **Admin Protection**: Prevents user registration as admin role

## Email Integration

- **Service**: Brevo (Sendinblue)
- **Features**: Account verification emails, password reset emails
- **Templates**: HTML emails with secure links and expiration notices

## API Endpoints

- `POST /api/login` - User authentication
- `POST /api/register` - User registration
- `GET /api/me` - Current user info (requires authentication)

## Web Routes

- `/login` - Login page
- `/register` - Registration page
- `/verify-email/{token}` - Email verification
- `/forgot-password` - Password reset request
- `/reset-password/{token}` - Password reset form

All changes maintain consistency with the existing codebase styling and follow Symfony best practices for security and user experience.