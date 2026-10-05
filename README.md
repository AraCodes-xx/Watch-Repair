# Watch Repair Shop Website

A comprehensive watch repair shop booking and management system with customer portal and admin dashboard.

## Features

### User Side (Customer Portal)
- **Home Page**: Display services, reviews, and contact information
- **User Authentication**: Login and signup with email validation
- **Booking System**: Interactive calendar with time slot selection
- **Payment Integration**: 20% down payment with GCash proof upload
- **My Bookings**: View booking history and status
- **User Profile**: Update personal information and change password
- **Feedback & Ratings**: Rate technicians after service completion
- **Notifications**: Real-time updates on booking status

### Admin Side (Management Dashboard)
- **Dashboard**: Overview metrics and statistics
- **Booking Management**: Approve/reject bookings, assign technicians
- **Payment Verification**: Verify uploaded payment proofs
- **Service Management**: Full CRUD operations for services
- **Technician Management**: Manage technician profiles and schedules
- **Time Slot Management**: Configure available booking time slots
- **Feedback Management**: Review and approve customer feedback

## Technologies Used
- **Frontend**: HTML5, CSS3, JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL
- **Server**: XAMPP (Apache + MySQL)

## Installation Instructions

### 1. Prerequisites
- XAMPP installed on your system
- Web browser (Chrome, Firefox, Edge, etc.)

### 2. Setup Steps

#### Step 1: Start XAMPP
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** modules

#### Step 2: Create Database
1. Open your web browser and go to `http://localhost/phpmyadmin`
2. Click on "SQL" tab
3. Copy and paste the entire content from `database/schema.sql`
4. Click "Go" to execute the SQL script
5. The database `watch_repair_shop` will be created with sample data

#### Step 3: Configure Application
1. The project is already configured for XAMPP default settings:
   - Database Host: `localhost`
   - Database User: `root`
   - Database Password: `` (empty)
   - Database Name: `watch_repair_shop`

2. If your XAMPP uses different settings, edit `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', ''); // Change if you have a password
   define('DB_NAME', 'watch_repair_shop');
   ```

#### Step 4: Create Upload Directory
The system will automatically create the upload directory, but you can manually create it:
1. Navigate to `c:/xampp/htdocs/repair_shop/`
2. Create a folder named `uploads`
3. Ensure it has write permissions

#### Step 5: Access the Application
1. **Customer Portal**: `http://localhost/repair_shop/`
2. **Admin Panel**: `http://localhost/repair_shop/admin/login.php`

## Default Login Credentials

### Admin Account
- **Username**: `admin`
- **Password**: `password`

### Test User Account
You can create a new user account by clicking "Sign Up" on the customer portal.

## Project Structure

```
repair_shop/
├── admin/                      # Admin panel files
│   ├── dashboard.php          # Admin dashboard
│   ├── view_booking.php       # View all bookings
│   ├── edit_booking.php       # Edit/manage bookings
│   ├── view_payment.php       # Payment verification
│   ├── view_service.php       # Service management
│   ├── view_technician.php    # Technician management
│   ├── view_timeslot.php      # Time slot management
│   ├── view_feedback.php      # Feedback management
│   └── login.php              # Admin login
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet
├── config/
│   ├── config.php             # Main configuration
│   └── database.php           # Database connection
├── database/
│   └── schema.sql             # Database schema
├── user/                       # Customer portal files
│   ├── dashboard.php          # User dashboard
│   ├── booking.php            # Booking page
│   ├── my_bookings.php        # View bookings
│   ├── profile.php            # User profile
│   ├── notifications.php      # Notifications
│   ├── add_feedback.php       # Add feedback
│   ├── login.php              # User login
│   └── signup.php             # User registration
├── uploads/                    # Payment proof uploads
├── index.php                   # Homepage
└── README.md                   # This file
```

## Usage Guide

### For Customers

1. **Register an Account**
   - Click "Login" → "Sign up here"
   - Fill in your details
   - Login with your credentials

2. **Book a Service**
   - Click "Book Now" from homepage or dashboard
   - Select a service
   - Choose date and time slot (Sundays are disabled)
   - Describe the problem
   - Confirm your address
   - Pay 20% down payment via GCash to: **0917-123-4567**
   - Upload payment screenshot
   - Submit booking

3. **Track Your Booking**
   - Go to "My Bookings" to see status
   - View booking details
   - Check notifications for updates

4. **Rate Your Experience**
   - After service completion, click "Rate" button
   - Give a star rating (1-5)
   - Leave a comment
   - Submit feedback

### For Administrators

1. **Login to Admin Panel**
   - Go to `http://localhost/repair_shop/admin/login.php`
   - Use admin credentials

2. **Manage Bookings**
   - View all bookings
   - Approve or reject bookings
   - Assign technicians
   - Update booking status
   - Add admin remarks

3. **Verify Payments**
   - View payment proofs
   - Approve or reject payments
   - Add verification remarks

4. **Manage Services**
   - Add new services
   - Edit service details and pricing
   - Activate/deactivate services

5. **Manage Technicians**
   - Add new technicians
   - Update technician information
   - View performance ratings
   - Set availability status

6. **Configure Time Slots**
   - Add new time slots
   - Edit existing slots
   - Activate/deactivate slots

7. **Review Feedback**
   - View all customer feedback
   - Approve feedback for homepage display
   - Delete inappropriate feedback

## Key Features Explained

### Booking System
- **Calendar**: Interactive calendar that disables past dates and Sundays
- **Time Slots**: 1-2 hour intervals (customizable by admin)
- **Validation**: Prevents double-booking of time slots

### Payment System
- **20% Down Payment**: Automatically calculated based on service price
- **Proof Upload**: Customers upload GCash payment screenshot
- **Admin Verification**: Manual verification by admin before approval

### Notification System
- Real-time notifications for:
  - Booking approval/rejection
  - Payment verification
  - Technician assignment
  - Service completion

### Security Features
- Password hashing using PHP's `password_hash()`
- SQL injection prevention using prepared statements
- Input sanitization
- Session management
- File upload validation

## Database Tables

1. **admin** - Admin user credentials
2. **users** - Customer accounts
3. **services** - Available repair services
4. **technicians** - Technician profiles
5. **timeslots** - Available time slots
6. **bookings** - Service bookings
7. **payments** - Payment records
8. **feedback** - Customer reviews
9. **notifications** - User notifications

## Customization

### Change Site Name
Edit `config/config.php`:
```php
define('SITE_NAME', 'Your Shop Name');
```

### Change Payment Percentage
Edit `config/config.php`:
```php
define('DOWN_PAYMENT_PERCENTAGE', 20); // Change to desired percentage
```

### Add More Services
1. Login to admin panel
2. Go to "Services" → "Add New Service"
3. Fill in service details

### Modify Time Slots
1. Login to admin panel
2. Go to "Time Slots" → "Add New Time Slot"
3. Set start and end times

## Troubleshooting

### Database Connection Error
- Ensure MySQL is running in XAMPP
- Check database credentials in `config/database.php`
- Verify database `watch_repair_shop` exists

### Upload Directory Error
- Create `uploads` folder in project root
- Ensure folder has write permissions

### Login Issues
- Clear browser cache and cookies
- Check if database tables are created properly
- Verify password is correct (default: `password`)

### Calendar Not Working
- Ensure JavaScript is enabled in browser
- Check browser console for errors

## Support

For issues or questions:
- Check the troubleshooting section
- Review the code comments
- Verify XAMPP is running properly

## License

This project is created for educational purposes.

## Credits

Developed as a comprehensive watch repair shop management system with modern UI/UX design and full CRUD operations.
