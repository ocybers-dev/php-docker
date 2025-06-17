<?php
require_once 'db.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $seatId = (int)$_POST['seat_id'];
    $reservationDate = $_POST['reservation_date'];
    $startTime = $_POST['start_time'];
    $endTime = $_POST['end_time'];
    $userId = $_SESSION['user_id'];

    // 验证输入
    if (empty($seatId) || empty($reservationDate) || empty($startTime) || empty($endTime)) {
        $message = '所有字段都是必填的';
    } elseif ($reservationDate < date('Y-m-d')) {
        $message = '不能预约过去的日期';
    } elseif ($reservationDate > date('Y-m-d', strtotime('+7 days'))) {
        $message = '最多只能提前7天预约';
    } elseif ($startTime >= $endTime) {
        $message = '结束时间必须晚于开始时间';
    } else {
        // 检查时间冲突和预约限制
        $conflictSql = "SELECT COUNT(*) FROM reservations
                        WHERE user_id = ? AND reservation_date = ?
                        AND status = 'active'
                        AND (
                            (start_time <= ? AND end_time > ?) OR
                            (start_time < ? AND end_time >= ?) OR
                            (start_time >= ? AND end_time <= ?)
                        )";

        $conflictStmt = $pdo->prepare($conflictSql);
        $conflictStmt->execute([$userId, $reservationDate, $startTime, $startTime, $endTime, $endTime, $startTime, $endTime]);

        if ($conflictStmt->fetchColumn() > 0) {
            $message = '您在该时间段已有预约，请选择其他时间';
        } else {
            // 尝试预约座位
            if (reserveSeat($pdo, $userId, $seatId, $reservationDate, $startTime, $endTime)) {
                $message = '预约成功！';
                $messageType = 'success';
            } else {
                $message = '该座位在选定时间已被预约，请选择其他座位或时间';
            }
        }
    }
}

// 获取座位信息（用于显示）
$seatInfo = null;
if (!empty($_POST['seat_id'])) {
    $seatSql = "SELECT seat_number, floor_number, area FROM seats WHERE id = ?";
    $seatStmt = $pdo->prepare($seatSql);
    $seatStmt->execute([$_POST['seat_id']]);
    $seatInfo = $seatStmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>预约结果 - 图书馆选座系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 导航栏 -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-xl font-bold hover:text-blue-200 transition duration-200">
                        📚 图书馆选座系统
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php $currentUser = getCurrentUser($pdo); ?>
                    <span class="text-blue-100">你好, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="my_reservations.php" class="bg-blue-500 hover:bg-blue-700 px-3 py-2 rounded-md transition duration-200">我的预约</a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-700 px-3 py-2 rounded-md transition duration-200">退出</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- 结果显示 -->
            <div class="text-center mb-8">
                <?php if ($messageType == 'success'): ?>
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-green-600 mb-2">预约成功！</h1>
                <?php else: ?>
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-red-600 mb-2">预约失败</h1>
                <?php endif; ?>

                <div class="<?php echo $messageType == 'success' ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200'; ?>
                           border px-4 py-3 rounded-lg">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            </div>

            <!-- 预约详情 -->
            <?php if ($seatInfo && $messageType == 'success'): ?>
                <div class="bg-gray-50 rounded-lg p-6 mb-6">
                    <h2 class="text-lg font-semibold mb-4 text-gray-800">预约详情</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-600">座位号：</span>
                            <span class="font-medium"><?php echo htmlspecialchars($seatInfo['seat_number']); ?></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">位置：</span>
                            <span class="font-medium"><?php echo $seatInfo['floor_number']; ?>楼 <?php echo htmlspecialchars($seatInfo['area']); ?></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">日期：</span>
                            <span class="font-medium"><?php echo htmlspecialchars($_POST['reservation_date']); ?></span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-600">时间：</span>
                            <span class="font-medium"><?php echo htmlspecialchars($_POST['start_time']); ?> - <?php echo htmlspecialchars($_POST['end_time']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 操作按钮 -->
            <div class="flex flex-col sm:flex-row gap-3 justify-center">
                <a href="index.php"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-center transition duration-200">
                    返回选座
                </a>
                <a href="my_reservations.php"
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-center transition duration-200">
                    查看我的预约
                </a>
                <?php if ($messageType == 'error'): ?>
                    <button onclick="history.back()"
                            class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-3 rounded-lg transition duration-200">
                        重新预约
                    </button>
                <?php endif; ?>
            </div>

            <!-- 温馨提示 -->
            <?php if ($messageType == 'success'): ?>
                <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-semibold text-blue-800 mb-2">温馨提示</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• 请准时到达，迟到15分钟座位将自动释放</li>
                        <li>• 如需取消预约，请在预约开始前1小时操作</li>
                        <li>• 保持座位整洁，离开时请整理好个人物品</li>
                        <li>• 在图书馆内请保持安静，不要影响他人学习</li>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
