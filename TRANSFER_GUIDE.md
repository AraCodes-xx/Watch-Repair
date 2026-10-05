# 📦 PROJECT TRANSFER GUIDE

## 🎯 How to Transfer This Project to Another Laptop

Follow these steps to successfully transfer your MC Repair Shop project to your classmate's laptop.

---

## 📋 STEP-BY-STEP GUIDE

### **STEP 1: Prepare Files on Your Laptop**

#### **A. Files to DELETE (Not Needed):**

Delete these files/folders before transferring:

```
❌ database/schema.sql (old file)
❌ database/add_payment_status.sql (old file)
❌ database/add_watch_type.sql (old file)
❌ database/migrate_booking_improvements.sql (old file)
❌ DATE_TIME_SLOT_ENHANCEMENT.md (documentation only)
❌ PAYMENT_DETAILS_FEATURE.md (documentation only)
❌ ID_REMOVAL_COMPLETE.md (documentation only)
❌ BOOKING_REFERENCE_SYSTEM.md (if exists - documentation only)
```

**Keep only:**
✅ `database/COMPLETE_DATABASE.sql` (the new complete file)
✅ `TRANSFER_GUIDE.md` (this file)

#### **B. Update Configuration File:**

Before transferring, you need to update the database configuration for localhost (without port 8080).

**File to Edit:** `config/database.php`

**Change FROM:**
```php
define('DB_HOST', 'localhost:8080');
```

**Change TO:**
```php
define('DB_HOST', 'localhost');
```

---

### **STEP 2: Copy Project Folder**

#### **Option A: Using USB Drive**
1. Copy entire `repair_shop` folder
2. Paste to USB drive
3. Give USB to classmate

#### **Option B: Using Cloud (Google Drive, OneDrive)**
1. Compress `repair_shop` folder to ZIP
2. Upload to cloud storage
3. Share link with classmate

#### **Option C: Using Network/Email**
1. Compress `repair_shop` folder to ZIP
2. Send via email or network transfer

---

### **STEP 3: Setup on Classmate's Laptop**

Your classmate should follow these steps:

#### **A. Install XAMPP (if not installed)**
1. Download XAMPP from: https://www.apachefriends.org
2. Install XAMPP
3. Start Apache and MySQL from XAMPP Control Panel

#### **B. Copy Project Files**
1. Open XAMPP installation folder
2. Navigate to `htdocs` folder (usually `C:\xampp\htdocs`)
3. Paste the `repair_shop` folder here
4. Final path should be: `C:\xampp\htdocs\repair_shop`

#### **C. Create Database**
1. Open browser
2. Go to: `http://localhost/phpmyadmin`
3. Click "Import" tab
4. Click "Choose File"
5. Select: `repair_shop/database/COMPLETE_DATABASE.sql`
6. Click "Go" button at bottom
7. Wait for success message

#### **D. Verify Database Name**

Make sure database name matches your config file:

**Check in phpMyAdmin:**
- Database should be named: `repair_shop`

**Check in config file:** `config/database.php`
```php
define('DB_NAME', 'repair_shop');
```

If different, either:
- Rename database in phpMyAdmin, OR
- Update DB_NAME in config file

---

### **STEP 4: Test the System**

#### **A. Access the Website**
Open browser and go to:
```
http://localhost/repair_shop
```

#### **B. Test Admin Login**
1. Go to: `http://localhost/repair_shop/admin/login.php`
2. Username: `admin`
3. Password: `password`
4. Click Login

#### **C. Test User Registration**
1. Go to: `http://localhost/repair_shop/user/register.php`
2. Create a test account
3. Login and test booking

---

## 🔧 CONFIGURATION DIFFERENCES

### **Your Laptop (Port 8080):**
```php
// config/database.php
define('DB_HOST', 'localhost:8080');
define('SITE_URL', 'http://localhost:8080/repair_shop');
```

### **Classmate's Laptop (No Port):**
```php
// config/database.php
define('DB_HOST', 'localhost');
define('SITE_URL', 'http://localhost/repair_shop');
```

---

## 📁 FOLDER STRUCTURE

After transfer, the structure should be:

```
C:\xampp\htdocs\repair_shop\
├── admin\                  (Admin pages)
├── assets\                 (CSS, JS, images)
├── config\                 (Configuration files)
├── database\               (SQL file)
│   └── COMPLETE_DATABASE.sql  ← Import this!
├── uploads\                (Payment proofs - may be empty)
├── user\                   (User pages)
├── index.php               (Homepage)
└── TRANSFER_GUIDE.md       (This file)
```

