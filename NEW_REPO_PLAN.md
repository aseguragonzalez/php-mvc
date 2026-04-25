# Plan de tareas: nuevo repositorio del paquete `alfonsosegura/mvc-framework`

**Fecha:** 2026-04-19
**Autor:** Alfonso Segura
**Alcance:** tareas a realizar sobre el repositorio Git independiente del framework,
una vez extraído `src/Framework/Mvc/` de `resbooking`.

---

## Fase 1 — Bloqueadores críticos de packaging

> Sin completar esta fase el paquete no debe publicarse bajo ningún concepto.

| # | Tarea | Archivo(s) | Esfuerzo |
|---|---|---|---|
| 1.1 | ~~Reemplazar SHA-256/SHA-512 por `password_hash()`/`password_verify()`~~ ✅ | `UserIdentity.php` | — |
| 1.2 | Decidir namespace vendor root (e.g. `AlfonsoSG\Mvc\`) | Decisión de diseño | XS |
| 1.3 | Renombrar namespaces en ~150 archivos PHP del framework | `src/**` | L |
| 1.4 | Actualizar los 21 stubs del CLI con el nuevo namespace | `Cli/Commands/stubs/**` | M |
| 1.5 | Corregir `composer.json`: type, autoload, metadata | `composer.json` | S |
| 1.6 | ~~Añadir tests para el nuevo hashing de contraseñas~~ ✅ | `tests/` | — |

### Notas sobre 1.2 / 1.3

El namespace raíz actual (`Framework\Mvc\`, `Domain\`, `Application\`, `Infrastructure\`)
colisiona con cualquier proyecto consumidor que use esos mismos prefijos. La decisión de
qué namespace adoptar (p.ej. `AlfonsoSG\Mvc\`) debe tomarse antes de iniciar 1.3, ya que
condiciona todo lo demás.

El renombrado de 1.3 puede automatizarse con un script de `sed`/`str_replace` o con
Rector. Afecta también a:
- Todos los archivos de tests
- Los 21 stubs de la CLI (tarea 1.4)
- Las referencias en `phpunit.xml`, `phpstan.neon`, `.php-cs-fixer.php`

### Notas sobre 1.5

```json
{
  "name": "alfonsosegura/mvc-framework",
  "type": "library",
  "description": "Lightweight PHP 8.4 MVC framework following DDD and Hexagonal Architecture",
  "keywords": ["mvc", "php", "ddd", "hexagonal", "framework"],
  "homepage": "https://github.com/aseguragonzalez/mvc-framework",
  "minimum-stability": "stable",
  "prefer-stable": true,
  "autoload": {
    "psr-4": {
      "AlfonsoSG\\Mvc\\": "src/"
    }
  }
}
```

---

## Fase 3 — Packaging y CI

| # | Tarea | Archivo(s) | Esfuerzo |
|---|---|---|---|
| 3.1 | Crear repositorio independiente para el paquete | GitHub | S |
| 3.2 | Actualizar GitHub Actions (`checkout@v4`, `composer-install@v3`, matrix PHP 8.3+8.4) | `.github/workflows/**` | S |
| 3.3 | Añadir job de CI en push a `main` | `.github/workflows/ci.yml` | XS |
| 3.4 | Añadir workflow de release → Packagist webhook | `.github/workflows/release.yml` | S |
| 3.5 | Configurar `dependabot` para Composer | `.github/dependabot.yml` | XS |
| 3.6 | Integrar subida de coverage a Codecov/Coveralls | `.github/workflows/ci.yml` | S |
| 3.7 | Escribir `README.md` orientado al framework (quickstart, requisitos, ejemplos) | `README.md` | M |
| 3.8 | Añadir `CHANGELOG.md`, `CONTRIBUTING.md`, `SECURITY.md` | Raíz del repo | M |
| 3.9 | Crear primer tag `v0.1.0` y registrar en Packagist | Git + Packagist | XS |

### Orden recomendado

```
3.1 → 1.2 → 1.3 → 1.4 → 1.5   (extracción + namespaces + composer)
       ↓
3.2 → 3.3 → 3.5                  (CI básico funcional)
       ↓
3.6                               (coverage en CI)
       ↓
3.7 → 3.8                         (documentación)
       ↓
3.4 → 3.9                         (release workflow + Packagist)
```

---

## Checklist de publicación

### Código
- [ ] Namespace vendor root en todos los archivos y stubs
- [ ] `composer.json` con `"type": "library"`, autoload PSR-4 correcto, metadata completa

### CI/CD
- [ ] Actions actualizados (`checkout@v4`, `composer-install@v3`)
- [ ] Matrix PHP 8.3 + 8.4
- [ ] CI corriendo en push a `main` y en PR
- [ ] Workflow de release configurado
- [ ] Coverage subiendo a Codecov o Coveralls

### Documentación y repositorio
- [ ] `README.md` con quickstart, requisitos, instalación y ejemplos
- [ ] `CHANGELOG.md` con entrada `v0.1.0`
- [ ] `CONTRIBUTING.md`
- [ ] `SECURITY.md`
- [ ] `LICENSE`
- [ ] Tag `v0.1.0` creado en Git
- [ ] Paquete registrado en Packagist con webhook de auto-update

### Integración con `resbooking`
- [ ] `resbooking` actualizado para consumir el paquete via `composer require`
- [ ] CI de `resbooking` pasa con la dependencia externa

**Leyenda de esfuerzo:** XS < 1h · S 1-3h · M 3-8h · L 8h+
