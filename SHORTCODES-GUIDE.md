# Camp Frontend Shortcodes - Complete Guide

**Version:** 2.8.3  
**Last Updated:** January 16, 2026  
**Total Shortcodes:** 15

---

## 🎯 Setup Instructions

### 1. Create a Camp Page
1. Create a new page in WordPress (e.g., "Timber Lake Camp")
2. Add a **Custom Field** to the page:
   - **Key:** `camp_id`
   - **Value:** The camp's ID from the database (e.g., `123`)

### 2. Add Shortcodes to Elementor
Use the **Shortcode** widget in Elementor and paste the desired shortcode.

---

## 📋 Available Shortcodes (15 Total)

### **HEADER & IDENTITY SECTION**

#### 1. Camp Logo
```
[camp_logo size="medium"]
```
**Parameters:**
- `size`: small, medium (default), large
- `class`: Custom CSS class

**Example:**
```
[camp_logo size="large" class="my-custom-class"]
```

---

#### 2. Camp Name (Styled H1)
```
[camp_name]
```
**Parameters:**
- `tag`: h1 (default), h2, h3, div, span
- `class`: Custom CSS class

**Note:** Use `tag="h1"` only ONCE per page for SEO!

**Example:**
```
[camp_name tag="h1" class="hero-title"]
```

---

#### 3. Camp Name (Plain Text) ⭐ NEW in v2.8.3
```
[camp_name_text]
```
**Parameters:**
- `class`: Custom CSS class

**Purpose:** Returns just the camp name without any HTML tags.  
**Use Cases:**
- SEO title tags: `<title>[camp_name_text] | Best USA Camps</title>`
- Meta descriptions: `<meta name="description" content="Visit [camp_name_text] in New York">`
- Breadcrumbs and structured data

**Example:**
```
Output: "Timber Lake Camp" (no HTML, no wrapper)
```

---

#### 4. Camp Subtitle
Auto-generates from camp types + location (e.g., "Coed Overnight Camp – Shandaken, New York")
```
[camp_subtitle]
```
**Parameters:**
- `class`: Custom CSS class

---

#### 5. Camp Rating Display
Displays address, email, phone, website
```
[camp_contact_bar]
```
**Parameters:**
- `class`: Custom CSS class

---

#### 5. Camp Rating Display
Shows 5 circles based on camp rating
```
[camp_rating]
```
**Parameters:**
- `class`: Custom CSS class

**Note:** Rating is admin-only field, protected from director overwrites.

---

### **CONTACT SECTION**

#### 6. Contact Bar (Horizontal)
Displays address, email, phone, website in horizontal layout
```
[camp_contact_bar]
```
**Parameters:**
- `class`: Custom CSS class

**Output:** Horizontal contact bar with icons

---

#### 7. Contact Info (Vertical Sidebar) ⭐ NEW in v2.8.3
```
[camp_contact_info]
```
**Parameters:**
- `class`: Custom CSS class

