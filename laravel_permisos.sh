#!/usr/bin/env bash
# =============================================================================
# fix-laravel-permissions.sh
# Ajusta permisos de directorios y archivos de un proyecto Laravel para DDEV.
#
# Uso:
#   bash fix-laravel-permissions.sh [RUTA_PROYECTO]
#
# Si no se entrega RUTA_PROYECTO, usa el directorio actual.
#
# Modos de ejecución:
#   Dentro del contenedor  →  ddev exec bash fix-laravel-permissions.sh
#   Desde el host (WSL2)   →  bash fix-laravel-permissions.sh
# =============================================================================

set -euo pipefail

# ── Colores ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
DIM='\033[2m'
NC='\033[0m'

# ── Argumentos ───────────────────────────────────────────────────────────────
PROJECT_ROOT="${1:-$(pwd)}"

# ── Validación ───────────────────────────────────────────────────────────────
if [[ ! -f "${PROJECT_ROOT}/artisan" ]]; then
    echo -e "${RED}✗ No se encontró artisan en '${PROJECT_ROOT}'.${NC}"
    echo -e "${YELLOW}  Asegúrate de estar en la raíz del proyecto Laravel o pasa la ruta como argumento.${NC}"
    exit 1
fi

echo -e "${CYAN}══════════════════════════════════════════════${NC}"
echo -e "${CYAN}  Laravel Permission Fix — DDEV edition       ${NC}"
echo -e "${CYAN}══════════════════════════════════════════════${NC}"
echo -e "  Proyecto : ${PROJECT_ROOT}"
echo ""

# ── Detectar contexto: root dentro de contenedor vs usuario en host ───────────
IS_ROOT=false
if [[ "$(id -u)" -eq 0 ]]; then
    IS_ROOT=true
fi

# Usuario web objetivo
WEB_USER="www-data"
WEB_GROUP="www-data"

# Verificar que www-data existe en este sistema
if ! id "${WEB_USER}" &>/dev/null; then
    WEB_USER="$(id -un)"
    WEB_GROUP="$(id -gn)"
    echo -e "${YELLOW}⚠  Usuario www-data no encontrado. Usando: ${WEB_USER}:${WEB_GROUP}${NC}"
    echo ""
fi

# ── Directorios que deben existir y ser escribibles por el servidor web ───────
WRITABLE_DIRS=(
    "storage"
    "storage/app"
    "storage/app/public"
    "storage/framework"
    "storage/framework/cache"
    "storage/framework/cache/data"
    "storage/framework/sessions"
    "storage/framework/testing"
    "storage/framework/views"
    "storage/logs"
    "bootstrap/cache"
)

# ── Función: crear dir si no existe ──────────────────────────────────────────
ensure_dir() {
    local dir="${PROJECT_ROOT}/$1"
    if [[ ! -d "${dir}" ]]; then
        mkdir -p "${dir}"
        echo -e "  ${YELLOW}+${NC} Creado  : $1"
    else
        echo -e "  ${DIM}·${NC} Existe  : $1"
    fi
}

# ── PASO 1 — Crear directorios faltantes ─────────────────────────────────────
echo -e "${CYAN}▶ 1/5 Verificando directorios requeridos...${NC}"
for dir in "${WRITABLE_DIRS[@]}"; do
    ensure_dir "${dir}"
done
echo -e "${GREEN}  ✓ Directorios OK${NC}"
echo ""

# ── PASO 2 — Permisos base (archivos 644, directorios 755) ───────────────────
echo -e "${CYAN}▶ 2/5 Aplicando permisos base (644 archivos / 755 directorios)...${NC}"
find "${PROJECT_ROOT}" \
    -not -path "${PROJECT_ROOT}/.git/*" \
    -not -path "${PROJECT_ROOT}/node_modules/*" \
    -not -path "${PROJECT_ROOT}/vendor/*" \
    \( -type f -exec chmod 644 {} + \
    -o -type d -exec chmod 755 {} + \)
echo -e "${GREEN}  ✓ Permisos base aplicados${NC}"
echo ""

# ── PASO 3 — chmod 775 en directorios escribibles ────────────────────────────
echo -e "${CYAN}▶ 3/5 Aplicando chmod 775 en storage y bootstrap/cache...${NC}"
for dir in "${WRITABLE_DIRS[@]}"; do
    target="${PROJECT_ROOT}/${dir}"
    chmod -R 775 "${target}"
    echo -e "  ${GREEN}✓${NC} 775 → ${dir}"
done

# .env: legible solo por owner y grupo, sin escritura pública
if [[ -f "${PROJECT_ROOT}/.env" ]]; then
    chmod 640 "${PROJECT_ROOT}/.env"
    echo -e "  ${GREEN}✓${NC} 640 → .env"
fi

