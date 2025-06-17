<?php
require_once 'db.php';

// 处理筛选条件
$selectedFloor = $_GET['floor'] ?? '';
$selectedArea = $_GET['area'] ?? '';

// 获取座位数据
$seats = getAllSeats($pdo, $selectedFloor ?: null, $selectedArea ?: null);

// 按楼层和区域分组
$seatsByFloorAndArea = [];
foreach ($seats as $seat) {
    $seatsByFloorAndArea[$seat['floor_number']][$seat['area']][] = $seat;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>图书馆选座系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .seat {
            transition: all 0.3s ease;
        }
        .seat:hover {
            transform: scale(1.05);
        }
        .seat.occupied {
            cursor: not-allowed;
        }
        .seat.available {
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- 导航栏 -->
    <nav class="bg-blue-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold">📚 图书馆选座系统</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <?php $currentUser = getCurrentUser($pdo); ?>
                        <span class="text-blue-100">你好, <?php echo htmlspecialchars($currentUser['username']); ?></span>

                        <!-- 检查是否为管理员，显示管理后台链接 -->
                        <?php if (isAdmin($pdo)): ?>
                            <a href="admin_panel.php" class="bg-purple-500 hover:bg-purple-700 px-3 py-2 rounded-md transition duration-200 flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                管理后台
                            </a>
                        <?php endif; ?>

                        <a href="my_reservations.php" class="bg-blue-500 hover:bg-blue-700 px-3 py-2 rounded-md transition duration-200">我的预约</a>
                        <a href="logout.php" class="bg-red-500 hover:bg-red-700 px-3 py-2 rounded-md transition duration-200">退出</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-blue-500 hover:bg-blue-700 px-3 py-2 rounded-md transition duration-200">登录</a>
                        <a href="register.php" class="bg-green-500 hover:bg-green-700 px-3 py-2 rounded-md transition duration-200">注册</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 筛选器 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-lg font-semibold mb-4">筛选座位</h2>
            <form method="GET" class="flex flex-wrap gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">楼层</label>
                    <select name="floor" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">所有楼层</option>
                        <option value="1" <?php echo $selectedFloor == '1' ? 'selected' : ''; ?>>1楼</option>
                        <option value="2" <?php echo $selectedFloor == '2' ? 'selected' : ''; ?>>2楼</option>
                        <option value="3" <?php echo $selectedFloor == '3' ? 'selected' : ''; ?>>3楼</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">区域</label>
                    <select name="area" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">所有区域</option>
                        <option value="A区" <?php echo $selectedArea == 'A区' ? 'selected' : ''; ?>>A区</option>
                        <option value="B区" <?php echo $selectedArea == 'B区' ? 'selected' : ''; ?>>B区</option>
                        <option value="C区" <?php echo $selectedArea == 'C区' ? 'selected' : ''; ?>>C区</option>
                        <option value="D区" <?php echo $selectedArea == 'D区' ? 'selected' : ''; ?>>D区</option>
                    </select>
                </div>
                <div class="flex items-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition duration-200">
                        筛选
                    </button>
                </div>
            </form>
        </div>

        <!-- 图例 -->
        <div class="bg-white rounded-lg shadow-md p-4 mb-8">
            <h3 class="text-md font-semibold mb-3">座位状态说明</h3>
            <div class="flex flex-wrap gap-6">
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
                    <span class="text-sm">可预约</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
                    <span class="text-sm">已占用</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
                    <span class="text-sm">我的预约</span>
                </div>
            </div>
        </div>

        <!-- 座位展示 -->
        <?php foreach ($seatsByFloorAndArea as $floor => $areas): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <h2 class="text-xl font-bold mb-6 text-gray-800"><?php echo $floor; ?>楼</h2>

                <?php foreach ($areas as $area => $areaSeats): ?>
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4 text-gray-700"><?php echo $area; ?></h3>
                        <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 xl:grid-cols-10 gap-3">
                            <?php foreach ($areaSeats as $seat): ?>
                                <?php
                                // 修复座位状态判断逻辑
                                $isMyReservation = false;
                                $isOccupied = false;

                                if (isLoggedIn() && !empty($seat['user_id']) && $seat['user_id'] == $_SESSION['user_id']) {
                                    // 是我的预约
                                    $isMyReservation = true;
                                    $seatClass = 'bg-blue-500 hover:bg-blue-600';
                                    $cursorClass = 'available';
                                } elseif (!empty($seat['user_id']) || $seat['is_available'] == 0) {
                                    // 被其他人占用
                                    $isOccupied = true;
                                    $seatClass = 'bg-red-500';
                                    $cursorClass = 'occupied';
                                } else {
                                    // 可预约
                                    $seatClass = 'bg-green-500 hover:bg-green-600';
                                    $cursorClass = 'available';
                                }
                                ?>
                                <div class="seat <?php echo $seatClass; ?> <?php echo $cursorClass; ?> text-white text-center py-3 px-2 rounded-lg transition-all duration-200 shadow-md"
                                     onclick="<?php echo (!$isOccupied || $isMyReservation) ? 'reserveSeat(' . $seat['id'] . ', \'' . $seat['seat_number'] . '\')' : 'showOccupiedMessage()'; ?>"
                                     title="座位号: <?php echo $seat['seat_number']; ?>
<?php if ($isOccupied && !$isMyReservation): ?>
状态: 已占用
使用时间: <?php echo $seat['start_time']; ?> - <?php echo $seat['end_time']; ?>
<?php elseif ($isMyReservation): ?>
状态: 我的预约
使用时间: <?php echo $seat['start_time']; ?> - <?php echo $seat['end_time']; ?>
<?php else: ?>
状态: 可预约
<?php endif; ?>">
                                    <div class="text-xs font-medium"><?php echo $seat['seat_number']; ?></div>
                                    <?php if (($isOccupied || $isMyReservation) && !empty($seat['start_time']) && !empty($seat['end_time'])): ?>
                                        <div class="text-xs mt-1 opacity-90">
                                            <?php echo substr($seat['start_time'], 0, 5); ?>-<?php echo substr($seat['end_time'], 0, 5); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <?php if (empty($seats)): ?>
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="text-gray-500 text-lg">暂无座位数据</div>
            </div>
        <?php endif; ?>
    </div>

    <!-- 预约模态框 -->
    <div id="reservationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold mb-4">预约座位</h3>
            <form id="reservationForm" action="reserve_seat.php" method="POST">
                <input type="hidden" id="seatId" name="seat_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">座位号</label>
                    <input type="text" id="seatNumber" readonly class="w-full border border-gray-300 rounded-md px-3 py-2 bg-gray-50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">预约日期</label>
                    <input type="date" name="reservation_date" required
                           min="<?php echo date('Y-m-d'); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+7 days')); ?>"
                           value="<?php echo date('Y-m-d'); ?>"
                           class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">开始时间</label>
                        <input type="time" name="start_time" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">结束时间</label>
                        <input type="time" name="end_time" required
                               class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeReservationModal()"
                            class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-md transition duration-200">
                        取消
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition duration-200">
                        确认预约
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function reserveSeat(seatId, seatNumber) {
            <?php if (!isLoggedIn()): ?>
                alert('请先登录');
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            document.getElementById('seatId').value = seatId;
            document.getElementById('seatNumber').value = seatNumber;
            document.getElementById('reservationModal').classList.remove('hidden');
            document.getElementById('reservationModal').classList.add('flex');
        }

        function closeReservationModal() {
            document.getElementById('reservationModal').classList.add('hidden');
            document.getElementById('reservationModal').classList.remove('flex');
        }

        function showOccupiedMessage() {
            alert('该座位已被占用，无法预约');
        }

        // 点击模态框外部关闭
        document.getElementById('reservationModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReservationModal();
            }
        });
    </script>
</body>
</html>
