# Campus Reserve Capstone Guide

## 1. Project Overview

Campus Reserve is a Laravel-based reservation system that allows authenticated users to book facilities, manage reservations, receive notifications, and for admins to approve or reject reservation requests.

Key responsibilities:
- Authentication + 2FA
- Reservation creation, editing, listing, and deletion
- Calendar view of reservations
- Notifications for approvals/rejections
- Admin approval workflow
- Secure storage of saved credentials and user profile data
- Centralized frontend layout using Tailwind CSS

---

## 2. Core Files and Folders

### Main route entrypoint
- `routes/web.php`
  - Defines all web routes for the app
  - Contains routes for public pages, auth, reservation pages, notifications, and admin portal

### Controllers
- `app/Http/Controllers/AuthController.php`
  - Handles login, signup, logout, admin login, Google OAuth, 2FA issuance/verification, profile update
- `app/Http/Controllers/CalendarController.php`
  - Exposes API endpoint to fetch reservation events for the calendar
  - Stores new reservations from the calendar page
- `app/Http/Controllers/NotificationController.php`
  - Loads notification list for the user
  - Marks all notifications as read
  - Deletes selected notifications and clears associated pending requests
- `app/Http/Controllers/AdminController.php`
  - Shows admin dashboard
  - Approves, rejects, deletes reservations
  - Updates user roles and deletes users
- `app/Http/Controllers/FacilityController.php`
  - Provides facility list API used by the reservation form
- `app/Http/Controllers/CredentialController.php`
  - Handles CRUD for saved credentials/password manager

### Models
- `app/Models/User.php`
  - User authentication model
  - Encrypts email, name, phone, and stores 2FA state
  - Defines `savedCredentials()` relationship
- `app/Models/Reservation.php`
  - Reservation record model
  - Belongs to `User` and `Facility`
  - Contains status helper and scopes for upcoming/completed reservations
- `app/Models/Facility.php`
  - Facility model with availability check
  - Defines `reservations()` and `active()` scope
- `app/Models/SavedCredential.php`
  - Encrypts saved credential fields like site URL, username, password, and notes
- `app/Models/AuditLog.php`
  - Tracks admin actions such as approval/rejection/deletion

### Views
- `resources/views/layouts/app.blade.php`
  - Shared layout wrapper for page header, navigation, and scripts
- `resources/views/reserve.blade.php`
  - Reserve page listing user reservations and actions
- `resources/views/reservations/edit.blade.php`
  - Reservation edit form
- `resources/views/calendar.blade.php`
  - Calendar display page, loaded only by authenticated users
- `resources/views/notifications/index.blade.php`
  - Notifications page with mark-read/delete actions
- `resources/views/auth/*.blade.php`
  - Login, signup, admin login, 2FA views
- `resources/views/admin/dashboard.blade.php`
  - Admin dashboard interface

### Non-PHP files
- `.env.example`
  - Defines local environment variables used by Laravel
  - Includes database, session, cache, mail, and queue driver settings
- `resources/js/bootstrap.js`
  - Configures Axios for AJAX requests from the frontend
- `resources/js/app.js`
  - Main frontend entrypoint loaded by Vite
- `resources/css/app.css`
  - Tailwind CSS import and source mapping for asset compilation
- `vite.config.js`
  - Defines Vite plugin setup and local dev server configuration

### Database schema and migrations
- `database/migrations/0001_01_01_000000_create_users_table.php`
  - Creates `users` table with encrypted user data and authentication fields
- `database/migrations/2024_01_03_000000_create_facilities_table.php`
  - Creates `facilities`
- `database/migrations/2024_01_04_000000_create_reservations_table.php`
  - Creates `reservations`
- `database/migrations/2026_04_18_000000_create_notifications_table.php`
  - Creates Laravel notifications table
- `database/migrations/2026_04_17_000000_add_is_admin_to_users_and_create_audit_logs_table.php`
  - Adds admin flags and audit log table
- `database/migrations/2026_04_12_000000_add_profile_phone_and_2fa_settings_to_users_table.php`
  - Adds profile phone and 2FA settings
- `database/migrations/2026_03_15_000001_add_is_active_to_facilities_table.php`
  - Adds facility active flag

---

## 3. What happens when a user logs in

1. User visits `/login`
2. `routes/web.php` sends request to `AuthController@showLogin`
3. On POST `/login`, `AuthController@login` validates credentials
4. If valid and 2FA is enabled, user is sent an OTP code and session state is set
5. User completes `/2fa` verification or is logged in directly if 2FA is disabled
6. After login, user is redirected to `/reserve`

