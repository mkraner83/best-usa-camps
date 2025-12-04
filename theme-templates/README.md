# Camp Login Page Templates - Installation Instructions

## Overview
These custom page templates will display the WordPress login forms within your Astra theme's design (with header, navigation, and footer).

## Installation Steps

### Step 1: Upload Templates to Your Astra Child Theme

1. **Connect to your server** via FTP or File Manager
2. **Navigate to:** `/wp-content/themes/astra-child/`
3. **Upload these files:**
   - `template-camp-login.php`
   - `template-camp-lost-password.php`

### Step 2: Create WordPress Pages

1. **Login to WordPress Admin**
2. **Go to:** Pages → Add New

#### Create Login Page:
- **Title:** Camp Login
- **Permalink:** `/camp-login/`
- **Template:** Select "Camp Login" from the Template dropdown (right sidebar)
- **Publish** the page

#### Create Lost Password Page:
- **Title:** Camp Lost Password
- **Permalink:** `/camp-lost-password/`
- **Template:** Select "Camp Lost Password" from the Template dropdown
- **Publish** the page

### Step 3: Update Links in Your Site

Update all login links to point to the new pages:
- Change `/wp-login.php` to `/camp-login/`
- Change `/wp-login.php?action=lostpassword` to `/camp-lost-password/`

### Step 4: Add Google Font (if needed)

If the "Annie Use Your Telescope" font isn't loading, add this to your Astra child theme:

**Option A - Add to functions.php:**
```php
add_action( 'wp_enqueue_scripts', 'enqueue_annie_font' );
function enqueue_annie_font() {
    wp_enqueue_style( 'annie-font', 'https://fonts.googleapis.com/css2?family=Annie+Use+Your+Telescope&display=swap' );
}
```

**Option B - Add via Customizer:**
Go to Appearance → Customize → Additional CSS and add:
```css
@import url('https://fonts.googleapis.com/css2?family=Annie+Use+Your+Telescope&display=swap');
```

## Features

### Login Page (`/camp-login/`)
- Custom styled login form with your site's header/footer
- Green color scheme (#497C5E)
- Annie Use Your Telescope font for headings
- Redirects to `/user-dashboard/` after login
- Shows "already logged in" message if user is authenticated

### Lost Password Page (`/camp-lost-password/`)
- Custom styled password reset form
- Matches login page design
- Email instructions sent via WordPress
- Success/error messages with green styling

## Customization

### Change Redirect After Login
Edit `template-camp-login.php`, line with `'redirect'`:
```php
'redirect' => home_url( '/your-custom-page/' ),
```

### Change Form Styles
All CSS is inline in each template file. Look for the `<style>` section and modify colors, fonts, spacing, etc.

### Add Custom Messages
Edit the PHP sections in each template to customize error/success messages.

## Troubleshooting

**Template not showing in dropdown?**
- Make sure files are in `/wp-content/themes/astra-child/` (not a subfolder)
- File must start with the Template Name comment
- Refresh the WordPress admin page

**Form not styling correctly?**
- Check if Astra CSS is overriding styles
- May need to add `!important` to some CSS rules
- Use browser inspector to check for conflicts

**Redirect not working?**
- Check that `/user-dashboard/` page exists
- Verify user has proper role and permissions

## Support

For issues specific to the Camp Management plugin, check the main plugin documentation.
