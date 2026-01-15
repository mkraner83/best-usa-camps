# Best USA Camps

WordPress plugin and theme templates for managing summer camp profiles, user dashboards, and custom login flows.

## Current Version: 2.8.5

## Key Features
- **Camp Management Dashboard** - Camp directors can manage their profiles, photos, accommodations, FAQs, and sessions
- **Frontend Shortcodes** - 12 shortcodes for displaying camp data on public pages (see SHORTCODES-GUIDE.md)
- **Rating System** - Admin-controlled star ratings (0-5) displayed on frontend
- **Custom Login/Password Pages** - Astra theme templates for camp director authentication
- **AJAX Module System** - Real-time updates for accommodations, FAQs, and sessions
- **Ninja Forms Integration** - Camp registration and signup forms
- **Storage Tracking** - Photo and logo upload management

## Frontend Shortcodes (13 Total)
Display camp information on public pages using shortcodes:

**Header & Identity:**
- `[camp_header]` - **NEW** Combined header with all elements (name, subtitle, logo, contact, rating)
- `[camp_logo]` - Camp logo with size options
- `[camp_name]` - SEO-optimized H1 heading
- `[camp_subtitle]` - Auto-generated from types + location
- `[camp_rating]` - Star rating display (0-5)

**Contact Information:**
- `[camp_contact_bar]` - Horizontal contact bar with icons

**Content Sections:**
- `[camp_description]` - About camp section
- `[camp_gallery]` - **NEW** Photo gallery with Elementor lightbox
- `[camp_activities]` - Activity tags
- `[camp_types_weeks]` - Camp types and weeks
- `[camp_accommodations]` - Facility cards with #E6E6E6 background
- `[camp_faqs]` - FAQ accordion (first item open by default)
- `[camp_sessions]` - Session pricing cards with #E6E6E6 background
- `[camp_additional_info]` - Director, dates, rates

**Full Documentation:** [SHORTCODES-GUIDE.md](./SHORTCODES-GUIDE.md)

## Quick Start
See [README-INSTRUCTIONS.md](./README-INSTRUCTIONS.md) for installation, setup, and usage details.

## Latest Updates (v2.8.5 - January 16, 2026)
- ✅ **NEW:** [camp_header] combined shortcode with all header elements
- ✅ **NEW:** [camp_gallery] photo gallery with smart grid layout (1-10 photos)
- ✅ **ENHANCED:** Header full-width in Elementor containers with two-row layout
- ✅ **ENHANCED:** Mobile header optimization (15px padding, 12px gaps, 70px logo)
- ✅ **ENHANCED:** Gallery uses Elementor's native lightbox (keyboard navigation, slideshow)
- ✅ **ENHANCED:** Sessions/accommodations with #E6E6E6 background, white cards
- ✅ **ENHANCED:** FAQ first item opens by default for better UX
- ✅ Gallery smart layout: 1-4 single row, 5-10 two rows with specific distributions
- ✅ Mobile gallery always 2 columns (prevents long pages)
- ✅ Safe plugin updates with global initialization flag
- ✅ Image thumbnails fill containers with object-fit cover
- ✅ Responsive design with 768px mobile breakpoint

## Requirements
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
