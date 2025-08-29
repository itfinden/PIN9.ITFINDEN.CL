<h1 align="center">Pin9 - Project Management Platform</h1>

<p align="center">A modern, multilingual project management platform featuring a kanban board and calendar for managing projects, events, and tasks.</p>

## 🌟 Features

### 🗓️ **Calendar Management**
- **FullCalendar 6.x**: Modern calendar with 4 different views (month, week, day, list)
- **Event Management**: Add, modify, and delete events with drag & drop
- **Multilingual Calendar**: Spanish and English calendar interface
- **Real-time Updates**: Instant synchronization across all views

### 📋 **Kanban Board**
- **Project Management**: Create and manage projects with priorities
- **Task Organization**: Add tasks to projects with color-coded priorities
- **Drag & Drop**: Intuitive task and project management
- **Date Tracking**: Start and end dates for all items

### 🌐 **Multilingual Support**
- **Spanish & English**: Complete interface translation
- **Auto-Detection**: Automatic language detection based on browser
- **Language Switcher**: Easy language switching in navigation
- **Persistent Settings**: Language preferences saved per session

### 📱 **Modern Interface**
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Professional UI**: Clean, modern interface with Bootstrap 4
- **User Authentication**: Secure login and registration system
- **Today's Summary**: Quick overview of today's events and tasks

### 🛠️ **Gestión de Servicios y Planes**
- **CRUD de Servicios**: Los administradores de empresa pueden crear, editar, eliminar y listar servicios asociados a su empresa.
- **Atributos de Servicio**: Cada servicio puede tener nombre, tipo, unidad, duración, precio, descripción y estado.
- **Panel de Superadmin**: Los superadmins pueden ver y gestionar todos los servicios de todas las empresas.
- **Internacionalización**: Todos los campos y formularios de servicios están disponibles en español e inglés.

## 🚀 Quick Start

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/ITFINDEN-SPA/PIN9.ITFINDEN.CL.git
   cd PIN9.ITFINDEN.CL
   ```

2. **Database Setup**
   - Import `db/kanban.sql` to create the database structure
   - Update database credentials in `db/functions.php`

3. **Configure Web Server**
   - Point your web server to the project directory
   - Ensure PHP has write permissions

4. **Access the Application**
   - Visit your domain
   - Register a new account or login

## 📸 Screenshots

### 🏠 **Main Dashboard**
![Main Dashboard](/screenshots/main.jpg "Main Dashboard")

### 🔐 **Authentication**
![Login](/screenshots/login.jpg "Login Form")
![Register](/screenshots/signup.jpg "Registration Form")

### 📊 **Kanban Board**
![Projects & Tasks](/screenshots/tasks.jpg "Projects and Tasks Management")

### 📅 **Calendar Views**
![Monthly Calendar](/screenshots/calendar.jpg "Monthly Calendar View")
![Weekly Calendar](/screenshots/week.jpg "Weekly Calendar View")
![Event List](/screenshots/list.jpg "Event List View")

### 📋 **Today's Summary**
![Today's Summary](/screenshots/today.jpg "Today's Events and Tasks")

## 🛠️ Technology Stack

### **Backend**
- **PHP 7.4+**: Server-side logic and database operations
- **MySQL**: Database management
- **PDO**: Secure database connections

### **Frontend**
- **HTML5 & CSS3**: Modern markup and styling
- **JavaScript (ES6+)**: Interactive functionality
- **Bootstrap 4**: Responsive UI framework
- **FullCalendar 6.x**: Advanced calendar component
- **jQuery 3.7.1**: DOM manipulation and AJAX

### **Libraries & Dependencies**
- **Bootstrap Datepicker**: Date selection components
- **SweetAlert2**: Beautiful alert dialogs
- **FontAwesome**: Icon library
- **Moment.js**: Date manipulation (legacy support)

## 🌍 Language Support

### **Supported Languages**
- 🇪🇸 **Spanish (es)**: Complete translation
- 🇺🇸 **English (en)**: Full English interface

### **Language Features**
- Automatic browser language detection
- User-selectable language preference
- Persistent language settings
- Real-time language switching

## 📦 Version History

### **v3.0.0** (Current)
- ✅ FullCalendar 6.x migration
- ✅ Complete multilingual support
- ✅ Modern JavaScript API
- ✅ Enhanced date handling
- ✅ Professional navigation bar

### **v2.0.0**
- ✅ Initial multilingual implementation
- ✅ Basic language switching

### **v1.0.0**
- ✅ Core project management features
- ✅ Calendar and kanban functionality

## 🔧 Configuration

### **Database Configuration**
Edit `db/functions.php`:
```php
private $hostname = 'localhost';
private $username = 'your_username';
private $password = 'your_password';
private $database = 'your_database';
```

### **Language Configuration**
Languages are managed in the `lang/` directory:
- `lang/ES.php`: Spanish translations
- `lang/EN.php`: English translations
- `lang/Languaje.php`: Language management class

## 🤝 Contributing

We welcome contributions! Please feel free to submit a Pull Request.

### **Development Setup**
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👥 Authors

- **Original**: Friedrich von Muhlenbrock
- **Pin9 Development**: ITFINDEN Team

## 🌐 Live Demo

- **Production**: [https://pin9.itfinden.cl](https://pin9.itfinden.cl)
- **GitHub**: [https://github.com/ITFINDEN-SPA/PIN9.ITFINDEN.CL](https://github.com/ITFINDEN-SPA/PIN9.ITFINDEN.CL)

## ⭐ Support

If you find this project helpful, please give it a star! 

For support, email us or create an issue on GitHub.

---

**Pin9** - Making project management simple and multilingual! 🚀
