# Changelog

## v2.6.0 (2026-01-14) - AJAX Rebuild & Upload Overlay

### Major Changes
- **Complete AJAX Rebuild**: Replaced broken POST-based save system for Accommodations, FAQs, and Sessions modules
- **Upload Progress Overlay**: Added visual feedback during photo/logo uploads with animated progress bar
- **Independent Module Saves**: Each module (Accommodations, FAQs, Sessions) now saves independently via AJAX without page refresh

### Technical Improvements
- Converted all three dashboard modules to AJAX architecture using wp_ajax_ hooks
- Implemented 9 new AJAX endpoints (save/get/delete for each module)
- Fixed namespace issues: Updated all database calls to use `\CreativeDBS\CampMgmt\DB::`
- Replaced innerHTML-based overlay with DOM createElement methods to eliminate syntax errors
- Removed form validation conflicts by removing `required` attributes from AJAX-managed fields

### Bug Fixes
- **Critical**: Fixed data deletion bug where "Save All Changes" would delete all Accommodations, FAQs, and Sessions records
- Fixed JavaScript syntax errors caused by emoji characters (⏳, ⚠️) in inline scripts
- Fixed special character encoding issues (× replaced with &times; HTML entity)
- Fixed missing if statement in camp types validation
- Fixed form auto-close issues caused by `<script>` tags inside `<form>` elements

### UI/UX Enhancements
- Enhanced "Save All Changes" button styling (full width, custom font, green background)
- Cleaned up Photos section (removed duplicate nested boxes)
- Fixed photo display consistency (200px height, object-fit: cover, fills container properly)
- Updated dashboard padding to 0px for cleaner edge-to-edge layout
- Added animated progress bar overlay (0-90%) during file uploads with clear warnings

### Database
- Commented out old POST-based handlers for Accommodations, FAQs, and Sessions to prevent data loss
- All AJAX operations use proper nonce validation and user role checks
- Database operations maintain backward compatibility

## v2.5.0 (2025-12-04)
- Added custom login, lost password, and reset password templates for Astra theme
- Integrated full site design for authentication pages
- Password reset flow handled entirely in custom pages
- Storage tracking for photo and logo uploads
- Automatic redirect from WordPress login/reset to custom pages
- Email templates now use custom reset URLs
- Logout button redirects to /camp-login/
- All authentication URLs updated to use custom pages
- Login errors display on same page without redirecting
- All plugin files reverted to restore point except for template login fixes

## v2.4.2 (2025-11-28)
- Activities tag/chip input system
- Full-width activities input field
- Centered h2 titles, left-aligned form labels
- Table and column name mismatches resolved

## v2.4.1 (2025-11-28)
- Complete form redesign with required validations
- US states dropdown
- Date pickers for opening/closing days
- Currency inputs with $ prefix for rates
- `[camp_dashboard_title]` shortcode
- Inline checkbox display
- Login title centered at 32px
- Database column and pivot table name fixes

## v2.4.0 (2025-11-28)
- Added `user_id` column to `camp_management` table
- Migration system to auto-link camps to users by email
- Login redirect for Camp users to `/user-dashboard/`
- Block Camp users from accessing wp-admin
- Hide admin bar for Camp users
