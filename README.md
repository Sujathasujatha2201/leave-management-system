# Leave Management System

A web-based Leave Management System for managing student leave applications and approval workflows.

## Features

- Student login and leave application
- Faculty Advisor approval and rejection
- HOD approval workflow
- Department-based leave routing
- Leave status tracking
- Automatic 48-hour leave locking
- HOD lock-release workflow
- Email notifications
- Secure authentication
- MySQL database integration

## Leave Workflow

Student → Faculty Advisor → HOD

If the advisor does not take action within 48 hours, the leave request is automatically locked and can be released by the HOD.

## Technologies

- HTML5
- CSS3
- Bootstrap
- JavaScript
- jQuery
- AJAX
- PHP
- MySQL
- XAMPP
- PHPMailer

## Database

MySQL is used to manage:

- User accounts
- Student details
- Faculty and HOD details
- Leave requests
- Student-advisor assignments
- Leave statuses

## How to Run

1. Install XAMPP with Apache and MySQL.
2. Place the project inside `C:\xampp\htdocs\`.
3. Start Apache and MySQL from XAMPP.
4. Open `http://localhost/phpmyadmin`.
5. Create the required database and import the SQL file.
6. Configure the database connection.
7. Open the project using the localhost URL.

## Developer

**Sujatha S**  
B.Tech – Computer Science and Business Systems
