# PHP + MySQL Docker 开发环境

欢迎使用本项目！这是一个基于 Docker 的 PHP 和 MySQL 开发环境，旨在帮助你快速搭建开发环境，进行 PHP 开发和学习。此环境仅供学习使用，帮助理解如何将 PHP 和 MySQL 配合使用。

## 项目结构

本项目包含以下文件和目录结构：

```
.
├── docker-compose.yml  # Docker 配置文件
├── Dockerfile          # 自定义 PHP 镜像的 Dockerfile
├── src/                # PHP 代码文件夹
│   └── index.php       # PHP 文件，位于该文件夹中编写代码
└── README.md           # 项目说明文件
```

- `docker-compose.yml`：定义了 PHP 和 MySQL 服务的配置。
- `Dockerfile`：用于构建自定义的 PHP 镜像。
- `src/`：所有的 PHP 代码应当编写在这个文件夹中。
- `index.php`：示例文件，演示了如何连接 MySQL 数据库并进行简单的操作。
- `README.md`：本文件，帮助你了解如何使用本项目。

## 环境要求

- **Docker**：本项目使用 Docker 来创建和管理容器。
- **Docker Compose**：用于管理多容器的 Docker 服务。

## 使用步骤

### 1. 克隆项目

首先，克隆本项目到本地：

```bash
git clone <your-repository-url>
cd <project-directory>
```

### 2. 配置 MySQL（可选）

你可以在 `docker-compose.yml` 文件中修改 MySQL 的配置：

```yaml
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
```

- `MYSQL_ROOT_PASSWORD`：设置 root 用户的密码。
- `MYSQL_DATABASE`：设置默认创建的数据库名。
- `MYSQL_USER`：设置数据库用户名。
- `MYSQL_PASSWORD`：设置该用户的密码。

### 3. 启动 Docker 服务

运行以下命令来启动 PHP 和 MySQL 服务：

```bash
docker-compose up -d
```

`-d` 参数表示后台运行容器。

### 4. 访问 PHP 服务

容器启动后，你可以通过浏览器访问 PHP 服务：

```
http://localhost:8888
```

### 5. 编写代码

所有的 PHP 代码应当放置在 `src/` 文件夹中。你可以在 `index.php` 文件中进行数据库连接测试，或者编写自己的 PHP 脚本。

### 6. 测试数据库连接

`index.php` 文件是一个示例文件，它展示了如何连接 MySQL 数据库并查询表格。连接信息如下：

- **数据库主机名**：`php-mysql`（在 Docker 网络中使用容器名称）
- **用户名**：`php`
- **密码**：`php`
- **数据库名**：`php`

数据库连接成功后，页面将显示成功信息并列出数据库中的表格。

## 更多资源

- [PHP 官方文档](https://www.php.net/manual/zh/)
- [MySQL 官方文档](https://dev.mysql.com/doc/)

## 免责声明

本项目仅供学习和开发使用，不建议用于生产环境。在使用过程中，如有任何问题，可以参考官方文档或者在此提问。