---

## ⚠️ COMMON ISSUES & SOLUTIONS

### **Issue 1: "Access Denied" Error**
**Cause:** Wrong database credentials
**Solution:**
1. Open `config/database.php`
2. Check these values:
```php
define('DB_HOST', 'localhost');      // No port number
define('DB_USER', 'root');           // Default XAMPP user
define('DB_PASS', '');               // Empty for XAMPP
define('DB_NAME', 'repair_shop');    // Database name
```

### **Issue 2: "Database Not Found"**
**Cause:** Database not imported
**Solution:**
1. Go to phpMyAdmin: `http://localhost/phpmyadmin`
2. Import `database/COMPLETE_DATABASE.sql`
3. Refresh the page

### **Issue 3: "Cannot Connect to Database"**
**Cause:** MySQL not running
**Solution:**
1. Open XAMPP Control Panel
2. Click "Start" next to MySQL
3. Wait for green highlight
4. Refresh browser

### **Issue 4: Blank Page or Errors**
**Cause:** PHP errors
**Solution:**
1. Check if Apache is running in XAMPP
2. Check if files are in correct folder: `C:\xampp\htdocs\repair_shop`
3. Try accessing: `http://localhost/repair_shop/index.php`

### **Issue 5: Images/Styles Not Loading**
**Cause:** Wrong SITE_URL
**Solution:**
1. Open `config/config.php`
2. Update:
```php
define('SITE_URL', 'http://localhost/repair_shop');
```

---

## 📝 CHECKLIST FOR CLASSMATE

Before transferring, make sure:

**On Your Laptop:**
- [ ] Delete old SQL files (keep only COMPLETE_DATABASE.sql)
- [ ] Delete documentation files (.md files except TRANSFER_GUIDE.md)
- [ ] Update `config/database.php` to use `localhost` (no port)
- [ ] Update `config/config.php` SITE_URL to `http://localhost/repair_shop`
- [ ] Compress folder to ZIP (optional)

**On Classmate's Laptop:**
- [ ] XAMPP installed
- [ ] Apache started (green in XAMPP)
- [ ] MySQL started (green in XAMPP)
- [ ] Project folder in `C:\xampp\htdocs\repair_shop`
- [ ] Database imported via phpMyAdmin
- [ ] Can access: `http://localhost/repair_shop`
- [ ] Can login as admin (username: admin, password: password)

---

## 🎓 DEFAULT ACCOUNTS

### **Admin Account:**
- URL: `http://localhost/repair_shop/admin/login.php`
- Username: `admin`
- Password: `password`

### **User Account:**
- URL: `http://localhost/repair_shop/user/register.php`
- Create new account (no default user)

---

## 💡 TIPS

1. **Keep Original Copy:** Don't delete your original project until classmate confirms it works
2. **Test First:** Test on your laptop after making changes
3. **Document Issues:** If classmate has issues, note the error messages
4. **Backup Database:** After classmate sets up, they should export database as backup
5. **Change Passwords:** Tell classmate to change admin password after setup

---

## 🆘 QUICK TROUBLESHOOTING

**Problem:** Can't access website
**Check:**
1. Is Apache running? (XAMPP Control Panel)
2. Is URL correct? (`http://localhost/repair_shop`)
3. Are files in `C:\xampp\htdocs\repair_shop`?

**Problem:** Database errors
**Check:**
1. Is MySQL running? (XAMPP Control Panel)
2. Was database imported? (Check phpMyAdmin)
3. Is `config/database.php` correct?

**Problem:** Login not working
**Check:**
1. Was `COMPLETE_DATABASE.sql` imported? (Contains admin account)
2. Try default credentials: admin / password
3. Check database has `admin` table with data

---

## 📞 SUPPORT

If issues persist:
1. Check error messages carefully
2. Verify all steps were followed
3. Check XAMPP error logs
4. Try accessing phpMyAdmin to verify database exists

---

## ✅ SUCCESS INDICATORS

You'll know it's working when:
- ✅ Homepage loads at `http://localhost/repair_shop`
- ✅ Can login as admin
- ✅ Can register new user
- ✅ Can create test booking
- ✅ No error messages appear

---

**Good luck with the transfer!** 🚀

If you follow these steps carefully, the transfer should be smooth and successful.
