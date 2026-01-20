# CloudEwork Backend API

Laravel 11 REST API para CloudEwork - Plataforma de gestión de coaches y atletas de CrossFit.

## 🚀 Instalación Rápida

### Requisitos
- PHP 8.2 o superior
- Composer
- MySQL 8.0+
- Node.js 18+ (para compilar assets si es necesario)

### Paso 1: Clonar e Instalar Dependencias

```bash
# Navegar a la carpeta backend
cd backend

# Instalar dependencias de Composer
composer install

# Copiar archivo de configuración
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### Paso 2: Configurar Base de Datos

1. Crear base de datos en MySQL:
```sql
CREATE DATABASE cloudework_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Actualizar archivo `.env` con tus credenciales:
```env
DB_DATABASE=cloudework_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### Paso 3: Ejecutar Migraciones y Seeders

```bash
# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders (carga benchmarks y datos de ejemplo)
php artisan db:seed

# O todo en un comando
php artisan migrate:fresh --seed
```

### Paso 4: Configurar Laravel Sanctum

```bash
# Publicar configuración de Sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Ejecutar migración de Sanctum
php artisan migrate
```

### Paso 5: Iniciar Servidor de Desarrollo

```bash
php artisan serve
```

La API estará disponible en: `http://localhost:8000`

## 📁 Estructura del Proyecto

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── AthleteController.php
│   │   │   ├── WorkoutController.php
│   │   │   ├── AssignmentController.php
│   │   │   ├── ResultController.php
│   │   │   └── ...
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Coach.php
│   │   ├── Athlete.php
│   │   ├── Workout.php
│   │   ├── WorkoutAssignment.php
│   │   ├── WorkoutResult.php
│   │   └── ...
│   ├── Services/
│   │   ├── WorkoutService.php
│   │   ├── AssignmentService.php
│   │   └── AnalyticsService.php
│   └── Policies/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
│       ├── BenchmarkSeeder.php
│       └── DatabaseSeeder.php
├── routes/
│   ├── api.php
│   └── web.php
├── tests/
└── .env.example
```

## 🔑 Autenticación

La API usa Laravel Sanctum para autenticación basada en tokens.

### Obtener Token
```bash
POST /api/login
Content-Type: application/json

{
  "email": "coach@example.com",
  "password": "password"
}
```

### Usar Token en Requests
```bash
GET /api/athletes
Authorization: Bearer {tu_token}
```

## 📊 Endpoints Principales

### Autenticación
- `POST /api/register/coach` - Registrar coach
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/user` - Usuario actual

### Atletas
- `GET /api/athletes` - Listar atletas
- `POST /api/athletes` - Crear atleta
- `GET /api/athletes/{id}` - Ver atleta
- `PUT /api/athletes/{id}` - Actualizar atleta
- `DELETE /api/athletes/{id}` - Eliminar atleta

### Workouts
- `GET /api/workouts` - Listar workouts
- `POST /api/workouts` - Crear workout
- `GET /api/workouts/{id}` - Ver workout
- `PUT /api/workouts/{id}` - Actualizar workout
- `DELETE /api/workouts/{id}` - Eliminar workout

### Asignaciones
- `GET /api/assignments` - Listar asignaciones
- `POST /api/assignments` - Crear asignación
- `POST /api/assignments/bulk` - Asignación masiva
- `PUT /api/assignments/{id}` - Actualizar asignación
- `DELETE /api/assignments/{id}` - Eliminar asignación

### Resultados
- `GET /api/results` - Listar resultados
- `POST /api/results` - Registrar resultado
- `GET /api/results/{id}` - Ver resultado
- `PUT /api/results/{id}` - Actualizar resultado
- `DELETE /api/results/{id}` - Eliminar resultado

### Analíticas
- `GET /api/analytics/dashboard` - Stats del dashboard
- `GET /api/analytics/athlete/{id}/progress` - Progreso del atleta

Ver documentación completa en `/docs/API.md`

## 🧪 Testing

```bash
# Ejecutar todos los tests
php artisan test

# Ejecutar tests específicos
php artisan test --filter=WorkoutTest

# Con cobertura
php artisan test --coverage
```

## 🛠️ Comandos Útiles

```bash
# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Crear nuevo controlador
php artisan make:controller NombreController

# Crear nueva migración
php artisan make:migration create_tabla_table

# Crear nuevo modelo con migración
php artisan make:model NombreModelo -m

# Crear seeder
php artisan make:seeder NombreSeeder

# Ver rutas
php artisan route:list
```

## 📦 Deploy en cPanel

### 1. Preparar Archivos

```bash
# En tu máquina local
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Crear archivo ZIP del proyecto
zip -r cloudework-backend.zip . -x "*.git*" "node_modules/*" "tests/*"
```

### 2. Subir a cPanel

1. Subir ZIP via File Manager
2. Extraer en `/home/usuario/cloudework-api`
3. Mover carpeta `public` a `/public_html/api`

### 3. Configurar .htaccess

Crear `/public_html/api/.htaccess`:
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ /home/usuario/cloudework-api/public/$1 [L]
</IfModule>
```

### 4. Configurar Base de Datos

1. Crear BD en cPanel MySQL
2. Actualizar `.env`:
```env
DB_HOST=localhost
DB_DATABASE=usuario_cloudework
DB_USERNAME=usuario_cloudework
DB_PASSWORD=contraseña_segura
```

### 5. Ejecutar Migraciones

```bash
# Via SSH
cd /home/usuario/cloudework-api
php artisan migrate --force
php artisan db:seed --force
```

### 6. Configurar Permisos

```bash
chmod -R 755 storage bootstrap/cache
```

## 🔒 Seguridad

- Nunca commitear archivo `.env`
- Usar HTTPS en producción
- Configurar CORS apropiadamente
- Rate limiting activado en rutas API
- Validación de inputs en todas las requests
- Sanitización de datos antes de guardar

## 🐛 Troubleshooting

### Error: "No application encryption key"
```bash
php artisan key:generate
```

### Error de permisos en storage
```bash
chmod -R 775 storage bootstrap/cache
```

### Error de CORS
Verificar configuración en `config/cors.php` y `.env`

### Error 500 en producción
```bash
php artisan config:cache
php artisan route:cache
chmod -R 755 storage
```

## 📚 Recursos

- [Laravel Documentation](https://laravel.com/docs)
- [Laravel Sanctum](https://laravel.com/docs/sanctum)
- [API Documentation](../docs/API.md)
- [Database Schema](../docs/SCHEMA.md)

## 👥 Contribuir

1. Crear branch: `git checkout -b feature/nueva-feature`
2. Commit cambios: `git commit -am 'Add nueva feature'`
3. Push: `git push origin feature/nueva-feature`
4. Crear Pull Request

## 📄 Licencia

Proprietary - All rights reserved
