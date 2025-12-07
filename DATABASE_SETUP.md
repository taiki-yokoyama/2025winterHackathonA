# CAP System Database Setup

## Overview

This document describes the database setup for the CAP (Check-Action-Plan) Cycle Management System.

## Database Schema

The system uses MySQL 8.0+ with the following tables:

### Tables

1. **users** - User accounts
   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
   - email (VARCHAR(255), UNIQUE, NOT NULL)
   - password (VARCHAR(255), NOT NULL) - Plain text for prototype
   - name (VARCHAR(100), NOT NULL)
   - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

2. **issues** - User improvement goals/issues
   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
   - user_id (INT, FOREIGN KEY → users.id, NOT NULL)
   - name (VARCHAR(255), NOT NULL)
   - metric_type (ENUM: 'percentage', 'scale_5', 'numeric', NOT NULL)
   - unit (VARCHAR(50), NULL) - Unit for numeric type
   - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

3. **caps** - CAP cycle records
   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
   - user_id (INT, FOREIGN KEY → users.id, NOT NULL)
   - issue_id (INT, FOREIGN KEY → issues.id, NOT NULL)
   - value (DECIMAL(10,2), NOT NULL) - Check value
   - analysis (TEXT, NOT NULL)
   - improve_direction (TEXT, NOT NULL)
   - plan (TEXT, NOT NULL)
   - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

4. **comments** - Comments on CAP posts
   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
   - from_user_id (INT, FOREIGN KEY → users.id, NOT NULL)
   - to_user_id (INT, FOREIGN KEY → users.id, NOT NULL)
   - to_cap_id (INT, FOREIGN KEY → caps.id, NOT NULL)
   - comment (TEXT, NOT NULL)
   - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)

### Indexes

Performance optimization indexes:
- idx_issues_user_id on issues(user_id)
- idx_caps_user_id on caps(user_id)
- idx_caps_issue_id on caps(issue_id)
- idx_comments_to_user_id on comments(to_user_id)
- idx_comments_to_cap_id on comments(to_cap_id)

## Setup Instructions

### Using Docker Compose

1. Start the Docker containers:
   ```bash
   docker-compose up -d
   ```

2. The database will be automatically initialized with the schema from `docker/mysql/init.sql`

3. Access the application at: http://localhost:8080

4. Access phpMyAdmin at: http://localhost:8081
   - Username: root
   - Password: root

### Manual Database Setup

If you need to manually recreate the database:

1. Connect to MySQL:
   ```bash
   docker-compose exec db mysql -u root -p
   ```

2. Run the initialization script:
   ```sql
   source /docker-entrypoint-initdb.d/init.sql
   ```

## Database Connection

The database connection is configured in `src/dbconnect.php`:
- Host: db (Docker service name)
- Database: posse
- User: root
- Password: root
- Charset: utf8mb4

## File Structure

```
src/
├── config.php              # Main configuration file
├── dbconnect.php           # Database connection
└── includes/
    ├── auth.php            # Authentication functions
    ├── validation.php      # Input validation functions
    └── db_functions.php    # Database query functions
```

## Requirements Validation

This setup satisfies the following requirements:
- **10.1**: Data is immediately persisted to MySQL database
- **10.2**: Database errors are properly handled with appropriate error codes
- **10.3**: Users table stores id, email, password (plain text), name, created_at
- **10.4**: Issues table stores id, user_id, name, metric_type, unit, created_at
- **10.5**: CAPs table stores id, user_id, issue_id, value, analysis, improve_direction, plan, created_at
- **10.6**: Comments table stores id, from_user_id, to_user_id, to_cap_id, comment, created_at

## Security Notes

**For Prototype Only:**
- Passwords are stored in plain text
- HTTPS is not configured
- CSRF protection is not implemented

**For Production, implement:**
- Password hashing with password_hash()
- HTTPS/SSL certificates
- CSRF token protection
- Input sanitization
- Rate limiting
