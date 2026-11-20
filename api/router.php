<?php
// api/router.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

$rawInput = file_get_contents('php://input');
$body = $rawInput ? json_decode($rawInput, true) : [];

function sendJson($data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function sendError(string $message, int $status = 400): void {
    sendJson(['error' => $message], $status);
}

function getAllRoomsWithStatus(PDO $pdo, string $today): array {
    $rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_number ASC")->fetchAll();
    $occStmt = $pdo->prepare("
        SELECT DISTINCT room_number FROM bookings 
        WHERE status IN ('Booked', 'Checked In') 
        AND check_in_date <= ? AND check_out_date > ?
    ");
    $occStmt->execute([$today, $today]);
    $occupiedSet = array_flip($occStmt->fetchAll(PDO::FETCH_COLUMN));

    foreach ($rooms as &$room) {
        $room['price_per_night'] = (float)$room['price_per_night'];
        $room['availability_status'] = isset($occupiedSet[$room['room_number']]) ? 'Occupied' : 'Available';
    }
    return $rooms;
}

function recalculateBookingTotal(PDO $pdo, int $bookingId): float {
    $stmt = $pdo->prepare("
        SELECT b.check_in_date, b.check_out_date, r.price_per_night
        FROM bookings b
        JOIN rooms r ON b.room_number = r.room_number
        WHERE b.booking_id = ?
    ");
    $stmt->execute([$bookingId]);
    $row = $stmt->fetch();
    if (!$row) return 0.0;

    $d1 = new DateTime($row['check_in_date']);
    $d2 = new DateTime($row['check_out_date']);
    $nights = max(1, (int)$d1->diff($d2)->days);
    $roomCost = $nights * (float)$row['price_per_night'];

    $srvStmt = $pdo->prepare("
        SELECT COALESCE(SUM(s.price), 0)
        FROM booking_services bs
        JOIN services s ON bs.service_id = s.service_id
        WHERE bs.booking_id = ?
    ");
    $srvStmt->execute([$bookingId]);
    $servicesCost = (float)$srvStmt->fetchColumn();

    $total = $roomCost + $servicesCost;
    $upd = $pdo->prepare("UPDATE bookings SET total_amount = ? WHERE booking_id = ?");
    $upd->execute([$total, $bookingId]);

    return $total;
}

try {
    $today = date('Y-m-d');

    // ---------------- DASHBOARD ----------------
    if ($action === 'dashboard' && $method === 'GET') {
        $allRooms = getAllRoomsWithStatus($pdo, $today);
        $allGuests = $pdo->query("SELECT * FROM guests ORDER BY guest_id DESC")->fetchAll();

        $occStmt = $pdo->prepare("
            SELECT b.*, r.room_type, r.price_per_night, g.name AS guest_name, g.phone AS guest_phone
            FROM bookings b
            JOIN rooms r ON b.room_number = r.room_number
            JOIN guests g ON b.guest_id = g.guest_id
            WHERE b.status IN ('Booked', 'Checked In')
              AND b.check_in_date <= ? AND b.check_out_date > ?
            ORDER BY b.room_number ASC
        ");
        $occStmt->execute([$today, $today]);
        $occupiedRooms = $occStmt->fetchAll();

        $inStmt = $pdo->prepare("
            SELECT b.*, g.name AS guest_name, r.room_type
            FROM bookings b
            JOIN guests g ON b.guest_id = g.guest_id
            JOIN rooms r ON b.room_number = r.room_number
            WHERE b.check_in_date = ? AND b.status != 'Cancelled'
        ");
        $inStmt->execute([$today]);
        $checkIns = $inStmt->fetchAll();

        $outStmt = $pdo->prepare("
            SELECT b.*, g.name AS guest_name, r.room_type
            FROM bookings b
            JOIN guests g ON b.guest_id = g.guest_id
            JOIN rooms r ON b.room_number = r.room_number
            WHERE b.check_out_date = ? AND b.status != 'Cancelled'
        ");
        $outStmt->execute([$today]);
        $checkOuts = $outStmt->fetchAll();

        // Calculate Total Active Revenue
        $rev = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE status != 'Cancelled'")->fetchColumn();

        sendJson([
            'date' => $today,
            'total_rooms' => count($allRooms),
            'occupied_rooms_today' => count($occupiedRooms),
            'total_guests' => count($allGuests),
            'total_revenue' => $rev,
            'todays_check_ins_count' => count($checkIns),
            'todays_check_outs_count' => count($checkOuts),
            'occupied_rooms' => $occupiedRooms,
            'all_rooms' => $allRooms,
            'all_guests' => $allGuests,
            'todays_check_ins' => $checkIns,
            'todays_check_outs' => $checkOuts
        ]);
    }

    // ---------------- ROOMS ----------------
    if ($action === 'rooms') {
        if ($method === 'GET') {
            sendJson(getAllRoomsWithStatus($pdo, $today));
        }
        if ($method === 'POST') {
            $roomNum = trim($body['room_number'] ?? '');
            $roomType = trim($body['room_type'] ?? '');
            $price = (float)($body['price_per_night'] ?? 0);

            if ($roomNum === '' || $roomType === '' || $price <= 0) {
                sendError('Valid room number, type, and positive price are required.');
            }
            $check = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE room_number = ?");
            $check->execute([$roomNum]);
            if ($check->fetchColumn() > 0) {
                sendError("Room {$roomNum} already exists.");
            }
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, price_per_night) VALUES (?, ?, ?)");
            $stmt->execute([$roomNum, $roomType, $price]);
            sendJson(['success' => true], 201);
        }
        if ($method === 'PUT' && $id) {
            $roomType = trim($body['room_type'] ?? '');
            $price = (float)($body['price_per_night'] ?? 0);
            if ($price <= 0) sendError('Price must be greater than zero.');

            $stmt = $pdo->prepare("UPDATE rooms SET room_type = ?, price_per_night = ? WHERE room_number = ?");
            $stmt->execute([$roomType, $price, $id]);
            sendJson(['success' => true]);
        }
        if ($method === 'DELETE' && $id) {
            $check = $pdo->prepare("
                SELECT COUNT(*) FROM bookings 
                WHERE room_number = ? AND status IN ('Booked', 'Checked In') AND check_out_date >= ?
            ");
            $check->execute([$id, $today]);
            if ($check->fetchColumn() > 0) {
                sendError("Cannot delete Room {$id}: It has active or upcoming bookings.");
            }
            $stmt = $pdo->prepare("DELETE FROM rooms WHERE room_number = ?");
            $stmt->execute([$id]);
            sendJson(['success' => true]);
        }
    }

    // ---------------- GUESTS ----------------
    if ($action === 'guests') {
        if ($method === 'GET') {
            sendJson($pdo->query("SELECT * FROM guests ORDER BY guest_id DESC")->fetchAll());
        }
        if ($method === 'POST') {
            $name = trim($body['name'] ?? '');
            if ($name === '') sendError('Guest name is required.');
            $stmt = $pdo->prepare("INSERT INTO guests (name, email, phone) VALUES (?, ?, ?)");
            $stmt->execute([$name, trim($body['email'] ?? ''), trim($body['phone'] ?? '')]);
            sendJson(['success' => true, 'guest_id' => $pdo->lastInsertId()], 201);
        }
        if ($method === 'PUT' && $id) {
            $name = trim($body['name'] ?? '');
            if ($name === '') sendError('Guest name is required.');
            $stmt = $pdo->prepare("UPDATE guests SET name = ?, email = ?, phone = ? WHERE guest_id = ?");
            $stmt->execute([$name, trim($body['email'] ?? ''), trim($body['phone'] ?? ''), (int)$id]);
            sendJson(['success' => true]);
        }
    }

    // ---------------- SERVICES ----------------
    if ($action === 'services') {
        if ($method === 'GET') {
            $list = $pdo->query("SELECT * FROM services ORDER BY service_id ASC")->fetchAll();
            foreach ($list as &$s) $s['price'] = (float)$s['price'];
            sendJson($list);
        }
        if ($method === 'POST') {
            $name = trim($body['name'] ?? '');
            $price = (float)($body['price'] ?? -1);
            if ($name === '' || $price < 0) sendError('Valid service name and non-negative price are required.');
            $stmt = $pdo->prepare("INSERT INTO services (name, description, price) VALUES (?, ?, ?)");
            $stmt->execute([$name, trim($body['description'] ?? ''), $price]);
            sendJson(['success' => true], 201);
        }
        if ($method === 'PUT' && $id) {
            $name = trim($body['name'] ?? '');
            $price = (float)($body['price'] ?? -1);
            if ($name === '' || $price < 0) sendError('Valid service name and price are required.');
            $stmt = $pdo->prepare("UPDATE services SET name = ?, description = ?, price = ? WHERE service_id = ?");
            $stmt->execute([$name, trim($body['description'] ?? ''), $price, (int)$id]);
            sendJson(['success' => true]);
        }
    }

    // ---------------- BOOKINGS ----------------
    if ($action === 'bookings') {
        if ($method === 'GET') {
            $bookings = $pdo->query("
                SELECT b.*, g.name AS guest_name, r.room_type
                FROM bookings b
                JOIN guests g ON b.guest_id = g.guest_id
                JOIN rooms r ON b.room_number = r.room_number
                ORDER BY b.booking_id DESC
            ")->fetchAll();

            $srvStmt = $pdo->prepare("
                SELECT s.service_id, s.name, s.price
                FROM booking_services bs
                JOIN services s ON bs.service_id = s.service_id
                WHERE bs.booking_id = ?
            ");

            foreach ($bookings as &$b) {
                $b['total_amount'] = (float)$b['total_amount'];
                $srvStmt->execute([$b['booking_id']]);
                $b['services'] = $srvStmt->fetchAll();
                $b['is_active'] = in_array($b['status'], ['Booked', 'Checked In'])
                    && $b['check_in_date'] <= $today
                    && $b['check_out_date'] > $today;
            }
            sendJson($bookings);
        }

        if ($method === 'POST') {
            $roomNum = $body['room_number'] ?? '';
            $guestId = (int)($body['guest_id'] ?? 0);
            $checkIn = $body['check_in_date'] ?? '';
            $checkOut = $body['check_out_date'] ?? '';
            $serviceIds = $body['service_ids'] ?? [];

            if (!$roomNum || !$guestId || !$checkIn || !$checkOut) {
                sendError('All booking fields are required.');
            }
            if ($checkOut <= $checkIn) {
                sendError('Check-out date must be after check-in date.');
            }

            // Prevent overlapping bookings for the same room
            $overlap = $pdo->prepare("
                SELECT COUNT(*) FROM bookings
                WHERE room_number = ?
                  AND status IN ('Booked', 'Checked In')
                  AND check_in_date < ?
                  AND check_out_date > ?
            ");
            $overlap->execute([$roomNum, $checkOut, $checkIn]);
            if ($overlap->fetchColumn() > 0) {
                sendError("Room {$roomNum} is already booked during those dates.");
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                INSERT INTO bookings (room_number, guest_id, check_in_date, check_out_date, status, total_amount)
                VALUES (?, ?, ?, ?, 'Booked', 0)
            ");
            $stmt->execute([$roomNum, $guestId, $checkIn, $checkOut]);
            $bookingId = (int)$pdo->lastInsertId();

            if (!empty($serviceIds)) {
                $insSrv = $pdo->prepare("INSERT INTO booking_services (booking_id, service_id) VALUES (?, ?)");
                foreach ($serviceIds as $sId) {
                    $insSrv->execute([$bookingId, (int)$sId]);
                }
            }

            recalculateBookingTotal($pdo, $bookingId);
            $pdo->commit();
            sendJson(['success' => true, 'booking_id' => $bookingId], 201);
        }
    }

    if ($action === 'booking_services' && $method === 'POST' && $id) {
        $bookingId = (int)$id;
        $serviceIds = $body['service_ids'] ?? [];
        if (empty($serviceIds)) sendError('Select at least one service.');

        $pdo->beginTransaction();
        $insSrv = $pdo->prepare("INSERT INTO booking_services (booking_id, service_id) VALUES (?, ?)");
        foreach ($serviceIds as $sId) {
            $insSrv->execute([$bookingId, (int)$sId]);
        }
        recalculateBookingTotal($pdo, $bookingId);
        $pdo->commit();
        sendJson(['success' => true]);
    }

    if ($action === 'booking_status' && $method === 'PUT' && $id) {
        $bookingId = (int)$id;
        $status = $body['status'] ?? '';
        $validStatuses = ['Booked', 'Checked In', 'Checked Out', 'Cancelled'];
        if (!in_array($status, $validStatuses)) sendError('Invalid status.');

        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE booking_id = ?");
        $stmt->execute([$status, $bookingId]);
        sendJson(['success' => true]);
    }

    sendError('Endpoint not found', 404);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    sendError($e->getMessage(), 500);
}
