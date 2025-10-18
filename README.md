# Research Paper Database Management System

## 📚 Database Course Project - Academic Research Management Platform

A comprehensive **Database Management System (DBMS)** project for managing academic research papers, authors, citations, journals, conferences, and institutions. This project demonstrates advanced **SQL operations**, **database design principles**, and **full-stack web development** using PHP and MySQL.

---

## 🎯 Project Overview

This system is designed to manage the complete lifecycle of academic research, including:
- **Authors** and their institutional affiliations
- **Research Papers** (journal and conference publications)
- **Citations** and citation networks between papers
- **Journals** and **Conferences** publication venues
- **Institutions** and research organizations
- **Analytics** for research metrics and insights

### Key Database Concepts Demonstrated:
- ✅ **Entity-Relationship (ER) Design**
- ✅ **Normalization** (3NF compliance)
- ✅ **Foreign Key Constraints** and referential integrity
- ✅ **Transactions** and ACID properties
- ✅ **Complex Queries** (JOINs, Subqueries, Aggregations)
- ✅ **Stored Procedures** and prepared statements
- ✅ **Indexing** for query optimization
- ✅ **CRUD Operations** (Create, Read, Update, Delete)

---

## 🗄️ Database Schema

### **Entity-Relationship Diagram (ERD)**

```
┌─────────────┐       ┌──────────────┐       ┌─────────────┐
│ Institutions│◄──────┤   Authors    │──────►│ Authorship  │
└─────────────┘       └──────────────┘       └─────────────┘
                             │                       │
                             │                       ▼
                             │                ┌─────────────┐
                             │                │   Papers    │
                             │                └─────────────┘
                             │                   │        │
                             │                   │        │
                      ┌──────┴──────┐     ┌─────┴───┐    └─────┐
                      │             │     │         │          │
                      ▼             ▼     ▼         ▼          ▼
               ┌──────────┐  ┌─────────┐  ┌──────────────┐ ┌───────────┐
               │ Journals │  │ Conf.   │  │  Citations   │ │   Users   │
               └──────────┘  └─────────┘  └──────────────┘ └───────────┘
```

### **Database Tables**

| Table | Description | Key Columns |
|-------|-------------|-------------|
| **Users** | System users and authentication | user_id, username, password_hash, role |
| **Institutions** | Universities and research centers | institution_id, name, country, ranking |
| **Authors** | Research paper authors | author_id, name, email, institution_id |
| **Journals** | Academic journals | journal_id, name, publisher, ISSN, impact_factor |
| **Conferences** | Conference proceedings | conference_id, name, location, year |
| **Papers** | Research publications | paper_id, title, abstract, publication_year, type |
| **Authorship** | Author-Paper relationship (M:N) | author_id, paper_id, author_order |
| **Citations** | Paper citation network | citation_id, citing_paper_id, cited_paper_id |

---

## 🔧 SQL Operations Implemented

### **1. Data Definition Language (DDL)**

#### **CREATE Operations**
```sql
-- Create database with proper character set
CREATE DATABASE IF NOT EXISTS rica_db 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create tables with constraints
CREATE TABLE Authors (
  author_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) UNIQUE NOT NULL,
  institution_id INT,
  FOREIGN KEY (institution_id) REFERENCES Institutions(institution_id)
    ON DELETE SET NULL
) ENGINE=InnoDB;
```

#### **ALTER Operations**
```sql
-- Modify table structure
ALTER TABLE Papers ADD COLUMN citation_count INT DEFAULT 0;
ALTER TABLE Authors ADD INDEX idx_institution (institution_id);
ALTER TABLE Papers MODIFY COLUMN abstract TEXT;
```

#### **DROP Operations**
```sql
-- Safe deletion with checks
DROP TABLE IF EXISTS temporary_data;
```

---

### **2. Data Manipulation Language (DML)**

