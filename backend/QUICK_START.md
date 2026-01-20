# 🚀 Setup Rápido - CloudEwork Backend

## ✅ Lo que YA está hecho HOY:

1. **Modelos completos:**
   - ✅ User (con auth)
   - ✅ Coach (con subscriptions)
   - ✅ Athlete (con stats)
   - ✅ Workout (con benchmarks)
   - ✅ WorkoutAssignment
   - ✅ WorkoutResult
   - ✅ PersonalRecord
   - ✅ AthleteGroup
   - ✅ AthleteProgressSnapshot

2. **Auth Controller completo:**
   - ✅ POST /api/v1/register/coach - Registrar coach
   - ✅ POST /api/v1/login - Login
   - ✅ POST /api/v1/logout - Logout
   - ✅ GET /api/v1/me - Usuario actual
   - ✅ POST /api/v1/refresh - Refresh token

3. **Rutas API:**
   - ✅ Rutas públicas (register, login, health)
   - ✅ Rutas protegidas con Sanctum
   - ✅ Health check endpoint

4. **Seeders:**
   - ✅ BenchmarkSeeder con 6 WODs famosos (Fran, Helen, Cindy, Murph, Grace, Karen)

---

## 📦 Próximos Pasos (Tú en tu máquina)

### 1. Instalar Laravel

Como no puedo ejecutar Composer aquí, necesitas hacerlo en tu máquina:

```bash
# Opción A: Instalar Laravel desde cero
composer create-project laravel/laravel cloudework-backend
cd cloudework-backend

# Opción B: O usar la estructura que te doy
cd cloudework-project/backend
composer install
```

### 2. Copiar Archivos

Copia estos archivos que creé a tu proyecto Laravel:

```
De esta carpeta → A tu Laravel:

app/Models/User.php
app/Models/Coach.php
app/Models/Athlete.php
app/Models/Workout.php
app/Models/WorkoutAssignment.php
app/Models/WorkoutResult.php
app/Models/Additional.php (contiene PersonalRecord, AthleteGroup, etc)

app/Http/Controllers/Api/AuthController.php

database/migrations/2024_01_01_000001_create_users_table.php
database/seeders/BenchmarkSeeder.php

routes/api.php

.env.example
composer.json
```

### 3. Configurar Base de Datos

```bash
# Crear base de datos
mysql -u root -p
CREATE DATABASE cloudework_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Actualizar .env
cp .env.example .env
php artisan key:generate

# Editar .env
DB_DATABASE=cloudework_db
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 4. Instalar Sanctum

```bash
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
```

### 5. Crear Migrations Faltantes

Necesitas crear las migrations para las otras tablas. Puedo dartelas en el próximo mensaje, o puedes usar el `database-schema.sql` como referencia.

Por ahora, puedes crear manualmente con:

```bash
php artisan make:migration create_coaches_table
php artisan make:migration create_athletes_table
# etc...
```

O ejecutar directamente el SQL del schema:

```bash
mysql -u root -p cloudework_db < database-schema.sql
```

### 6. Ejecutar Migrations

```bash
php artisan migrate
php artisan db:seed --class=BenchmarkSeeder
```

### 7. Iniciar Servidor

```bash
php artisan serve
```

Tu API estará en: `http://localhost:8000`

---

## 🧪 Probar los Endpoints

### Health Check
```bash
curl http://localhost:8000/api/v1/health
```

### Registrar Coach
```bash
curl -X POST http://localhost:8000/api/v1/register/coach \
  -H "Content-Type: application/json" \
  -d '{
    "email": "coach@example.com",
    "password": "Password123!",
    "password_confirmation": "Password123!",
    "first_name": "Juan",
    "last_name": "Pérez",
    "phone": "+506-8888-8888"
  }'
```

### Login
```bash
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "coach@example.com",
    "password": "Password123!"
  }'
```

Guarda el token que te devuelve!

### Get User (con token)
```bash
curl -X GET http://localhost:8000/api/v1/me \
  -H "Authorization: Bearer TU_TOKEN_AQUI"
```

---

## 📝 Colección Postman

Voy a crear una colección Postman completa en el próximo archivo para que puedas importarla y probar todos los endpoints fácilmente.

---

## 🐛 Troubleshooting

### Error: "Class 'Coach' not found"
```bash
composer dump-autoload
```

### Error: "SQLSTATE[42S02]: Base table or table not found"
```bash
php artisan migrate:fresh
php artisan db:seed
```

### Error: "Unauthenticated"
```bash
# Verifica que el token esté en el header:
Authorization: Bearer {tu_token}
```

---

## 📊 Estado Actual

**Completado HOY:**
- ✅ Estructura backend
- ✅ Modelos principales (9 modelos)
- ✅ Auth completo (register, login, logout)
- ✅ Rutas API básicas
- ✅ Seeder de benchmarks

**Falta (próximos pasos):**
- ⏳ Resto de migrations (las daré en siguiente mensaje)
- ⏳ AthleteController (CRUD completo)
- ⏳ WorkoutController (CRUD + búsqueda)
- ⏳ AssignmentController
- ⏳ ResultController
- ⏳ AnalyticsController
- ⏳ Tests

**Tiempo estimado:** Con estas bases, los controladores restantes son ~2-3 horas de trabajo.

---

## 🎯 ¿Listo para continuar?

Una vez que tengas esto corriendo en tu máquina, podemos:

1. ✅ Probar que auth funciona
2. ✅ Crear el resto de controllers
3. ✅ Agregar más endpoints
4. ✅ Integrar con frontend

**¿Te funciona? ¿Algún error? ¡Avísame y lo arreglamos!** 🚀
