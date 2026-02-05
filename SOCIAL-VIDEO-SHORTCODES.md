# Social Media & Video Shortcodes

## Overview
Added two new shortcodes for displaying social media links and videos on camp pages.

## Shortcodes

### 1. Social Media Links - `[camp_social_media]`

**Usage:**
```
[camp_social_media]
```

**With custom class:**
```
[camp_social_media class="my-custom-class"]
```

**Features:**
- Displays social media links as styled badges
- Automatically hides if no links are set
- Supports: Facebook, Twitter, Instagram, YouTube, LinkedIn, TikTok
- Links open in new tab with proper security attributes
- Hover effects with platform-specific colors
- Fully responsive

**Data Source:**
- Reads from `social_media_links` column (JSON format)
- Set via admin dashboard editor

---

### 2. Video Embed - `[camp_video]`

**Usage:**
```
[camp_video]
```

**With custom aspect ratio:**
```
[camp_video aspect_ratio="4-3"]
```

**With custom class:**
```
[camp_video class="my-custom-class"]
```

**Features:**
- Responsive video embed (maintains aspect ratio)
- No autoplay - respects user preference
- Supports YouTube and Vimeo URLs
- Automatically converts watch URLs to embed format
- Default: 16:9 aspect ratio
- Lazy loading enabled

**Supported URL Formats:**
- YouTube: `https://www.youtube.com/watch?v=VIDEO_ID`
- YouTube Short: `https://youtu.be/VIDEO_ID`
- YouTube Embed: `https://www.youtube.com/embed/VIDEO_ID`
- Vimeo: `https://vimeo.com/VIDEO_ID`
- Vimeo Embed: `https://player.vimeo.com/video/VIDEO_ID`

**Parameters:**
- `aspect_ratio`: `16-9` (default) or `4-3`
- `class`: Custom CSS class

**Data Source:**
- Reads from `video_url` column
- Set via admin dashboard editor

---

## Conditional Display Pattern

Both shortcodes follow the same pattern as FAQs, Sessions, and Accommodations:

1. **Return empty string if no content** - The shortcode renders nothing if:
   - Social media: No links set or all links are empty
   - Video: No video URL set

2. **Template-level title hiding** - Page templates can use CSS to hide titles when content is empty:

```html
<h2 class="social-title">Follow Us</h2>
[camp_social_media]

<h2 class="video-title">Camp Video</h2>
[camp_video]
```

**CSS for conditional hiding:**
```css
/* Hide title if next sibling shortcode is empty */
.social-title:has(+ .camp-social-media:empty),
.video-title:has(+ .camp-video:empty) {
    display: none;
}

/* OR use wrapper approach */
.social-section:not(:has(.camp-social-media)) .social-title,
.video-section:not(:has(.camp-video)) .video-title {
    display: none;
}
```

---

## CSS Classes

### Social Media
- `.camp-social-media` - Main container
- `.social-links-container` - Flexbox wrapper
- `.social-badge` - Individual link badge
- `.social-{platform}` - Platform-specific class (e.g., `.social-facebook`)
- `.platform-name` - Platform name text

### Video
- `.camp-video` - Main container
- `.video-wrapper` - Responsive wrapper
- `.aspect-16-9` - 16:9 aspect ratio
- `.aspect-4-3` - 4:3 aspect ratio

---

## Implementation Details

### Files Modified
1. **includes/Public/class-camp-frontend.php**
   - Added shortcode registration (lines 41-42)
   - Added `render_social_media()` method
   - Added `render_video()` method
   - Added `convert_to_embed_url()` helper method

2. **assets/camp-frontend.css**
   - Social media badge styles
   - Platform-specific hover colors
   - Responsive video wrapper with aspect ratio
   - Mobile responsive styles

### Database Columns Used
- `social_media_links` (TEXT) - JSON with platform => URL pairs
- `video_url` (VARCHAR 500) - Video URL

### Security Features
- All URLs sanitized with `esc_url()`
- External links use `rel="noopener noreferrer"`
- Video iframes use proper `allow` attributes
- Lazy loading for video embeds

---

## Example Template Usage

```html
<div class="camp-content">
    <!-- Camp Header -->
    [camp_header]
    
    <!-- Description -->
    <h2>About This Camp</h2>
    [camp_description]
    
    <!-- Video (title hidden if no video) -->
    <h2 class="video-title">Watch Our Camp Video</h2>
    [camp_video]
    
    <!-- Social Media (title hidden if no links) -->
    <h2 class="social-title">Connect With Us</h2>
    [camp_social_media]
    
    <!-- FAQs -->
    <h2>Frequently Asked Questions</h2>
    [camp_faqs]
</div>
```

---

## Version
Added in: v3.4.7
Date: January 2026
