# CreativeDBS Camp Management Plugin - Complete Documentation

**Version:** 2.5.0  
**Last Updated:** December 4, 2025

---

## Table of Contents
1. [Installation](#installation)
2. [Shortcodes](#shortcodes)
3. [User Roles](#user-roles)
4. [Database Structure](#database-structure)
5. [Features Overview](#features-overview)
6. [Configuration](#configuration)
7. [Troubleshooting](#troubleshooting)
8. [Version History](#version-history)

---

## Installation

### Prerequisites
- WordPress 5.8 or higher
- PHP 8.0 or higher
- Ninja Forms plugin (v3+) with File Uploads addon installed
- MySQL 5.7+ or MariaDB 10.2+

### Installation Steps

1. **Download the plugin**
   - Get `creativedbs-camp-mgmt-v2.4.2.zip` from the GitHub repository

2. **Install via WordPress Admin**
   - Go to: Plugins → Add New → Upload Plugin
   - Choose the zip file and click "Install Now"
   - Activate the plugin

3. **Configure Ninja Forms**
   - Create or use Form ID: 4
   - Add required fields (see below)

4. **Create Pages**
   - Create a page with slug `/user-dashboard/` for the camp dashboard
   - Add the `[camp_dashboard]` shortcode to the page content

5. **Set Up Custom Login Pages (Astra Theme)**
   - Copy files from `theme-templates/` to your Astra child theme folder
   - Required template files:
     - `template-camp-login.php` - Custom login page
     - `template-camp-lost-password.php` - Password reset request
     - `template-camp-reset-password.php` - New password generation
   - Create WordPress pages:
     - `/camp-login/` - Use "Camp Login" template
     - `/camp-lost-password/` - Use "Camp Lost Password" template
     - `/camp-reset-password/` - Use "Camp Reset Password" template

### Required Ninja Forms Fields

The plugin expects these field keys in Ninja Form #4:
- `camp_name` - Camp Name
- `camp_directors` - Camp Director(s)
- `email` - Email Address
- Any other camp information fields

---

## Shortcodes

### `[camp_dashboard]`
Displays the complete camp management dashboard for logged-in Camp users.

**Usage:**
```
[camp_dashboard]
```

**Features:**
- Login form for non-logged-in users
- Complete camp profile editor for Camp role users
- Real-time form validation
- Auto-save functionality

**Place on:** A dedicated page (e.g., `/user-dashboard/`)

---

### `[camp_dashboard_title]`
Dynamic page title that changes based on login state.

**Usage:**
```
[camp_dashboard_title]
```

**Output:**
- When logged out: `CAMP DASHBOARD`
- When logged in: `Admin: [Camp Name]`

**Use case:** In Elementor heading widget or page title

---

## User Roles

### Camp Role
**Capabilities:**
- `read` - Can view their own dashboard
- Cannot access WordPress admin area
- Admin bar hidden automatically
- Redirected to `/user-dashboard/` upon login

**How users are created:**
- Automatically via Ninja Forms submission
- Username: sanitized camp name
- Password: automatically generated (emailed to user)
- Role: `camp`

---

## Database Structure

### Main Tables

#### `wp_camp_management`
Primary table storing all camp information.

**Columns:**
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `ninja_entry_id` | BIGINT UNSIGNED | Link to Ninja Forms submission |
| `user_id` | BIGINT UNSIGNED | Link to WordPress user (added in v2.4.0) |
| `unique_key` | VARCHAR(64) | Unique identifier |
| `camp_name` | VARCHAR(255) | Camp name (required) |
| `opening_day` | DATE | Season start date |
| `closing_day` | DATE | Season end date |
| `minprice_2026` | DECIMAL(10,2) | Lowest rate |
| `maxprice_2026` | DECIMAL(10,2) | Highest rate |
| `activities` | TEXT | Legacy activities field |
| `email` | VARCHAR(190) | Contact email |
| `phone` | VARCHAR(64) | Contact phone |
| `website` | VARCHAR(255) | Camp website |
| `camp_directors` | TEXT | Director names |
| `address` | VARCHAR(255) | Street address |
| `city` | VARCHAR(190) | City |
| `state` | VARCHAR(64) | US State (2-letter code) |
| `zip` | VARCHAR(32) | ZIP code |
| `about_camp` | LONGTEXT | Camp description |
| `photos` | LONGTEXT | Photo URLs (CSV) |
| `logo` | VARCHAR(255) | Logo URL |
| `search_image` | VARCHAR(255) | Search result image URL |
| `approved` | TINYINT(1) | Approval status |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

**Indexes:**
- PRIMARY: `id`
- KEY: `user_id`

---

#### `wp_camp_type_terms`
Lookup table for camp types (e.g., Day Camp, Overnight Camp).

**Columns:**
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `name` | VARCHAR(190) | Type name |
| `slug` | VARCHAR(190) | URL-friendly slug |
| `is_active` | TINYINT(1) | Active status (default: 1) |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

**Indexes:**
- PRIMARY: `id`
- UNIQUE: `slug`

---

#### `wp_camp_week_terms`
Lookup table for duration/weeks (e.g., 1 Week, 2 Weeks, Full Summer).

**Structure:** Same as `wp_camp_type_terms`

---

#### `wp_camp_activity_terms`
Lookup table for activities (e.g., Swimming, Horseback Riding, Arts & Crafts).

**Structure:** Same as `wp_camp_type_terms`

**Special Feature:** Activities are auto-created when users add them via the tag input system.

---

### Pivot/Junction Tables

#### `wp_camp_management_types_map`
Links camps to their types (many-to-many).

**Columns:**
| Column | Type | Description |
|--------|------|-------------|
| `id` | BIGINT UNSIGNED | Primary key |
| `camp_id` | BIGINT UNSIGNED | Foreign key to camp_management |
| `type_id` | BIGINT UNSIGNED | Foreign key to camp_type_terms |

**Indexes:**
- PRIMARY: `id`
- KEY: `camp_id`
- KEY: `type_id`

---

#### `wp_camp_management_weeks_map`
Links camps to their available weeks/durations.

**Structure:** Same as types_map, with `week_id` instead of `type_id`

---

#### `wp_camp_management_activities_map`
Links camps to their offered activities.

**Structure:** Same as types_map, with `activity_id` instead of `type_id`

---

### Additional Tables

#### `wp_camp_credentials`
Stores camp login credentials and metadata.

#### `wp_camp_management_types`
Legacy table (may be deprecated).

#### `wp_camp_management_weeks`
Legacy table (may be deprecated).

---

## Features Overview

### Front-End Camp Dashboard
- **Login System:** WordPress native login form with custom styling
- **Profile Editor:** Complete form to edit all camp information
- **Real-time Validation:** JavaScript validation for required fields
- **Auto-save:** Changes saved immediately upon submission

### Admin Features
- **Camp Management:** List, view, edit, delete camps
- **Camp Types Management:** Add/edit/deactivate camp types
- **Durations/Weeks Management:** Manage available session lengths
- **Activities Management:** Manage activity options
- **Import/Export:** Bulk data operations

### Form Fields (Front-End Dashboard)

**Required Fields:**
- Camp Name
- Camp Director(s)
- Camp Description
- Email
- Phone
- Website
- Street Address
- City
- State (dropdown)
- ZIP Code
- Starting Date
- Ending Date
- Lowest Rate ($)
- Highest Rate ($)
- Camp Types (at least 1)
- Available Weeks (at least 1)
- Activities Offered (at least 1)

**Field Types:**
- Text inputs
- Email input
- Phone input
- URL input
- Textarea
- Date pickers
- Currency inputs (with $ prefix)
- Checkboxes (Types, Weeks)
- Tag/chip input (Activities)

### Activities Tag System
Users can:
- Type activity name and press Enter or comma to add
- Click × button to remove activities
- Activities are automatically created in database if they don't exist
- Existing activities load as colored chips

---

## Configuration

### Color Scheme
- Primary: `#679B7C` (header backgrounds)
- Accent: `#497C5E` (buttons, titles)
- Required: `#c33` (asterisks)
- White text: `#ffffff` (on dark backgrounds)

### Typography
- Headings (h2, buttons): "Annie Use Your Telescope", sans-serif
- Body: System default

### Login Page (WordPress Default)
- Title: 32px, centered, "Annie Use Your Telescope" font
- Max width: 450px
- Centered on page
- Used only for `/administrator/` URL (WordPress admin login)

### Custom Login Pages (Astra Theme Templates)
- Full site design with header, navigation, and footer
- Font: "Annie Use Your Telescope" for headings
- Colors: #497C5E (primary green), #679B7C (secondary green)
- Templates include:
  - Login form with error handling
  - Password reset request form
  - New password generation form
- All forms process on same page (no WordPress redirects)

### Dashboard Layout
- Max width: 1200px
- Sections: Rounded corners, subtle shadows
- Form rows: Responsive flex layout
- Mobile: Stacked fields

---

## Troubleshooting

### "No camp profile found for your account"
**Cause:** User's camp record doesn't have `user_id` linked.

**Solution:** Run migration:
```php
// The plugin auto-runs this migration
// Check: get_option('creativedbs_campmgmt_phase7_migrated') should be >= 2
```

### "Activities not showing"
**Cause:** Table name mismatch.

**Solution:** Ensure tables exist:
- `wp_camp_activity_terms` (singular "activity")
- Not `wp_camp_activities_terms` (plural)

### "User can't login"
**Check:**
1. User has 'camp' role
2. Password was received via email
3. User is going to `/user-dashboard/` page
4. `login_redirect` filter is active

### "Checkboxes empty"
**Check:**
1. Lookup tables have data with `is_active = 1`
2. Table names correct: `wp_camp_type_terms`, `wp_camp_week_terms`, `wp_camp_activity_terms`

---

## Version History

### v2.5.0 (2025-12-04) - Current
- **Feature:** Custom login page templates for Astra theme (login, lost password, reset password)
- **Feature:** Full site design integration for authentication pages (header/footer/navigation)
- **Feature:** Custom login form handling with wp_signon() - no WordPress redirects
- **Feature:** Password reset flow processes entirely in custom pages
- **Feature:** Storage tracking for photo uploads (25MB limit, up to 10 files)
- **Feature:** Storage tracking for logo uploads (5MB limit)
- **Feature:** Auto-redirect from /administrator/?action=rp to custom reset page
- **Enhancement:** Upload overlay with full-width progress bar
- **Enhancement:** "This might take a few minutes" message during file uploads
- **Enhancement:** Logout button redirects to /camp-login/ instead of /administrator/
- **Enhancement:** All authentication URLs updated to use custom pages
- **Fix:** Password reset emails now use custom page URLs
- **Fix:** Login errors display on same page without redirecting

### v2.4.2 (2025-11-28)
- **Feature:** Activities tag/chip input system (type and add activities dynamically)
- **Enhancement:** Full-width activities input field
- **Enhancement:** Centered h2 titles, left-aligned form labels
- **Fix:** All table and column name mismatches resolved

### v2.4.1 (2025-11-28)
- **Feature:** Complete form redesign with required validations
- **Feature:** US states dropdown (all 50 states)
- **Feature:** Date pickers for opening/closing days
- **Feature:** Currency inputs with $ prefix for rates
- **Feature:** `[camp_dashboard_title]` shortcode
- **Enhancement:** Inline checkbox display matching admin dashboard
- **Enhancement:** Login title centered at 32px
- **Fix:** Corrected database column names (about_camp vs description)
- **Fix:** Fixed pivot table names for proper data retrieval

### v2.4.0 (2025-11-28)
- **Critical Fix:** Added `user_id` column to `camp_management` table
- **Feature:** Migration system to auto-link existing camps to users by email
- **Feature:** Login redirect for Camp users to `/user-dashboard/`
- **Feature:** Block Camp users from accessing wp-admin
- **Feature:** Hide admin bar for Camp users

### v2.3.x (2025-11-28)
- **Feature:** Front-end camp dashboard created
- **Feature:** Login form with custom styling
- **Enhancement:** Debug logging for troubleshooting

### v2.2.3 (2025-11-27)
- **Feature:** Email template with static password reset link
- **Enhancement:** Ninja Forms integration improvements

---

## Development Notes

### File Structure
```
creativedbs-camp-mgmt/
├── creativedbs-camp-mgmt.php (Main plugin file)
├── includes/
│   ├── class-plugin.php (Core plugin class)
│   ├── class-db.php (Database operations)
│   ├── class-ninja-forms-integration.php (Form handling)
│   ├── migrations-phase7.php (Database migrations)
│   ├── Admin/
│   │   └── class-admin.php (Admin dashboard)
│   └── Public/
│       ├── class-public-controller.php
│       └── class-camp-dashboard.php (Front-end dashboard)
├── assets/
│   ├── admin.css
│   └── camp-dashboard.css (Front-end styles)
├── ninja-forms-email-template.html
└── README-INSTRUCTIONS.md (This file)
```

### Git Repository
- **Owner:** mkraner83
- **Repo:** best-usa-camps
- **Branch:** main
- **Tags:** 
  - v2.5.0 (latest restore point - December 4, 2025)
  - v2.4.2 (November 28, 2025)

### Restore to Version
```bash
# Latest version
git checkout v2.5.0

# Previous version
git checkout v2.4.2
```

---

## Support & Maintenance

### Debug Mode
Enable WordPress debug logging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs at: `/wp-content/debug.log`

### Plugin Logs
Search for: `CDBS Camp Dashboard:` in debug log

---

**Document Version:** 1.1  
**Plugin Version:** 2.5.0  
**Maintained by:** CreativeDBS  
**Last Review:** December 4, 2025
