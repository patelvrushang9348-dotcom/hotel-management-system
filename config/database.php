<?php
// config/database.php

class Database {
    private static ?PDO $instance = null;

    // Update these credentials for your XAMPP / WAMP / LAMP / Live MySQL server
    private static string $host = '127.0.0.1';
    private static string $dbName = 'hotel_booking_db';
    private static string $username = 'root';
    private static string $password = '';

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                // Connect without DB first to create it if missing
                $pdoInit = new PDO(
                    "mysql:host=" . self::$host . ";charset=utf8mb4",
                    self::$username,
                    self::$password,
                    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
                );
                $pdoInit->exec("CREATE DATABASE IF NOT EXISTS `" . self::$dbName . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

                // Connect to the actual database
                self::$instance = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$dbName . ";charset=utf8mb4",
                    self::$username,
                    self::$password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );

                self::initializeSchema(self::$instance);
            } catch (PDOException $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
                exit;
            }
        }
        return self::$instance;
    }

    private static function initializeSchema(PDO $pdo): void {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rooms (
                room_number VARCHAR(20) PRIMARY KEY,
                room_type VARCHAR(30) NOT NULL,
                price_per_night DECIMAL(10, 2) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS guests (
                guest_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                email VARCHAR(150) DEFAULT NULL,
                phone VARCHAR(40) DEFAULT NULL
            );

            CREATE TABLE IF NOT EXISTS services (
                service_id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL
            );

            CREATE TABLE IF NOT EXISTS bookings (
                booking_id INT AUTO_INCREMENT PRIMARY KEY,
                room_number VARCHAR(20) NOT NULL,
                guest_id INT NOT NULL,
                check_in_date DATE NOT NULL,
                check_out_date DATE NOT NULL,
                status VARCHAR(30) DEFAULT 'Booked',
                total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                FOREIGN KEY (room_number) REFERENCES rooms(room_number) ON DELETE RESTRICT,
                FOREIGN KEY (guest_id) REFERENCES guests(guest_id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS booking_services (
                id INT AUTO_INCREMENT PRIMARY KEY,
                booking_id INT NOT NULL,
                service_id INT NOT NULL,
                FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
                FOREIGN KEY (service_id) REFERENCES services(service_id) ON DELETE CASCADE
            );
        ");

        // Seed default data if rooms table is empty
        $count = (int)$pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
        if ($count === 0) {
            $pdo->exec("
                INSERT INTO rooms (room_number, room_type, price_per_night) VALUES
                ('101', 'Single', 95.00),
                ('102', 'Single', 95.00),
                ('201', 'Double', 145.00),
                ('202', 'Double', 155.00),
                ('301', 'Suite', 280.00);

                INSERT INTO guests (name, email, phone) VALUES
                ('Eleanor Vance', 'eleanor.vance@example.com', '+1-555-0142'),
                ('Marcus Sterling', 'm.sterling@example.com', '+1-555-0198'),
                ('Aarav Sharma', 'aarav.sharma@example.com', '+91-98765-43210');

                INSERT INTO services (name, description, price) VALUES
                ('Airport Chauffeur', 'Luxury sedan pickup & drop-off', 45.00),
                ('Artisanal Breakfast', 'Daily gourmet buffet breakfast', 20.00),
                ('Aromatherapy Spa', '60-minute full-body relaxation massage', 85.00),
                ('Late Check-Out', 'Extend stay until 4:00 PM', 35.00);
            ");

            // Create a sample active booking for today
            $today = date('Y-m-d');
            $checkout = date('Y-m-d', strtotime('+3 days'));
            $stmt = $pdo->prepare("INSERT INTO bookings (room_number, guest_id, check_in_date, check_out_date, status, total_amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['201', 1, $today, $checkout, 'Checked In', 480.00]);
            $bookingId = $pdo->lastInsertId();
            $pdo->exec("INSERT INTO booking_services (booking_id, service_id) VALUES ($bookingId, 1)");
        }
    }
}
