<?php
// 数据库配置
$host = 'mysql';     // 如果是 Docker 环境，应换成 'db' 或 'host.docker.internal'
$port = 3306;            // 默认 MySQL 端口
$dbname = 'php';
$username = 'root';
$password = 'php';
try {
    // 创建PDO连接
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // 如果数据库不存在，先创建数据库
    try {
        $pdo_temp = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
        $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // 重新连接到新创建的数据库
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch(PDOException $e2) {
        die("数据库连接失败: " . $e2->getMessage());
    }
}

// 初始化数据库表
function initializeDatabase($pdo) {
    // 创建用户表
    $createUsersTable = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            role ENUM('user', 'admin') DEFAULT 'user',
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";

    // 创建座位表
    $createSeatsTable = "
        CREATE TABLE IF NOT EXISTS seats (
            id INT AUTO_INCREMENT PRIMARY KEY,
            seat_number VARCHAR(10) UNIQUE NOT NULL,
            floor_number INT NOT NULL,
            area VARCHAR(50) NOT NULL,
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ";

    // 创建预约表
    $createReservationsTable = "
        CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            seat_id INT NOT NULL,
            reservation_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (seat_id) REFERENCES seats(id) ON DELETE CASCADE
        )
    ";

    try {
        $pdo->exec($createUsersTable);
        $pdo->exec($createSeatsTable);
        $pdo->exec($createReservationsTable);

        // 检查是否需要插入初始数据
        $seatCount = $pdo->query("SELECT COUNT(*) FROM seats")->fetchColumn();
        if ($seatCount == 0) {
            insertInitialData($pdo);
        }

    } catch(PDOException $e) {
        die("数据库初始化失败: " . $e->getMessage());
    }
}

// 插入初始座位数据
function insertInitialData($pdo) {
    $areas = ['A区', 'B区', 'C区', 'D区'];
    $floors = [1, 2, 3];

    $insertSeat = $pdo->prepare("INSERT INTO seats (seat_number, floor_number, area) VALUES (?, ?, ?)");

    foreach ($floors as $floor) {
        foreach ($areas as $area) {
            for ($i = 1; $i <= 20; $i++) {
                $seatNumber = $area . sprintf('%02d', $i);
                $insertSeat->execute([$seatNumber, $floor, $area]);
            }
        }
    }
}

// 数据库操作辅助函数

// 获取所有座位
function getAllSeats($pdo, $floor = null, $area = null) {
    $sql = "SELECT s.*,
                   r.user_id,
                   r.start_time,
                   r.end_time,
                   r.reservation_date,
                   r.status as reservation_status
            FROM seats s
            LEFT JOIN reservations r ON s.id = r.seat_id
                AND r.reservation_date = CURDATE()
                AND r.status = 'active'
            WHERE 1=1";

    $params = [];

    if ($floor) {
        $sql .= " AND s.floor_number = ?";
        $params[] = $floor;
    }

    if ($area) {
        $sql .= " AND s.area = ?";
        $params[] = $area;
    }

    $sql .= " ORDER BY s.floor_number, s.area, s.seat_number";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $seats = $stmt->fetchAll();

    // 在PHP中计算 is_available 状态
    foreach ($seats as &$seat) {
        $currentTime = date('H:i:s');

        // 如果有预约且当前时间在预约时间范围内，则不可用
        if ($seat['user_id'] &&
            $seat['start_time'] &&
            $seat['end_time'] &&
            $currentTime >= $seat['start_time'] &&
            $currentTime <= $seat['end_time']) {
            $seat['is_available'] = 0;
        } else {
            $seat['is_available'] = 1;
        }
    }

    return $seats;
}

// 预约座位
function reserveSeat($pdo, $userId, $seatId, $date, $startTime, $endTime) {
    // 检查座位是否已被预约
    $checkSql = "SELECT COUNT(*) FROM reservations
                 WHERE seat_id = ? AND reservation_date = ?
                 AND status = 'active'
                 AND (
                     (start_time <= ? AND end_time > ?) OR
                     (start_time < ? AND end_time >= ?) OR
                     (start_time >= ? AND end_time <= ?)
                 )";

    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$seatId, $date, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);

    if ($checkStmt->fetchColumn() > 0) {
        return false; // 座位已被预约
    }

    // 插入预约记录
    $insertSql = "INSERT INTO reservations (user_id, seat_id, reservation_date, start_time, end_time)
                  VALUES (?, ?, ?, ?, ?)";
    $insertStmt = $pdo->prepare($insertSql);
    return $insertStmt->execute([$userId, $seatId, $date, $startTime, $endTime]);
}

