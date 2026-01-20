# CloudEwork - CrossFit Coach & Athlete Management Platform

## 🏋️ Overview
CloudEwork is a comprehensive platform for CrossFit coaches to manage their athletes, create programming, track progress, and communicate effectively.

## 🎯 Features
- **Coach Dashboard**: Manage multiple athletes, view KPIs, track progress
- **Athlete Management**: Individual profiles, workout history, PRs tracking
- **Workout Library**: Pre-loaded CrossFit benchmarks + custom WOD creation
- **Programming**: Assign workouts to individuals or groups
- **Results Tracking**: Log performance, track improvements over time
- **Messaging**: In-app communication between coaches and athletes
- **Analytics**: Comprehensive KPIs and progress visualization

## 🛠️ Tech Stack

### Backend
- **Framework**: Laravel 11
- **Database**: MySQL 8.0+
- **Authentication**: Laravel Sanctum
- **API**: RESTful JSON API

### Frontend
- **Framework**: React 18 + Vite
- **State Management**: Zustand
- **Styling**: Tailwind CSS
- **HTTP Client**: Axios

## 📁 Project Structure
```
cloudework/
├── backend/          # Laravel API
├── frontend/         # React Application
└── docs/            # Documentation
```

## 🚀 Quick Start

### Backend Setup
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend Setup
```bash
cd frontend
npm install
npm run dev
```

## 📊 Database Schema
See `backend/database/schema.sql` for complete database structure.

## 🔐 Environment Variables
See `.env.example` files in backend and frontend directories.

## 📖 API Documentation
API documentation available at `/api/documentation` when running the backend.

## 👥 Team
- Initial Development: Claude AI + Human Collaboration

## 📄 License
Proprietary - All rights reserved

## 🔄 Version
v0.1.0 - Initial Development
