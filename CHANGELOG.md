# Changelog

## v2.8.6 (2026-01-16) - Mobile Gallery Optimization

### Changed
- Gallery mobile layout locked to 2 columns (removed 1-column breakpoint at 480px)
- Prevents long scrolling on mobile devices while maintaining visual appeal

## v2.8.5 (2026-01-16) - Elementor Lightbox Integration

### Added
- Elementor native lightbox integration for gallery
- Slideshow support with camp ID grouping
- Keyboard navigation (arrow keys) support

### Removed
- ~160 lines of custom lightbox code (CSS, HTML, JavaScript)
- Custom modal overlay and navigation system

### Changed
- Gallery links now use Elementor data attributes
- Improved performance using Elementor's optimized lightbox
- Better compatibility with Elementor theme/plugins

## v2.8.4 (2026-01-16) - Gallery Thumbnail Fixes

### Fixed
- Gallery thumbnails now fill properly using dashboard CSS technique
- min-width/min-height: 100%, max-width: none, object-fit: cover
- Photos display without stretching or distortion

## v2.8.3 (2026-01-16) - Gallery Shortcode

### Added
- `[camp_gallery]` shortcode with smart 1-10 photo grid layouts
- Intelligent distribution: 1-4 single row, 5-10 two rows with balanced layouts
- 200px fixed height thumbnails with proper aspect ratios
- 5px border-radius, 2px #CCCCCC borders
- Custom lightbox with keyboard navigation and slideshow
- Database migration for `photos` TEXT column

### Changed
- Gallery uses comma-separated photo URLs from database
- Responsive thumbnail filling with object-fit: cover

## v2.8.2 (2026-01-15) - Mobile Header Optimization

### Changed
- Mobile logo max-height reduced to 70px (from 130px)
- Header padding: 15px 0px on mobile (tighter spacing)
- Row gap: 12px (elements closer together)
- Contact items gap: 2px (minimal spacing)

## v2.8.1 (2026-01-15) - Header Full-Width Fix

### Fixed
- Header now properly full-width inside Elementor containers
- CSS targeting `.elementor-widget` for container compatibility
- Removed viewport-width constraints

## v2.8.0 (2026-01-15) - Combined Header Shortcode

### Added
- `[camp_header]` shortcode combining logo, name, subtitle, contact, rating
- Two-row flexbox layout (logo/name/subtitle top, contact/rating bottom)
- Dark background (#343434) with full-width styling
- Auto-generated subtitle from camp types and location

### Changed
- Header typography updated: Name 42px Abel, Subtitle 18px Lato
- Contact bar integrated into header bottom row
- Rating display integrated with contact info

### Notes
- Individual header shortcodes (`[camp_logo]`, `[camp_name]`, etc.) still available for backward compatibility

## v2.7.9 (2026-01-15) - Design Updates

### Changed
- FAQ first item now opens by default (`open_first='true'`)
- Session/accommodation outer containers: #E6E6E6 background
- Session/accommodation cards: White background for better contrast
- Improved visual hierarchy and user experience

## v2.7.8 (2026-01-15) - Safe Plugin Updates

### Added
- Global initialization flag: `$GLOBALS['camp_frontend_initialized']`
- Prevents double class loading during WordPress updates
- Eliminates fatal errors on plugin activation/update

## v2.7.7 (2026-01-15) - FAQ Styling & UX Polish

### Changed
- FAQ toggles now green (#497C5E) with white text and darker hover (#3d6449)
- FAQ containers expanded to 100% width (removed 900px constraint)
- All FAQs initially closed by default (changed `open_first='false'`)

## v2.8.2 (2026-01-15) - Accommodation Grid Fix

### Fixed
- Removed duplicate `<div class="accommodations-container">` causing single-column layout
- Accommodations now properly display in dynamic columns (1-3) like sessions

## v2.8.1 (2026-01-15) - Accommodations Enhancement & Word Counters

### Added
- Accommodations match sessions design: gradient background, green border, Amaranth font
- Dynamic column system for accommodations (1-3 columns based on count)
- 90-word counters for accommodation descriptions in admin
- 90-word counters for session descriptions in admin
- Real-time JavaScript word validation with visual warnings
- Responsive CSS: accommodation columns stack on mobile

### Changed
- Accommodation cards: gradient #F5F5F5 → #D0D0D0, 2px green border
- Accommodation H3: Amaranth font, 26px, 600 weight, 1.6em line-height

## v2.8.0 (2026-01-15) - Session Card Polish

### Changed
- Session gradient updated: lighter top (#F5F5F5), darker bottom (#D0D0D0)
- Added 2px solid green border (#497C5E) to session cards

## v2.7.9 (2026-01-15) - Word Limits & Typography

### Added
- 90-word limit with automatic truncation for session descriptions
- 90-word limit with automatic truncation for accommodation descriptions

### Changed
- Session H3 typography: Amaranth, 600 weight, 26px, 1.6em line-height
- Session dates: 15px, #999, italic, 400 weight
- Session card gradient: #E6E6E6 → #D0D0D0

## v2.7.8 (2026-01-15) - Description Word Limit Adjustment

### Changed
- Camp description word limit reduced from 350 to 220 words
- Updated all validation messages and UI counters

## v2.7.7 (2026-01-15) - Rating Persistence Fix

### Fixed
- **Critical**: Rating field removed from camp director update handler
- Admin ratings no longer overwritten when directors save changes
- Directors can now update camp info without affecting admin-set ratings

## v2.7.6 (2026-01-15) - Sessions Grid Fix

### Fixed
- Sessions grid classes moved to correct container element
- Columns now display properly (2 or 3 side-by-side based on count)

## v2.7.5 (2026-01-15) - Contact Info & Grid Updates

### Changed
- Contact info: removed H3 camp name, increased label to 16px/600, values to 15px
- Sessions grid: fixed to `repeat(2, 1fr)` and `repeat(3, 1fr)` for proper columns
- Added mobile responsive stacking for session columns

### Fixed
- Sessions no longer incorrectly stack in single column on desktop

## v2.7.4 (2026-01-15) - Sessions Design & ZIP Fix

### Removed
- "Sessions & Pricing" H2 title

### Changed
- Session cards: #E6E6E6 background, 5px border-radius, no border
- Dynamic columns based on session count (max 3)

### Fixed
- **Critical**: ZIP creation now excludes old ZIP files (size reduced from 160MB to 78KB)

## v2.7.3 (2026-01-15) - New Shortcodes & Major Features

### Added
- `[camp_contact_info]` shortcode for sidebar contact display
- `[camp_name_text]` shortcode for plain text name (SEO)
- Google Maps clickable addresses in contact shortcodes
- 350-word limit for camp description with real-time counter
- Server-side word count validation
- Visual warning and submit button disable when over limit
- FontAwesome 6.5.1 with CDN + fallback loading

### Changed
- Info card icons: SVG → FontAwesome (fa-users, fa-calendar-check, etc.)
- Contact info: #E6E6E6 background, 5px radius, Amaranth font

### Fixed
- FontAwesome icons not loading (multi-layer loading strategy)
- Missing HTML closing tags in info cards

## v2.7.2 (2026-01-15) - Info Cards Green Theme

### Changed
- Info cards: green background (#497C5E), hover (#548968)
- Icons: FontAwesome 48px white with !important for theme override

### Fixed
- Card HTML structure with complete tags

## v2.7.1 (2026-01-14) - Rating Polish

### Fixed
- Rating circle borders changed to white

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
