# Post Installation Steps

Después de `composer install`, ejecuta estos comandos manualmente.

## 🚀 Pasos Después de Composer Install

### 1. Copiar .env.example → .env

```bash
cd backend
cp .env.example .env
```

### 2. Generar App Key

```bash
php artisan key:generate
```

**Output esperado:**
```
Application key set successfully.
```

### 3. Crear Base de Datos MySQL

```bash
mysql -u root -p
```

```sql
CREATE DATABASE mail_alert CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mail_alert_user'@'localhost' IDENTIFIED BY 'your_secure_password_here';
GRANT ALL PRIVILEGES ON mail_alert.* TO 'mail_alert_user'@'localhost';
FLUSH PRIVILEGES;
```

Luego exit:
```sql
EXIT;
```

### 4. Actualizar .env con Credenciales MySQL

Editar `backend/.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mail_alert
DB_USERNAME=mail_alert_user
DB_PASSWORD=your_secure_password_here
```

### 5. Ejecutar Migraciones

```bash
php artisan migrate
```

**Output esperado:**
```
Migration table created successfully.
Creating table mail_alerts... Done.
```

### 6. Verificar Migraciones

```bash
php artisan migrate:status
```

Debería mostrar:
```
+------+---+-------+------+--------------------------------------------------+-------+
| Ran? | Migration | Batch | Started At | Completed At |
+------+---+-------+------+--------------------------------------------------+-------+
| Yes | 2026_01_15_000001_create_mail_alerts_table | 1 | ... | ... |
+------+---+-------+------+--------------------------------------------------+-------+
```

### 7. Copiar Service Account JSON

```bash
# Copiar tu service account JSON de Firebase
cp /path/to/service-account.json backend/storage/firebase/service-account.json
```

O crear directorio si no existe:
```bash
mkdir -p backend/storage/firebase
cp /path/to/service-account.json backend/storage/firebase/service-account.json
```

### 8. Iniciar Servidor

```bash
php artisan serve
```

**Output esperado:**
```
   INFO  Server running on [http://127.0.0.1:8000].

  Press Ctrl+C to quit
```

---

## ✅ Verificar que Todo Funciona

```bash
# Test endpoint (en otra terminal)
curl http://localhost:8000/api/alerts \
  -H "Authorization: Bearer your_secret_key"

# Debería retornar 200 OK con JSON vacío o error de validación
```

---

## 📋 Checklist

- [ ] `composer install` completado sin errores
- [ ] `.env` creado con valores MySQL
- [ ] App key generado (`php artisan key:generate`)
- [ ] Base de datos MySQL creada
- [ ] Usuario MySQL con permisos creado
- [ ] Migraciones ejecutadas (`php artisan migrate`)
- [ ] Service account JSON copiado
- [ ] Servidor iniciado (`php artisan serve`)
- [ ] API responde en `http://localhost:8000`

---

## 🐛 Si Algo Falla

### Error: "Could not open input file: artisan"
✅ **Ya solucionado** - Se removieron los scripts problemáticos

### Error: "Connection refused" (MySQL)
```bash
# Verificar que MySQL está corriendo
mysql -u root -p

# Si no conecta, iniciar MySQL:
# Windows:
net start MySQL80

# macOS:
brew services start mysql

# Linux:
sudo systemctl start mysql
```

### Error: "Access denied for user"
```bash
# Verificar credenciales en .env
# Verificar que usuario exists:
mysql -u root -p -e "SELECT user FROM mysql.user WHERE user='mail_alert_user';"

# Si no existe, crear de nuevo (ver paso 3)
```

### Error: "No such file or directory: .env"
```bash
cp .env.example .env
php artisan key:generate
```

---

## 📝 Resumen Rápido

```bash
# 1. Install
cd backend
composer install

# 2. Setup
cp .env.example .env
php artisan key:generate

# 3. Database
mysql -u root -p
# (Crear BD y usuario - ver paso 3)

# 4. Edit .env with MySQL credentials

# 5. Migrate
php artisan migrate

# 6. Copy Firebase service account
mkdir -p storage/firebase
cp /path/to/service-account.json storage/firebase/

# 7. Run
php artisan serve
```

✅ **Listo!**

---

## 🎯 Próximos Pasos

Una vez que el servidor está corriendo:

1. **Leer MYSQL_SETUP.md** - Para más detalles sobre MySQL
2. **Leer GITHUB_ACTIONS_SETUP.md** - Para configurar CI/CD
3. **Probar API** - Con TESTING.md
4. **Deploy** - Con PRE_DEPLOYMENT_CHECKLIST.md

---

¡El backend está listo para usar! 🚀
