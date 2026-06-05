# Composer Install Troubleshooting

Guía para resolver problemas comunes con `composer install`.

## ❌ Error: Dependency Conflict

### Problema Original

```
Problem 1
  - Root composer.json requires laravel/tinker ^2.9 -> satisfiable by laravel/tinker[v2.9.0, ..., v2.11.1]
  - laravel/tinker[v2.9.0, ..., v2.10.0] require illuminate/support ^6.0|^7.0|^8.0|^9.0|^10.0|^11.0
  - Conclusion: don't install laravel/framework v13.11.0 (conflict analysis result)
```

**Causa**: `laravel/tinker ^2.9` no es totalmente compatible con Laravel 13

### ✅ Solución Aplicada

Actualizar `composer.json`:

```json
{
  "require": {
    "php": "^8.3",
    "laravel/framework": "^13.0",
    "laravel/tinker": "^2.11",           // Cambio: ^2.9 → ^2.11
    "guzzlehttp/guzzle": "^7.8",
    "firebase/php-jwt": "^6.10"          // Agregado
  },
  "require-dev": {
    "phpunit/phpunit": "^11.0",
    "laravel/pint": "^1.13",
    "laravel/sail": "^1.28"              // Cambio: ^1.26 → ^1.28
  }
}
```

---

## 🚀 Resolver el Problema

### Opción 1: Usar versión actualizada (Recomendado)

```bash
# El composer.json ya fue actualizado
composer install
```

### Opción 2: Si aún hay conflictos

```bash
# Limpiar cache
composer clear-cache

# Eliminar lock file
rm composer.lock

# Reinstalar
composer install
```

### Opción 3: Instalar sin optimizaciones

```bash
# Skip autoloader optimization
composer install --no-autoloader

# Luego generar autoloader
composer dump-autoload
```

---

## 📋 Versiones Correctas por Laravel

| Laravel | PHP | tinker | sail | pint |
|---------|-----|--------|------|------|
| 12.x | 8.2+ | ^2.10 | ^1.27 | ^1.13 |
| **13.x** | **8.3+** | **^2.11** | **^1.28** | **^1.13** |

---

## ✅ Verificación Después de Install

```bash
# 1. Verificar que instaló correctamente
composer show

# 2. Generar key
php artisan key:generate

# 3. Listar migraciones
php artisan migrate:status

# 4. Test que todo funciona
php artisan tinker
>>> exit
```

---

## 🔧 Cambios en composer.json

### Antes
```json
{
  "require": {
    "laravel/framework": "^13.0",
    "laravel/tinker": "^2.9",
    "guzzlehttp/guzzle": "^7.8"
  },
  "require-dev": {
    "laravel/sail": "^1.26"
  }
}
```

### Después
```json
{
  "require": {
    "laravel/framework": "^13.0",
    "laravel/tinker": "^2.11",          // ← Actualizado
    "guzzlehttp/guzzle": "^7.8",
    "firebase/php-jwt": "^6.10"          // ← Agregado
  },
  "require-dev": {
    "laravel/sail": "^1.28"              // ← Actualizado
  }
}
```

---

## ❓ Otras Soluciones Comunes

### Error: "Package not found"

```bash
# Actualizar repositorios
composer update

# Con verbose para ver más detalles
composer install -vvv
```

### Error: "PHP version does not satisfy"

```bash
# Verificar versión PHP
php -v

# Si necesitas cambiar versión
# (Requiere cambiar en el servidor, no en composer)
```

### Error: "Out of memory"

```bash
# Aumentar memoria PHP
php -d memory_limit=-1 composer install

# O agregar a php.ini
memory_limit = -1
```

---

## 📝 Archivos Actualizados

✅ `backend/composer.json` - Actualizado con versiones correctas

```bash
# Versiones nuevas:
laravel/framework    ^13.0
laravel/tinker       ^2.11    (antes: ^2.9)
guzzlehttp/guzzle    ^7.8
firebase/php-jwt     ^6.10    (agregado)
laravel/pint         ^1.13
laravel/sail         ^1.28    (antes: ^1.26)
phpunit/phpunit      ^11.0
```

---

## 🎯 Próximos Pasos

```bash
# 1. Si aún no instalaste
cd backend
composer install

# 2. Si necesitas reinstalar
rm -rf vendor composer.lock
composer install

# 3. Generar clave
php artisan key:generate

# 4. Crear BD MySQL
mysql -u root -p < crear_bd.sql

# 5. Migraciones
php artisan migrate

# 6. Listo!
php artisan serve
```

---

## 📞 Si Persisten los Problemas

1. **Limpia todo**:
   ```bash
   rm -rf vendor
   rm composer.lock
   composer clear-cache
   ```

2. **Reinstala**:
   ```bash
   composer install
   ```

3. **Si falla por PHP version**:
   ```bash
   php -v  # Ver versión
   # Asegurar que sea 8.3+
   ```

4. **Si falla por memoria**:
   ```bash
   php -d memory_limit=-1 composer install
   ```

---

## ✅ Verificación Final

```bash
# Todo debería funcionar ahora
composer install
php artisan migrate:status
php artisan serve
```

¡Listo! 🚀

---

**Nota**: El `composer.lock` no está en el repositorio. Se generará automáticamente en tu máquina local.
