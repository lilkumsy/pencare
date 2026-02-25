# Pencare HMO Consent Application

This is a PHP application designed to capture employee consent for Pencare HMO enrollment. It verifies the employee against an existing database before recording their consent.

## Setup Instructions

### 1. Database Configuration
Ensure your MSSQL database is running and accessible.
Update `includes/db.php` if your credentials differ from the defaults:
```php
$server   = "localhost";
$database = "your_database";
$username = "xyz";
$password = "abc";
```

### 2. Database Schema
Run the SQL script located at `sql/setup.sql` to create the necessary `pencare_consent` table.
*Note: This application assumes an `employees` table already exists with columns: PIN, FIRSTNAME, SURNAME, OTHERNAMES, DATE_OF_BIRTH, STATE_OF_POSTING.*

### 3. Deploying
- Place the contents of this folder in your web server's root (e.g., `htdocs` or `wwwroot`).
- Ensure PHP has the `pdo_sqlsrv` extension enabled.

## Features
- **Secure PIN Verification**: Checks employee existence before allowing consent.
- **Duplicate Prevention**: Prevents an employee from consenting multiple times.
- **Audit Trail**: Captures IP Address and User Agent for security.
- **Premium UI**: Modern, responsive interface with dark mode and glassmorphism effects.

## File Structure
- `index.php`: Entry point, PIN input form.
- `verify.php`: Displays employee details and consent checkbox.
- `submit.php`: Processes the form and inserts data into the database.
- `includes/db.php`: Database connection settings.
- `css/style.css`: Styling.
- `sql/setup.sql`: Database creation script.
