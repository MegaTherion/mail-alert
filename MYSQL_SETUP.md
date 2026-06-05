# MySQL Setup Guide

Guía para configurar MySQL para el backend Mail Alert.

## 📋 Prerequisites

- MySQL 5.7+ o MySQL 8.0+
- MySQL Client o herramienta de administración
- Acceso root a MySQL
- PHP con extensión MySQL habilitada (generalmente preinstalada)

---

## 🚀 Quick Setup (5 minutos)

### 1. Crear Base de Datos y Usuario

```sql
-- Conectarse a MySQL como root
mysql -u root -p

-- Crear base de datos
CREATE DATABASE mail_alert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crear usuario
CREATE USER 'mail_alert_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON mail_alert.* TO 'mail_alert_user'@'localhost';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Verificar
SHOW GRANTS FOR 'mail_alert_user'@'localhost';
```

### 2. Actualizar .env

```bash
cd backend

# Copiar ejemplo
cp .env.example .env

# Editar con tus valores
nano .env
```

Valores importantes en `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1        # localhost
DB_PORT=3306             # puerto default
DB_DATABASE=mail_alert
DB_USERNAME=mail_alert_user
DB_PASSWORD=your_secure_password_here
```

### 3. Ejecutar Migraciones

```bash
# Generar clave de app (si no existe)
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Verificar
php artisan migrate:status
```

### 4. Listo

```bash
php artisan serve
# El servidor está listo en http://localhost:8000
```

---

## 🔧 Configuración Detallada

### MySQL Connection

```env
DB_CONNECTION=mysql         # Tipo de BD
DB_HOST=127.0.0.1          # Host (localhost)
DB_PORT=3306               # Puerto default
DB_DATABASE=mail_alert     # Nombre de la BD
DB_USERNAME=mail_alert_user # Usuario MySQL
DB_PASSWORD=...            # Contraseña
DB_CHARSET=utf8mb4         # Charset recomendado
DB_COLLATION=utf8mb4_unicode_ci  # Collation
```

### Diferentes Ambientes

**Local (desarrollo)**
```env
DB_HOST=127.0.0.1
DB_USERNAME=mail_alert_user
DB_PASSWORD=password123
```

**Staging**
```env
DB_HOST=staging-db.ejemplo.com
DB_USERNAME=staging_user
DB_PASSWORD=staging_password
```

**Production**
```env
DB_HOST=prod-db.ejemplo.com
DB_USERNAME=prod_user
DB_PASSWORD=very_secure_password_min_16_chars
```

---

## 📊 Crear Usuarios para Diferentes Ambientes

### Usuario para Staging

```sql
CREATE DATABASE mail_alert_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'staging_user'@'localhost' IDENTIFIED BY 'staging_password';

GRANT ALL PRIVILEGES ON mail_alert_staging.* TO 'staging_user'@'localhost';

FLUSH PRIVILEGES;
```

### Usuario para Production (Remote Access)

```sql
CREATE DATABASE mail_alert_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Usuario con acceso remoto
CREATE USER 'prod_user'@'%' IDENTIFIED BY 'very_secure_password';

-- Otorgar permisos (restrictar a IP si es posible)
-- GRANT ALL PRIVILEGES ON mail_alert_prod.* TO 'prod_user'@'192.168.1.100';
-- O permitir desde cualquier IP:
GRANT ALL PRIVILEGES ON mail_alert_prod.* TO 'prod_user'@'%';

FLUSH PRIVILEGES;
```

---

## 🔐 Seguridad de Contraseñas

### Requisitos Mínimos

```
✅ Mínimo 16 caracteres
✅ Mezclar mayúsculas, minúsculas, números, símbolos
✅ No usar información personal
✅ No reutilizar contraseñas
✅ Cambiar cada 90 días en producción
```

### Ejemplos Seguros

```
❌ password123
❌ mail_alert
❌ 12345678

✅ M@ilAl3rt!x2024#Sec
✅ P7$kL9mN2@qRwE5t
✅ aBc123!XyZ456$Def
```

---

## 🗄️ Estructura de la BD

La migración crea automáticamente la tabla `mail_alerts`:

```sql
CREATE TABLE mail_alerts (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rule VARCHAR(255) NOT NULL,
  priority ENUM('high', 'emergency') NOT NULL,
  from_address VARCHAR(255) NOT NULL,
  subject VARCHAR(255) NOT NULL,
  snippet LONGTEXT NOT NULL,
  timestamp DATETIME NOT NULL,
  sent_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at),
  INDEX idx_priority (priority),
  INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 🧪 Verificar Conexión

### Desde Terminal

```bash
# Test directo a MySQL
mysql -h 127.0.0.1 -u mail_alert_user -p mail_alert

# Si conecta, estás listo
```

### Desde Laravel

```bash
# Artisan tinker
php artisan tinker

# En el prompt de tinker
>>> DB::connection()->getPdo()
# Debe retornar una instancia PDO

>>> App\Models\MailAlert::count()
# Debe retornar 0 (sin datos aún)
```

---

## 📋 Migraciones

### Ejecutar

```bash
# Ejecutar todas las migraciones pendientes
php artisan migrate

# Con output detallado
php artisan migrate --verbose

# Solo sin preguntar confirmación
php artisan migrate --force
```

### Verificar Status

```bash
# Ver migraciones ejecutadas
php artisan migrate:status

# Output:
# +------+----+-------+------+--------------------------------------------------+-------+
# | Ran? | Migration | Batch | Started At | Completed At |
# +------+----+-------+------+--------------------------------------------------+-------+
# | Yes  | 2026_01_15_000001_create_mail_alerts_table | 1 | ... | ... |
# +------+----+-------+------+--------------------------------------------------+-------+
```

### Rollback (Si algo falla)

```bash
# Rollback último batch
php artisan migrate:rollback