#### **INSERT Operations**
```sql
-- Single row insertion
INSERT INTO Authors (name, email, institution_id) 
VALUES ('Dr. John Smith', 'john@university.edu', 1);

-- Multiple rows insertion
INSERT INTO Papers (title, publication_year, publication_type) VALUES
('Machine Learning Survey', 2024, 'journal'),
('Deep Learning Trends', 2024, 'conference');

-- INSERT with ON DUPLICATE KEY UPDATE
INSERT INTO Users (username, email) VALUES (?, ?)
ON DUPLICATE KEY UPDATE username = username;
```

#### **SELECT Operations**

**Basic Queries:**
```sql
-- Simple selection
SELECT * FROM Authors WHERE institution_id = 5;

-- With sorting and limiting
SELECT * FROM Papers 
ORDER BY publication_year DESC 
LIMIT 10 OFFSET 0;

-- Distinct values
SELECT DISTINCT country FROM Institutions;
```

**JOIN Queries:**
```sql
-- INNER JOIN: Papers with their authors
SELECT p.title, a.name, i.name as institution
FROM Papers p
INNER JOIN Authorship ap ON p.paper_id = ap.paper_id
INNER JOIN Authors a ON ap.author_id = a.author_id
LEFT JOIN Institutions i ON a.institution_id = i.institution_id;

-- LEFT JOIN: All authors with their papers (including authors without papers)
SELECT a.name, COUNT(ap.paper_id) as paper_count
FROM Authors a
LEFT JOIN Authorship ap ON a.author_id = ap.author_id
GROUP BY a.author_id;
```

**Aggregate Functions:**
```sql
-- Count, Sum, Avg, Min, Max
SELECT 
  COUNT(*) as total_papers,
  AVG(citation_count) as avg_citations,
  MAX(citation_count) as max_citations,
  MIN(publication_year) as earliest_year
FROM Papers;

-- GROUP BY with HAVING
SELECT institution_id, COUNT(*) as author_count
FROM Authors
GROUP BY institution_id
HAVING author_count > 5;
```

**Subqueries:**
```sql
-- Nested subquery: Top cited papers
SELECT title, citation_count FROM Papers
WHERE citation_count > (SELECT AVG(citation_count) FROM Papers);

-- IN subquery: Authors from top institutions
SELECT name FROM Authors
WHERE institution_id IN (
  SELECT institution_id FROM Institutions WHERE ranking <= 100
);

-- EXISTS subquery: Authors with publications
SELECT name FROM Authors a
WHERE EXISTS (
  SELECT 1 FROM Authorship ap WHERE ap.author_id = a.author_id
);
```

**Window Functions (Advanced):**
```sql
-- Rank papers by citations within each year
SELECT 
  title, 
  publication_year,
  citation_count,
  RANK() OVER (PARTITION BY publication_year ORDER BY citation_count DESC) as rank
FROM Papers;
```

#### **UPDATE Operations**
```sql
-- Simple update
UPDATE Papers 
SET citation_count = citation_count + 1 
WHERE paper_id = 100;

-- Conditional update with JOIN
UPDATE Authors a
INNER JOIN Institutions i ON a.institution_id = i.institution_id
SET a.verified = 1
WHERE i.ranking <= 50;

-- Dynamic update with SET clause
UPDATE Conferences 
SET name=?, location=?, year=? 
WHERE conference_id=?;
```

#### **DELETE Operations**
```sql
-- Simple delete
DELETE FROM Citations WHERE citation_id = 5;

-- Conditional delete
DELETE FROM Papers 
WHERE publication_year < 2000 
AND citation_count = 0;

-- Delete with subquery
DELETE FROM Authors 
WHERE author_id NOT IN (
  SELECT DISTINCT author_id FROM Authorship
);
```

---

### **3. Data Query Language (Advanced DQL)**

