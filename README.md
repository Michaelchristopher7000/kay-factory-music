# 🎵 Kay Factory Music — Record Label Management System

> A full-stack record label management platform built to centralize artist management, music releases, contracts, distribution, finance, royalties, events, notifications, and reporting.

## 📌 About

**Kay Factory Music** is a modern Record Label Management System developed to manage the core operations of a professional music label from one centralized platform.

The system provides different levels of access for label staff and departments through **Role-Based Access Control (RBAC)** while maintaining structured records for artists, contracts, tracks, releases, distributions, finances, royalties, events, and administrative activities.

The application was designed and developed as a complete full-stack system using **Laravel and React**.

---

## ✨ Core Features

### 🔐 Authentication & Access Control

* Secure authentication
* Role-Based Access Control (RBAC)
* Protected routes and permissions
* Super Admin controls
* Laravel Sanctum authentication
* Role-based access to management modules

### 🎤 Artist Management

* Artist profiles
* Artist identification numbers
* Biography and genre information
* Social media profiles
* Artist status management
* Soft deletion
* Artist-related records

### 📄 Contract Management

* Artist contracts
* Multiple contract types
* Contract rates
* Rate snapshots
* Contract periods
* Contract status
* Soft deletion

### 🎵 Track Management

* Track management
* ISRC support
* Track metadata
* Multiple artists
* Genre and credits
* Track status
* Structured track relationships

### 💿 Release Management

* Singles
* EPs
* Albums
* Release metadata
* Release artwork
* Artist-track relationships
* Release status management
* Relationship validation

### 🌍 Music Distribution

* Distribution management
* Digital platforms
* Platform-specific distribution records
* Distribution status tracking
* Release distribution history

### 💰 Finance Management

* Revenue records
* Expense records
* Payment records
* Currency-aware financial data
* Financial summaries
* Management reporting

### 💎 Royalty Management

* Royalty statements
* Artist royalty records
* Royalty calculations
* Statement tracking
* Payment status

### 📊 Management Reports

The system provides management-focused reports covering:

* Artists
* Releases
* Distribution
* Finance
* Royalties
* Operations

### 📅 Events & Tours

* Artist events
* Tour management
* Event dates and times
* Venues
* Cities and countries
* Ticket links
* Event links
* Event artwork

### 🔔 Notifications

* Database notifications
* Email notifications
* Unread notification counts
* Notification history
* Mark as read
* Mark all as read
* Role-based notification recipients

### 📝 Audit Logs

The system maintains custom audit records of important administrative activities for accountability and operational tracking.

---

## 👥 User Roles

The system supports the following staff roles:

| Role                     | Description                          |
| ------------------------ | ------------------------------------ |
| **Super Admin**          | Complete system administration       |
| **Label Manager**        | Label-wide management                |
| **A&R**                  | Artist and talent management         |
| **Artist Manager**       | Artist operations                    |
| **Finance Staff**        | Revenue, expenses and payments       |
| **Marketing Staff**      | Marketing and promotional operations |
| **Distribution Manager** | Music distribution                   |
| **General Staff**        | General authorized operations        |

---

## 🛠️ Technology Stack

### Backend

* **Laravel 12**
* **PHP 8.2+**
* **Laravel Sanctum**
* **PostgreSQL**
* **Supabase**

### Frontend

* **React 19**
* **Vite**
* **Bootstrap 5**
* **Bootstrap Icons**
* **Axios**

### Testing & Development

* PHPUnit
* Laravel Feature Tests
* Automated backend testing
* Vite production builds
* Git & GitHub

---

## 🏗️ Architecture

The application follows a modern full-stack architecture:

```text
React + Vite
     │
     │ HTTP / API
     ▼
Laravel 12
     │
     ├── Authentication
     ├── Authorization / RBAC
     ├── Business Logic
     ├── Notifications
     ├── Audit Logging
     └── API Endpoints
             │
             ▼
      PostgreSQL / Supabase
```

---

## 📂 Main Modules

```text
Authentication
│
├── Users & Roles
├── Artists
├── Contracts
├── Tracks
├── Releases
├── Distribution
├── Finance
├── Royalties
├── Reports
├── Events
├── Notifications
└── Audit Logs
```

---

## 🧪 Testing

The project includes automated tests covering the major backend modules.

Run the test suite with:

```bash
php artisan test
```

The test coverage includes areas such as:

* Authentication
* Authorization
* Artists
* Contracts
* Tracks
* Releases
* Distribution
* Finance
* Royalties
* Reports
* Notifications

---

## 🚀 Production Build

Frontend production assets are generated using:

```bash
npm run build
```

---

## 🔒 Security

The application implements several security practices including:

* Authentication
* Role-based authorization
* Protected routes
* Laravel Sanctum
* Server-side validation
* Database constraints
* Soft deletion where appropriate
* Audit logging
* Environment-based configuration

> **Important:** Production credentials and environment variables should never be committed to the repository.

---

## 🎯 Project Objectives

Kay Factory Music was developed to provide a centralized platform for:

* Managing record label artists
* Managing artist contracts
* Organizing music catalogs
* Tracking releases
* Managing music distribution
* Recording financial activities
* Managing royalties
* Organizing events and tours
* Providing management reports
* Tracking important administrative activities

---

## 📈 Project Status

**Status: ✅ Completed**

The core system has been fully developed, tested, and prepared for deployment.

* [x] Authentication
* [x] Role-Based Access Control
* [x] Artist Management
* [x] Contract Management
* [x] Track Management
* [x] Release Management
* [x] Distribution Management
* [x] Finance Management
* [x] Royalty Management
* [x] Reports
* [x] Events & Tours
* [x] Notifications
* [x] Audit Logs
* [x] Automated Testing
* [x] Production Build
* [x] GitHub Repository

---

## 👨‍💻 Developer

### Michael Christopher Nnazuo

**Computer Software Engineering Technology**

Full-stack web application developed with Laravel, React, PostgreSQL, and modern web technologies.

---

## 📄 License

This project is proprietary software developed for **Kay Factory Music**.

Unauthorized copying, redistribution, modification, or commercial use is not permitted without permission from the project owner.

---

<p align="center">
  Built with ❤️ using Laravel & React
</p>
