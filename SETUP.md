# Legal Management System - Setup Guide

Follow these step-by-step instructions to set up and run the **Legal Management System** project on your local machine for the first time.

## Prerequisites

Before you begin, ensure you have the following installed on your system:
- **PHP** (>= 8.2)
- **Composer** (Dependency manager for PHP)
- **Node.js & npm** (Javascript runtime and package manager)
- **MySQL** (or any preferred database server like XAMPP, WAMP, Laravel Herd, etc.)
- **Git** (Optional, if you are cloning the repository)

## Step-by-Step Installation

### 1. Get the Project
If you haven't already, download or clone the project repository to your local machine, then open your terminal and navigate to the project directory:
```bash
cd /path/to/Legal-Management-System
```

### 2. Install PHP Dependencies
Run Composer to install all the required PHP packages defined in the project:
```bash
composer install
```

### 3. Install NPM Dependencies
Run npm to install the frontend dependencies (like Tailwind CSS, Vite, etc.):
```bash
npm install
```

### 4. Set Up the Environment File
Create your local environment configuration file by copying the example file:
```bash
# On Windows (Command Prompt / PowerShell)
copy .env.example .env

# On Mac/Linux
cp .env.example .env
```

### 5. Generate Application Key
Generate a unique application key for Laravel to secure your sessions and encrypted data:
```bash
php artisan key:generate
```

### 6. Database Setup
1. Open your database manager (e.g., phpMyAdmin, TablePlus, or MySQL CLI).
2. Create a new, empty database named **`lexlanka`** (this is the default name in the `.env.example`).
3. If your MySQL credentials are not the default (`root` with an empty password), open the `.env` file in your code editor and update the database variables to match your local setup:
    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=lexlanka
    DB_USERNAME=root
    DB_PASSWORD=
    ```

### 7. Run Migrations and Seeders
Create the necessary database tables and populate them with initial seed data (like default admin accounts, roles, or dummy data):
```bash
php artisan migrate --seed
```

### 8. Create Storage Link (Optional but Recommended)
If the application manages file uploads (e.g., legal documents, user avatars), you must link the storage directory to make files publicly accessible:
```bash
php artisan storage:link
```

### 9. Start the Application Servers
To run the application, you need to start both the Laravel backend server and the Vite frontend server. 

Open **two separate terminal windows/tabs** in your project directory:

**Terminal 1 (Backend):**
```bash
php artisan serve
```

**Terminal 2 (Frontend/Assets):**
```bash
npm run dev
```

### 10. Access the Application
Open your web browser and visit the URL provided by the `php artisan serve` command, which is typically:
👉 **http://127.0.0.1:8000** or **http://localhost:8000**

---

## Troubleshooting

- **"Could not find driver" error:** Ensure that the PDO MySQL extension is enabled in your `php.ini` file.
- **Node errors during `npm run dev`:** Try deleting the `node_modules` folder and the `package-lock.json` file, then run `npm install` again.
- **Permission issues (Mac/Linux):** You may need to grant write permissions to the `storage` and `bootstrap/cache` directories:
  ```bash
  chmod -R 775 storage bootstrap/cache
  ```
