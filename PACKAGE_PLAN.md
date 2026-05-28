# Plan: `aseguragonzalez/php-mvc`

**Actualizado:** 2026-05-28
**Autor:** Alfonso Segura

---

## Estado de completitud

Todas las fases de extracción, calidad y CI están completadas. Queda la publicación final y la integración con el repositorio consumidor.

### Completado

| Área | Resumen |
|---|---|
| Namespace | `PhpMvc\` en todos los archivos PHP y stubs (~336 archivos) |
| `composer.json` | `type: library`, autoload PSR-4, metadata completa, binario en `bin/mvc` |
| PSR deps | Solo `psr/*` en `require`; `nyholm` y `php-di` eliminados; `MutableContainerInterface` en `src/` |
| Calidad | `phpstan.neon` nivel 8, `.php-cs-fixer.dist.php`, `phpunit.xml`, `Makefile`; `squizlabs/php_codesniffer` eliminado (redundante con `php-cs-fixer`) |
| Tests | 723/723 pasando con implementaciones PSR-7/PSR-17 mínimas en `tests/Support/` |
| CI | `checkout@v4`, `composer-install@v3`, matrix PHP 8.3+8.4, coverage → Codecov |
| Workflows | `ci.yml` (push main + PR) y `release.yml` (Packagist webhook) |
| Docs | `README.md`, `CHANGELOG.md` (v0.1.0), `CONTRIBUTING.md`, `SECURITY.md` |

---

## Pendiente

### P1 — Tag `v0.1.0` y publicación en Packagist

1. Asegurarse de que `main` está limpio y CI verde.
2. Crear el tag:
   ```bash
   git tag -s v0.1.0 -m "Initial release"
   git push origin v0.1.0
   ```
3. Registrar el paquete en [packagist.org](https://packagist.org/packages/submit) con la URL del repositorio.
4. Configurar el webhook de auto-update en GitHub → Settings → Webhooks con la URL que proporciona Packagist.
5. Verificar que el paquete aparece en `https://packagist.org/packages/aseguragonzalez/php-mvc`.

---

## Checklist de publicación

### Código
- [x] Namespace `PhpMvc\` en todos los archivos PHP y stubs
- [x] `composer.json` con `type: library`, autoload PSR-4, metadata completa
- [x] Solo `psr/*` en `require`; `MutableContainerInterface` en `src/`
- [x] PHP_CodeSniffer eliminado de `require-dev` (cubierto por `php-cs-fixer`)

### Calidad
- [x] `phpstan.neon` nivel 8 sin errores
- [x] `.php-cs-fixer.dist.php` configurado
- [x] `phpunit.xml` configurado
- [x] 723/723 tests pasando

### CI/CD
- [x] `checkout@v4`, `composer-install@v3`
- [x] Matrix PHP 8.4
- [x] CI en push a `main` y en PR
- [x] Workflow de release configurado
- [x] Coverage subiendo a Codecov

### Documentación y repositorio
- [x] `README.md` con quickstart, requisitos, instalación y ejemplos
- [x] `CHANGELOG.md` con entrada `v0.1.0`
- [x] `CONTRIBUTING.md`
- [x] `SECURITY.md`
- [ ] Tag `v0.1.0` creado y pusheado
- [ ] Paquete registrado en Packagist con webhook de auto-update
