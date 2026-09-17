# Leave Management System

A web-based Leave Management System designed to simplify and automate the process of applying, reviewing, approving, rejecting, and tracking student leave requests.

## 📌 Overview

The Leave Management System provides a centralized platform for students, faculty advisors, and Heads of Departments (HODs) to manage leave requests efficiently.

The system follows a structured approval workflow where leave applications are reviewed by the appropriate department advisor and, when required, forwarded to the HOD.

## ✨ Key Features

- Student login and authentication
- Student leave application
- Faculty/Advisor leave approval and rejection
- HOD approval workflow
- Department-based leave routing
- Leave status tracking
- Automatic leave locking after 48 hours
- HOD lock-release workflow
- Email notifications for leave status updates
- Rejection reason notification
- Session-based authentication
- Secure password hashing
- MySQL database integration
- Responsive web interface

## 🔄 Leave Approval Workflow

```text
Student
   ↓
Apply for Leave
   ↓
Department Advisor
   ↓
Approve / Reject
   ↓
If Approved
   ↓
HOD
   ↓
Final Approval
Automatic Lock Workflow
Leave Pending with Advisor
          ↓
      48 Hours
          ↓
    Automatically Locked
          ↓
         HOD
          ↓
     Release Lock
          ↓
       Advisor
          ↓
   Approve / Reject
👥 User Roles
Student
Apply for leave
View submitted leave requests
Track leave status
Receive status notifications
Faculty / Advisor
View assigned students' leave requests
Approve or reject leave
Provide rejection reasons
Manage pending leave requests
HOD
Review department leave requests
Approve or reject eligible requests
Release automatically locked requests
🛠️ Technologies Used
Frontend
HTML5
CSS3
Bootstrap
JavaScript
jQuery
AJAX
Backend
PHP
Database
MySQL
Development Environment
XAMPP
Apache
Visual Studio Code
Email
PHPMailer
Gmail SMTP
🗄️ Database

The system uses MySQL for storing:

User accounts
Student information
Faculty and HOD information
Leave requests
Student-advisor assignments
Leave approval statuses
🔐 Security
Passwords are stored using secure password hashing.
PHP prepared statements are used for database operations.
Session-based authentication is used for role-based access.
Sensitive credentials are kept outside the public repository.
⏱️ Automatic 48-Hour Lock

If an advisor does not take action on a pending leave request within 48 hours, the system automatically changes the leave status to locked.

This process is handled automatically without requiring the faculty dashboard to remain open.

📧 Email Notifications

The system provides email notifications for important leave events, including:

Leave approval
Leave rejection
Rejection reason
Automatic leave locking
🚀 How to Run
1. Install XAMPP

Install XAMPP with:

Apache
MySQL
2. Place the project

Copy the project into:

C:\xampp\htdocs\
3. Start XAMPP

Start:

Apache
MySQL
4. Create the database

Open:

http://localhost/phpmyadmin

Create the required database and import the SQL file provided in the project.

5. Configure database connection

Update the database configuration according to your local MySQL setup.

6. Open the application

Open the project through the XAMPP localhost URL.

📁 Project Structure
Leave Management System
│
├── backend/
├── frontend/
├── uploads/
├── database_setup.sql
├── db.php
├── login.php
├── logout.php
├── index.php
├── composer.json
├── composer.lock
└── README.md
🎯 Project Objective

The main objective of this project is to replace manual leave processing with a centralized digital workflow that improves transparency, reduces processing delays, and allows students and authorities to track leave requests efficiently.

🔮 Future Enhancements
Mobile application
Attendance integration
Advanced analytics dashboard
Push notifications
Leave balance management
Cloud deployment
Multi-college support
👩‍💻 Developer

Sujatha S

B.Tech – Computer Science and Business Systems
