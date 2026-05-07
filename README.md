# Laravel Client Showcase

A production-quality Laravel application demo that showcases enterprise-level development capabilities, perfect for client presentations and business acquisition.

## 🚀 Features

### Core Features
- **Authentication System** - Complete auth with JWT/Sanctum, social login, email verification
- **User Management** - Full CRUD with roles, permissions, and activity logging
- **Project Management** - Comprehensive project tracking with progress monitoring
- **Task Management** - Advanced task system with assignments and deadlines
- **File Management** - Secure file uploads with organization
- **Real-time Notifications** - Live updates and alerts
- **Advanced Search & Filtering** - Powerful search across all entities
- **Role & Permission System** - Granular access control
- **Activity Logging** - Complete audit trail
- **Dashboard Analytics** - Rich charts and statistics
- **API Documentation** - Auto-generated Swagger/OpenAPI docs

### Technical Features
- **RESTful API** - Clean, well-documented API endpoints
- **Service Repository Pattern** - Clean architecture with separation of concerns
- **Queue System** - Background job processing with Horizon
- **Caching Strategy** - Optimized performance with Redis
- **Rate Limiting** - API protection and abuse prevention
- **Global Exception Handling** - Centralized error management
- **Form Request Validation** - Robust input validation
- **Database Optimization** - Proper indexing and relationships
- **Unit & Feature Tests** - Comprehensive test coverage
- **Docker Support** - Complete containerization setup

## 🛠 Tech Stack

### Backend
- **Laravel 11** - Modern PHP framework
- **PHP 8.3+** - Latest PHP features
- **MySQL 8.0** - Robust database
- **Redis** - Caching and sessions
- **Elasticsearch** - Advanced search capabilities

### Frontend
- **Blade Templates** - Server-side rendering
- **TailwindCSS** - Utility-first CSS framework
- **Alpine.js** - Lightweight interactivity
- **Chart.js** - Data visualization

### DevOps
- **Docker & Docker Compose** - Container orchestration
- **Nginx** - High-performance web server
- **Supervisor** - Process management
- **Laravel Horizon** - Queue monitoring

## 📋 Requirements

- PHP >= 8.3
- Composer
- MySQL >= 8.0
- Redis >= 6.0
- Node.js & NPM (for frontend assets)

## 🚀 Installation

### 1. Clone Repository
```bash
git clone https://github.com/your-repo/laravel-showcase.git
cd laravel-showcase
```

### 2. Install Dependencies
```bash
composer install
npm install
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

Configure your `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_showcase
DB_USERNAME=laravel
DB_PASSWORD=your_password

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 4. Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### 5. Frontend Assets
```bash
npm run build
npm run prod
```

### 6. Start Application
```bash
php artisan serve
```

## 🐳 Docker Installation

### Quick Start
```bash
docker-compose up -d
```

### Services Included
- **app** - Laravel application (PHP-FPM)
- **nginx** - Web server
- **mysql** - Database
- **redis** - Cache and queue
- **phpmyadmin** - Database management (port 8080)
- **mailhog** - Email testing (port 1025)
- **supervisor** - Process management
- **horizon** - Queue monitoring

### Docker Commands
```bash
# Build and start all services
docker-compose up -d --build

# View logs
docker-compose logs -f app

# Stop services
docker-compose down

# Rebuild specific service
docker-compose up -d --build app
```

## 📊 API Documentation

### Base URL
```
http://localhost:8000/api/v1
```

### Authentication Endpoints
```
POST /api/v1/auth/register     - User registration
POST /api/v1/auth/login        - User login
POST /api/v1/auth/logout       - User logout
POST /api/v1/auth/refresh      - Token refresh
POST /api/v1/auth/forgot-password - Password reset request
POST /api/v1/auth/reset-password  - Password reset
```

### User Endpoints
```
GET    /api/v1/users           - List users
POST   /api/v1/users           - Create user
GET    /api/v1/users/{id}      - Get user
PUT    /api/v1/users/{id}      - Update user
DELETE /api/v1/users/{id}      - Delete user
```

### Project Endpoints
```
GET    /api/v1/projects           - List projects
POST   /api/v1/projects           - Create project
GET    /api/v1/projects/{id}      - Get project
PUT    /api/v1/projects/{id}      - Update project
DELETE /api/v1/projects/{id}      - Delete project
```

