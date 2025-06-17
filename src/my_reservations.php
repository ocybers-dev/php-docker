<?php
require_once 'db.php';

// 检查用户是否已登录
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$message = '';
$messageType = '';

// 处理取消预约
if (isset($_POST['cancel_reservation'])) {
    $reservationId = (int)$_POST['reservation_id'];

    // 验证预约是否属于当前用户且可以取消
    $checkSql = "SELECT * FROM reservations
                 WHERE id = ? AND user_id = ? AND status = 'active'
                 AND CONCAT(reservation_date, ' ', start_time) > DATE_ADD(NOW(), INTERVAL 1 HOUR)";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$reservationId, $_SESSION['user_id']]);

    if ($checkStmt->fetch()) {
        $cancelSql = "UPDATE reservations SET status = 'cancelled' WHERE id = ?";
        $cancelStmt = $pdo->prepare($cancelSql);
        if ($cancelStmt->execute([$reservationId])) {
            $message = '预约已成功取消';
            $messageType = 'success';
        } else {
            $message = '取消预约失败，请重试';
            $messageType = 'error';
        }
    } else {
        $message = '无法取消该预约（预约不存在或开始时间不足1小时）';
        $messageType = 'error';
    }
}

// 获取用户的预约记录
$reservations = getUserReservations($pdo, $_SESSION['user_id']);

// 按状态分组
$activeReservations = [];
$pastReservations = [];
$cancelledReservations = [];

foreach ($reservations as $reservation) {
    $reservationDateTime = $reservation['reservation_date'] . ' ' . $reservation['end_time'];
    $isUpcoming = strtotime($reservationDateTime) > time();

    if ($reservation['status'] == 'cancelled') {
        $cancelledReservations[] = $reservation;
    } elseif ($reservation['status'] == 'active' && $isUpcoming) {
        $activeReservations[] = $reservation;
    } else {
        $pastReservations[] = $reservation;
    }
}

