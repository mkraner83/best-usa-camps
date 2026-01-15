# Build Instructions for WordPress Plugin ZIP

## CRITICAL: Plugin Folder Name

**The ZIP file MUST contain a folder named `creativedbs-camp-mgmt/` to properly overwrite the existing plugin in WordPress.**

### Why This Matters
- WordPress identifies plugins by their folder name
- If the folder name doesn't match, WordPress will install it as a NEW plugin instead of updating the existing one
- This causes duplicate plugins and fatal errors

### Correct Structure
```
creativedbs-camp-mgmt-v2.x.x.zip
└── creativedbs-camp-mgmt/
    ├── creativedbs-camp-mgmt.php (main plugin file)
    ├── includes/
    ├── assets/
    └── (all other files)
```

### Wrong Structure (DO NOT USE)
```
creativedbs-camp-mgmt-v2.x.x.zip
└── best-usa-camps/   ← WRONG FOLDER NAME
    ├── creativedbs-camp-mgmt.php
    └── ...
```

## How to Build the ZIP File

### Method 1: Using the Build Command
```bash
cd /Users/matjazkraner/best-usa-camps
cd ..
rm -f creativedbs-camp-mgmt-v2.x.x.zip
mkdir -p creativedbs-camp-mgmt
cp -R best-usa-camps/* creativedbs-camp-mgmt/
zip -r creativedbs-camp-mgmt-v2.x.x.zip creativedbs-camp-mgmt/ \
  -x "*.git*" "*.DS_Store" "*node_modules/*" "*.bak*" \
  "*screenshoots/*" "*debug-*.php" "*.md" "*.xml.dist" -q
rm -rf creativedbs-camp-mgmt
mv creativedbs-camp-mgmt-v2.x.x.zip best-usa-camps/
```

### What Gets Excluded from ZIP
- `.git*` - Git files and folders
- `.DS_Store` - macOS system files
- `node_modules/` - NPM dependencies (if any)
- `*.bak*` - Backup files
- `screenshoots/` - Screenshot folder
- `debug-*.php` - Debug scripts
- `*.md` - Markdown documentation files
- `*.xml.dist` - Development config files

### What Gets Included
✅ All PHP files  
✅ CSS and JavaScript files  
✅ Images and assets  
✅ Class files and includes  
✅ Templates  

## Upload to WordPress

**⚠️ CRITICAL: Always deactivate the existing plugin BEFORE uploading the new version!**

1. Go to **WordPress Admin → Plugins**
2. Find "Creative DBS Camp Management" and click **Deactivate**
3. Click **Add New → Upload Plugin**
4. Choose the ZIP file
5. Click **Install Now**
6. After installation completes, click **Activate Plugin**

### Why Deactivate First?
WordPress loads plugin classes when they're active. If you try to replace an active plugin, PHP will throw fatal errors because classes are already declared in memory. Deactivating first allows the new files to overwrite cleanly.

## Version Numbering

Update version in these files before building:
1. `creativedbs-camp-mgmt.php` - Header comment and `CDBS_CAMP_VERSION` constant
2. Restore point file name (if creating one)

## Troubleshooting

### Error: "Plugin could not be activated because it triggered a fatal error"
- This happens when the folder name doesn't match
- Delete BOTH duplicate plugins from WordPress
- Rebuild ZIP with correct folder name
- Upload fresh copy

### Error: Duplicate Plugin Appears
- The folder name in the ZIP was wrong
- Delete both plugins
- Rebuild ZIP with `creativedbs-camp-mgmt/` folder name
- Upload again

### Plugin Won't Overwrite
- Deactivate the existing plugin first
- Then upload the new ZIP
- WordPress should offer to replace it

## Quick Reference

**Development Folder:** `/Users/matjazkraner/best-usa-camps/`  
**Plugin Folder Name in ZIP:** `creativedbs-camp-mgmt/`  
**Main Plugin File:** `creativedbs-camp-mgmt.php`  
**WordPress Plugin Slug:** `creativedbs-camp-mgmt`

---

**REMEMBER:** Always use `creativedbs-camp-mgmt/` as the folder name in the ZIP file!
