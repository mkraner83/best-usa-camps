# Flaticon Setup for Camp Additional Info Icons

## Icon Classes Used

The `[camp_additional_info]` shortcode uses these Flaticon classes:

1. **Director(s):** `flaticon-user`
2. **Opening Day:** `flaticon-calendar`
3. **Closing Day:** `flaticon-calendar-1`
4. **Lowest Rate:** `flaticon-dollar`
5. **Highest Rate:** `flaticon-diamond`

## How to Add Flaticon to Your WordPress Site

### Option 1: Flaticon Plugin (Recommended)
1. Download icons from https://www.flaticon.com/
2. Select the icons you need
3. Download as "Icon Font" format
4. Upload the font files to your WordPress site
5. Enqueue the CSS file in your theme

### Option 2: Manual CSS Addition
Add this to your theme's custom CSS or Elementor Custom CSS:

```css
/* Add your Flaticon font-face declarations here */
@font-face {
  font-family: "Flaticon";
  src: url("path/to/your/Flaticon.woff2") format("woff2"),
       url("path/to/your/Flaticon.woff") format("woff"),
       url("path/to/your/Flaticon.ttf") format("truetype");
  font-weight: normal;
  font-style: normal;
}

[class^="flaticon-"]:before,
[class*=" flaticon-"]:before {
  font-family: Flaticon;
  font-style: normal;
  font-weight: normal;
  font-variant: normal;
  text-transform: none;
  line-height: 1;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.flaticon-user:before { content: "\\f001"; }
.flaticon-calendar:before { content: "\\f002"; }
.flaticon-calendar-1:before { content: "\\f003"; }
.flaticon-dollar:before { content: "\\f004"; }
.flaticon-diamond:before { content: "\\f005"; }
```

**Note:** The content codes (\\f001, etc.) will be provided by Flaticon when you download the icon pack.

## Suggested Icons from Flaticon

Search for these on flaticon.com:

1. **User/Person icon** - For Director(s)
2. **Calendar icon** - For Opening/Closing dates
3. **Dollar/Money icon** - For Lowest rate
4. **Diamond/Premium icon** - For Highest rate

## Alternative: Use Elementor's Icons

If you're using Elementor Pro, you can also use Font Awesome or Elementor Icons library instead of Flaticon.

Replace the icon classes with:
- `fas fa-user` (Director)
- `fas fa-calendar` (Opening Day)
- `fas fa-calendar-check` (Closing Day)
- `fas fa-dollar-sign` (Lowest Rate)
- `fas fa-gem` (Highest Rate)

## Current Design Specs

- **Background:** #343434 (dark gray)
- **Icon Color:** #fff (white)
- **Icon Size:** 48px
- **Font:** Abel, Sans-serif
- **Text Color:** #fff (white)
- **Hover Effect:** Lighter background (#3d3d3d) with subtle lift

## Testing

After adding Flaticon fonts:
1. Visit your camp page
2. Check that all 5 icons display correctly
3. Verify white color on dark background
4. Test hover effects
5. Check mobile responsiveness
