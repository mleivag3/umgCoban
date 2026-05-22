# Taller Practico — CI/CD con GitHub Actions, Docker y AI Code Review

**Universidad Mariano Galvez — Taller DevOps**  
**Duracion estimada:** 90 minutos  
**Nivel:** Intermedio

---

## Que vamos a construir

Un pipeline de CI/CD completo que:

1. **Verifica sintaxis PHP** automaticamente en cada push
2. **Revisa el codigo con IA (Ollama)** y bloquea el build si encuentra vulnerabilidades
3. **Construye una imagen Docker** y la sube a Docker Hub
4. **Despliega automaticamente** en un servidor real con rollback automatico

```
[Push] -> [PHP Syntax] -> [AI Code Review] -> [Build & Push] -> [Deploy]
                                |
                         BLOQUEA si hay vulnerabilidades
```

---

## Requisitos previos

Tener instalado en tu computadora:

| Herramienta | Descarga |
|-------------|----------|
| Git | https://git-scm.com/downloads |
| VS Code (recomendado) | https://code.visualstudio.com |

Cuentas necesarias:

| Cuenta | URL | Nota |
|--------|-----|------|
| **GitHub** (personal) | https://github.com | Cada alumno crea la suya |
| **Docker Hub** | compartida por el instructor | Se te dara en clase |

---

## Recursos compartidos del taller

> El instructor te dara estos datos al inicio de la clase.

| Recurso | Valor |
|---------|-------|
| Servidor de deploy | `86.48.0.160` |
| API de Ollama (IA) | `http://213.199.37.66:11434` |
| Docker Hub usuario | `cdcd` |
| Repositorio base | `https://github.com/mleivag3/umgCoban` |

---

---

# PARTE 1 — Configuracion inicial

---

## Paso 1 — Crear tu cuenta de GitHub

1. Ve a **https://github.com** y crea una cuenta si no tienes una
2. Verifica tu correo electronico
3. Inicia sesion

---

## Paso 2 — Hacer Fork del repositorio

El Fork crea una **copia del proyecto en tu cuenta** para que puedas trabajar de forma independiente.

1. Ve a: **https://github.com/mleivag3/umgCoban**
2. Haz clic en el boton **Fork** (esquina superior derecha)
3. En "Owner" selecciona **tu usuario**
4. Nombre del repo: dejalo como `umgCoban`
5. Clic en **Create fork**

Ahora tienes tu propio repo: `https://github.com/TU-USUARIO/umgCoban`

---

## Paso 3 — Clonar el repositorio en tu computadora

Abre una terminal (Git Bash en Windows) y ejecuta:

```bash
git clone https://github.com/TU-USUARIO/umgCoban.git
cd umgCoban
```

> Reemplaza `TU-USUARIO` con tu nombre de usuario de GitHub.

---

## Paso 4 — Configurar los Secrets en GitHub

Los **Secrets** son variables privadas que usa el pipeline. Sin ellos, el build y el deploy fallan.

1. Ve a tu repo en GitHub: `https://github.com/TU-USUARIO/umgCoban`
2. Clic en **Settings** (pestana superior)
3. En el menu izquierdo: **Secrets and variables -> Actions**
4. Clic en **New repository secret**
5. Agrega los siguientes 5 secrets **uno por uno**:

| Name | Value |
|------|-------|
| `DOCKERHUB_USERNAME` | *(el instructor te lo da)* |
| `DOCKERHUB_TOKEN` | *(el instructor te lo da)* |
| `SSH_HOST` | `86.48.0.160` |
| `SSH_USER` | `root` |
| `SSH_PASSWORD` | *(el instructor te lo da)* |

Al terminar deberias ver los 5 secrets listados.

---

## Paso 5 — Explorar la estructura del proyecto

```
umgCoban/
├── src/
│   ├── config.php       <- configuracion y sesiones
│   ├── index.php        <- pagina de login
│   ├── dashboard.php    <- dashboard principal
│   └── logout.php       <- cierre de sesion
├── .github/
│   └── workflows/
│       └── ci-cd.yml    <- EL PIPELINE (toda la magia aqui)
├── Dockerfile           <- como se construye la imagen
└── docker-compose.yml   <- como se ejecuta el contenedor
```

