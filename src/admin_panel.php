<?php
require_once 'db.php';

// 检查是否为管理员
if (!isLoggedIn() || !isAdmin($pdo)) {
    header('Location: index.php');
    exit;
}

// 处理POST请求
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'delete_user':
            $userId = $_POST['user_id'] ?? 0;
            if ($userId && $userId != $_SESSION['user_id']) { // 不能删除自己
                deleteUser($pdo, $userId);
                $message = "用户删除成功";
            }
            break;

        case 'update_user_role':
            $userId = $_POST['user_id'] ?? 0;
            $role = $_POST['role'] ?? '';
            if ($userId && in_array($role, ['user', 'admin'])) {
                updateUserRole($pdo, $userId, $role);
                $message = "用户角色更新成功";
            }
            break;

        case 'add_seat':
            $seatNumber = trim($_POST['seat_number'] ?? '');
            $floorNumber = $_POST['floor_number'] ?? 0;
            $area = trim($_POST['area'] ?? '');

            if ($seatNumber && $floorNumber && $area) {
                if (addSeat($pdo, $seatNumber, $floorNumber, $area)) {
                    $message = "座位添加成功";
                } else {
                    $error = "座位号已存在";
                }
            } else {
                $error = "请填写完整信息";
            }
            break;

        case 'update_seat':
            $seatId = $_POST['seat_id'] ?? 0;
            $seatNumber = trim($_POST['seat_number'] ?? '');
            $floorNumber = $_POST['floor_number'] ?? 0;
            $area = trim($_POST['area'] ?? '');

            if ($seatId && $seatNumber && $floorNumber && $area) {
                updateSeat($pdo, $seatId, $seatNumber, $floorNumber, $area);
                $message = "座位信息更新成功";
            }
            break;

        case 'delete_seat':
            $seatId = $_POST['seat_id'] ?? 0;
            if ($seatId) {
                deleteSeat($pdo, $seatId);
                $message = "座位删除成功";
            }
            break;

        case 'cancel_reservation':
            $reservationId = $_POST['reservation_id'] ?? 0;
            if ($reservationId) {
                cancelReservation($pdo, $reservationId);
                $message = "预约取消成功";
            }
            break;
    }
}

// 获取当前选中的标签页
$activeTab = $_GET['tab'] ?? 'dashboard';

