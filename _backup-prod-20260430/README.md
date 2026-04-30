# Backup forense del contenedor de producción — 2026-04-30 14:16 CEST

## Contexto

El 2026-04-30 al intentar desplegar el feature `Revenue Management`, se descubrió
que el contenedor `laravel-f6irzmls5je67llxtivpv7lx` (Coolify, servidor interno
217.160.39.79, dominio `crm.apartamentosalgeciras.com`) tenía **trabajo
hecho directamente en producción sin pasar por git**:

- 17 ficheros modificados respecto al HEAD del contenedor (`05def43f`)
- 2872 archivos sin trackear (incluyendo nuevos Commands, Services, Jobs +
  PII como DNIs en `storage/backups-externo/`)
- 12.464 líneas de diff acumuladas

El `git checkout -B main origin/main` que hace el entrypoint de Coolify
fallaba al actualizar el código por estos cambios sin commit, y se quedaba
en silencio en el commit viejo `05def43f`.

## Decisión

Para no perder ese trabajo (semana de hotfixes de IA, MIR, cerraduras…)
**NI exponer DNIs de huéspedes en el repo público**, esta rama
`backup/contenedor-prod-2026-04-30` contiene SÓLO los metadatos:

- `uncommitted.diff` (1 MB) — diff completo de los modificados
- `untracked-files.txt` (190 KB) — lista de los 2872 untracked

El contenido binario completo (incluida la PII) está guardado **fuera de
git** en:

- `217.160.39.79:/data/coolify/_backups/laravel-f6irzmls-20260430-141647/`
  - `var-www-html.tar.gz` (1.1 GB) — tar completo del contenedor
  - `untracked-content.tar.gz` (40 MB) — contenido de los untracked
- Local en máquina de desarrollo (Windows):
  - `D:/proyectos/programasivan/NuevoHeraAppartment/_backup-contenedor-prod-20260430/`
    (gitignored, no se sube nunca)

## Cómo recuperar

1. **Aplicar el diff sobre commit `05def43f`** (HEAD del contenedor en ese
   momento):
   ```sh
   git checkout 05def43f
   git apply _backup-prod-20260430/uncommitted.diff
   ```
2. **Restaurar los untracked** desde el tar:
   ```sh
   ssh -i ~/.ssh/hawcert_server claude@217.160.39.79 \
     "sudo cat /data/coolify/_backups/laravel-f6irzmls-20260430-141647/untracked-content.tar.gz" \
     | tar xzf -
   ```
   ⚠️ Cuidado: el tar incluye DNIs en `storage/backups-externo/` que NO
   deben commitearse jamás.

## Por qué no se mergea esta rama a `main`

Cualquier merge requeriría primero:
1. Filtrar PII (DNIs, fotos personales) de los untracked
2. Resolver conflictos con `feature/revenue-management` (tocan los mismos
   ficheros: `Kernel.php`, `AppServiceProvider.php`)
3. Validar que cada Service/Command nuevo tiene tests o al menos un
   smoke test manual

Ese trabajo se hace tras el fin de semana del 2026-05-02.
