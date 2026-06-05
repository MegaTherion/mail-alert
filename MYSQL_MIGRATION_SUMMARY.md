# MySQL Migration Summary

Resumen de los cambios para migrar de SQLite a MySQL.

## ✅ Cambios Realizados

### 1. **backend/.env.example** (Actualizado)

**Antes** (SQLite):
```env
DB_CONNECTION=sqlite
DB_DATABASE=database.sqlite
```

**Ahora** (MySQL):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mail_alert
DB_USERNAME=mail_alert_user
DB_PASSWORD=your_secure_password_here
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

### 2. **backend/README.md** (Actualizado)

- Sección "Crear base de datos" ahora muestra MySQL como opción recomendada
- Incluye comandos para crear BD y usuario MySQL
- Links a MYSQL_SETUP.md para documentación completa

### 3. **MYSQL_SETUP.md** (Nuevo)

Guía completa con:
- ✅ Setup rápido (5 minutos)
- ✅ Configuración detallada por ambiente
- ✅ Seguridad de contraseñas
- ✅ Estructura de BD
- ✅ Migraciones y rollback
- ✅ Troubleshooting completo
- ✅ Backups y restauración
- ✅ Optimizaciones
- ✅ Monitoreo y mantenimiento

---

## 🚀 Quick Start (MySQL)

### 1. Crear BD y Usuario

```bash
mysql -u root -p
```

```sql
CREATE DATABASE mail_alert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mail_alert_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON mail_alert.* TO 'mail_alert_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Actualizar .env

```bash
cd backend
cp .env.example .env

# Editar .env con tus valores:
# DB_HOST=127.0.0.1
# DB_DATABASE=mail_alert
# DB_USERNAME=mail_alert_user
# DB_PASSWORD=your_secure_password_here
```

### 3. Ejecutar Migraciones

```bash
php artisan key:generate
php artisan migrate
```

### 4. Verificar

```bash
php artisan migrate:status
php artisan serve
```

---

## 📊 Configuración por Ambiente

### Desarrollo (Local)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mail_alert
DB_USERNAME=mail_alert_user
DB_PASSWORD=password123
```

### Staging

```env
DB_CONNECTION=mysql
DB_HOST=staging-db.ejemplo.com
DB_PORT=3306
DB_DATABASE=mail_alert_staging
DB_USERNAME=staging_user
DB_PASSWORD=staging_password_secure
```

### Production

```env
DB_CONNECTION=mysql
DB_HOST=prod-db.ejemplo.com
DB_PORT=3306
DB_DATABASE=mail_alert_prod
DB_USERNAME=prod_user
DB_PASSWORD=very_secure_password_min_16_chars
```

---

## 🔐 Crear Usuarios por Ambiente

### Staging

```sql
CREATE DATABASE mail_alert_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'staging_user'@'localhost' IDENTIFIED BY 'staging_password_secure';
GRANT ALL PRIVILEGES ON mail_alert_staging.* TO 'staging_user'@'localhost';
FLUSH PRIVILEGES;
```

### Production (Con acceso remoto)

```sql
CREATE DATABASE mail_alert_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'prod_user'@'192.168.1.100' IDENTIFIED BY 'very_secure_password_min_16_chars';
GRANT ALL PRIVILEGES ON mail_alert_prod.* TO 'prod_user'@'192.168.1.100';
FLUSH PRIVILEGES;
```

---

## 📝 Migraciones

### Ejecutar

```bash
# Todas las migraciones pendientes
php artisan migrate

# Específica
php artisan migrate --path=database/migrations/2026_01_15_000001_create_mail_alerts_table.php

# Con output detallado
php artisan migrate --verbose

# Forzar en producción (sin confirmación)
php artisan migrate --force
```

### Ver Status

```bash
php artisan migrate:status
```

### Rollback (Si falla)

```bash
# Último batch
php artisan migrate:rollback

# Específico
php artisan migrate:rollback --step=1

# Reset total (PELIGROSO)
php artisan migrate:reset
```

---

## 🔍 Verificación

### Desde Terminal

```bash
# Test de conexión
mysql -h 127.0.0.1 -u mail_alert_user -p mail_alert

# Ver BD
SHOW DATABASES;

# Ver tablas
SHOW TABLES;

# Ver estructura
DESC mail_alerts;
```

### Desde Laravel

```bash
php artisan tinker

# Probar conexión
>>> DB::connection()->getPdo()

# Contar alertas
>>> App\Models\MailAlert::count()
```

---

## 🐛 Troubleshooting

### "SQLSTATE[HY000]: General error"
- Verificar que BD existe
- Verificar que usuario tiene permisos

### "SQLSTATE[28000]: Invalid credentials"
- Verificar usuario y contraseña en .env
- Verificar que usuario existe en MySQL

### "Connection refused"
- Verificar que MySQL está corriendo
- Verificar que host es correcto (127.0.0.1)
- Verificar que puerto es 3306

---

## 📊 Tabla Creada

La migración crea automáticamente:

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

## 🔄 Backup y Restauración

### Backup

```bash
# Full
mysqldump -u mail_alert_user -p mail_alert > backup.sql

# Comprimido
mysqldump -u mail_alert_user -p mail_alert | gzip > backup.sql.gz
```

### Restaurar

```bash
# Desde backup
mysql -u mail_alert_user -p mail_alert < backup.sql

# Desde backup comprimido
gunzip < backup.sql.gz | mysql -u mail_alert_user -p mail_alert
```

---

## ✅ Checklist

### Setup Inicial

- [ ] MySQL instalado y corriendo
- [ ] BD creada (mail_alert)
- [ ] Usuario creado (mail_alert_user)
- [ ] Permisos otorgados
- [ ] .env.example actualizado
- [ ] .env configurado con credenciales
- [ ] Migraciones ejecutadas
- [ ] Tabla mail_alerts creada

### Antes de Producción

- [ ] Contraseña segura (16+ caracteres)
- [ ] Backups configurados
- [ ] Índices creados
- [ ] Performance tunning
- [ ] Logs habilitados
- [ ] Monitoring configurado

---

## 📚 Documentación Relacionada

- **[MYSQL_SETUP.md](./MYSQL_SETUP.md)** - Guía completa MySQL
- **[backend/README.md](./backend/README.md)** - Setup backend
- **[GITHUB_ACTIONS_SETUP.md](./GITHUB_ACTIONS_SETUP.md)** - Deploy con GitHub Actions

---

## 🎯 Próximos Pasos

1. **Crear BD MySQL**: Seguir comandos SQL arriba
2. **Actualizar .env**: Agregar credenciales MySQL
3. **Ejecutar migrations**: `php artisan migrate`
4. **Verificar**: `php artisan migrate:status`
5. **Testear API**: `curl http://localhost:8000/api/alerts`

---

## 📞 Soporte

Para problemas detallados, ver **[MYSQL_SETUP.md](./MYSQL_SETUP.md)** sección Troubleshooting.

---

¡MySQL está configurado y listo para usar! 🚀
