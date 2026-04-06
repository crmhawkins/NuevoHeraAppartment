# 🔧 Instrucciones para Crear el Archivo .env

## 🚨 IMPORTANTE: El archivo .env es necesario para que Laravel funcione

### 1. Crear el archivo .env en el directorio raíz

Crea un archivo llamado `.env` en el directorio raíz de tu proyecto (alquiler-piso/) con el siguiente contenido:

```env
APP_NAME="Hawkins Suite"
APP_ENV=local
APP_KEY=base64:TU_CLAVE_GENERADA_AQUI
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=apartamentocrm
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=480
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_SAME_SITE=lax

# Configuraciones mejoradas para CSRF
CSRF_TOKEN_NAME=_token
CSRF_TOKEN_LENGTH=32
CSRF_TOKEN_EXPIRATION=480
CSRF_REGENERATE_TOKEN=false
CSRF_STORAGE=session
CSRF_CACHE_STORE=default

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
```

### 2. Obtener tu clave de aplicación

La clave de aplicación ya se generó automáticamente. Si necesitas regenerarla, ejecuta:

```bash
php artisan key:generate
```

### 3. Configurar la base de datos

Modifica estas líneas según tu configuración:

```env
DB_DATABASE=apartamentocrm
DB_USERNAME=root
DB_PASSWORD=tu_password_aqui
```

### 4. Verificar que el archivo se creó correctamente

```bash
ls -la | grep .env
```

Deberías ver algo como:
```
-rw-r--r--  1 usuario usuario  XXXX .env
```

### 5. Probar que la aplicación funciona

```bash
php artisan serve
```

Luego abre tu navegador en `http://127.0.0.1:8000`

## 🔍 Solución de Problemas

### Si sigues viendo el error "Undefined array key 'path'":

1. **Verifica que el archivo .env existe** en el directorio raíz
2. **Limpia todas las cachés**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
3. **Reinicia el servidor** si estás usando `php artisan serve`

### Si hay problemas de permisos:

```bash
chmod 644 .env
```

## 📝 Notas Importantes

- **NUNCA** subas el archivo `.env` a Git (ya está en .gitignore)
- **Siempre** mantén una copia de `.env.example` actualizada
- **Regenera** la clave de aplicación si cambias de entorno
- **Verifica** que las variables de base de datos sean correctas

## ✅ Verificación Final

Después de crear el archivo `.env`, ejecuta:

```bash
php artisan config:cache
php artisan route:cache
```

Si no hay errores, tu aplicación debería funcionar correctamente.