// 获取用户预约记录
function getUserReservations($pdo, $userId) {
    $sql = "SELECT r.*, s.seat_number, s.floor_number, s.area
            FROM reservations r
            JOIN seats s ON r.seat_id = s.id
            WHERE r.user_id = ?
            ORDER BY r.reservation_date DESC, r.start_time DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

// 用户认证
function authenticateUser($pdo, $username, $password) {
    $sql = "SELECT id, username, password FROM users WHERE username = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    return false;
}

// 注册用户
function registerUser($pdo, $username, $password, $email = null, $phone = null) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // 查询当前是否已有用户
    $countStmt = $pdo->query("SELECT COUNT(*) FROM users");
    $userCount = $countStmt->fetchColumn();

    // 如果是第一个用户，设置为 admin，否则为普通 user
    $role = ($userCount == 0) ? 'admin' : 'user';

    $sql = "INSERT INTO users (username, password, email, phone, role) VALUES (?, ?, ?, ?, ?)";

    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$username, $hashedPassword, $email, $phone, $role]);
    } catch(PDOException $e) {
        if ($e->errorInfo[1] == 1062) { // 重复键错误
            return false;
        }
        throw $e;
    }
}


// 启动session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 初始化数据库
initializeDatabase($pdo);

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 获取当前用户信息
function getCurrentUser($pdo) {
    if (!isLoggedIn()) {
        return null;
    }

    $sql = "SELECT id, username, email, phone, role FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// 修复后的管理员检查函数
function isAdmin($pdo) {
    if (!isLoggedIn()) {
        return false;
    }
    $user = getCurrentUser($pdo);
    return $user && $user['role'] === 'admin';
}

// 用户管理
// 获取所有用户信息
function getAllUsers($pdo) {
    $sql = "SELECT id, username, email, phone, role, created_at FROM users ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// 删除用户
function deleteUser($pdo, $userId) {
    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$userId]);
}

// 更新用户角色
function updateUserRole($pdo, $userId, $role) {
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$role, $userId]);
}

// 座位管理
function getAllSeatsAdmin($pdo) {
    $sql = "SELECT * FROM seats ORDER BY floor_number, area, seat_number";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// 添加座位
function addSeat($pdo, $seatNumber, $floorNumber, $area) {
    $sql = "INSERT INTO seats (seat_number, floor_number, area) VALUES (?, ?, ?)";
    try {
        $stmt = $pdo->prepare($sql);
        return $stmt->execute([$seatNumber, $floorNumber, $area]);
    } catch(PDOException $e) {
        if ($e->errorInfo[1] == 1062) { // 重复键错误
            return false;
        }
        throw $e;
    }
}

// 更新座位信息
function updateSeat($pdo, $seatId, $seatNumber, $floorNumber, $area) {
    $sql = "UPDATE seats SET seat_number = ?, floor_number = ?, area = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$seatNumber, $floorNumber, $area, $seatId]);
}

// 删除座位
function deleteSeat($pdo, $seatId) {
    $sql = "DELETE FROM seats WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$seatId]);
}

// 预约管理

// 获取所有预约记录（管理员用）
function getAllReservations($pdo) {
    $sql = "SELECT r.*, u.username, s.seat_number, s.floor_number, s.area
            FROM reservations r
            JOIN users u ON r.user_id = u.id
            JOIN seats s ON r.seat_id = s.id
            ORDER BY r.reservation_date DESC, r.start_time DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// 取消预约
function cancelReservation($pdo, $reservationId) {
    $sql = "UPDATE reservations SET status = 'cancelled' WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute([$reservationId]);
}

// 获取统计数据
function getStatistics($pdo) {
    $stats = [];

    // 总用户数
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();

    // 总座位数
    $stmt = $pdo->query("SELECT COUNT(*) FROM seats");
    $stats['total_seats'] = $stmt->fetchColumn();

    // 今日预约数
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE reservation_date = CURDATE() AND status = 'active'");
    $stmt->execute();
    $stats['today_reservations'] = $stmt->fetchColumn();

    // 当前占用座位数
    $currentTime = date('H:i:s');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations r
                          WHERE r.reservation_date = CURDATE()
                          AND r.status = 'active'
                          AND r.start_time <= ?
                          AND r.end_time >= ?");
    $stmt->execute([$currentTime, $currentTime]);
    $stats['current_occupied'] = $stmt->fetchColumn();

    return $stats;
}

?>
