# Quick Start Guide

## 🚀 Get Started in 3 Minutes

### Step 1: Start XAMPP (30 seconds)
1. Open **XAMPP Control Panel**
2. Click **Start** for **Apache**
3. Click **Start** for **MySQL**

### Step 2: Create Database (1 minute)
1. Open browser: `http://localhost:8080/phpmyadmin`
2. Click **SQL** tab
3. Open file: `repair_shop/database/schema.sql`
4. Copy **ALL** content and paste
5. Click **Go**

### Step 3: Access Website (30 seconds)
- **Homepage**: `http://localhost:8080/repair_shop/`
- **Login (User & Admin)**: `http://localhost:8080/repair_shop/login.php`

### Step 4: Login (30 seconds)
**Admin Credentials:**
- Username: `admin`
- Password: `password`

**Customer:**
- Click "Sign Up" to create account

---

## ✅ Quick Test

### Test Customer Flow:
1. Go to `http://localhost:8080/repair_shop/`
2. Click "Login" → "Sign up here" → Create account
3. Login with your credentials
4. Click "Book Now"
5. Select service, date, time
6. Upload fake payment screenshot
7. Submit booking

### Test Admin Flow:
1. Go to `http://localhost:8080/repair_shop/login.php`
2. Login with username: `admin`, password: `password`
3. Click "Bookings" → See your test booking
4. Click "Manage" → Approve booking
5. Assign a technician
6. Update status to "Completed"

---

## 📁 Project Structure

```
repair_shop/
├── index.php              ← Homepage
├── admin/                 ← Admin panel
│   ├── login.php         ← Admin login
│   └── dashboard.php     ← Admin dashboard
├── user/                  ← Customer portal
│   ├── login.php         ← Customer login
│   ├── signup.php        ← Registration
│   ├── booking.php       ← Book service
│   └── dashboard.php     ← Customer dashboard
├── config/                ← Configuration
├── database/              ← Database schema
└── uploads/               ← Payment proofs
```

---

## 🎯 Key Features

### Customer Side:
- ✅ Register & Login
- ✅ Book services with calendar
- ✅ Upload payment proof (20% down)
- ✅ Track booking status
- ✅ Rate technicians
- ✅ Receive notifications

### Admin Side:
- ✅ Dashboard with statistics
- ✅ Approve/reject bookings
- ✅ Verify payments
- ✅ Assign technicians
- ✅ Manage services
- ✅ Manage technicians
- ✅ Manage time slots
- ✅ Review feedback

---

## 🔧 Common Issues

**Database Error?**
→ Make sure MySQL is running in XAMPP

**Can't Upload Files?**
→ Create `uploads` folder in project root

**Login Not Working?**
→ Clear browser cookies and try again

**Blank Page?**
→ Check if Apache is running

---

## 📞 Default Settings

- **Database**: `watch_repair_shop`
- **Admin User**: `admin`
- **Admin Pass**: `password`
- **Down Payment**: 20%
- **Max Upload**: 5MB
- **Closed Days**: Sundays

---

## 🎨 Customization

**Change Site Name:**
Edit `config/config.php` → `SITE_NAME`

**Change Down Payment %:**
Edit `config/config.php` → `DOWN_PAYMENT_PERCENTAGE`

**Add Services:**
Admin Panel → Services → Add New Service

**Add Technicians:**
Admin Panel → Technicians → Add New Technician

---

## 📚 Full Documentation

For detailed instructions, see:
- `README.md` - Complete documentation
- `INSTALLATION.txt` - Detailed installation guide

---

## 🎉 You're Ready!

Your watch repair shop system is now ready to use. Start by creating a customer account and making your first booking!