#### **Complex Analytics Queries**
```sql
-- Top 10 most cited papers with author info
SELECT 
  p.title,
  p.citation_count,
  GROUP_CONCAT(a.name SEPARATOR ', ') as authors,
  j.name as journal_name
FROM Papers p
LEFT JOIN Authorship ap ON p.paper_id = ap.paper_id
LEFT JOIN Authors a ON ap.author_id = a.author_id
LEFT JOIN Journals j ON p.journal_id = j.journal_id
GROUP BY p.paper_id
ORDER BY p.citation_count DESC
LIMIT 10;

-- Publication statistics by year
SELECT 
  publication_year,
  COUNT(*) as paper_count,
  COUNT(DISTINCT journal_id) as journal_count,
  AVG(citation_count) as avg_citations
FROM Papers
WHERE publication_type = 'journal'
GROUP BY publication_year
ORDER BY publication_year DESC;

-- Author productivity ranking
SELECT 
  a.name,
  i.name as institution,
  COUNT(ap.paper_id) as paper_count,
  SUM(p.citation_count) as total_citations
FROM Authors a
LEFT JOIN Authorship ap ON a.author_id = ap.author_id
LEFT JOIN Papers p ON ap.paper_id = p.paper_id
LEFT JOIN Institutions i ON a.institution_id = i.institution_id
GROUP BY a.author_id
ORDER BY total_citations DESC
LIMIT 20;
```

#### **Citation Network Analysis**
```sql
-- Papers citing each other (citation network)
SELECT 
  citing.title as citing_paper,
  cited.title as cited_paper,
  citing.publication_year as citing_year,
  cited.publication_year as cited_year
FROM Citations c
INNER JOIN Papers citing ON c.citing_paper_id = citing.paper_id
INNER JOIN Papers cited ON c.cited_paper_id = cited.paper_id
WHERE citing.publication_year > cited.publication_year;

-- Most influential papers (highly cited)
SELECT 
  p.title,
  COUNT(c.citation_id) as times_cited
FROM Papers p
LEFT JOIN Citations c ON p.paper_id = c.cited_paper_id
GROUP BY p.paper_id
ORDER BY times_cited DESC
LIMIT 10;
```

---

### **4. Data Control Language (DCL)**

```sql
-- User privileges (for production)
GRANT SELECT, INSERT, UPDATE, DELETE ON rica_db.* TO 'app_user'@'localhost';
GRANT ALL PRIVILEGES ON rica_db.* TO 'admin_user'@'localhost';
REVOKE DELETE ON rica_db.Users FROM 'app_user'@'localhost';
```

---

### **5. Transaction Control Language (TCL)**

```sql
-- Transaction example: Add paper with authors atomically
START TRANSACTION;

INSERT INTO Papers (title, abstract, publication_year) 
VALUES ('New Research', 'Abstract text', 2024);

SET @paper_id = LAST_INSERT_ID();

INSERT INTO Authorship (paper_id, author_id, author_order) VALUES
(@paper_id, 1, 1),
(@paper_id, 2, 2);

COMMIT;
-- If error occurs: ROLLBACK;
```

---

### **6. Constraints & Integrity**

```sql
-- Primary Key Constraint
PRIMARY KEY (author_id)

-- Foreign Key Constraint with CASCADE
FOREIGN KEY (institution_id) REFERENCES Institutions(institution_id)
  ON DELETE SET NULL
  ON UPDATE CASCADE

-- Unique Constraint
UNIQUE KEY (email)

-- Check Constraint (MySQL 8.0+)
CHECK (publication_year >= 1900 AND publication_year <= 2100)

-- NOT NULL Constraint
name VARCHAR(255) NOT NULL

-- Default Values
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
```

---

### **7. Indexing & Optimization**

```sql
-- Primary Index (automatic)
PRIMARY KEY (paper_id)

-- Secondary Index
CREATE INDEX idx_publication_year ON Papers(publication_year);
CREATE INDEX idx_author_name ON Authors(name);

-- Composite Index
CREATE INDEX idx_paper_year_type ON Papers(publication_year, publication_type);

-- Full-text Search Index
CREATE FULLTEXT INDEX idx_paper_content ON Papers(title, abstract);

-- Query optimization with EXPLAIN
EXPLAIN SELECT * FROM Papers WHERE publication_year = 2024;
```

---

### **8. Views (Virtual Tables)**

