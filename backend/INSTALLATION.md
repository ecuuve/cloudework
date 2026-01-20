# 🚀 Guía de Instalación CloudEwork - PASO A PASO

## ✅ Requisitos Previos

Antes de empezar, verifica que tienes instalado:

```bash
# PHP 8.2 o superior
php --version

# Composer
composer --version

# MySQL 8.0 o superior
mysql --version

# Git
git --version
```

Si falta algo, instala primero:
- **PHP:** https://www.php.net/downloads
- **Composer:** https://getcomposer.org/download/
- **MySQL:** https://dev.mysql.com/downloads/mysql/
- **Git:** https://git-scm.com/downloads

---

## 📦 PASO 1: Descargar Proyecto

```bash
# Opción A: Clonar desde GitHub
git clone https://github.com/ecuuve/cloudework.git
cd cloudework

# Opción B: Descargar ZIP (si ya lo descargaste)
cd cloudework-project
```

---

## 🔧 PASO 2: Instalar Dependencias

```bash
cd backend

# Instalar dependencias de Laravel
composer install

# Esto puede tardar 2-3 minutos
```

**Si da error "composer not found":**
```bash
# Instala Composer primero
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

---

## 🗄️ PASO 3: Configurar Base de Datos

### 3.1 Crear Base de Datos

```bash
# Entra a MySQL
mysql -u root -p

# Crea la base de datos
CREATE DATABASE cloudework_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Sal de MySQL
EXIT;
```

### 3.2 Configurar .env

```bash
# Copia el archivo de ejemplo
cp .env.example .env

# Genera la clave de la aplicación
php artisan key:generate
```

### 3.3 Editar .env

Abre el archivo `.env` y configura:

```env
APP_NAME="CloudEwork"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cloudework_db
DB_USERNAME=root
DB_PASSWORD=tu_password_mysql

# Resto queda igual
```

**⚠️ IMPORTANTE:** Cambia `DB_PASSWORD` con tu contraseña real de MySQL.

---

## 🏗️ PASO 4: Ejecutar Migrations

```bash
# Ejecuta las migrations (crea todas las tablas)
php artisan migrate

# Deberías ver:
# Migration table created successfully.
# Migrating: 2024_01_01_000001_create_users_table
# Migrated:  2024_01_01_000001_create_users_table
# ... (10 migrations en total)
```

**Si da error:**
```bash
# Verifica conexión a MySQL
php artisan migrate:status

# Si falla, verifica .env y que MySQL esté corriendo
```

---

## 🌱 PASO 5: Cargar Datos Demo

```bash
# Ejecuta los seeders
php artisan db:seed

# Deberías ver:
# Seeding: Database\Seeders\BenchmarkSeeder
# Benchmark workouts seeded successfully!
# Seeding: Database\Seeders\DemoSeeder
# ✅ Demo Coach created: demo@cloudework.com / demo123
# ✅ 8 Demo Athletes created
# ✅ 2 Demo Groups created
# ✅ Workout Assignments created for current week
# ✅ Historical results created
# 🎉 Demo data seeded successfully!
```

---

## 🚀 PASO 6: Iniciar Servidor

```bash
# Inicia el servidor de desarrollo
php artisan serve

# Deberías ver:
# INFO  Server running on [http://127.0.0.1:8000]
# Press Ctrl+C to stop the server
```

**El servidor está corriendo en:** http://localhost:8000

---

## ✅ PASO 7: Verificar Instalación

### 7.1 Test en Navegador

Abre: http://localhost:8000/api/v1/health

Deberías ver:
```json
{
  "success": true,
  "message": "CloudEwork API is running",
  "version": "1.0.0",
  "timestamp": "2026-01-20T..."
}
```

### 7.2 Test de Login

```bash
# Con curl
curl -X POST http://localhost:8000/api/v1/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "demo@cloudework.com",
    "password": "demo123"
  }'

# Deberías recibir un token
```

---

## 🎯 CREDENCIALES DEMO

### Coach (Principal):
```
Email: demo@cloudework.com
Password: demo123
```

### Atletas (para probar):
```
maria@example.com / password123
carlos@example.com / password123
laura@example.com / password123
juan@example.com / password123
... (8 atletas total)
```

---

## 📊 DATOS INCLUIDOS

Después de los seeders tendrás:

- ✅ 1 Coach demo (Juan Pérez)
- ✅ 8 Atletas activos
- ✅ 2 Grupos de atletas
- ✅ 6 Benchmarks de CrossFit (Fran, Helen, Cindy, Murph, Grace, Karen)
- ✅ ~40 Workouts asignados esta semana
- ✅ ~160 Resultados históricos
- ✅ ~20-30 PRs registrados

---

## 🐛 TROUBLESHOOTING

### Error: "Access denied for user"
```bash
# Verifica usuario y password en .env
# Asegúrate que MySQL esté corriendo
sudo systemctl start mysql  # Linux
# o
brew services start mysql   # Mac
```

### Error: "Class 'Illuminate\...' not found"
```bash
# Reinstala dependencias
rm -rf vendor
composer install
```

### Error: "SQLSTATE[42S02]: Base table not found"
```bash
# Ejecuta migrations de nuevo
php artisan migrate:fresh --seed
```

### Error: "Port 8000 already in use"
```bash
# Usa otro puerto
php artisan serve --port=8001
```

### Error en migrations
```bash
# Reset completo
php artisan migrate:fresh
php artisan db:seed
```

---

## 📝 COMANDOS ÚTILES

```bash
# Ver estado de migrations
php artisan migrate:status

# Reset database completo
php artisan migrate:fresh --seed

# Ver rutas disponibles
php artisan route:list

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Ver logs de errores
tail -f storage/logs/laravel.log
```

---

## 🎉 ¡LISTO!

Tu backend está funcionando en:
- **API:** http://localhost:8000/api/v1
- **Health Check:** http://localhost:8000/api/v1/health

### Próximos pasos:

1. ✅ Probar endpoints con Postman (ver POSTMAN_COLLECTION.json)
2. ✅ Abrir demo frontend (demo/login.html)
3. ✅ Conectar frontend con backend

---

## 📞 ¿Problemas?

Si algo no funciona:
1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica que MySQL esté corriendo
3. Confirma que .env esté bien configurado
4. Ejecuta `php artisan config:clear`

**¡Todo debería funcionar!** 🚀