# artisan ejecutable
chmod +x "${PROJECT_ROOT}/artisan"
echo -e "  ${GREEN}✓${NC} +x  → artisan"
echo ""

# ── PASO 4 — chown granular (siempre, independiente de si somos root) ─────────
# Este es el paso crítico para DDEV: cuando composer/artisan se corren desde
# el host, los archivos quedan con el owner del usuario WSL2 y PHP-FPM
# (www-data dentro del contenedor) no puede escribir aunque chmod diga 775.
echo -e "${CYAN}▶ 4/5 Ajustando propietario de directorios críticos (${WEB_USER}:${WEB_GROUP})...${NC}"

if $IS_ROOT; then
    # Dentro del contenedor DDEV corremos como root → chown sin problemas
    for dir in "${WRITABLE_DIRS[@]}"; do
        target="${PROJECT_ROOT}/${dir}"
        chown -R "${WEB_USER}:${WEB_GROUP}" "${target}"
        echo -e "  ${GREEN}✓${NC} chown ${WEB_USER} → ${dir}"
    done

    # También el directorio raíz y archivos sueltos (artisan, .env, composer.json…)
    # pero sin tocar vendor/ ni node_modules/ para no romper instalaciones del host
    find "${PROJECT_ROOT}" \
        -maxdepth 1 \
        -not -path "${PROJECT_ROOT}" \
        -not -name "vendor" \
        -not -name "node_modules" \
        -not -name ".git" \
        -exec chown -R "${WEB_USER}:${WEB_GROUP}" {} +

    echo -e "${GREEN}  ✓ chown aplicado${NC}"
else
    # Fuera del contenedor: intentamos con sudo si está disponible,
    # si no, damos instrucciones claras.
    echo -e "${YELLOW}  ⚠ No eres root. Intentando con sudo...${NC}"
    if command -v sudo &>/dev/null && sudo -n true 2>/dev/null; then
        for dir in "${WRITABLE_DIRS[@]}"; do
            target="${PROJECT_ROOT}/${dir}"
            sudo chown -R "${WEB_USER}:${WEB_GROUP}" "${target}"
            echo -e "  ${GREEN}✓${NC} sudo chown ${WEB_USER} → ${dir}"
        done
        echo -e "${GREEN}  ✓ chown aplicado vía sudo${NC}"
    else
        echo -e "${YELLOW}  ⚠ sudo no disponible o requiere contraseña.${NC}"
        echo -e "  Ejecuta manualmente desde el host:"
        echo ""
        echo -e "  ${CYAN}ddev exec bash fix-laravel-permissions.sh${NC}"
        echo ""
        echo -e "  O bien estos comandos dentro del contenedor:"
        for dir in "${WRITABLE_DIRS[@]}"; do
            echo -e "  ${CYAN}ddev exec chown -R www-data:www-data ${dir}${NC}"
        done
    fi
fi
echo ""

# ── PASO 5 — Verificación rápida ─────────────────────────────────────────────
echo -e "${CYAN}▶ 5/5 Verificación de directorios críticos...${NC}"
ALL_OK=true
for dir in "${WRITABLE_DIRS[@]}"; do
    target="${PROJECT_ROOT}/${dir}"
    owner=$(stat -c '%U' "${target}" 2>/dev/null || stat -f '%Su' "${target}" 2>/dev/null || echo "?")
    perms=$(stat -c '%a' "${target}" 2>/dev/null || stat -f '%OLp' "${target}" 2>/dev/null || echo "?")

    # Writeable por owner o group
    if [[ -w "${target}" ]]; then
        echo -e "  ${GREEN}✓${NC} ${dir} ${DIM}[${perms} ${owner}]${NC}"
    else
        echo -e "  ${RED}✗${NC} ${dir} ${DIM}[${perms} ${owner}]${NC} — ¡no escribible!"
        ALL_OK=false
    fi
done
echo ""

# ── Resumen ───────────────────────────────────────────────────────────────────
echo -e "${CYAN}══════════════════════════════════════════════${NC}"
if $ALL_OK; then
    echo -e "${GREEN}  ✓ Todos los permisos están correctos.${NC}"
else
    echo -e "${RED}  ✗ Algunos directorios aún no son escribibles.${NC}"
    echo -e "${YELLOW}  → Corre el script desde dentro del contenedor:${NC}"
    echo -e "     ${CYAN}ddev exec bash fix-laravel-permissions.sh${NC}"
fi
echo -e "${CYAN}══════════════════════════════════════════════${NC}"
echo ""
echo -e "  Limpieza de caché recomendada:"
echo -e "  ${CYAN}ddev exec php artisan config:clear${NC}"
echo -e "  ${CYAN}ddev exec php artisan cache:clear${NC}"
echo -e "  ${CYAN}ddev exec php artisan view:clear${NC}"
echo ""
