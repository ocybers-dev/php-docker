<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎使用 PHP + MySQL 开发环境</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            padding: 20px;
        }
        h1 {
            color: #2c3e50;
        }
        h2 {
            color: #34495e;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <h1>欢迎使用 Docker 化 PHP 开发环境</h1>
    <p><strong>注意：</strong>本环境仅供学习使用，帮助快速搭建 PHP 和 MySQL 开发环境。</p>

    <h2>项目目录结构</h2>
    <p>项目目录结构如下：</p>
    <pre>
    .
    ├── docker-compose.yml  # Docker 配置文件
    ├── Dockerfile  # 自定义 PHP 镜像的 Dockerfile
    ├── src/
    │   └── index.php  # PHP 文件，位于该文件夹中编写代码
    └── README.md  # 项目说明文件（本文件内容可以放入此处）
    </pre>

    <h2>如何编写代码</h2>
    <p>所有 PHP 代码应当编写在 <code>src/</code> 文件夹中。当前文件 <code>index.php</code> 是一个示例文件，你可以在这里编写新的 PHP 脚本。</p>
    <p>例如：数据库连接、查询等逻辑可以在 <code>index.php</code> 中实现。</p>

    <h2>数据库连接</h2>
    <p>这是一个用于测试数据库连接的简单 PHP 脚本。以下是数据库的连接信息：</p>
    <ul>
        <li><strong>数据库主机名：</strong> php-mysql（Docker 容器的名称，在同一网络中使用容器名称）</li>
        <li><strong>用户名：</strong> php</li>
        <li><strong>密码：</strong> php</li>
        <li><strong>数据库名：</strong> php</li>
    </ul>
    <p><strong>注意：</strong>如果数据库连接成功，页面将显示成功信息，并列出数据库中的表格。</p>
    <p>连接代码如下：</p>
    <pre>
    $servername = "php-mysql";  // MySQL 容器的名称
    $username = "php";
    $password = "php";
    $dbname = "php";

    $conn = new mysqli($servername, $username, $password, $dbname);
    </pre>

    <h2>Docker Compose 启动命令</h2>
    <p>运行以下命令来启动 Docker 服务：</p>
    <pre>
    docker-compose up -d
    </pre>
    <p>该命令将构建并启动 PHP 和 MySQL 服务。在成功启动后，PHP 服务可以通过 <code>http://localhost:8888</code> 访问。</p>

    <h2>MySQL 配置</h2>
    <p>你可以在 <code>docker-compose.yml</code> 文件中修改 MySQL 配置项：</p>
    <pre>
    mysql:
      image: mysql:8.0
      container_name: php-mysql
      environment:
        MYSQL_ROOT_PASSWORD: php
        MYSQL_DATABASE: php
        MYSQL_USER: php
        MYSQL_PASSWORD: php
      ports:
        - "3333:3306"
    </pre>
    <p>以上配置定义了数据库的 root 密码、数据库名和用户权限等信息。</p>

    <h2>成功连接数据库</h2>
    <?php
    // MySQL 数据库连接示例
    $servername = "php-mysql";  // MySQL 容器的名称
    $username = "php";
    $password = "php";
    $dbname = "php";

    // 创建数据库连接
    $conn = new mysqli($servername, $username, $password, $dbname);

    // 检查连接
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    } else {
        echo "<p>成功连接到数据库！</p>";
        echo "<p>数据库: $dbname</p>";

        // 查询数据库中的表格
        $sql = "SHOW TABLES";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            echo "<p>数据库中的表格：</p><ul>";
            while($row = $result->fetch_assoc()) {
                echo "<li>" . $row["Tables_in_php"] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>没有找到任何表格</p>";
        }
    }

    // 关闭连接
    $conn->close();
    ?>
</body>
</html>