// 获取数据
$stats = getStatistics($pdo);
$users = getAllUsers($pdo);
$seats = getAllSeatsAdmin($pdo);
$reservations = getAllReservations($pdo);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 图书馆选座系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 导航栏 -->
    <nav class="bg-purple-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold">🛠️ 管理后台</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-purple-100">管理员: <?php echo htmlspecialchars(getCurrentUser($pdo)['username']); ?></span>
                    <a href="index.php" class="bg-purple-500 hover:bg-purple-700 px-3 py-2 rounded-md transition duration-200">返回首页</a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-700 px-3 py-2 rounded-md transition duration-200">退出</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 消息提示 -->
        <?php if (isset($message)): ?>
            <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- 标签页导航 -->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6">
                    <button onclick="switchTab('dashboard')"
                            class="tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                                   <?php echo $activeTab === 'dashboard' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                        📊 数据总览
                    </button>
                    <button onclick="switchTab('users')"
                            class="tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                                   <?php echo $activeTab === 'users' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                        👥 用户管理
                    </button>
                    <button onclick="switchTab('seats')"
                            class="tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                                   <?php echo $activeTab === 'seats' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                        🪑 座位管理
                    </button>
                    <button onclick="switchTab('reservations')"
                            class="tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200
                                   <?php echo $activeTab === 'reservations' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                        📅 预约管理
                    </button>
                </nav>
            </div>
        </div>

        <!-- 数据总览 -->
        <div id="dashboard" class="tab-content <?php echo $activeTab === 'dashboard' ? 'active' : ''; ?>">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-500 bg-opacity-75">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">总用户数</dt>
                                <dd class="text-3xl font-bold text-gray-900"><?php echo $stats['total_users']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-500 bg-opacity-75">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">总座位数</dt>
                                <dd class="text-3xl font-bold text-gray-900"><?php echo $stats['total_seats']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-500 bg-opacity-75">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">今日预约</dt>
                                <dd class="text-3xl font-bold text-gray-900"><?php echo $stats['today_reservations']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-500 bg-opacity-75">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">当前占用</dt>
                                <dd class="text-3xl font-bold text-gray-900"><?php echo $stats['current_occupied']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-semibold mb-4">系统概览</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">座位使用率</h4>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <?php $usage_rate = $stats['total_seats'] > 0 ? ($stats['current_occupied'] / $stats['total_seats']) * 100 : 0; ?>
                            <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $usage_rate; ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-1"><?php echo number_format($usage_rate, 1); ?>% (<?php echo $stats['current_occupied']; ?>/<?php echo $stats['total_seats']; ?>)</p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">今日预约完成度</h4>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <?php $reservation_rate = $stats['total_seats'] > 0 ? ($stats['today_reservations'] / $stats['total_seats']) * 100 : 0; ?>
                            <div class="bg-green-600 h-2.5 rounded-full" style="width: <?php echo min($reservation_rate, 100); ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-1"><?php echo number_format($reservation_rate, 1); ?>% (<?php echo $stats['today_reservations']; ?>/<?php echo $stats['total_seats']; ?>)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 用户管理 -->
        <div id="users" class="tab-content <?php echo $activeTab === 'users' ? 'active' : ''; ?>">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">用户管理</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户名</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">邮箱</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">电话</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">角色</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">注册时间</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $user['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_user_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <select name="role" onchange="this.form.submit()" class="text-sm border-gray-300 rounded">
                                                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>普通用户</option>
                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>管理员</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('确定要删除此用户吗？')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">删除</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400">当前用户</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 座位管理 -->
        <div id="seats" class="tab-content <?php echo $activeTab === 'seats' ? 'active' : ''; ?>">
            <!-- 添加座位表单 -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">添加新座位</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <input type="hidden" name="action" value="add_seat">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">座位号</label>
                        <input type="text" name="seat_number" required class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">楼层</label>
                        <input name="floor_number" list="floorOptions" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="选择或输入楼层" />
                        <datalist id="floorOptions">
                            <option value="1">1楼</option>
                            <option value="2">2楼</option>
                            <option value="3">3楼</option>
                        </datalist>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">区域</label>
                        <input name="area" list="areaOptions" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500"
                            placeholder="选择或输入区域" />
                        <datalist id="areaOptions">
                            <option value="A区"></option>
                            <option value="B区"></option>
                            <option value="C区"></option>
                            <option value="D区"></option>
                        </datalist>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-md transition duration-200">
                            添加座位
                        </button>
                    </div>
                </form>
            </div>

            <!-- 座位列表 -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">座位列表</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">座位号</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">楼层</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">区域</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">创建时间</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($seats as $seat): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $seat['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($seat['seat_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $seat['floor_number']; ?>楼</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($seat['area']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('Y-m-d H:i', strtotime($seat['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="editSeat(<?php echo $seat['id']; ?>, '<?php echo htmlspecialchars($seat['seat_number']); ?>', <?php echo $seat['floor_number']; ?>, '<?php echo htmlspecialchars($seat['area']); ?>')"
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">编辑</button>
                                        <form method="POST" class="inline" onsubmit="return confirm('确定要删除此座位吗？')">
                                            <input type="hidden" name="action" value="delete_seat">
                                            <input type="hidden" name="seat_id" value="<?php echo $seat['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900">删除</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- 预约管理 -->
        <div id="reservations" class="tab-content <?php echo $activeTab === 'reservations' ? 'active' : ''; ?>">
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">预约记录</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">用户</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">座位</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">位置</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">预约日期</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">时间</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">状态</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $reservation['id']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($reservation['username']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($reservation['seat_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $reservation['floor_number']; ?>楼 <?php echo htmlspecialchars($reservation['area']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $reservation['reservation_date']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo substr($reservation['start_time'], 0, 5); ?> - <?php echo substr($reservation['end_time'], 0, 5); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $statusColors = [
                                            'active' => 'bg-green-100 text-green-800',
                                            'completed' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-red-100 text-red-800'
                                        ];
                                        $statusText = [
                                            'active' => '进行中',
                                            'completed' => '已完成',
                                            'cancelled' => '已取消'
                                        ];
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusColors[$reservation['status']]; ?>">
                                            <?php echo $statusText[$reservation['status']]; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($reservation['status'] === 'active'): ?>
                                            <form method="POST" class="inline" onsubmit="return confirm('确定要取消此预约吗？')">
                                                <input type="hidden" name="action" value="cancel_reservation">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-900">取消预约</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-400">已处理</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 编辑座位模态框 -->
    <div id="editSeatModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">编辑座位</h3>
            <form id="editSeatForm" method="POST">
                <input type="hidden" name="action" value="update_seat">
                <input type="hidden" id="editSeatId" name="seat_id">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">座位号</label>
                    <input type="text" id="editSeatNumber" name="seat_number" required
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">楼层</label>
                    <select id="editFloorNumber" name="floor_number" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="1">1楼</option>
                        <option value="2">2楼</option>
                        <option value="3">3楼</option>
                    </select>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">区域</label>
                    <select id="editArea" name="area" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="A区">A区</option>
                        <option value="B区">B区</option>
                        <option value="C区">C区</option>
                        <option value="D区">D区</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeEditSeatModal()"
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md transition duration-200">
                        取消
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md transition duration-200">
                        保存修改
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // 标签页切换
        function switchTab(tabName) {
            // 隐藏所有标签页内容
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // 重置所有标签按钮样式
            document.querySelectorAll('.tab-button').forEach(button => {
                button.className = 'tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
            });

            // 显示选中的标签页内容
            document.getElementById(tabName).classList.add('active');

            // 高亮选中的标签按钮
            event.target.className = 'tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 border-purple-500 text-purple-600';

            // 更新URL参数
            const url = new URL(window.location);
            url.searchParams.set('tab', tabName);
            window.history.replaceState({}, '', url);
        }

        // 编辑座位
        function editSeat(id, seatNumber, floorNumber, area) {
            document.getElementById('editSeatId').value = id;
            document.getElementById('editSeatNumber').value = seatNumber;
            document.getElementById('editFloorNumber').value = floorNumber;
            document.getElementById('editArea').value = area;

            document.getElementById('editSeatModal').classList.remove('hidden');
            document.getElementById('editSeatModal').classList.add('flex');
        }

        function closeEditSeatModal() {
            document.getElementById('editSeatModal').classList.add('hidden');
            document.getElementById('editSeatModal').classList.remove('flex');
        }

        // 点击模态框外部关闭
        document.getElementById('editSeatModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditSeatModal();
            }
        });

        // 初始化：根据URL参数设置活动标签页
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const activeTab = urlParams.get('tab') || 'dashboard';

            // 激活对应的标签页
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(activeTab).classList.add('active');

            // 激活对应的标签按钮
            document.querySelectorAll('.tab-button').forEach((button, index) => {
                const tabs = ['dashboard', 'users', 'seats', 'reservations'];
                if (tabs[index] === activeTab) {
                    button.className = 'tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 border-purple-500 text-purple-600';
                } else {
                    button.className = 'tab-button py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';
                }
            });
        });
    </script>
</body>
</html>