```sql
-- Create view for common queries
CREATE VIEW vw_papers_with_authors AS
SELECT 
  p.paper_id,
  p.title,
  p.publication_year,
  GROUP_CONCAT(a.name ORDER BY ap.author_order) as authors
FROM Papers p
LEFT JOIN Authorship ap ON p.paper_id = ap.paper_id
LEFT JOIN Authors a ON ap.author_id = a.author_id
GROUP BY p.paper_id;

-- Use view
SELECT * FROM vw_papers_with_authors WHERE publication_year = 2024;
```

---

### **9. Prepared Statements (Security)**

```php
// PHP mysqli prepared statements
$stmt = $conn->prepare("SELECT * FROM Authors WHERE institution_id = ?");
$stmt->bind_param("i", $institution_id);
$stmt->execute();
$result = $stmt->get_result();

// INSERT with prepared statement
$stmt = $conn->prepare("INSERT INTO Papers (title, publication_year) VALUES (?, ?)");
$stmt->bind_param("si", $title, $year);
$stmt->execute();
```

---

## 📁 Project Structure

```
dbProjrct2107102-final/
│
├── 📄 config.php                 # Database configuration & connection
├── 📄 index.php                  # Landing page with statistics
├── 📄 login.php                  # Admin authentication page
├── 📄 logout.php                 # Session termination
├── 📄 dashboard.php              # Admin dashboard
├── 📄 navbar.php                 # Navigation component
├── 📄 styles.css                 # Global styling
├── 📄 database.sql               # Complete database setup script
├── 📄 README.md                  # This documentation
├── 📄 .htaccess                  # Apache configuration
│
├── 📂 api/                       # REST API Endpoints
│   ├── auth.php                  # Authentication API
│   ├── authors.php               # Author CRUD operations
│   ├── papers.php                # Paper CRUD operations
│   ├── journals.php              # Journal CRUD operations
│   ├── conferences.php           # Conference CRUD operations
│   ├── institutions.php          # Institution CRUD operations
│   ├── citations.php             # Citation management
│   ├── analytics.php             # Statistics & analytics
│   ├── admin.php                 # Database admin operations
│   └── export.php                # CSV data export
│
├── 📂 includes/                  # Helper Files
│   ├── session.php               # Session management
│   ├── auth_check.php            # Authentication middleware
│   ├── admin_check.php           # Admin authorization
│   ├── csrf.php                  # CSRF token protection
│   ├── db.php                    # PDO database connection
│   ├── sql.php                   # SQL query loader (named queries)
│   ├── functions.php             # Utility functions
│   ├── header.php                # HTML header template
│   └── footer.php                # HTML footer template
│
├── 📂 queries/                   # Database Schema
│   ├── 00_create_database.sql    # Database creation
│   ├── 10_users.sql              # Users table
│   ├── 20_institutions.sql       # Institutions table
│   ├── 30_authors.sql            # Authors table
│   ├── 40_journals.sql           # Journals table
│   ├── 50_conferences.sql        # Conferences table
│   ├── 60_papers.sql             # Papers table
│   ├── 70_paper_authors.sql      # Authorship (M:N relationship)
│   ├── 80_citations.sql          # Citations table
│   ├── 90_seed.sql               # Sample data
│   │
│   └── 📂 query/                 # Named SQL Queries (96+ queries)
│       ├── authorQuery.sql       # Author operations (12 queries)
│       ├── paperQuery.sql        # Paper operations (15 queries)
│       ├── journalQuery.sql      # Journal operations (10 queries)
│       ├── conferenceQuery.sql   # Conference operations (12 queries)
│       ├── institutionQuery.sql  # Institution operations (9 queries)
│       ├── citationQuery.sql     # Citation operations (10 queries)
│       ├── adminQuery.sql        # Admin operations (7 queries)
│       ├── analyticsQuery.sql    # Analytics queries (10 queries)
│       ├── exportQuery.sql       # Export queries (7 queries)
│       ├── authQuery.sql         # Auth queries (9 queries)
│       ├── README.md             # Query documentation
│       └── API_MAPPING.md        # API-Query mapping
│
├── 📂 js/                        # Client-Side JavaScript
│   ├── script.js                 # Main application logic
│   └── theme.js                  # Theme switcher
│
├── 📂 uploads/                   # File upload directory
│
└── 📂 .vscode/                   # VS Code settings
    └── settings.json             # Editor configuration
```

