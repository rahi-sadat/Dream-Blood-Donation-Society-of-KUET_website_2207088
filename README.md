# Dream - Blood Donation Society of KUET

A PHP and MySQL web application for DREAM, the voluntary blood donation society of KUET. The site helps users register as donors, search available donors, post urgent blood requests, track submitted requests, and view DREAM campaigns, committees, volunteers, and donation summaries.

## Author

**Name:** Rahi Sadat Ruhan  
**Roll:** 2207088

## What It Does

- Shows the DREAM KUET homepage with society information, contact details, campaigns, committee photos, volunteer photos, and blood donation summaries.
- Allows users to create an account and log in securely.
- Lets users register as blood donors with blood group, district, phone number, and donation availability.
- Provides donor search by blood group and district.
- Allows logged-in users to submit blood requests with patient, hospital, contact, urgency, and date details.
- Lists blood requests with filters for blood group, district, urgency, and status.
- Lets eligible logged-in donors show interest in active blood requests.
- Gives users a profile page where they can update donor information and view their own submitted requests.
- Includes static campaign and gallery pages for DREAM activities.
- Provides an admin dashboard for managing committee photos, volunteer photos, campaigns, and blood donation summary images.

## Technologies Used

- PHP
- MySQL / MariaDB
- HTML
- CSS
- JavaScript
- XAMPP or any local PHP/MySQL environment

## Project Structure

```text
.
|-- index.php                 # Main homepage with navigation, about, contact, people, and summary sections
|-- register.php              # User registration page
|-- login.php                 # User login page
|-- logout.php                # User logout handler
|-- join-us.php               # DREAM member/donor joining page
|-- profile.php               # User profile and personal blood request dashboard
|-- find-donors.php           # Donor search page
|-- blood-requests.php        # Blood request listing and donor interest handling
|-- add-request.php           # Blood request form
|-- submit_request.php        # Blood request submission handler
|-- blood-summary.php         # Blood donation summary gallery page
|-- people.php                # Committee and volunteer gallery page
|-- campaigns.html            # Legacy static campaign fragment kept as reference
|-- campaigns.php             # Database-backed campaign content loaded by the homepage
|-- database.sql              # Database creation script and required tables
|-- style.css                 # Main stylesheet
|-- script.js                 # Frontend interactions and page behavior
|-- config/
|   |-- database.php          # PDO database connection settings
|   |-- districts.php         # Bangladesh district list
|   |-- admin.php             # Admin role, schema, CSRF, and image upload helpers
|   `-- member_schema.php     # Helper for member-related database columns
|-- admin/
|   |-- dashboard.php         # Admin dashboard
|   |-- gallery.php           # Committee and volunteer photo manager
|   |-- campaigns.php         # Campaign manager
|   `-- summary.php           # Blood donation summary manager
|-- images/                   # Project images, campaign photos, logo, and galleries
`-- data/                     # Reserved project data directory
```

## Local Setup

### 1. Install Requirements

Install and start a local PHP/MySQL environment. XAMPP is recommended because the project already uses XAMPP-style MySQL settings.

Required services:

- Apache
- MySQL

### 2. Place the Project in the Server Directory

Copy or keep the project inside your local web server directory. For XAMPP, a common location is:

```text
D:\xampp\htdocs\Dream-Blood-Donation-Society
```

### 3. Create the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Then import the `database.sql` file. It creates the database:

```text
dream_blood_donation
```

and the required tables:

- `users`
- `blood_requests`
- `donation_responses`
- `gallery_items`
- `blood_summaries`
- `campaigns`
- `campaign_images`
- `site_settings`

You can also import it from the command line if MySQL is available:

```bash
mysql -u root < database.sql
```

### 4. Check Database Configuration

The database connection is configured in:

```text
config/database.php
```

Default settings:

```php
$dbHost = '127.0.0.1';
$dbName = 'dream_blood_donation';
$dbUser = 'root';
$dbPass = '';
```

If your MySQL username or password is different, update this file.

### 5. Run the Project

Start Apache and MySQL from XAMPP, then visit:

```text
http://localhost/Dream-Blood-Donation-Society/index.php
```

If the folder name is different, replace `Dream-Blood-Donation-Society` with your actual project folder name.

You can also run the project with PHP's built-in server from the project root:

```bash
php -S localhost:8000
```

Then open:

```text
http://localhost:8000/index.php
```

MySQL must still be running for database features to work.

## Main Pages

- `index.php` - homepage, about section, campaigns, contact information, committee/volunteer links, and blood donation summary.
- `register.php` - creates a general donor user account.
- `join-us.php` - lets users join DREAM as a member and donor.
- `login.php` - logs existing users in.
- `profile.php` - manages user profile, donor availability, and submitted requests.
- `find-donors.php` - searches available donors.
- `blood-requests.php` - shows blood requests and lets donors express interest.
- `add-request.php` - collects a new blood request.
- `submit_request.php` - saves a new blood request.
- `blood-summary.php` - shows donation summary images.
- `people.php` - shows committee and volunteer galleries.
- `admin/dashboard.php` - admin-only dashboard for managing public content.
- Campaigns can have one main image plus extra slider photos from the admin campaign edit page.

## Admin Setup

The admin uses the same login page as normal users. Register one account first, then promote it from phpMyAdmin or MySQL:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your_email@example.com';
```

After that, login through `login.php`. Admin users are redirected to:

```text
admin/dashboard.php
```

Admin image uploads are saved under:

```text
images/admin/
```

## Notes

- The application expects valid Bangladeshi mobile numbers in the `01XXXXXXXXX` format.
- Some pages require login for profile access, adding requests, and donor response actions.
- The database connection error message asks users to start MySQL and import `database.sql` if setup is incomplete.
