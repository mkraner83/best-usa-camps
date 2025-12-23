# Changelog

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
