CREATE DATABASE IF NOT EXISTS hotel_booking_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_booking_db;

CREATE TABLE IF NOT EXISTS rooms (
    room_number VARCHAR(20) PRIMARY KEY,
    room_type ENUM('Single', 'Double', 'Suite') NOT NULL,
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
    status ENUM('Booked', 'Checked In', 'Checked Out', 'Cancelled') DEFAULT 'Booked',
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

-- Initial Seed Data
INSERT IGNORE INTO rooms (room_number, room_type, price_per_night) VALUES
('101', 'Single', 95.00),
('102', 'Single', 95.00),
('201', 'Double', 145.00),
('202', 'Double', 155.00),
('301', 'Suite', 280.00);

INSERT IGNORE INTO guests (guest_id, name, email, phone) VALUES
(1, 'Eleanor Vance', 'eleanor.vance@example.com', '+1-555-0142'),
(2, 'Marcus Sterling', 'm.sterling@example.com', '+1-555-0198'),
(3, 'Aarav Sharma', 'aarav.sharma@example.com', '+91-98765-43210');

INSERT IGNORE INTO services (service_id, name, description, price) VALUES
(1, 'Airport Chauffeur', 'Luxury sedan pickup & drop-off', 45.00),
(2, 'Artisanal Breakfast', 'Daily gourmet buffet breakfast', 20.00),
(3, 'Aromatherapy Spa', '60-minute full-body relaxation massage', 85.00),
(4, 'Late Check-Out', 'Extend stay until 4:00 PM', 35.00);
