# Aurelia Boutique Hotel — Room Booking & Management System (PHP & MySQL)

A full-stack, responsive web application for managing hotel operations, room inventory, guest directories, hospitality add-on services, and room reservations. Built with **PHP (PDO)**, **MySQL**, **Vanilla JavaScript (Fetch API)**, and a custom **CSS3** design system featuring Light/Dark mode .

---

## Key Features

- **Executive Dashboard:** Real-time metrics for Total Rooms, Occupied Rooms Today, Total Active Revenue, Registered Guests, Today's Check-Ins, and Today's Check-Outs[cite: 3, 5, 7].
- **Rooms Management:** Add, edit, and delete rooms (`Single`, `Double`, `Suite`) with automatic availability tracking (`Available` vs. `Occupied`) and deletion protection for rooms with active/upcoming bookings[cite: 3, 5, 7].
- **Guests Directory:** Register new guests and update existing contact records (name, email, phone)[cite: 3, 5, 7].
- **Hospitality Services:** Manage extra guest services (e.g., Airport Chauffeur, Breakfast, Spa) that can be attached during or after booking[cite: 4, 5, 7].
- **Reservations & Bookings Engine:**
  - Prevents overlapping reservations for the same room.
  - Automatically calculates total stay costs (`Nights × Nightly Rate + Selected Services`).
  - Supports multi-criteria live filtering (by Guest, Room Number, or Check-In Date)[cite: 3, 7].
  - Instant booking status updates (`Booked`, `Checked In`, `Checked Out`, `Cancelled`)[cite: 3, 5, 7].
- **Dynamic UI & Localization:**
  - **Responsive Layout:** Collapsible mobile sidebar drawer and horizontally scrollable tables for mobile and tablet devices[cite: 3, 8].
  - **Currency Switcher:** Instant toggle between **USD (\$)** and **INR (₹)** across all tables, forms, and modals[cite: 3, 7].
  - **Theme Toggle:** Persistent **Light** and **Dark** mode using CSS custom properties and `localStorage`[cite: 3, 7, 8].
- **Zero-Config Database Setup:** Automatically creates the `hotel_booking_db` database, schema tables, and sample seed data on first launch.

---

## Project Structure

```text
hotel-booking-php/
├── api/
│   └── router.php          # RESTful JSON API endpoints & business logic
├── assets/
│   ├── css/
│   │   └── style.css       # Responsive stylesheet with Light/Dark theme variables
│   └── js/
│       └── app.js          # Frontend state, Fetch API calls, currency converter & filters
├── config/
│   └── database.php        # PDO MySQL connection, auto-schema creation & initial seed data
├── database.sql            # Standalone MySQL schema & seed script (for manual import)
├── index.php               # Main responsive web portal interface
└── README.md               # Project documentation
```[cite: 2, 3, 4, 5, 6, 7, 8]

---

## Prerequisites

- **PHP** 8.0 or higher (with `pdo_mysql` extension enabled)[cite: 6]
- **MySQL** 5.7+ or **MariaDB** 10.3+[cite: 4, 6]
- Local or live web server stack (**XAMPP**, **WAMP**, **MAMP**, **Laragon**, or **LAMP**)[cite: 6]

---

## Installation & Setup

1. **Extract the Project:**
   Unzip `hotel-booking-php.zip` into your web server's root directory:
   - **XAMPP:** `C:\xampp\htdocs\hotel-booking-php`
   - **WAMP:** `C:\wamp64\www\hotel-booking-php`
   - **Linux (Apache):** `/var/www/html/hotel-booking-php`[cite: 2]

2. **Start Apache & MySQL:**
   Open your control panel (e.g., XAMPP Control Panel) and start both the **Apache** and **MySQL** services.

3. **Configure Database Credentials (If Needed):**
   Open `config/database.php` and verify your MySQL connection settings (configured by default for local XAMPP/WAMP):
   ```php
   private static string $host = '127.0.0.1';
   private static string $dbName = 'hotel_booking_db';
   private static string $username = 'root';
   private static string $password = '';
   ```[cite: 6]

4. **Launch the Application:**
   Open your browser and navigate to:
   ```text
   http://localhost/hotel-booking-php/