---

## 🚀 Installation & Setup

### **Prerequisites**
- XAMPP (Apache + MySQL + PHP 7.4+)
- Web browser (Chrome, Firefox, Safari, Edge)
- Text editor (VS Code recommended)

### **Step 1: Install XAMPP**
1. Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
2. Install and start **Apache** and **MySQL** services

### **Step 2: Setup Project**
```bash
# 1. Clone or extract project to XAMPP htdocs
cd /Applications/XAMPP/xamppfiles/htdocs/
# Project should be in: htdocs/dbProjrct2107102-final/

# 2. Verify file permissions
chmod -R 755 dbProjrct2107102-final/
chmod -R 777 dbProjrct2107102-final/uploads/
```

### **Step 3: Create Database**

#### **Option A: Using phpMyAdmin (Recommended)**
1. Open: `http://localhost/phpmyadmin`
2. Click **"Import"** tab
3. Choose file: `database.sql`
4. Click **"Go"** button
5. Wait for success message

#### **Option B: Using MySQL Command Line**
```bash
# Navigate to project directory
cd /Applications/XAMPP/xamppfiles/htdocs/dbProjrct2107102-final/

# Import database
mysql -u root -p < database.sql
# Press Enter (password is empty by default)
```

#### **Option C: Using MySQL Workbench**
1. Open MySQL Workbench
2. Connect to `localhost:3306` (user: root, password: empty)
3. File → Open SQL Script → Select `database.sql`
4. Execute script (⚡ lightning icon)

### **Step 4: Configure Database Connection**
Edit `config.php` if needed:
```php
define('DB_HOST', 'localhost');      // MySQL host
define('DB_USER', 'root');           // MySQL username
define('DB_PASS', '');               // MySQL password (empty for XAMPP)
define('DB_NAME', 'rica_db');        // Database name
```

### **Step 5: Access Application**
Open your browser and navigate to:
```
http://localhost/dbProjrct2107102-final/
```

---

## 👤 User Guide

### **Default Login Credentials**
```
Username: admin
Password: password123
```

### **Main Features**

#### **1. Dashboard** (`dashboard.php`)
- Quick overview of database statistics
- Recent activity summary
- Navigation to all modules

#### **2. Author Management** (`authors.php`)
- ✅ **Create**: Add new authors with email and institution
- ✅ **Read**: View all authors with pagination
- ✅ **Update**: Edit author information
- ✅ **Delete**: Remove authors (with validation)
- 🔍 **Search**: Filter by name, institution, or field
- 📊 **View**: Author publication count

#### **3. Paper Management** (`papers.php`)
- ✅ **Create**: Add papers with title, abstract, year
- ✅ **Read**: Browse all papers with filters
- ✅ **Update**: Edit paper details
- ✅ **Delete**: Remove papers (cascades to citations)
- 🔍 **Search**: Filter by title, year, type, journal, conference
- 📎 **Link**: Associate authors with papers
- 📈 **Track**: Citation counts

#### **4. Journal Management** (`journals.php`)
- ✅ **Create**: Add journals with ISSN, publisher, impact factor
- ✅ **Read**: View all journals
- ✅ **Update**: Edit journal information
- ✅ **Delete**: Remove journals (validates no papers)
- 📊 **View**: Paper count per journal

#### **5. Conference Management** (`conferences.php`)
- ✅ **Create**: Add conferences with location, year, organizer
- ✅ **Read**: View all conferences
- ✅ **Update**: Edit conference details
- ✅ **Delete**: Remove conferences (validates no papers)
- 🔍 **Filter**: By year or location

#### **6. Institution Management** (`institutions.php`)
- ✅ **Create**: Add research institutions
- ✅ **Read**: View all institutions
- ✅ **Update**: Edit institution data
- ✅ **Delete**: Remove institutions (sets author.institution_id to NULL)
- 🌍 **Filter**: By country
- 🔍 **Search**: By name

