# Best USA Camps

WordPress plugin and theme templates for managing summer camp profiles, user dashboards, and custom login flows.

## Current Version: 3.5.1

## Key Features
- **Modern Admin Dashboard** - Card-based grid interface with real-time search, sort, and filter
- **Camp Management Dashboard** - Camp directors can manage their profiles, photos, accommodations, FAQs, and sessions
- **Daily Notification System** - Automated 8 PM CET emails about camp updates with comprehensive debug tools
- **Frontend Shortcodes** - 15 shortcodes for displaying camp data on public pages (see SHORTCODES-GUIDE.md)
- **Pagination System** - 20 camps per page with smart ellipsis navigation
- **Rating System** - Admin-controlled star ratings (0-5) displayed on frontend
- **Custom Login/Password Pages** - Astra theme templates for camp director authentication
- **AJAX Module System** - Real-time updates for accommodations, FAQs, and sessions
- **Ninja Forms Integration** - Camp registration and signup forms
- **Storage Tracking** - Photo and logo upload management
- **Enhanced Validation** - 180-300 word limits with real-time visual feedback

## Frontend Shortcodes (15 Total)
Display camp information on public pages using shortcodes:

**Header & Identity:**
- `[camp_logo]` - Camp logo with size options
- `[camp_name]` - SEO-optimized H1 heading
- `[camp_name_text]` - **NEW** Plain text name for SEO titles/meta
- `[camp_subtitle]` - Auto-generated from types + location
- `[camp_rating]` - Star rating display (0-5)

**Contact Information:**
- `[camp_contact_bar]` - Horizontal contact bar with Google Maps links
- `[camp_contact_info]` - **NEW** Vertical sidebar contact display

**Content Sections:**
- `[camp_description]` - About camp (220-word limit)
- `[camp_activities]` - Activity tags
- `[camp_types_weeks]` - Camp types and weeks
- `[camp_accommodations]` - **ENHANCED** Dynamic columns, gradient design, 90-word limit
- `[camp_faqs]` - **ENHANCED** Green accordion toggles, all closed by default
- `[camp_sessions]` - **ENHANCED** Dynamic columns, gradient design, 90-word limit
- `[camp_additional_info]` - FontAwesome icons, green cards (directors, dates, rates)

**Full Documentation:** [SHORTCODES-GUIDE.md](./SHORTCODES-GUIDE.md)

## Quick Start
See [README-INSTRUCTIONS.md](./README-INSTRUCTIONS.md) for installation, setup, and usage details.

## Latest Updates (v3.5.0 - February 13, 2026)

### 🎨 **Admin UI Redesign**
- ✅ **NEW:** Modern responsive card grid replacing old table layout (280px cards, 50px gaps)
- ✅ **NEW:** Real-time search filter by camp name and state
- ✅ **NEW:** Sort dropdown (Newest First, Oldest First, A-Z, Z-A)
- ✅ **NEW:** Dynamic camp counts display: "85 / 112 camps" (approved/total)
- ✅ **NEW:** "Show Inactive Camps" toggle with gold highlight
- ✅ **ENHANCED:** Edit flow opens in same tab with "Back to Camps" link
- ✅ **ENHANCED:** Card design with circular logos, hover effects, centered layout

### 🔧 **Critical Bug Fixes**
- ✅ **FIXED:** Double-escaping issue with apostrophes and curly quotation marks
- ✅ **FIXED:** Backslash doubling in saved content
- ✅ **SOLUTION:** Implemented `wp_unslash($_POST)` pattern before sanitization

### 📧 **Daily Notification System**
- ✅ **NEW:** Automated 8 PM CET daily emails for camp updates
- ✅ **NEW:** Notification queue system with database tracking
- ✅ **NEW:** Debug tool (debug-check-notifications.php) with manual send controls
- ✅ **NEW:** Settings page integration with auto-detecting plugin folder

### 📄 **Pagination & Validation**
- ✅ **NEW:** [camps_list] shortcode pagination - 20 camps per page
- ✅ **NEW:** Smart ellipsis navigation (shows first, last, current ±2)
- ✅ **ENHANCED:** Word count minimum reduced from 220 → 180 words
- ✅ **ENHANCED:** Real-time word count validation with color-coded feedback

### 🗂️ **Project Organization**
- ✅ **NEW:** Archived 33 historical files into organized _archive/ structure
- ✅ **NEW:** Clean root directory with only essential documentation
- ✅ **NEW:** Comprehensive RESTORE-POINT-v3.5.0.md documentation

**Full Details:** See [RESTORE-POINT-v3.5.0.md](./RESTORE-POINT-v3.5.0.md)
- ✅ Word limits: 220 words (camp description), 90 words (sessions/cabins)
- ✅ Real-time word counters with visual warnings in admin
- ✅ Google Maps clickable addresses in contact shortcodes
- ✅ Rating persistence fix (directors can't overwrite admin ratings)
- ✅ Responsive mobile design (grids stack to single column)
- ✅ Amaranth font for headers (26px, 600 weight)
- ✅ 100% width FAQ containers

## Requirements
- WordPress 5.8+
- PHP 7.4+
- MySQL 5.7+