# Rollback específico
php artisan migrate:rollback --step=1

# Reset todo (PELIGROSO - borra datos)
php artisan migrate:reset

# Refresh (reset + re-migrate)
php artisan migrate:refresh

# Fresh (drop + create tables nuevas)
php artisan migrate:fresh
```

---

## 🔍 Troubleshooting

### Error: "SQLSTATE[HY000]: General error: 1030"

**Causa**: Problemas con la BD
```bash
# Solución:
# 1. Verificar que BD existe
mysql -u root -p -e "SHOW DATABASES LIKE 'mail_alert';"

# 2. Reparar tabla si es necesario
mysql -u root -p -e "REPAIR TABLE mail_alerts;"
```

### Error: "SQLSTATE[28000]: Invalid credentials"

**Causa**: Usuario o contraseña incorrecta
```bash
# Solución:
# 1. Verificar credenciales en .env
# 2. Verificar que usuario existe
mysql -u root -p -e "SELECT user FROM mysql.user WHERE user='mail_alert_user';"

# 3. Resetear contraseña
mysql -u root -p
ALTER USER 'mail_alert_user'@'localhost' IDENTIFIED BY 'new_password';
```

### Error: "SQLSTATE[HY000]: General error: 3161"

**Causa**: Problema de permisos
```bash
# Solución:
# 1. Verificar permisos del usuario
mysql -u root -p -e "SHOW GRANTS FOR 'mail_alert_user'@'localhost';"

# 2. Re-otorgar si es necesario
mysql -u root -p -e "GRANT ALL PRIVILEGES ON mail_alert.* TO 'mail_alert_user'@'localhost'; FLUSH PRIVILEGES;"
```

### Error: "Connection refused" (conexión rechazada)

**Causa**: MySQL no está corriendo
```bash
# Solución:
# Linux/Mac
sudo systemctl start mysql
# o
brew services start mysql

# Windows
net start MySQL80  # o tu versión de MySQL

# Verificar estado
mysql -u root -p -e "SELECT VERSION();"
```

---

## 📊 Monitoreo y Mantenimiento

### Ver Tamaño de BD

```sql
SELECT 
  table_name,
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.tables
WHERE table_schema = 'mail_alert'
ORDER BY (data_length + index_length) DESC;
```

### Ver Número de Alertas

```bash
php artisan tinker
>>> App\Models\MailAlert::count()
```

### Limpiar Alertas Antiguas (Opcional)

```bash
# En Laravel tinker
>>> App\Models\MailAlert::where('created_at', '<', now()->subMonths(3))->delete()
# Elimina alertas más viejas de 3 meses
```

---

## 🔄 Backup y Restauración

### Backup

```bash
# Full backup
mysqldump -u mail_alert_user -p mail_alert > backup.sql

# Backup con estructura pero sin datos
mysqldump -u mail_alert_user -p --no-data mail_alert > structure.sql

# Backup comprimido
mysqldump -u mail_alert_user -p mail_alert | gzip > backup.sql.gz
```

### Restauración

```bash
# Restaurar desde backup
mysql -u mail_alert_user -p mail_alert < backup.sql

# Restaurar desde backup comprimido
gunzip < backup.sql.gz | mysql -u mail_alert_user -p mail_alert
```

### Backup Automático (Cron)

```bash
# Agregar a crontab
# Ejecutar diariamente a las 2 AM
0 2 * * * mysqldump -u mail_alert_user -p'password' mail_alert | gzip > /backups/mail_alert_$(date +\%Y\%m\%d).sql.gz

# Limpiar backups más viejos de 30 días
0 3 * * * find /backups -name "mail_alert_*.sql.gz" -mtime +30 -delete
```

---

## 🚀 Optimizaciones Recomendadas

### En .env para Producción

```env
# Cache de conexiones
DB_CONNECTION=mysql
DB_HOST=prod-db.ejemplo.com
DB_PORT=3306

# Opciones de performance (opcional)
DB_OPTIONS=--max_allowed_packet=512M

# Connection pool (si es necesario)
# Agregar en config/database.php
```

### En config/database.php

```php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', 3306),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'unix_socket' => env('DB_SOCKET', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
    'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
    ]) : [],
],
```

---

## 📝 Checklist

### Setup Inicial

- [ ] MySQL instalado y corriendo
- [ ] Base de datos creada (mail_alert)
- [ ] Usuario creado (mail_alert_user)
- [ ] Permisos otorgados
- [ ] .env configurado con credenciales
- [ ] Migraciones ejecutadas
- [ ] Tabla mail_alerts creada
- [ ] Verificación de conexión exitosa

### Antes de Producción

- [ ] Contraseña segura (16+ caracteres)
- [ ] Backups configurados
- [ ] Índices creados en BD
- [ ] Performance tunning completado
- [ ] Logs habilitados
- [ ] Monitoring configurado
- [ ] Recovery plan documentado

---

## 🔗 Referencias

- [Laravel Database Configuration](https://laravel.com/docs/13/database)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [MySQL Best Practices](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)

---

## 📞 Soporte

Si tienes problemas:

1. Verificar que MySQL está corriendo
2. Verificar credenciales en .env
3. Verificar conexión: `mysql -u user -p`
4. Revisar logs: `tail -f /var/log/mysql/error.log`
5. Revisar Laravel logs: `tail -f storage/logs/laravel.log`

---

¡MySQL está configurado y listo para usar! 🚀
