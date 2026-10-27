# 🎓 Student Registration System

A full-stack, secure, and modern PHP & MySQL web application for student admissions, course enrollment, digital admit slips, student portal, and administrative student management.

---

## 📁 Directory Structure

```text
student-registration/
│
├── index.php             # Landing / Home Page with Portal overview & Quick Stats
├── register.php          # Student Registration Form with real-time validation & photo upload
├── success.php           # Registration Confirmation Slip (Official printable card / admit slip)
├── login.php             # Student Portal Login (using Email & Date of Birth)
├── dashboard.php         # Student Dashboard (Profile, course details & actions)
├── edit_student.php      # Edit Student Details (Accessible by Admin or Student)
├── delete_student.php    # Secure Deletion of Student Records (Admin-only + CSRF protection)
├── logout.php            # Session & Cookie destruction for Student & Admin
├── database.sql          # Complete MySQL database creation & seed data script
│
├── config/
│   └── db.php            # Database PDO connection, Auto-Installer, and security helpers
│
├── admin/
│   ├── login.php         # Administrator Login Portal (Username & Password)
│   └── dashboard.php     # Admin Management Console (Search, filter, CSV export, edit, delete)
│
├── css/
│   └── style.css         # Modern, responsive styling with clean UI and print media support
│
├── js/
│   └── validation.js     # Instant client-side validations, image preview & live table filter
│
└── uploads/              # Storage directory for student photos (protected from direct execution)
```

---

## 🗄️ Database Design

**Database Name:** `student_registration`

### Table 1: `students`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `INT` | `AUTO_INCREMENT PRIMARY KEY` | Unique student ID |
| `full_name` | `VARCHAR(100)` | `NOT NULL` | Student name |
| `dob` | `DATE` | `NOT NULL` | Date of birth |
| `email` | `VARCHAR(100)` | `NOT NULL UNIQUE` | Registered email address |
| `phone` | `VARCHAR(20)` | `NOT NULL` | Contact phone number |
| `address` | `TEXT` | `NOT NULL` | Residential address |
| `course` | `VARCHAR(100)` | `NOT NULL` | Selected course |
| `semester` | `VARCHAR(20)` | `NOT NULL` | Current academic semester |
| `created_at` | `TIMESTAMP` | `DEFAULT CURRENT_TIMESTAMP` | Registration date & timestamp |

### Table 2: `admins`
| Column | Type | Attributes | Description |
|---|---|---|---|
| `id` | `INT` | `AUTO_INCREMENT PRIMARY KEY` | Admin ID |
| `username` | `VARCHAR(50)` | `NOT NULL UNIQUE` | Administrator username |
| `password_hash` | `VARCHAR(255)` | `NOT NULL` | Bcrypt password hash |

---

## 🔑 Default Login Credentials

### 1. Administrator Portal (`admin/login.php`)
- **Username:** `admin`
- **Password:** `admin123`

### 2. Student Portal (`login.php`)
Students log in using their registered credentials:
- **Email:** e.g., `aarav.sharma@example.com`
- **Date of Birth:** `2003-05-14`

*(Sample accounts are pre-seeded in `database.sql` for immediate testing).*

---

## 🚀 Setup & Installation Instructions

### Option 1: Using XAMPP / WAMP / Laragon
1. Copy the `student-registration` directory into your web root:
   - For XAMPP: `C:\xampp\htdocs\student-registration`
   - For WAMP: `C:\wamp64\www\student-registration`
2. Start **Apache** and **MySQL** from your control panel.
3. Open [phpMyAdmin](http://localhost/phpmyadmin) in your browser.
4. Create a database named `student_registration` (or let the app auto-create it!).
5. Click **Import** and select the `database.sql` file.
6. Open your browser and navigate to:
   ```url
   http://localhost/student-registration/
   ```

### Option 2: Using PHP Built-In Server
If PHP and MySQL are installed in your command line:
1. Navigate to the project root:
   ```bash
   cd c:\Users\vinud\OneDrive\Desktop\Registration\student-registration
   ```
2. Start the PHP development server:
   ```bash
   php -S localhost:8000
   ```
3. Open `http://localhost:8000/` in your browser.

> **Note on Self-Healing Auto-Installer:**
> The `config/db.php` script is equipped with automatic database & table migration! If you connect to a running MySQL server with default credentials (`root` and blank password), it automatically creates the `student_registration` database, tables, and seeds the default admin user on first launch.