### Task Endpoints
```
GET    /api/v1/tasks           - List tasks
POST   /api/v1/tasks           - Create task
GET    /api/v1/tasks/{id}      - Get task
PUT    /api/v1/tasks/{id}      - Update task
DELETE /api/v1/tasks/{id}      - Delete task
```

### Response Format
```json
{
    "success": true,
    "message": "Operation successful",
    "data": {
        "users": [...],
        "pagination": {
            "total": 100,
            "per_page": 15,
            "current_page": 1,
            "last_page": 7
        }
    }
}
```

## 🎯 Usage Examples

### Authentication
```javascript
// Login
const response = await fetch('/api/v1/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password'
    })
});

const { token, user } = await response.json();
localStorage.setItem('token', token);
```

### API Calls
```javascript
// Get projects with authentication
const response = await fetch('/api/v1/projects', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    }
});

const { data } = await response.json();
```

### Real-time Updates
```javascript
// Listen for real-time events
import Echo from 'laravel-echo';

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: 'your-pusher-key',
    cluster: 'mt1',
});

window.Echo.private(`user.${userId}`)
    .listen('ProjectUpdated', (e) => {
        console.log('Project updated:', e.project);
    });
```

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME="Laravel Showcase"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_showcase
DB_USERNAME=laravel
DB_PASSWORD=secret

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue
QUEUE_CONNECTION=redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME="${APP_NAME}"

# File Upload
MAX_FILE_SIZE=10240
ALLOWED_FILE_TYPES=jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx

# API Rate Limiting
API_RATE_LIMIT=60
API_RATE_LIMIT_WINDOW=1
```

## 🧪 Testing

### Run Tests
```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter UserTest

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- Unit Tests: Models, Services, Repositories
- Feature Tests: Controllers, API Endpoints
- Browser Tests: User Interactions

## 📈 Performance

### Optimization Commands
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload --optimize

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Monitoring
- **Laravel Telescope** - Request monitoring and debugging
- **Laravel Horizon** - Queue monitoring
- **Custom Health Checks** - System status monitoring
- **Performance Metrics** - Response time tracking

## 🔒 Security

### Features
- **CSRF Protection** - Cross-site request forgery prevention
- **XSS Prevention** - Input sanitization and output encoding
- **SQL Injection Prevention** - Parameterized queries
- **Rate Limiting** - API abuse prevention
- **Secure File Uploads** - File type and size validation
- **Authorization Policies** - Resource-based access control
- **Password Hashing** - Bcrypt encryption
- **Session Security** - Secure session management

### Security Headers
```php
// Content Security Policy
'Content-Security-Policy' => "default-src 'self'"

// XSS Protection
'X-XSS-Protection' => '1; mode=block'

// Frame Options
'X-Frame-Options' => 'SAMEORIGIN'

// Content Type Options
'X-Content-Type-Options' => 'nosniff'
```

## 📊 Monitoring

### Health Checks
```bash
# Application health
curl http://localhost:8000/api/health

# System info
curl http://localhost:8000/api/info
```

### Logs
- **Application Logs**: `storage/logs/laravel.log`
- **Queue Logs**: Horizon dashboard
- **Access Logs**: Nginx access logs
- **Error Logs**: PHP error logs

## 🚀 Deployment

### Production Setup
```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader

# Set production environment
cp .env.production .env

# Run production migrations
php artisan migrate --force

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize for production
php artisan optimize
```

### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

### Supervisor Configuration
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- [Laravel](https://laravel.com/) - The PHP Framework For Web Artisans
- [TailwindCSS](https://tailwindcss.com/) - Utility-first CSS framework
- [Alpine.js](https://alpinejs.dev/) - A rugged, minimal framework for composing JavaScript behavior
- [Chart.js](https://www.chartjs.org/) - Simple yet flexible JavaScript charting

## 📞 Support

For support and questions:
- 📧 Email: support@example.com
- 📱 Phone: +1 (555) 123-4567
- 💬 Discord: [Join our community](https://discord.gg/your-server)
- 📖 Documentation: [Full docs](https://docs.your-domain.com)

---

**Built with ❤️ using Laravel 11**