**Output:**
- Vertical sidebar layout with labels
- Integrated Google Maps link for address
- Gray background (#E6E6E6)
- Perfect for sidebar widgets or dedicated contact sections

**Displays:**
- Camp Director
- Address (clickable Google Maps link)
- Email
- Phone
- Website

**Example:**
```html
<!-- Sidebar widget or dedicated section -->
<aside class="camp-sidebar">
    [camp_contact_info]
</aside>
```

---

### **MEDIA SECTION**

#### 8. Photo Carousel
Auto-populated from uploaded camp photos
```
[camp_photo_carousel]
```
**Parameters:**
- `height`: Default "500px"
- `class`: Custom CSS class

**Example:**
```
[camp_photo_carousel height="600px"]
```

**Features:**
- Auto-advance every 5 seconds
- Previous/Next arrows
- Click dots to navigate
- Responsive design

---

### **CONTENT SECTIONS**

#### 9. Camp Description
```
[camp_description]
```
**Parameters:**
- `class`: Custom CSS class

**Output:**
- H2 heading: "About [Camp Name]"
- Full camp description with paragraphs

**Limits:** 220-word maximum (enforced in dashboard with real-time counter)

---

#### 10. Activities
```
[camp_activities]
```
**Parameters:**
- `class`: Custom CSS class

**Output:****
- H2 heading: "Activities Offered"
- Activity tags in a grid

---

#### 11. Camp Types & Available Weeks
```
[camp_types_weeks]
```
**Parameters:**
- `class`: Custom CSS class

**Output:**
- Two columns: Camp Types | Available Weeks
- Badges for each item

---

#### 12. Accommodations ⭐ ENHANCED in v2.8.3
```
[camp_accommodations layout="cards"]
```
**Parameters:**
- `layout`: list, cards (default)
- `class`: Custom CSS class

**Output:**
- H2 heading: "Accommodation Facilities"
- Facility cards with name, capacity, description

**Enhancements:**
- **Dynamic Columns:** 1-3 columns based on number of accommodations
- **Gradient Background:** Light gray gradient (#F5F5F5 → #D0D0D0)
- **Typography:** Amaranth headers (26px, 600 weight), Abel body text
- **Word Limit:** 90-word description limit with truncation
- **Green Borders:** 2px border in theme green (#497C5E)
- **Mobile Responsive:** Stacks to single column on mobile

**Design Details:**
- 1 accommodation: Full width card
- 2 accommodations: 2-column grid
- 3+ accommodations: 3-column grid
- Auto-stacks on screens < 768px

---

#### 13. FAQs ⭐ ENHANCED in v2.8.3
```
[camp_faqs style="accordion" open_first="false"]
```
**Parameters:**
- `style`: accordion (default), list
- `open_first`: false (default - changed in v2.8.3), true
- `class`: Custom CSS class

**Output:**
- H2 heading: "Frequently Asked Questions"
- Interactive accordion or simple list

**Enhancements:**
- **Green Toggles:** Theme green color (#497C5E)
- **All Closed:** All FAQs start closed by default
- **Full Width:** 100% width layout
- **Smooth Transitions:** CSS animations on expand/collapse

---

#### 14. Sessions & Pricing ⭐ ENHANCED in v2.8.3
```
[camp_sessions layout="grid" columns="2"]
```
**Parameters:**
- `layout`: grid (default), list
- `columns`: 1, 2 (default), 3 (ignored - auto-calculated)
- `class`: Custom CSS class

**Output:**
- H2 heading: "Sessions & Pricing"
- Session cards with dates and prices

**Enhancements:**
- **Dynamic Columns:** 1-3 columns based on number of sessions
- **Gradient Background:** Light gray gradient (#F5F5F5 → #D0D0D0)
- **Typography:** Amaranth headers (26px, 600 weight), Abel body text
- **Word Limit:** 90-word description limit with truncation
- **Green Borders:** 2px border in theme green (#497C5E)
- **Mobile Responsive:** Stacks to single column on mobile
- **Date Styling:** Italic, gray (#999), 15px

**Design Details:**
- 1 session: Full width card
- 2 sessions: 2-column grid
- 3+ sessions: 3-column grid
- Auto-stacks on screens < 768px

---

#### 15. Additional Information ⭐ ENHANCED in v2.8.3
```
[camp_additional_info]
```
**Parameters:**
- `class`: Custom CSS class

**Output:**
- H2 heading: "Camp Information"
- Director, opening day, closing day, etc.

**Enhancements:**
- **FontAwesome Icons:** Visual icons for each field
- **Green Cards:** Background color (#497C5E) with white text
- **Improved Layout:** Card-based design with better spacing
- **Responsive:** Stacks nicely on mobile devices

---

## 🎨 Design System (v2.8.3)

### Color Palette
```css
Primary Green: #497C5E
Light Hover: #548968
Dark Hover: #3d6449
Gradient Top: #F5F5F5
Gradient Bottom: #D0D0D0
Contact BG: #E6E6E6
```

### Typography
```css
Headers: Amaranth, 26px, 600 weight, 1.6em line-height
Body Text: Abel font family
Contact Labels: 16px, 600 weight
Contact Values: 15px
Session Dates: 15px, italic, #999
```

### Layout System
- **Dynamic Grids:** 1-3 columns based on content count
- **Mobile Responsive:** Stack to single column < 768px
- **Card Borders:** 2px green borders on all cards
- **Gradient Backgrounds:** Sessions and accommodations

---

## 🎨 Example Page Layout in Elementor

### **Header Section (Dark Background)**
1. Add Container with dark background (#4a4a4a)
2. Two columns: Left (Logo) | Right (Info)
3. Left column: `[camp_logo size="medium"]`
4. Right column (vertically stacked):
   - `[camp_name]`
   - `[camp_subtitle]`
   - `[camp_contact_bar]`
   - `[camp_rating]`

### **Sidebar with Contact Info** ⭐ NEW Layout Option
1. Main content area (70% width):
   - All content shortcodes
2. Sidebar (30% width):
   - `[camp_contact_info]`

### **Photo Carousel (Full Width)**
```
[camp_photo_carousel height="500px"]
```

### **Content Sections (White Background)**
In order:
1. `[camp_description]` (220-word max)
2. `[camp_activities]`
3. `[camp_types_weeks]`
4. `[camp_accommodations layout="cards"]` (90-word descriptions, dynamic columns)
5. `[camp_sessions layout="grid"]` (90-word descriptions, dynamic columns)
6. `[camp_faqs style="accordion"]` (all closed by default, green toggles)
7. `[camp_additional_info]` (green cards with icons)

---

## 📝 Content Limits & Validation

### Word Limits (Enforced in Dashboard)
- **Camp Description:** 220 words max
- **Session Description:** 90 words max
- **Accommodation Description:** 90 words max

### Dashboard Features
- Real-time JavaScript word counters
- Visual warnings (red text, warning icons)
- Submit button disabling when over limit
- Server-side validation backup
- Frontend truncation if limit exceeded

---

## 🔧 Custom Styling

All shortcodes output with semantic CSS classes. You can add custom styles in **Appearance → Customize → Additional CSS** or your theme's CSS file.

**Example Custom CSS:**
```css
/* Make camp name green */
.camp-name {
    color: #497C5E !important;
}

/* Larger activity tags */
.activity-tag {
    font-size: 16px !important;
    padding: 10px 20px !important;
}

/* Custom session card hover */
.session-card:hover {
    transform: scale(1.05) !important;
}
```

---

## ⚙️ SEO Best Practices

### Heading Hierarchy
- **H1**: Use `[camp_name]` ONCE at the top
- **H2**: All section titles (automatically generated by shortcodes)
- **H3**: Subsections (facility names, session names, etc.)

### Example Structure:
```
H1: Timber Lake Camp (from [camp_name])
  H2: About Timber Lake Camp (from [camp_description])
  H2: Activities Offered (from [camp_activities])
  H2: Accommodation Facilities (from [camp_accommodations])
    H3: Cabin A (facility name)
    H3: Main Lodge (facility name)
  H2: Sessions & Pricing (from [camp_sessions])
    H3: Summer Session 1 (session name)
    H3: Summer Session 2 (session name)
```

---

## 🐛 Troubleshooting

### Shortcode shows but no data appears
1. Check that `camp_id` custom field is set on the page
2. Verify the camp ID exists in the database
3. Make sure camp has data entered in the dashboard

### Carousel not working
1. Check browser console for JavaScript errors
2. Verify camp has photos uploaded
3. Clear browser cache and reload

### Styling looks broken
1. Make sure `camp-frontend.css` is loading (check browser inspector)
2. Try clearing WordPress cache if using a caching plugin
3. Check for theme CSS conflicts

### Dynamic columns not working
1. Verify you're using v2.8.3 or later
2. Check that CSS classes are properly applied
3. Test with different numbers of items (1, 2, 3+)

### Google Maps link not working
1. Verify address is entered in dashboard
2. Check that special characters are properly formatted
3. Test on different devices/browsers

---

## 🆕 Version 2.8.3 Changelog

### New Shortcodes
1. **[camp_contact_info]** - Vertical sidebar contact layout with Google Maps
2. **[camp_name_text]** - Plain text camp name for SEO

### Enhanced Shortcodes
1. **[camp_sessions]** - Dynamic columns (1-3), gradient backgrounds, 90-word limit
2. **[camp_accommodations]** - Dynamic columns (1-3), gradient backgrounds, 90-word limit
3. **[camp_faqs]** - Green toggles, all closed by default, full width
4. **[camp_additional_info]** - FontAwesome icons, green card backgrounds

### Design Improvements
- Consistent Amaranth typography across all cards
- Green theme color (#497C5E) throughout
- Responsive grid layouts with mobile stacking
- Word limits with real-time counters in dashboard

---

## 📝 Notes

- All shortcodes automatically pull data from the database
- Data updates in real-time when camps update their dashboards
- Shortcodes are responsive and mobile-friendly
- Rating column is admin-only (protected from director overwrites)
- Photos carousel auto-advances every 5 seconds
- Word limits enforced: 220 (description), 90 (sessions/accommodations)
- FontAwesome CDN required for icons in [camp_additional_info]

---

## 🔍 Quick Reference Table

| # | Shortcode | Purpose | New/Enhanced |
|---|-----------|---------|--------------|
| 1 | `[camp_logo]` | Camp logo image | - |
| 2 | `[camp_name]` | Styled camp name (H1) | - |
| 3 | `[camp_name_text]` | Plain text name for SEO | ⭐ v2.8.3 |
| 4 | `[camp_subtitle]` | Auto-generated subtitle | - |
| 5 | `[camp_rating]` | 5-circle rating display | - |
| 6 | `[camp_contact_bar]` | Horizontal contact bar | - |
| 7 | `[camp_contact_info]` | Vertical sidebar contact | ⭐ v2.8.3 |
| 8 | `[camp_photo_carousel]` | Photo slideshow | - |
| 9 | `[camp_description]` | About section (220 words) | - |
| 10 | `[camp_activities]` | Activity tags grid | - |
| 11 | `[camp_types_weeks]` | Types & weeks badges | - |
| 12 | `[camp_accommodations]` | Facilities cards | ✨ v2.8.3 |
| 13 | `[camp_faqs]` | FAQ accordion | ✨ v2.8.3 |
| 14 | `[camp_sessions]` | Sessions & pricing cards | ✨ v2.8.3 |
| 15 | `[camp_additional_info]` | Camp info cards | ✨ v2.8.3 |

**Legend:** ⭐ New | ✨ Enhanced | - No change

---

**Need help?** Check the database for camp IDs:
```sql
SELECT id, camp_name FROM wp_camp_management;
```

---

**Plugin Version:** 2.8.3  
**Last Updated:** January 16, 2026  
**Documentation Maintained By:** Best USA Camps Development Team