#### **7. Citation Management** (via Papers)
- ✅ **Create**: Link papers in citation network
- ✅ **Read**: View citation relationships
- ✅ **Delete**: Remove citation links
- 📊 **Analyze**: Citation impact metrics

#### **8. Analytics** (`analytics.php`)
- 📊 **Overview Statistics**: Total counts for all entities
- 📈 **Top Cited Papers**: Most influential research
- 👥 **Top Authors**: Most productive researchers
- 📅 **Publication Trends**: Papers per year
- 🏛️ **Institution Rankings**: By author count

#### **9. Admin Tools** (`admin.php`)
- 🗄️ **Database Info**: Size, tables, MySQL version
- ⚡ **Run SQL**: Execute custom queries
- 🔄 **Reset Tables**: Clear data and reset auto-increment
- 📥 **Export Data**: Download table data as CSV

#### **10. Data Export** (`api/export.php`)
- 📥 **Single Table**: Export any table to CSV
- 📥 **Full Database**: Export all tables at once
- 📄 **Format**: UTF-8 CSV with headers

---

## 🔐 Security Features

### **1. SQL Injection Prevention**
- ✅ Prepared statements for all queries
- ✅ Parameter binding (mysqli `bind_param()`)
- ✅ Input type casting `(int)$_GET['id']`
- ✅ No raw SQL concatenation

### **2. XSS (Cross-Site Scripting) Protection**
- ✅ `htmlspecialchars()` for output escaping
- ✅ `ENT_QUOTES` flag for quote encoding
- ✅ `strip_tags()` for HTML removal
- ✅ Input sanitization functions

### **3. CSRF (Cross-Site Request Forgery) Protection**
- ✅ CSRF tokens for forms
- ✅ Token validation on submission
- ✅ Session-based token storage

### **4. Authentication & Authorization**
- ✅ Session-based authentication
- ✅ Password validation
- ✅ Login attempt logging
- ✅ IP address tracking
- ✅ Role-based access control (admin/user)

### **5. Apache Security (.htaccess)**
- ✅ `X-Frame-Options` header (clickjacking protection)
- ✅ `X-XSS-Protection` header
- ✅ `X-Content-Type-Options` header
- ✅ Config file access denied
- ✅ Directory listing disabled
- ✅ `.git` folder hidden

---

## 🎨 Technologies Used

### **Backend**
- **PHP 7.4+** - Server-side scripting
- **MySQL 5.7+ / MariaDB 10+** - Relational database
- **mysqli Extension** - Database connectivity
- **PDO** - Alternative database abstraction

### **Frontend**
- **HTML5** - Markup structure
- **CSS3** - Styling and layout
- **Bootstrap 5** - Responsive framework
- **JavaScript (ES6)** - Client-side interactivity
- **Font Awesome 6** - Icon library

### **Server**
- **Apache 2.4+** - Web server
- **XAMPP** - Development environment
- **.htaccess** - URL rewriting & security

---

## 📊 Database Statistics

After running `database.sql`, you'll have:
- **9 Tables** with proper relationships
- **30+ Sample Records** (seeds)
- **96+ Named SQL Queries** across 10 query files
- **10 REST API Endpoints**
- **Foreign Key Constraints** for data integrity
- **Indexes** for optimized queries

---

## 🧪 Testing the Application

### **1. Test Database Connection**
```
URL: http://localhost/dbProjrct2107102-final/
Expected: Landing page shows statistics (Authors, Papers, etc.)
```

### **2. Test Authentication**
```
URL: http://localhost/dbProjrct2107102-final/login.php
Username: admin
Password: password123
Expected: Redirects to dashboard
```

### **3. Test CRUD Operations**
```
1. Go to Authors page
2. Click "Add New Author"
3. Fill form and submit
4. Verify author appears in list
5. Click "Edit" and modify
6. Click "Delete" and confirm
```

### **4. Test API Endpoints**
```bash
# Get all authors
curl http://localhost/dbProjrct2107102-final/api/authors.php

# Get specific author
curl http://localhost/dbProjrct2107102-final/api/authors.php?id=1

# Analytics overview
curl http://localhost/dbProjrct2107102-final/api/analytics.php?type=overview
```

