# TripKo Tourism Management System

A comprehensive tourism management system for Pangasinan, Philippines, featuring destination management, itinerary planning, and real-time tourist capacity tracking.

## 🌟 Features

- **Tourist Spot Management** - Browse beaches, islands, waterfalls, caves, churches, and festivals
- **Interactive Maps** - MapLibre GL integration for destination visualization
- **Itinerary Planning** - Create and share custom travel itineraries
- **Real-time Capacity Tracking** - Monitor tourist capacity at destinations
- **Review System** - User ratings and reviews for spots and itineraries
- **Mobile-Optimized** - Fully responsive design with TripAdvisor-inspired UI
- **Route Finding** - Calculate routes and directions to destinations
- **Municipality Management** - Organized by Pangasinan municipalities

## 🛠️ Tech Stack

### Frontend
- HTML5, CSS3, JavaScript (Vanilla)
- MapLibre GL JS for maps
- Leaflet.js for route mapping
- Font Awesome & Boxicons for icons
- Inter font family

### Backend
- PHP 8.0+
- MySQL/MariaDB
- RESTful API architecture

### Development
- XAMPP (Apache, MySQL, PHP)
- Git for version control

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+
- Composer (for PHP dependencies)

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Mrpotatoes99/Tripko_System.git
   cd Tripko_System
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure database**
   - Create a MySQL database named `tripko_db`
   - Import the database schema from `tripko-backend/migrations/`
   - Copy config templates and update with your settings:
     ```bash
     cp tripko-backend/config/Database.example.php tripko-backend/config/Database.php
     ```

4. **Configure settings**
   - Update database credentials in `tripko-backend/config/Database.php`
   - Configure mail settings in `tripko-backend/config/mail_config.php`
   - Set up SMS configuration in `tripko-backend/config/sms_config.php`

5. **Start development server**
   ```bash
   # If using XAMPP, ensure Apache and MySQL are running
   # Access via: http://localhost/tripko-system/
   ```

## 📱 Mobile Optimization

The system features comprehensive mobile optimization including:
- Touch-friendly 44px+ tap targets
- Horizontal swiping for destination galleries
- Optimized navigation with slide-out menu
- Responsive card layouts
- Progressive pagination/load-more functionality
- iOS momentum scrolling support

## 📂 Project Structure

```
tripko-system/
├── tripko-frontend/
│   ├── file_html/
│   │   └── user side/        # User-facing pages
│   ├── file_css/
│   │   └── mobile-userside.css  # Mobile styles
│   └── file_js/               # JavaScript files
├── tripko-backend/
│   ├── api/                   # REST API endpoints
│   ├── config/                # Configuration files
│   ├── models/                # Data models
│   └── migrations/            # Database migrations
├── uploads/                   # User uploaded images
└── vendor/                    # PHP dependencies
```

## 🎨 Design System

- **Primary Color**: #00a6b8 (Teal)
- **Font Family**: Inter
- **Design Inspiration**: TripAdvisor
- **Mobile Breakpoint**: 768px
- **Border Radius**: 12px (cards), 8px (inputs)

## 🔧 Configuration Files

Create these from `.example.php` templates:
- `tripko-backend/config/Database.php` - Database connection
- `tripko-backend/config/mail_config.php` - Email settings
- `tripko-backend/config/sms_config.php` - SMS notifications

## 👥 User Types

1. **Tourists** - Browse destinations, create itineraries, leave reviews
2. **Tourism Officers** - Manage destinations, update capacity, moderate content
3. **Administrators** - Full system access and user management

## 📄 License

This project is proprietary software. All rights reserved.

## 👤 Author

Developed for Pangasinan Tourism Management

## 🐛 Known Issues

- ngrok tunneling may experience intermittent connectivity
- Ensure proper database port configuration (default: 3307)

## 📞 Support

For issues or questions, please open an issue on GitHub.

---

**Version**: 1.0.0  
**Last Updated**: November 2025