Important auth logic location:
- `app/Http/Controllers/AuthController.php`
  - `login()`
  - `issueOtp()`
  - `showAdminLogin()`
  - `loginAdmin()`
  - `show2faVerify()` and `verify2fa()`

### Notes for defense
- The system stores email, name, phone encrypted in the database using Laravel `Crypt`
- It stores `email_hash` for login lookups without revealing raw emails
- `2fa_code` is hashed, and expiry is stored in `2fa_expires_at`

---

## 4. Reservation flow

### Create/submit a reservation
- Calendar page `/calendar` loads events via `/api/calendar/events`
- `CalendarController@events` returns reservations formatted for FullCalendar
- New reservation POST goes to `/api/calendar/reserve`
- `CalendarController@store` validates the request
- It checks facility availability via `Facility::isAvailable()`
- It stores the reservation in `reservations` with `status = 'pending'`
- It notifies admins using `ReservationPendingApproval`

### Edit or delete reservation
- `/reserve/reservations/{reservation}/edit` shows the edit form
- Update uses `PUT /reserve/reservations/{reservation}` inside route closures in `routes/web.php`
- Delete uses `DELETE /reserve/reservations/{reservation}`
- Bulk delete uses `/reserve/reservations/delete-selected`

### Relevant files
- `routes/web.php`
  - Reserve routes and inline update/delete logic
- `app/Http/Controllers/CalendarController.php`
  - Event fetching and reservation storage
- `app/Models/Facility.php`
  - `isAvailable()` helper that prevents overlapping bookings
- `app/Models/Reservation.php`
  - Casts reservation date/times and holds status logic

---

## 5. Calendar and event rendering

Calendar page uses FullCalendar and fetches events from:
- `GET /api/calendar/events`
- Handled by `CalendarController@events`

The controller returns JSON with:
- `title` = facility name
- `start` and `end` = ISO 8601 datetime strings
- `color` = generated by `getReservationColor()`
- `extendedProps` = purpose, status, room, date

This is the bridge between backend reservations and frontend calendar rendering.

---

## 6. Notifications and approval workflow

### Notification pages
- `GET /notifications` → `NotificationController@index`
- `POST /notifications/mark-read` → `NotificationController@markAllRead`
- `POST /notifications/delete-selected` → `NotificationController@destroySelected`

### What happens when admin approves/rejects
- Admin routes under `/admin/*`
- `AdminController@approve()` marks a reservation approved
- `AdminController@reject()` marks a reservation rejected
- Both methods create an `AuditLog` record
- Both methods fire `ReservationStatusChanged` to notify the user

### Notification tables
- `database/migrations/2026_04_18_000000_create_notifications_table.php`
  - `notifications` table is the Laravel database notification table
  - Stores notification `type`, `data`, `read_at`, and `notifiable` polymorphic relation

### Notification payloads
- `app/Notifications/ReservationPendingApproval.php`
  - Created when a reservation is submitted or resubmitted
  - Data contains `title`, `message`, `action_url`, and `status`
- `app/Notifications/ReservationStatusChanged.php`
  - Sent to the requester when admin approves/rejects

---

## 7. Admin section

### Admin login flow
- Hidden admin login page: `/admin-secret`
- Admin credentials are validated in `AuthController@loginAdmin()`
- If admin has 2FA enabled, they are redirected to `/admin-2fa`

### Admin actions
- `GET /admin/dashboard` → `AdminController@dashboard`
- `POST /admin/reservations/{reservation}/approve` → approve
- `POST /admin/reservations/{reservation}/reject` → reject
- `DELETE /admin/reservations/{reservation}` → delete
- `POST /admin/users/{user}/role` → role change
- `DELETE /admin/users/{user}` → delete user

### Admin safety
- `requireAdmin()` and `requireSuperAdmin()` in `AdminController.php` protect sensitive actions
- `isSuperAdmin` prevents a head admin from being demoted or deleted incorrectly

---

## 8. Database location and configuration

### Where configuration lives
- `.env` file in the project root stores connection details such as:
  - `DB_CONNECTION`
  - `DB_HOST`
  - `DB_PORT`
  - `DB_DATABASE`
  - `DB_USERNAME`
  - `DB_PASSWORD`
- Schema configuration is in `config/database.php`

### Primary tables
- `users`
- `facilities`
- `reservations`
- `saved_credentials`
- `notifications`
- `audit_logs`

### Important migrations
- User plus 2FA fields: `2026_04_12_000000_add_profile_phone_and_2fa_settings_to_users_table.php`
- Admin flags and audit logs: `2026_04_17_000000_add_is_admin_to_users_and_create_audit_logs_table.php`
- Notifications table: `2026_04_18_000000_create_notifications_table.php`

