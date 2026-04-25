# Plan de configuración: paquete `alfonsosegura/mvc-framework`

**Fecha:** 2026-04-19  
**Autor:** Alfonso Segura  
**Fuente:** extraído de `aseguragonzalez/resbooking` → `src/Framework/Mvc/` (207 archivos PHP, 46 namespaces)

---

## Estado actual del repositorio (php-mvc)

| Elemento | Estado |
|---|---|
| `composer.json` | **Ausente** — no existe en la raíz |
| Namespace raíz | `Framework\Mvc` (sin vendor prefix) |
| Autoload PSR-4 | Incorrecto en origen (`Vscode\Workspace\` en resbooking) |
| Tipo de paquete | `project` en resbooking → debe ser `library` |
| Tests | Directorio presente, contenido pendiente de verificar |
| CI | Workflows no migrados al nuevo repo |
| Herramientas de calidad | Sin `phpstan.neon`, sin `phpcs.xml` en este repo |

---

## Fase 1 — Bloqueadores críticos de packaging

> Sin completar esta fase el paquete no debe publicarse.

| # | Tarea | Archivo(s) | Esfuerzo | Estado |
|---|---|---|---|---|
| 1.1 | ~~Reemplazar SHA-256/SHA-512 por `password_hash()`/`password_verify()`~~ | `UserIdentity.php` | — | ✅ |
| 1.2 | Decidir y documentar el namespace vendor root | Decisión de diseño | XS | ✅ |
| 1.3 | Renombrar namespaces en los ~207 archivos PHP del framework | `src/**` | L | ✅ |
| 1.4 | Actualizar los 25 stubs de la CLI con el nuevo namespace | `src/Cli/Commands/stubs/**` | M | ✅ |
| 1.5 | Crear `composer.json` correcto en la raíz | `composer.json` | S | ✅ |
| 1.6 | ~~Añadir tests para el nuevo hashing de contraseñas~~ | `tests/` | — | ✅ |

### 1.2 — Namespace vendor root

**Opción recomendada:** `AlfonsoSG\Mvc\`

Razonamiento:
- Corto, sin conflictos con namespaces de aplicación (`Domain\`, `Application\`, `Infrastructure\`)
- Consistente con el nombre de paquete `alfonsosegura/mvc-framework`
- Compatible con Packagist (sin caracteres especiales)

Alternativas descartadas:
- `AlfonsoSegura\MvcFramework\` — demasiado largo, redundante
- `Framework\Mvc\` — colisiona con proyectos consumidores que usen el mismo prefijo

### 1.3 — Renombrado de namespaces (207 archivos)

Distribución actual por subdirectorio:

| Subdirectorio | Archivos | Namespaces afectados |
|---|---|---|
| `Apps/` | 60 | `Framework\Mvc\BackgroundTasks\*`, `Framework\Mvc\Migrations\*` |
| `Functional/` | 60 | `Framework\Mvc\Files`, `Framework\Mvc\Security\*` |
| `Web/` | 55 | `Framework\Mvc\Controllers`, `Framework\Mvc\Routes`, `Framework\Mvc\Views`, `Framework\Mvc\Middlewares`, `Framework\Mvc\Responses\*`, ... |
| `Cli/` | 25 | `Framework\Mvc\Commands` |
| `DevTools/` | 2 | `Framework\Mvc\Tools` |
| Raíz | 5 | `Framework\Mvc` |

**Estrategia de renombrado:**

```bash
# Opción A: sed (rápido, sin dependencias)
find src/ tests/ -name "*.php" -exec sed -i \
  's/namespace Framework\\Mvc\\/namespace AlfonsoSG\\Mvc\\/g; \
   s/use Framework\\Mvc\\/use AlfonsoSG\\Mvc\\/g' {} \;

# Opción B: Rector (recomendado, más seguro)
composer require --dev rector/rector
# Configurar rector.php con RenameNamespaceRector
```

También afecta:
- `phpunit.xml` → atributo `<source>`
- `phpstan.neon` → `paths` y `scanDirectories`
- `.php-cs-fixer.dist.php` → `->in(__DIR__)`
- Tests en `tests/`

### 1.5 — `composer.json` objetivo

```json
{
  "name": "alfonsosegura/mvc-framework",
  "type": "library",
  "description": "Lightweight PHP 8.4 MVC framework — DDD and Hexagonal Architecture",
  "keywords": ["mvc", "php", "ddd", "hexagonal", "framework"],
  "homepage": "https://github.com/aseguragonzalez/mvc-framework",
  "license": "MIT",
  "authors": [
    {
      "name": "Alfonso Segura",
      "email": "a.segura.gonzalez@gmail.com"
    }
  ],
  "minimum-stability": "stable",
  "prefer-stable": true,
  "require": {
    "php": "^8.3 || ^8.4",
    "aseguragonzalez/php-seedwork": "^0.1.1",
    "monolog/monolog": "^3.10",
    "nyholm/psr7": "^1.8",
    "nyholm/psr7-server": "^1.1",
    "php-di/php-di": "^7.0",
    "phpmailer/phpmailer": "^7.0",
    "psr/container": "^2.0",
    "psr/http-message": "^2.0",
    "psr/http-server-handler": "^1.0",
    "psr/http-server-middleware": "^1.0",
    "psr/log": "^3.0"
  },
  "require-dev": {
    "fakerphp/faker": "^1.24",
    "friendsofphp/php-cs-fixer": "^3.95",
    "mikey179/vfsstream": "^1.6",
    "phpstan/phpstan": "^2.1",
    "phpunit/phpunit": "^12.5",
    "squizlabs/php_codesniffer": "^4.0"
  },
  "autoload": {
    "psr-4": {
      "AlfonsoSG\\Mvc\\": "src/"
    }
  },
  "autoload-dev": {
    "psr-4": {
      "AlfonsoSG\\Mvc\\Tests\\": "tests/"
    }
  },
  "bin": [
    "bin/mvc"
  ],
  "config": {
    "platform": {
      "php": "8.4"
    },
    "sort-packages": true
  },
  "scripts": {
    "test": "phpunit",
    "cs": "php-cs-fixer fix --dry-run --diff",
    "cs:fix": "php-cs-fixer fix",
    "stan": "phpstan analyse",
    "check": ["@cs", "@stan", "@test"]
  }
}
```

> **Cambios clave respecto a resbooking:**
> - `type`: `project` → `library`
> - `autoload`: `classmap` + `Vscode\Workspace\` → PSR-4 correcto `AlfonsoSG\Mvc\`
> - `autoload-dev`: añadido para tests
> - `bin`: movido a `bin/mvc` (convención de paquetes)
> - `scripts`: añadidos para CI local

---

## Fase 2 — Calidad de código

| # | Tarea | Archivo(s) | Esfuerzo | Estado |
|---|---|---|---|---|
| 2.1 | Crear `phpstan.neon` (nivel 8 mínimo) | `phpstan.neon` | S | ✅ |
| 2.2 | Migrar `.php-cs-fixer.dist.php` desde resbooking | `.php-cs-fixer.dist.php` | XS | ✅ |
| 2.3 | Crear `phpcs.xml` si se mantiene PHP_CodeSniffer | `phpcs.xml` | S | ⬜ |
| 2.4 | Adaptar `phpunit.xml` (rutas, namespaces nuevos) | `phpunit.xml` | XS | ✅ |
| 2.5 | Verificar que los tests existentes pasan con nuevos namespaces | `tests/` | M | ✅ |
| 2.6 | Añadir `Makefile` con targets `test`, `cs`, `stan`, `check` | `Makefile` | XS | ✅ |

### 2.1 — `phpstan.neon` objetivo

```neon
parameters:
  phpVersion: 80400
  level: 8
  paths:
    - src
  excludePaths:
    - src/Cli/stubs
```

### 2.4 — `phpunit.xml` adaptado

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/12.5/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         cacheDirectory=".phpunit.cache"
         executionOrder="depends,defects"
         beStrictAboutCoverageMetadata="true"
         beStrictAboutOutputDuringTests="true"
         failOnPhpunitDeprecation="true"
         failOnRisky="true"
         failOnWarning="true">
  <testsuites>
    <testsuite name="unit">
      <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="integration">
      <directory>tests/Integration</directory>
    </testsuite>
  </testsuites>
  <source ignoreIndirectDeprecations="true" restrictNotices="true" restrictWarnings="true">
    <include>
      <directory>src</directory>
    </include>
  </source>
  <coverage>
    <report>
      <clover outputFile="coverage.xml"/>
    </report>
  </coverage>
</phpunit>
```

---

## Fase 3 — Packaging y CI

| # | Tarea | Archivo(s) | Esfuerzo | Estado |
|---|---|---|---|---|
| 3.1 | Actualizar GitHub Actions: `checkout@v4`, `composer-install@v3` | `.github/workflows/ci.yml` | S | ✅ |
| 3.2 | Ampliar matrix PHP: 8.3 + 8.4 | `.github/workflows/ci.yml` | XS | ✅ |
| 3.3 | Añadir job de CI en push a `main` y en PR | `.github/workflows/ci.yml` | XS | ✅ |
| 3.4 | Añadir workflow de release → Packagist webhook | `.github/workflows/release.yml` | S | ✅ |
| 3.5 | Actualizar `dependabot.yml` (quitar devcontainers si no aplica) | `.github/dependabot.yml` | XS | ✅ |
| 3.6 | Integrar coverage a Codecov/Coveralls | `.github/workflows/ci.yml` | S | ✅ |
| 3.7 | Escribir `README.md` (quickstart, requisitos, ejemplos) | `README.md` | M | ✅ |
| 3.8 | Añadir `CHANGELOG.md`, `CONTRIBUTING.md`, `SECURITY.md` | Raíz del repo | M | ✅ |
| 3.9 | Crear primer tag `v0.1.0` y registrar en Packagist | Git + Packagist | XS | ⬜ |

### 3.1 — `ci.yml` objetivo

```yaml
name: CI

on:
  push:
    branches: [main]
  pull_request:

jobs:
  build:
    name: PHP ${{ matrix.php }}
    runs-on: ubuntu-latest
    strategy:
      fail-fast: false
      matrix:
        php: ["8.3", "8.4"]
    steps:
      - uses: actions/checkout@v4

      - name: Set up PHP ${{ matrix.php }}
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: xdebug

      - name: Install dependencies
        uses: ramsey/composer-install@v3

      - name: Check code style
        run: composer cs

      - name: Static analysis
        run: composer stan

      - name: Tests
        run: composer test -- --coverage-clover coverage.xml

      - name: Upload coverage
        uses: codecov/codecov-action@v4
        if: matrix.php == '8.4'
        with:
          files: coverage.xml
```

> **Cambios respecto a resbooking:**
> - `checkout@v2` → `checkout@v4`
> - `composer-install@v2` → `composer-install@v3`
> - Matrix añade PHP 8.3
> - Trigger: sólo `pull_request` → también `push` a `main`
> - Eliminado bloque `.env` y `pre-commit` (no aplican a biblioteca)
> - Añadido coverage a Codecov

---

## Orden de ejecución recomendado

```
1.2 (namespace decision)
  ↓
1.3 + 1.4 (renombrado masivo src/ + stubs)
  ↓
1.5 (crear composer.json)
  ↓
2.1 + 2.2 + 2.3 + 2.4 (archivos de calidad)
  ↓
2.5 (verificar tests)
  ↓
2.6 (Makefile)
  ↓
3.1 + 3.2 + 3.3 + 3.5 (CI básico)
  ↓
3.6 (coverage)
  ↓
3.7 + 3.8 (documentación)
  ↓
3.4 + 3.9 (release + Packagist)
```

---

## Checklist de publicación

### Código
- [ ] Namespace `AlfonsoSG\Mvc\` en todos los archivos PHP y stubs (207 archivos)
- [ ] `composer.json` con `"type": "library"`, autoload PSR-4 correcto, metadata completa
- [ ] Binario en `bin/mvc` (no en `src/`)

### Calidad
- [ ] `phpstan.neon` nivel 8 sin errores
- [ ] `.php-cs-fixer.dist.php` sin diferencias
- [ ] `phpunit.xml` con suites `unit` e `integration`
- [ ] Tests pasando con nuevos namespaces

### CI/CD
- [ ] `checkout@v4`, `composer-install@v3`
- [ ] Matrix PHP 8.3 + 8.4
- [ ] CI en push a `main` y en PR
- [ ] Workflow de release configurado
- [ ] Coverage subiendo a Codecov

### Documentación y repositorio
- [ ] `README.md` con quickstart, requisitos, instalación y ejemplos
- [ ] `CHANGELOG.md` con entrada `v0.1.0`
- [ ] `CONTRIBUTING.md`
- [ ] `SECURITY.md`
- [ ] `LICENSE`
- [ ] Tag `v0.1.0` creado en Git
- [ ] Paquete registrado en Packagist con webhook de auto-update

### Integración con `resbooking`
- [ ] `resbooking` actualizado para consumir el paquete via `composer require alfonsosegura/mvc-framework`
- [ ] CI de `resbooking` pasa con la dependencia externa

---

**Leyenda de esfuerzo:** XS < 1h · S 1-3h · M 3-8h · L 8h+
