# 📚 MCU E-Library Management System
### Enterprise Edition v2.0

> A comprehensive, full-stack digital library management system for **Makhanlal Chaturvedi National University of Journalism & Communication, Bhopal** — powered by PHP, MySQL, and Artificial Intelligence.

![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat&logo=mysql&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript&logoColor=black)
![AI](https://img.shields.io/badge/AI-Claude%20API-FF6B35?style=flat)
![License](https://img.shields.io/badge/License-MIT-green?style=flat)

---

## 🌟 Features

### 👤 Role-Based Access Control
| Role | Access |
|------|--------|
| **Super Admin** | Full system control — books, users, reports, settings |
| **Librarian** | Issue/return books, search members, manage requests |
| **Student** | Browse books, request/reserve, e-books, ID card |

### 📖 Book Management
- Add, edit, delete books with **custom cover image upload**
- PDF e-book upload and download
- Auto-generated barcode, shelf/rack location tracking
- Real-time availability: **Available / Out of Stock**
- Full-text search + typo-tolerant fuzzy search

### 🔄 Issue & Return System
- Student submits request → Admin approves/rejects
- Auto issue date + due date (14 days, configurable)
- **Automatic fine calculation** — ₹2/day after due date
- Return processing with fine collection tracking
- Stock auto-update on issue and return

### 📌 Reservation & Suggestion System
- Students can **reserve out-of-stock books**
- Students can **suggest new books** for acquisition
- Admin can approve suggestions and add books directly
- Student notified when reserved book becomes available

### 🤖 AI Features (Claude API)
- **AI Chatbot** — answers library queries 24/7
- **AI Book Recommendations** — personalized suggestions
- **AI Book Summary** — on-demand academic summaries
- **Smart Search** — autocomplete + soundex typo tolerance
- Rule-based fallback (works without API key)

### 📊 Analytics & Reports
- Monthly issue trend charts (Chart.js)
- Category-wise distribution pie chart
- Fine collection summary
- Most issued books & most active students
- Print-ready reports

### 🎴 Student Features
- Digital **QR Library ID Card** (printable)
- Book wishlist
- Borrowing history
- Real-time notifications (bell icon)
- Feedback & complaint system
- Forgot password with OTP

### ⚙️ Admin Features
- Manage books, users, librarians
- Approve/reject book requests
- Process returns with fine calculation
- Manage e-book library
- Configure library settings (fine rate, loan days, etc.)
- View and reply to student feedback

---

## 🛠️ Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, JavaScript ES6+ |
| Charts | Chart.js 4.x |
| Icons | Font Awesome 6.5 |
| Fonts | Plus Jakarta Sans, Playfair Display |
| Backend | PHP 8.x |
| Database ORM | PDO (Prepared Statements) |
| Database | MySQL 8.0 / MariaDB |
| Server | Apache (XAMPP/LAMPP) |
| AI Engine | Anthropic Claude API |
| QR Code | api.qrserver.com |
| Security | CSRF tokens, bcrypt, input sanitization |

---

## 📁 Project Structure

```
mcu-v2/
│
├── index.php                  ← Premium homepage
├── login.php                  ← 3-role login (Admin/Librarian/Student)
├── register.php               ← Student registration
├── logout.php
├── forgot_password.php        ← OTP-based password reset
├── books.php                  ← Browse books + book detail + AI
├── ebooks.php                 ← Digital e-book library
├── chatbot.php                ← AI chatbot interface
├── recommend.php              ← AI book recommendations
├── summary.php                ← AI book summary generator
├── search.php                 ← Smart fuzzy search
│
├── admin/                     ← Admin panel (15 pages)
│   ├── dashboard.php          ← Stats, charts, pending requests
│   ├── add_book.php           ← Add book + image/PDF upload
│   ├── edit_book.php          ← Edit book + change cover
│   ├── delete_book.php
│   ├── manage_books.php       ← All books table
│   ├── manage_users.php       ← Student management
│   ├── manage_librarians.php  ← Librarian management
│   ├── manage_ebooks.php      ← E-book library admin
│   ├── requests.php           ← Approve/reject requests
│   ├── reservations.php       ← Book reservations & suggestions
│   ├── return_book.php        ← Return processing + fine
│   ├── fines.php              ← Fine management
│   ├── reports.php            ← Analytics & reports
│   ├── feedback.php           ← View & reply to feedback
│   └── settings.php           ← Library configuration
│
├── librarian/                 ← Librarian panel (4 pages)
│   ├── dashboard.php
│   ├── issue_book.php         ← Direct book issue
│   ├── return_book.php
│   └── search_member.php      ← Search students
│
├── student/                   ← Student panel (7 pages)
│   ├── dashboard.php          ← Full student dashboard
│   ├── issue_book.php         ← Book request form
│   ├── reserve.php            ← Reserve/suggest books
│   ├── notifications.php      ← All notifications
│   ├── id_card.php            ← QR library ID card
│   ├── wishlist.php           ← Book wishlist
│   └── feedback.php           ← Submit feedback
│
├── config/
│   └── db.php                 ← DB connection + global config + helpers
│
├── includes/
│   ├── header.php             ← Navbar, session, flash messages
│   └── footer.php             ← Footer + FAB chatbot button
│
├── assets/
│   ├── css/style.css          ← Complete design system (dark + light)
│   └── js/script.js           ← Theme toggle, chatbot UI, animations
│
├── uploads/
│   ├── books/                 ← Book cover images
│   ├── profiles/              ← Student profile photos
│   └── ebooks/                ← PDF e-book files
│
└── database/
    └── library.sql            ← Complete MySQL schema + sample data
```

---

## 🚀 Installation & Setup

### Prerequisites
- XAMPP / LAMPP (Apache + MySQL + PHP 8.x)
- Web browser (Chrome / Firefox)
- Internet connection (for AI features, Google Fonts, Font Awesome)

---

### Step 1 — Start XAMPP
```bash
sudo /opt/lampp/lampp start        # Linux
# OR open XAMPP Control Panel → Start Apache + MySQL (Windows)
```

---

### Step 2 — Extract Project
```bash
# Copy ZIP to htdocs
sudo unzip MCU_COMPLETE_FINAL.zip -d /opt/lampp/htdocs/

# Verify
ls /opt/lampp/htdocs/mcu-v2/
```

---

### Step 3 — Set Permissions (Linux only)
```bash
sudo chown -R $(whoami):$(whoami) /opt/lampp/htdocs/mcu-v2/
chmod -R 755 /opt/lampp/htdocs/mcu-v2/
mkdir -p /opt/lampp/htdocs/mcu-v2/uploads/{books,profiles,ebooks}
chmod -R 777 /opt/lampp/htdocs/mcu-v2/uploads/
```

---

### Step 4 — Setup Database
```bash
# Create database
/opt/lampp/bin/mysql -u root -e \
  "DROP DATABASE IF EXISTS mcu_library; \
   CREATE DATABASE mcu_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import tables + sample data
/opt/lampp/bin/mysql -u root mcu_library \
  < /opt/lampp/htdocs/mcu-v2/database/library.sql

# Verify (should show 11 tables)
/opt/lampp/bin/mysql -u root -e "USE mcu_library; SHOW TABLES;"
```

---

### Step 5 — Configure Application
Open `config/db.php` and set:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');              // XAMPP default: blank
define('DB_NAME', 'mcu_library');
define('BASE_URL', '/mcu-v2');      // Must match your folder name

// Optional: Add Anthropic API key for AI features
define('ANTHROPIC_API_KEY', 'sk-ant-your-key-here');
```

---

### Step 6 — Restart & Open
```bash
sudo /opt/lampp/lampp restart
```

Open browser:
```
http://localhost/mcu-v2/
```

---

## 🔑 Default Login Credentials

| Role | Tab | Email | Password |
|------|-----|-------|----------|
| **Admin** | Admin | `admin@mcu.ac.in` | `password` |
| **Librarian** | Librarian | `librarian@mcu.ac.in` | `password` |
| **Student** | Student | Register at `/register.php` | Your choice |

> ⚠️ **Change the default admin password** after first login!

---

## 🤖 AI Features Setup (Optional)

1. Go to **https://console.anthropic.com**
2. Sign up → API Keys → **Create Key**
3. Copy the key (starts with `sk-ant-...`)
4. Add to `config/db.php`:
```php
define('ANTHROPIC_API_KEY', 'sk-ant-your-key-here');
```

> Without the API key, the system works with **rule-based responses** for the chatbot.

---

## 📊 Database Schema

| Table | Purpose |
|-------|---------|
| `admin` | Administrator accounts |
| `librarians` | Library staff accounts |
| `users` | Student member accounts |
| `books` | Complete book catalog |
| `issued_books` | Issue/return records with fines |
| `reservations` | Book reservations & new book suggestions |
| `notifications` | Real-time student notifications |
| `ebooks` | Digital e-book library |
| `wishlist` | Student book wishlist |
| `feedback` | Student feedback & complaints |
| `library_settings` | Configurable library policies |

---

## ⚙️ Library Configuration

Admin can configure from **Settings page**:

| Setting | Default | Description |
|---------|---------|-------------|
| Fine Per Day | ₹2.00 | Charged after due date |
| Max Issue Days | 14 | Loan period |
| Max Books Per Student | 3 | Simultaneous book limit |
| Opening Time | 09:00 | Library open time |
| Closing Time | 18:00 | Library close time |

---

## 🔒 Security Features

- ✅ **CSRF Token** protection on all forms
- ✅ **bcrypt** password hashing (`PASSWORD_DEFAULT`)
- ✅ **PDO Prepared Statements** — SQL injection prevention
- ✅ **Session-based** role verification on every page
- ✅ **htmlspecialchars()** — XSS prevention
- ✅ **File upload validation** — type & extension whitelist
- ✅ **Secure logout** — session destruction + cookie clearance

---

## 🌙 Theme

The system supports **Dark Mode** and **Light Mode**:
- Default: **Dark** (Deep navy + gold theme)
- Toggle: Click the 🌙 moon icon in the top navigation bar
- Preference is saved in `localStorage` — persists on reload

---

## 📱 All Pages & URLs

| Page | URL | Access |
|------|-----|--------|
| Home | `/mcu-v2/` | Public |
| Books | `/mcu-v2/books.php` | Public |
| E-Books | `/mcu-v2/ebooks.php` | Public |
| Login | `/mcu-v2/login.php` | Public |
| Register | `/mcu-v2/register.php` | Public |
| AI Chatbot | `/mcu-v2/chatbot.php` | Public |
| Smart Search | `/mcu-v2/search.php` | Public |
| Student Dashboard | `/mcu-v2/student/dashboard.php` | Student |
| Library ID Card | `/mcu-v2/student/id_card.php` | Student |
| Book Request | `/mcu-v2/student/issue_book.php` | Student |
| Reserve Book | `/mcu-v2/student/reserve.php` | Student |
| Admin Dashboard | `/mcu-v2/admin/dashboard.php` | Admin |
| Manage Books | `/mcu-v2/admin/manage_books.php` | Admin |
| Reports | `/mcu-v2/admin/reports.php` | Admin |
| Settings | `/mcu-v2/admin/settings.php` | Admin |

---

## ❓ Troubleshooting

| Problem | Solution |
|---------|----------|
| Database connection failed | Check `DB_PASS` in `config/db.php` |
| HTTP 500 error | Check PHP errors: `tail -20 /opt/lampp/logs/error_log` |
| CSS not loading | Verify `BASE_URL` = `/mcu-v2` in `config/db.php` |
| Object Not Found | BASE_URL mismatch — check folder name matches |
| AI not working | Add API key in `config/db.php` or use without it |
| Uploads not working | Run `chmod -R 777 uploads/` |
| MySQL not starting | Run `sudo pkill mysql && sudo rm -f /opt/lampp/var/mysql/mysql.sock` then restart |

---

## 📄 License

This project is developed for **academic purposes** as part of BCA curriculum at Makhanlal Chaturvedi National University, Bhopal.

---

## 👨‍💻 Developed For

**Makhanlal Chaturvedi National University**  
of Journalism & Communication  
B-38, Press Complex, Bhopal, MP — 462011  
📧 agnikhil2003@gmail.com | 📞 +91 6268783742

---

<div align="center">
 for MCU Students | BCA Project 2025-26
</div>
