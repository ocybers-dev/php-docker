<?php
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        $user = authenticateUser($pdo, $username, $password);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 图书馆选座系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- 返回主页链接 -->
        <div class="text-center mb-6">
            <a href="index.php" class="text-blue-600 hover:text-blue-800 font-medium transition duration-200">
                ← 返回主页
            </a>
        </div>

        <!-- 登录卡片 -->
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">📚</h1>
                <h2 class="text-2xl font-bold text-gray-800 mt-2">用户登录</h2>
                <p class="text-gray-600 mt-2">登录您的账户以预约座位</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        用户名
                    </label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="请输入用户名">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        密码
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="请输入密码">
                </div>

                <button type="submit"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                    登录
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    还没有账户？
                    <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium transition duration-200">
                        立即注册
                    </a>
                </p>
            </div>

            <!-- 测试账户提示 -->
            <div class="mt-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-sm font-medium text-blue-800 mb-2">测试账户</h3>
                <p class="text-sm text-blue-700">
                    用户名: test_user<br>
                    密码: 123456
                </p>
            </div>
        </div>

        <!-- 页脚 -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; 2024 图书馆选座系统. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
