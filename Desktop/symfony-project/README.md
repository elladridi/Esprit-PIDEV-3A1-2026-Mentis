# Mentis - Mental Health Content Management Platform

[![Symfony](https://img.shields.io/badge/Symfony-7.4-000000?style=flat&logo=symfony)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php)](https://php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat&logo=mysql)](https://mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat&logo=bootstrap)](https://getbootstrap.com/)
[![Z.ai](https://img.shields.io/badge/Z.ai-LLM-FF6B6B?style=flat)](https://z-ai.io/)

A comprehensive mental health content management platform built with Symfony 7.4, featuring AI-powered chatbot, content summarization, role-based access control, and advanced analytics.

## 🌟 Features

### Core Functionality
- **Role-Based Access Control**: Admin, Psychologist, and Patient roles with tailored dashboards
- **Content Management**: Full CRUD operations for mental health content with PDF support
- **Multi-User Assignment**: Assign content to multiple users with progress tracking
- **Dynamic Search & Filtering**: Real-time content search with advanced filters
- **Sorting & Organization**: Sort content by creation date, title, or other criteria

### AI Integration
- **Z.ai Chatbot**: Mental health assistant powered by Z.ai LLM
- **Content Summarization**: AI-generated summaries for content items
- **Intelligent Responses**: Context-aware mental health guidance

### Analytics & Reporting
- **KPI Dashboard**: Comprehensive statistics with Chart.js visualizations
- **Access Logs**: Detailed user interaction tracking
- **Performance Metrics**: Completion rates, engagement analytics
- **Visual Reports**: Bar charts and line graphs for data insights

### User Experience
- **Modern UI**: Responsive design with Bootstrap 5 and custom styling
- **Text-to-Speech**: Browser-native voice synthesis for content reading
- **Accessibility**: WCAG-compliant interface design
- **Mobile-First**: Optimized for all device sizes

## 🚀 Quick Start

### Prerequisites

Before running this project, make sure you have the following installed:

- **PHP 8.2 or higher** with these extensions:
  - `pdo_mysql`
  - `mbstring`
  - `xml`
  - `zip`
  - `curl`
  - `intl`

- **Composer** (PHP dependency manager)
- **Node.js 16+** and **npm** (for Z.ai service)
- **MySQL 8.0+** or **XAMPP** (with MySQL)
- **Git** (for cloning the repository)

### Installation Steps

#### 1. Clone the Repository

```bash
git clone https://github.com/elladridi/Esprit-PIDEV-3A1-2026-Mentis.git
cd Esprit-PIDEV-3A1-2026-Mentis
```

#### 2. Install PHP Dependencies

```bash
composer install
```

#### 3. Install Node.js Dependencies (for Z.ai service)

```bash
npm install
```

#### 4. Database Setup

**Option A: Using XAMPP (Recommended for Windows)**

1. Start XAMPP and ensure MySQL is running
2. Create a new database named `mentis`
3. Update the database configuration in `.env`:

```env
DATABASE_URL="mysql://root:@127.0.0.1:3306/mentis"
```

**Option B: Using MySQL directly**

```bash
mysql -u root -p
CREATE DATABASE mentis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;
```

Update `.env` with your MySQL credentials:

```env
DATABASE_URL="mysql://username:password@localhost:3306/mentis"
```

#### 5. Create Database Schema

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

#### 6. Create Admin User

```bash
php create_admin.php
```

This creates an admin user:
- **Email**: `admin@mentis.local`
- **Password**: `admin123456`

#### 7. Start the Application

**Terminal 1: Start Symfony Server**

```bash
symfony server:start
```

Or using PHP's built-in server:

```bash
php -S localhost:8000 -t public
```

**Terminal 2: Start Z.ai Service**

```bash
node zai-chatbot-service.js
```

#### 8. Access the Application

Open your browser and navigate to:
- **Application**: http://127.0.0.1:8000
- **Login**: http://127.0.0.1:8000/login

## 📋 Usage Guide

### User Roles

#### Admin User
- **Login**: `admin@mentis.local` / `admin123456`
- **Capabilities**:
  - Manage all users and content
  - View comprehensive analytics
  - Access all system features

#### Psychologist
- Create and manage mental health content
- Assign content to patients
- Monitor patient progress
- Access analytics for assigned content

#### Patient
- View assigned content
- Track reading progress
- Use AI chatbot for support
- Access personal dashboard

### Key Features Usage

#### Content Management
1. Login as Admin or Psychologist
2. Navigate to "Content Library"
3. Create new content with title, description, and optional PDF
4. Assign content to users
5. Monitor access logs and completion rates

#### AI Chatbot
1. Click "AI Chatbot" in the sidebar
2. Ask questions about mental health, stress management, or content
3. Receive AI-powered responses

#### Content Summarization
1. View any content item
2. Click the "AI Summary" button
3. Get an AI-generated summary of the content

#### Analytics Dashboard
1. Login as Admin or Psychologist
2. Navigate to "Access Logs"
3. View KPI metrics, charts, and detailed statistics

## 🛠️ Development

### Project Structure

```
├── bin/                    # Console commands
├── config/                 # Symfony configuration
├── public/                 # Web assets and entry point
├── src/
│   ├── Controller/         # Symfony controllers
│   ├── Entity/            # Doctrine entities
│   ├── Form/              # Form types
│   └── Repository/        # Doctrine repositories
├── templates/             # Twig templates
├── var/                   # Cache, logs, sessions
├── vendor/                # Composer dependencies
├── zai-chatbot-service.js # Z.ai Node.js service
└── package.json          # Node.js dependencies
```

### Available Commands

```bash
# Clear cache
php bin/console cache:clear

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Check routes
php bin/console debug:router

# Lint templates
php bin/console lint:twig templates
```

### Environment Configuration

Copy `.env` and customize for your environment:

```env
APP_ENV=dev
APP_SECRET=your-secret-key
DATABASE_URL=mysql://user:password@host:port/database
```

## 🔧 Troubleshooting

### Common Issues

#### 1. Database Connection Error
```bash
# Check if MySQL is running
sudo systemctl status mysql

# Or for XAMPP, ensure MySQL is started
```

#### 2. Permission Issues
```bash
# Fix var directory permissions
chmod -R 775 var/
chown -R www-data:www-data var/
```

#### 3. Z.ai Service Not Starting
```bash
# Check Node.js version
node --version

# Reinstall dependencies
rm -rf node_modules package-lock.json
npm install
```

#### 4. Cache Issues
```bash
# Clear all caches
php bin/console cache:clear
php bin/console cache:clear --env=prod
```

### Logs

Check logs for debugging:
- **Symfony logs**: `var/log/dev.log`
- **Z.ai service logs**: `zai-service.log`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Symfony Framework** - The web framework powering this application
- **Z.ai** - AI services for chatbot and summarization
- **Bootstrap** - UI framework for responsive design
- **Chart.js** - Data visualization library

## 📞 Support

For support or questions:
- Create an issue in this repository
- Contact the development team

---

**Built with ❤️ for mental health professionals and patients**