---

## Paso 6 — Entender el pipeline (`ci-cd.yml`)

Abre el archivo `.github/workflows/ci-cd.yml` y observa los 4 jobs:

### Job 1 — PHP Syntax check
```yaml
- run: find src -name "*.php" -exec php -l {} \;
```
Verifica que todos los archivos `.php` tengan sintaxis valida.

### Job 2 — AI Code Review (Ollama)
```yaml
# Busca patrones inseguros en el codigo PHP:
SQL=$(grep ... "SELECT|INSERT|UPDATE|DELETE" src/)
XSS=$(grep ... "echo \$" src/)
PWD=$(grep ... "password\s*=" src/)
GET=$(grep ... '\$var = \$_(GET|POST)' src/)
# Si encuentra algo -> exit 1 -> bloquea el build
```
Usa IA para detectar vulnerabilidades. Si encuentra algo **detiene todo el pipeline**.

### Job 3 — Build & Push Docker
```yaml
uses: docker/build-push-action@v5
# Construye la imagen y la sube a Docker Hub con tags automaticos:
# latest, sha-xxxxxxx
```

### Job 4 — Deploy + Rollback
```yaml
# Se conecta al servidor por SSH y ejecuta:
docker compose pull   # descarga la nueva imagen
docker compose up -d  # levanta el contenedor
# Si el servidor no responde en 15s -> rollback automatico
```

---

---

# PARTE 2 — Demo practica

---

## Demo A — Provocar el fallo con codigo inseguro

### Objetivo
Ver como el **AI Code Review bloquea el build** cuando hay vulnerabilidades en el codigo.

### Paso 1 — Abrir el archivo a modificar

En tu editor abre: **`src/config.php`**

El archivo actualmente luce asi (version limpia):

```php
<?php
define('APP_NAME', 'Demo CI/CD');
define('APP_VERSION', getenv('APP_VERSION') ?: '1.1.0');
define('BUILD_DATE', getenv('BUILD_DATE') ?: date('Y-m-d'));

define('USERS', [ ... ]);
session_start();
...
```

### Paso 2 — Agregar codigo inseguro intencional

**Agrega estas 4 lineas** despues de `define('BUILD_DATE', ...)`:

```php
<?php
define('APP_NAME', 'Demo CI/CD');
define('APP_VERSION', getenv('APP_VERSION') ?: '1.1.0');
define('BUILD_DATE', getenv('BUILD_DATE') ?: date('Y-m-d'));

// CODIGO INSEGURO — SOLO PARA DEMO
$db_password = "root1234";                                   // Password hardcodeada
$user_input  = $_GET['search'];                              // Input sin validar
$query = "SELECT * FROM users WHERE name = '$user_input'";  // SQL Injection
echo $user_input;                                            // XSS directo
// FIN codigo inseguro

define('USERS', [
    ...
```

### Paso 3 — Hacer commit y push

```bash
git add src/config.php
git commit -m "test: codigo inseguro intencional"
git push origin main
```

### Paso 4 — Observar el pipeline

1. Ve a tu repo en GitHub
2. Clic en la pestana **Actions**
3. Observa el workflow que se acaba de disparar
4. Clic en el job **"AI Code Review (Ollama)"**

### Resultado esperado

```
FAIL: AI Code Review
   - SQL Injection detectado
   - XSS detectado
   - Password hardcodeada detectada
   - Input sin validar detectado

SKIPPED: Build & Push  (bloqueado)
SKIPPED: Deploy        (bloqueado)
```

> **Punto de discusion:** Por que es importante detectar estas vulnerabilidades antes de que el codigo llegue a produccion?

---

## Demo B — Corregir el codigo y ver el pipeline completo

### Objetivo
Ver como al corregir las vulnerabilidades **los 4 jobs pasan** y la app se despliega automaticamente.

### Paso 1 — Corregir `src/config.php`

**Elimina las 4 lineas inseguras** que agregaste. El archivo debe quedar exactamente asi:

```php
<?php
define('APP_NAME', 'Demo CI/CD');
define('APP_VERSION', getenv('APP_VERSION') ?: '1.1.0');
define('BUILD_DATE', getenv('BUILD_DATE') ?: date('Y-m-d'));

define('USERS', [
    'admin' => ['password' => 'admin123', 'name' => 'Administrador', 'role' => 'Admin',  'avatar' => 'A'],
    'demo'  => ['password' => 'demo123',  'name' => 'Usuario Demo',  'role' => 'Viewer', 'avatar' => 'D'],
]);

session_start();

function is_logged_in(): bool  { return isset($_SESSION['user']); }
function require_login(): void { if (!is_logged_in()) { header('Location: /'); exit; } }
function current_user(): array { return $_SESSION['user'] ?? []; }
```

### Paso 2 — Hacer commit y push

```bash
git add src/config.php
git commit -m "fix: vulnerabilidades corregidas"
git push origin main
```

### Paso 3 — Observar el pipeline completo

Ve a **Actions** y observa como ahora los 4 jobs corren en secuencia.

### Resultado esperado

```
PASS: PHP Syntax check   (~10 seg)
PASS: AI Code Review     (~40 seg)
PASS: Build & Push       (~2 min)  -> imagen subida a Docker Hub
PASS: Deploy             (~30 seg) -> app actualizada en el servidor
```

### Paso 4 — Verificar la app en el servidor

Abre en el navegador: **http://86.48.0.160:8080**

Credenciales de prueba:

| Usuario | Contrasena |
|---------|-----------|
| `admin` | `admin123` |
| `demo`  | `demo123`  |

---

---

# PARTE 3 — Conceptos clave

---

## Que es CI/CD

| Termino | Significado | En este taller |
|---------|-------------|----------------|
| **CI** — Continuous Integration | Integrar y verificar codigo automaticamente en cada push | Jobs 1 y 2 (test + AI review) |
| **CD** — Continuous Delivery | Entregar el software listo para produccion | Job 3 (build + Docker Hub) |
| **CD** — Continuous Deployment | Desplegar automaticamente a produccion | Job 4 (deploy al servidor) |

---

## Que vulnerabilidades detecta la IA

| Vulnerabilidad | Codigo inseguro | Riesgo |
|----------------|-----------------|--------|
| **SQL Injection** | `"SELECT * WHERE name='$var'"` | Atacante accede o destruye la base de datos |
| **XSS** | `echo $var` sin sanitizar | Atacante inyecta JavaScript malicioso |
| **Password hardcodeada** | `$pass = "root1234"` | Credencial visible en el repositorio |
| **Input sin validar** | `$x = $_GET['search']` | Datos del usuario usados sin filtrar |

---

## Que es Docker y por que lo usamos

```
Sin Docker:  "En mi maquina funciona..."
Con Docker:  La misma imagen corre igual en todos lados
```

- **Dockerfile** -> receta para construir la imagen
- **docker-compose.yml** -> como ejecutar el contenedor (puertos, variables, etc.)
- **Docker Hub** -> repositorio publico de imagenes (como GitHub pero para Docker)

---

## Flujo completo resumido

```
Tu computadora          GitHub                   Servidor
--------------          ------                   --------
git push     ->    GitHub Actions corre:    ->   Docker pull
                   1. php -l (sintaxis)          docker compose up
                   2. Ollama (seguridad)          http://86.48.0.160:8080
                   3. docker build + push
                   4. ssh deploy
```

---

## Reto adicional (si terminas antes)

Modifica el archivo `src/dashboard.php` y cambia algun texto visible en el dashboard (por ejemplo el titulo o algun numero de las estadisticas). Haz commit y push, y verifica que el pipeline despliega tu cambio automaticamente en el servidor.

---

## Referencias

- GitHub Actions docs: https://docs.github.com/en/actions
- Docker docs: https://docs.docker.com
- OWASP Top 10 (vulnerabilidades): https://owasp.org/www-project-top-ten/
- Ollama (IA local): https://ollama.ai

---

*Taller preparado con Claude — Universidad Mariano Galvez*
