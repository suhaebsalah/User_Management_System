# User Management System

A clean PHP OOP-based user management dashboard for managing employees, departments, roles, and admin profiles. This project was built to run locally with XAMPP and MySQL and includes login, CRUD operations, search/filtering, image uploads, and AJAX-based user listing.

## Project Start Date

January 2026

## Demo

The app provides a dashboard where an admin can:

- log in securely
- add new users
- edit existing users
- delete users
- filter users by department, status, and salary
- search by name, email, role, or department
- manage departments and roles
- update admin profile details
- upload user profile images

## Features

### Admin Authentication
- Admin login page with username and password validation
- Session-based access control
- Protected pages for admin-only functionality

### User Management
- Add users with first name, last name, email, department, role, salary, status, and notes
- Update user records
- Delete users from the dashboard
- Image upload support with thumbnail handling

### Department and Role Management
- Add, edit, and delete departments
- Add, edit, and delete roles
- Assign roles and departments to users

### Search and Filters
- Live search by name, email, role, and department
- Department filter
- Status filter
- Salary range filter
- Pagination/infinite scroll style loading in the dashboard

### UI
- Responsive dashboard layout
- Modern admin panel styling
- Alert messages and validation feedback
- Modal forms for add/edit actions

## Tech Stack

- PHP (Object-Oriented Programming)
- MySQL
- JavaScript
-ajax
- HTML/CSS
- XAMPP

## Project Structure

```text
User_Management_System/
├── api.php
├── delete.php
├── edit.php
├── index.php
├── insert_data.php
├── login.php
├── profile.php
├── seach_living.php
├── assets/
│   ├── css/
│   ├── js/
│   └── uploads/
├── classes/
│   ├── admin.php
│   ├── database_obj.php
│   ├── db.php
│   ├── department.php
│   ├── function.php
│   ├── init.php
│   ├── role.php
│   ├── session.php
│   └── user.php
├── includes/
│   ├── footer.php
│   ├── header.php
│   └── navBar.php
└── README.md
```

## Database Setup

This project expects a MySQL database named `company_system`.

The application uses the following core tables:

- `admin`
- `users`
- `departments`
- `roles`

You can create the database in phpMyAdmin or MySQL and make sure it matches the project expectations.

### Example database connection

The connection is configured in `classes/db.php`:

```php
$this->db = new mysqli("localhost", "root", "", "company_system");
```

If your local MySQL uses a different username/password, update that file accordingly.

## Local Setup

### 1. Install XAMPP
Download and install XAMPP, then start:

- Apache
- MySQL

### 2. Place the project in htdocs
Move this project into:

```text
C:\xampp\htdocs\
```

Example:

```text
C:\xampp\htdocs\User_Management_System
```

### 3. Create the database
Open phpMyAdmin and create a database named:

```text
company_system
```

### 4. Run the app
Open your browser and visit:

```text
http://localhost/User_Management_System/login.php
```

## Login

After setting up the database and admin record, access the login page and sign in with your admin username and password.

## Main Files

- `login.php` — admin authentication page
- `index.php` — user dashboard and main management screen
- `insert_data.php` — add-user logic
- `edit.php` — edit-user logic and modal handling
- `profile.php` — admin profile and role/department management
- `api.php` — API-like validation and responses
- `seach_living.php` — AJAX search and live filtering
- `classes/` — model and database logic

## Notes

- This project is built for local development and learning purposes.
- Image uploads require writable upload folders.
- Make sure `assets/uploads/` and `assets/uploads/thumbs/` are writable by the web server.
- The admin account should be created in the `admin` table before login is possible.

## Future Improvements

Possible enhancements for this project:

- user role-based permissions
- better password hashing and security policies
- CSV export/import
- pagination with server-side filtering
- REST API integration
- email notifications
- improved validations and tests

## License

This project is for educational and local development use.

## Author

Built as a PHP user management dashboard for efficient employee administration.