### Database access notes
- The project uses Eloquent models for ORM
- Relationships use `belongsTo` and `hasMany`
- Sensitive fields are encrypted at the model layer using Laravel `Crypt`

---

## 9. Most important files for your defense

### Authentication and security
- `app/Http/Controllers/AuthController.php`
- `app/Models/User.php`
- `database/migrations/0001_01_01_000000_create_users_table.php`
- `database/migrations/2024_11_01_000000_add_2fa_columns_to_users_table.php`
- `database/migrations/2026_04_12_000000_add_profile_phone_and_2fa_settings_to_users_table.php`

### Reservation logic
- `routes/web.php` (reserve, edit, delete, bulk delete)
- `app/Http/Controllers/CalendarController.php`
- `app/Models/Reservation.php`
- `app/Models/Facility.php`
- `resources/views/reserve.blade.php`
- `resources/views/reservations/edit.blade.php`

### Admin review
- `app/Http/Controllers/AdminController.php`
- `app/Notifications/ReservationStatusChanged.php`
- `app/Notifications/ReservationPendingApproval.php`
- `resources/views/admin/dashboard.blade.php`

### Notifications
- `app/Http/Controllers/NotificationController.php`
- `resources/views/notifications/index.blade.php`

### Debug utilities
- `app/Http/Controllers/DebugController.php`
  - Provides a simple web form to decrypt Laravel encrypted strings using the app's current `APP_KEY`
- `resources/views/debug/decrypt.blade.php`
  - Web interface for pasting and decrypting `Crypt::encryptString()` values

### Data security
- `app/Models/SavedCredential.php`
- `app/Models/User.php`
- `database/migrations/2024_01_02_000000_create_saved_credentials_table.php`

---

## 10. Dependencies and Build Configuration

### `composer.json` (PHP dependencies)
- **Requires**: Laravel 12.0, Socialite 5.26 (OAuth), Tinker (artisan shell)
- **Dev requires**: PHPUnit 11.5.3, Faker, Laravel Pint (linter), Laravel Pail (log viewer), Mockery
- **Key scripts**:
  - `setup`: Installs composer, creates `.env`, generates key, runs migrations, builds frontend
  - `dev`: Runs Laravel server, queue listener, pail logs, and Vite dev server concurrently
  - `test`: Runs PHPUnit test suite after clearing config cache
  - `post-autoload-dump`: Publishes Laravel assets and discovers packages

### `package.json` (Frontend dependencies)
- **Dev dependencies**: Vite 7, Tailwind CSS with Vite plugin, Laravel Vite plugin, Axios
- **Dependencies**: FullCalendar 6.1.20 with plugins for day/time grid views
- **Key scripts**:
  - `dev`: Starts Vite development server for hot module replacement
  - `build`: Compiles CSS and JS for production
- **Purpose**: Manages frontend build pipeline and calendar/styling libraries

### Build flow
1. `npm run dev` starts Vite at `http://127.0.0.1:5173`
2. `resources/js/app.js` and `resources/css/app.css` are entry points compiled by Vite
3. Output is written to `public/build/` for production
4. Laravel Blade templates reference compiled assets via `@vite(['...'])`

---

## 11. Quick run commands

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

If you are using a local environment with XAMPP, ensure the `.env` database settings match the local MySQL/MariaDB instance.

---

## 11. Straightforward guide to explain the system

### How a reservation is created
1. Authenticated user loads `/calendar`
2. Frontend requests `/api/calendar/events` to show existing bookings
3. User submits a new booking form
4. Backend validates input and checks facility availability in `Facility::isAvailable()`
5. Reservation is saved in `reservations` with `status = 'pending'`
6. Notification is sent to admins via `ReservationPendingApproval`

### How admin approves/rejects
1. Admin logs in via `/admin-secret`
2. Admin reviews pending bookings on `/admin/dashboard`
3. Approve or reject action updates the reservation status
4. `AuditLog` stores the admin action
5. User receives a notification from `ReservationStatusChanged`

### How notifications work
- Stored in the `notifications` table
- Loaded by `NotificationController@index`
- The user can mark all as read or delete selected notifications
- Deleting an approval notification may also remove the pending reservation request

---

## 12. Recommended defense talking points
- The project separates routes, controllers, models, and views clearly
- Authentication is layered with 2FA and device remembering
- Reservation conflicts are prevented by model-level availability checks
- Notifications use Laravel's built-in database notification system
- Admin actions are logged in `audit_logs`
- Sensitive text data is encrypted in the model layer before database storage

Good luck with your capstone defense!