---

## 🐛 Troubleshooting

### **Issue: "Connection failed"**
**Solution:**
```bash
1. Verify MySQL is running in XAMPP
2. Check config.php database credentials
3. Ensure database 'rica_db' exists
4. Import database.sql again
```

### **Issue: "404 Not Found"**
**Solution:**
```bash
1. Verify Apache is running in XAMPP
2. Check project is in htdocs/dbProjrct2107102-final/
3. Access via: http://localhost/dbProjrct2107102-final/
```

### **Issue: "SQL syntax error"**
**Solution:**
```bash
1. Check MySQL version (5.7+ required)
2. Verify you imported database.sql completely
3. Re-import database.sql
```

### **Issue: "Cannot login"**
**Solution:**
```bash
Username: admin
Password: password123
(Case-sensitive)
```

---

## 📝 Assignment/Project Submission Notes

### **Database Concepts Demonstrated:**

1. **ER Design** ✅
   - 8 entities with clear relationships
   - Many-to-Many (Authors ↔ Papers via Authorship)
   - One-to-Many (Institutions → Authors)

2. **Normalization** ✅
   - 1NF: Atomic values, no repeating groups
   - 2NF: No partial dependencies
   - 3NF: No transitive dependencies

3. **Constraints** ✅
   - PRIMARY KEY on all tables
   - FOREIGN KEY with referential integrity
   - UNIQUE constraints (email, ISSN)
   - NOT NULL constraints
   - CHECK constraints (year range)

4. **SQL Operations** ✅
   - SELECT with JOINs (INNER, LEFT, RIGHT)
   - Aggregate functions (COUNT, AVG, SUM, MIN, MAX)
   - GROUP BY with HAVING
   - Subqueries (nested, correlated)
   - UNION operations
   - INSERT, UPDATE, DELETE with conditions

5. **Transactions** ✅
   - ACID properties maintained
   - BEGIN TRANSACTION, COMMIT, ROLLBACK
   - Atomicity in multi-step operations

6. **Indexing** ✅
   - Primary indexes (automatic)
   - Secondary indexes on foreign keys
   - Composite indexes for multi-column queries

7. **Security** ✅
   - Prepared statements (SQL injection prevention)
   - Input validation and sanitization
   - Authentication and authorization
   - CSRF protection

---

## 🎓 Learning Outcomes

By completing this project, students will understand:
- ✅ Database design principles and ER modeling
- ✅ Normalization techniques
- ✅ Complex SQL query writing
- ✅ JOIN operations and relationships
- ✅ Transaction management
- ✅ Database security best practices
- ✅ Full-stack web development
- ✅ API design and implementation
- ✅ CRUD operations in practice

---

## 📚 Additional Resources

- **MySQL Documentation**: [https://dev.mysql.com/doc/](https://dev.mysql.com/doc/)
- **PHP mysqli**: [https://www.php.net/manual/en/book.mysqli.php](https://www.php.net/manual/en/book.mysqli.php)
- **Database Design**: [https://www.guru99.com/database-design.html](https://www.guru99.com/database-design.html)
- **SQL Tutorial**: [https://www.w3schools.com/sql/](https://www.w3schools.com/sql/)

---

## 👨‍💻 Author

**Kamrul**
- GitHub: [@kamrul2107102](https://github.com/kamrul2107102)
- Repository: [ResearchPaper-db-project](https://github.com/kamrul2107102/ResearchPaper-db-project)

---

## 📄 License

This project is created for educational purposes as part of a Database Course.

---

##  Acknowledgments

- Database course instructors and teaching assistants
- MySQL and PHP documentation communities
- Bootstrap and Font Awesome for UI components

---

**Last Updated:** October 18, 2025

---

## 📞 Support

For issues or questions:
1. Check the Troubleshooting section above
2. Review `queries/query/README.md` for query documentation
3. Check `queries/query/API_MAPPING.md` for API endpoints
4. Open an issue on GitHub repository

---