$currentUser = getCurrentUser($pdo);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的预约 - 图书馆选座系统</title>
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
                    <span class="text-blue-100">你好, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="index.php" class="bg-blue-500 hover:bg-blue-700 px-3 py-2 rounded-md transition duration-200">选座</a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-700 px-3 py-2 rounded-md transition duration-200">退出</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">我的预约</h1>
            <p class="text-gray-600 mt-2">管理您的座位预约记录</p>
        </div>

        <!-- 消息提示 -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType == 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- 统计卡片 -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">活跃预约</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo count($activeReservations); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">历史预约</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo count($pastReservations); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">已取消</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo count($cancelledReservations); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 选项卡 -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex">
                    <button onclick="showTab('active')" id="tab-active"
                            class="tab-button py-4 px-6 text-sm font-medium text-blue-600 border-b-2 border-blue-600">
                        活跃预约 (<?php echo count($activeReservations); ?>)
                    </button>
                    <button onclick="showTab('past')" id="tab-past"
                            class="tab-button py-4 px-6 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300">
                        历史记录 (<?php echo count($pastReservations); ?>)
                    </button>
                    <button onclick="showTab('cancelled')" id="tab-cancelled"
                            class="tab-button py-4 px-6 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 hover:border-gray-300">
                        已取消 (<?php echo count($cancelledReservations); ?>)
                    </button>
                </nav>
            </div>

            <!-- 活跃预约 -->
            <div id="content-active" class="tab-content p-6">
                <?php if (empty($activeReservations)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">暂无活跃预约</h3>
                        <p class="mt-2 text-sm text-gray-500">去选择一个座位开始学习吧！</p>
                        <div class="mt-6">
                            <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-200">
                                立即选座
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($activeReservations as $reservation): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition duration-200">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-4 mb-2">
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">
                                                座位 <?php echo htmlspecialchars($reservation['seat_number']); ?>
                                            </span>
                                            <span class="text-gray-600 text-sm">
                                                <?php echo $reservation['floor_number']; ?>楼 <?php echo htmlspecialchars($reservation['area']); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            <span class="mr-4">📅 <?php echo $reservation['reservation_date']; ?></span>
                                            <span>🕐 <?php echo substr($reservation['start_time'], 0, 5); ?> - <?php echo substr($reservation['end_time'], 0, 5); ?></span>
                                        </div>
                                    </div>
                                    <div class="mt-3 sm:mt-0 sm:ml-4">
                                        <?php
                                        $reservationStartTime = $reservation['reservation_date'] . ' ' . $reservation['start_time'];
                                        $canCancel = strtotime($reservationStartTime) > (time() + 3600); // 1小时前可以取消
                                        ?>
                                        <?php if ($canCancel): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('确定要取消这个预约吗？')">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <button type="submit" name="cancel_reservation"
                                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm transition duration-200">
                                                    取消预约
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-500 text-sm">开始前1小时内无法取消</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 历史记录 -->
            <div id="content-past" class="tab-content p-6 hidden">
                <?php if (empty($pastReservations)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">暂无历史记录</h3>
                        <p class="mt-2 text-sm text-gray-500">您还没有完成过任何预约</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($pastReservations as $reservation): ?>
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="flex items-center space-x-4 mb-2">
                                    <span class="bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">
                                        座位 <?php echo htmlspecialchars($reservation['seat_number']); ?>
                                    </span>
                                    <span class="text-gray-600 text-sm">
                                        <?php echo $reservation['floor_number']; ?>楼 <?php echo htmlspecialchars($reservation['area']); ?>
                                    </span>
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs">已完成</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <span class="mr-4">📅 <?php echo $reservation['reservation_date']; ?></span>
                                    <span>🕐 <?php echo substr($reservation['start_time'], 0, 5); ?> - <?php echo substr($reservation['end_time'], 0, 5); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- 已取消 -->
            <div id="content-cancelled" class="tab-content p-6 hidden">
                <?php if (empty($cancelledReservations)): ?>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="mt-4 text-lg font-medium text-gray-900">暂无取消记录</h3>
                        <p class="mt-2 text-sm text-gray-500">您还没有取消过任何预约</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($cancelledReservations as $reservation): ?>
                            <div class="border border-gray-200 rounded-lg p-4 bg-red-50">
                                <div class="flex items-center space-x-4 mb-2">
                                    <span class="bg-red-100 text-red-800 px-3 py-1 rounded-full text-sm font-medium">
                                        座位 <?php echo htmlspecialchars($reservation['seat_number']); ?>
                                    </span>
                                    <span class="text-gray-600 text-sm">
                                        <?php echo $reservation['floor_number']; ?>楼 <?php echo htmlspecialchars($reservation['area']); ?>
                                    </span>
                                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded text-xs">已取消</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <span class="mr-4">📅 <?php echo $reservation['reservation_date']; ?></span>
                                    <span>🕐 <?php echo substr($reservation['start_time'], 0, 5); ?> - <?php echo substr($reservation['end_time'], 0, 5); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 快速操作 -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h2 class="text-lg font-semibold mb-4">快速操作</h2>
            <div class="flex flex-col sm:flex-row gap-4">
                <a href="index.php"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg text-center transition duration-200">
                    📍 立即选座
                </a>
                <a href="index.php?floor=1"
                   class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg text-center transition duration-200">
                    🏢 1楼座位
                </a>
                <a href="index.php?floor=2"
                   class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 rounded-lg text-center transition duration-200">
                    🏢 2楼座位
                </a>
                <a href="index.php?floor=3"
                   class="bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg text-center transition duration-200">
                    🏢 3楼座位
                </a>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // 隐藏所有内容
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));

            // 重置所有按钮样式
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => {
                button.classList.remove('text-blue-600', 'border-blue-600');
                button.classList.add('text-gray-500', 'border-transparent');
            });

            // 显示选中的内容
            document.getElementById('content-' + tabName).classList.remove('hidden');

            // 激活选中的按钮
            const activeButton = document.getElementById('tab-' + tabName);
            activeButton.classList.remove('text-gray-500', 'border-transparent');
            activeButton.classList.add('text-blue-600', 'border-blue-600');
        }
    </script>
</body>
</html>
