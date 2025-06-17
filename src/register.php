<?php
require_once 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // 验证输入
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = '用户名长度必须在3-20个字符之间';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6个字符';
    } elseif ($password !== $confirmPassword) {
        $error = '两次输入的密码不匹配';
    } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '邮箱格式不正确';
    } else {
        // 尝试注册用户
        if (registerUser($pdo, $username, $password, $email ?: null, $phone ?: null)) {
            $success = '注册成功！请登录您的账户。';
        } else {
            $error = '用户名已存在，请选择其他用户名';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>注册 - 图书馆选座系统</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-50 to-emerald-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <!-- 返回主页链接 -->
        <div class="text-center mb-6">
            <a href="index.php" class="text-green-600 hover:text-green-800 font-medium transition duration-200">
                ← 返回主页
            </a>
        </div>

        <!-- 注册卡片 -->
        <div class="bg-white rounded-lg shadow-xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800">📚</h1>
                <h2 class="text-2xl font-bold text-gray-800 mt-2">用户注册</h2>
                <p class="text-gray-600 mt-2">创建您的账户以开始使用</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo htmlspecialchars($success); ?>
                    <div class="mt-2">
                        <a href="login.php" class="font-medium underline">立即登录</a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        用户名 <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                           placeholder="3-20个字符">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        密码 <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                           placeholder="至少6个字符">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        确认密码 <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" required
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                           placeholder="请再次输入密码">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        邮箱 <span class="text-gray-400">(可选)</span>
                    </label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                           placeholder="your@example.com">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        手机号 <span class="text-gray-400">(可选)</span>
                    </label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                           class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-200"
                           placeholder="13800138000">
                </div>

                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105 mt-6">
                    注册账户
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    已有账户？
                    <a href="login.php" class="text-green-600 hover:text-green-800 font-medium transition duration-200">
                        立即登录
                    </a>
                </p>
            </div>
        </div>

        <!-- 页脚 -->
        <div class="text-center mt-8 text-gray-500 text-sm">
            <p>&copy; 2024 图书馆选座系统. All rights reserved.</p>
        </div>
    </div>

    <script>
        // 实时密码匹配验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;

            if (confirmPassword && password !== confirmPassword) {
                this.setCustomValidity('密码不匹配');
                this.classList.add('border-red-500');
            } else {
                this.setCustomValidity('');
                this.classList.remove('border-red-500');
            }
        });
    </script>
</body>
</html>
