# 💍 QRápido - Wedding Guest Management System

A comprehensive web application for wedding organizers to manage guest lists, generate QR codes for each guest, and streamline check-in logistics on the event day.

## 🎯 Overview

**Lightweight PHP application** that simplifies wedding guest management from invitation to check-in. QRápido allows event organizers to register guests, generate unique QR codes, track invitations sent, and efficiently manage guest arrivals with a built-in QR scanner.

Built with PHP and Bootstrap to provide an intuitive, mobile-friendly interface for wedding logistics management.

## ✨ Key Features

- **Guest Registration** - Complete guest management with contact details and party size
- **QR Code Generation** - Automatic QR code creation for each guest using the Endroid QR Code library
- **Check-in System** - Real-time guest check-in tracking with number of attendees
- **QR Scanner** - Built-in scanner to validate guest QR codes at venue entrance
- **Search & Filter** - Advanced filtering by check-in status, invitation sent status, and alphabetical ordering
- **CSV Import/Export** - Bulk guest management and data export capabilities
- **History Tracking** - Complete check-in history with timestamps for each guest entry
- **Privacy-First** - All data stored locally on your server with file-based JSON storage

## 🛠️ Tech Stack

- **Backend:** PHP 7.4+
- **Frontend:** Bootstrap 5.2.3, Bootstrap Icons
- **QR Code Generation:** Endroid QR Code 4.8+
- **Storage:** JSON file-based database (no SQL required)
- **Dependencies:** Composer for PHP package management

## 🚀 Getting Started

### Prerequisites

- PHP 7.4 or higher
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### Installation

```bash
# Clone the repository
git clone https://github.com/ETVO/casamentoQR.git
cd casamentoQR

# Install dependencies
composer install

# Set up permissions for data directory
chmod 755 data/

# Start PHP development server
php -S localhost:8000
```

Access the application at `http://localhost:8000`

### Initial Setup

1. On first access, you'll be prompted to create login credentials
2. Navigate to "Registar convidados" to add your first guests
3. Use the QR code download feature to generate codes for invitations
4. On event day, use "Scanner Código QR" to check in guests

## 📋 Features Breakdown

### Guest Management
- Register guests with name, email, phone, and party size
- Edit guest information anytime before the event
- Delete guests if plans change
- Mark invitations as sent to track communication

### QR Code System
- Unique QR code generated for each guest
- Downloadable QR codes for printing on invitations
- Scannable codes that link back to guest record

### Check-in Logistics
- Live QR scanner for quick guest validation
- Manual check-in option if QR code unavailable
- Track number of guests checked in vs. registered
- View check-in history with timestamps
- Remove incorrect check-ins if needed

### Data Management
- Export full guest list to CSV for external use
- Import guests via CSV for bulk operations
- Filter guests by:
  - Check-in status (checked in / not checked in)
  - Invitation status (sent / not sent)
  - Alphabetical order (A-Z or Z-A)
- Search functionality across all guest fields

## 💡 Use Cases

- **Wedding Organizers** - Comprehensive guest management from invitation to arrival
- **Event Planners** - Scalable solution for any event requiring guest tracking
- **Venue Managers** - Efficient check-in system to manage event capacity
- **Catering Services** - Accurate guest counts for meal preparation

## 📁 Project Structure

```
casamentoQR/
├── assets/          # CSS, images, and static resources
├── data/            # JSON storage for guests and check-ins
├── util/            # Helper functions and utilities
├── views/           # PHP view templates for each feature
├── index.php        # Main application entry point
├── login.php        # Authentication system
├── get_qr.php       # QR code generation endpoint
├── get_csv.php      # CSV export functionality
└── composer.json    # PHP dependencies
```

## 🔒 Security Notes

- Session-based authentication required for all operations
- Data stored in local JSON files (ensure proper server permissions)
- No external API calls - all data remains on your server
- Recommended to use HTTPS in production environment

## 🌐 Deployment

For production deployment:

1. Upload files to your web server
2. Run `composer install --no-dev` for production dependencies
3. Ensure `data/` directory is writable by web server
4. Configure your web server to route all requests through `index.php`
5. Set up HTTPS for secure guest data transmission
6. Update login credentials from default

## 👤 Author

**Estevão Pereira Rolim** - [@ETVO](https://github.com/ETVO) | [LinkedIn](https://linkedin.com/in/estevao-p-rolim)

Full Stack Developer with 8 years of experience in PHP and JavaScript development.

## 📄 License

This project is available for personal and commercial use.

---

*Developed as a freelance project for wedding event management. Built with PHP to provide a reliable, self-hosted guest management solution.*

*README generated in collaboration with Claude AI.*
