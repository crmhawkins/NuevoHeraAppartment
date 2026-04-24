#!/usr/bin/env bash
# coolify-laravel-guardian.sh
# Protege entrypoint.sh y supervisord.conf del servicio Laravel
# del proyecto apartamentos-algeciras contra regeneraciones de Coolify.
set -uo pipefail

SERVICE_DIR="/data/coolify/services/f6irzmls5je67llxtivpv7lx"
ENTRYPOINT="${SERVICE_DIR}/entrypoint.sh"
ENTRYPOINT_BAK="${SERVICE_DIR}/entrypoint.sh.bak"
SUPERVISORD="${SERVICE_DIR}/supervisord.conf"
SUPERVISORD_BAK="${SERVICE_DIR}/supervisord.conf.bak"

# Patron que identifica el entrypoint malo regenerado por Coolify.
BAD_PATTERN='sed -i "/^\[program:'

CONTAINER_FILTER="f6irzmls5je67llxtivpv7lx"
CONTAINER_NAME_HINT="laravel"

LOG="/var/log/coolify-laravel-guardian.log"
LOCK="/var/run/coolify-laravel-guardian.lock"

log() {
    printf '%s [guardian] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$*" >> "$LOG"
}

exec 9>"$LOCK" || exit 0
if ! flock -n 9; then
    exit 0
fi

if [[ ! -d "$SERVICE_DIR" ]]; then
    exit 0
fi

changed=0

# 1. entrypoint.sh
if [[ -f "$ENTRYPOINT" ]]; then
    if grep -qF "$BAD_PATTERN" "$ENTRYPOINT"; then
        log "DETECTADO entrypoint.sh regenerado. Restaurando desde .bak."
        if [[ -f "$ENTRYPOINT_BAK" ]]; then
            cp -a "$ENTRYPOINT_BAK" "$ENTRYPOINT"
            chmod +x "$ENTRYPOINT"
            log "entrypoint.sh restaurado desde $ENTRYPOINT_BAK ($(stat -c%s "$ENTRYPOINT") bytes)."
            changed=1
        else
            log "ERROR: entrypoint.sh corrupto y no existe $ENTRYPOINT_BAK. Intervencion manual."
        fi
    else
        if [[ ! -f "$ENTRYPOINT_BAK" ]]; then
            cp -a "$ENTRYPOINT" "$ENTRYPOINT_BAK"
            log "Creado backup inicial $ENTRYPOINT_BAK ($(stat -c%s "$ENTRYPOINT_BAK") bytes)."
        fi
    fi
else
    log "ADVERTENCIA: $ENTRYPOINT no existe."
fi

# 2. supervisord.conf
check_user_in_block() {
    local file="$1" block="$2"
    awk -v b="[program:${block}]" '
        $0 == b          { inblk=1; next }
        /^\[/ && inblk   { exit }
        inblk && /^user[[:space:]]*=[[:space:]]*www-data[[:space:]]*$/ { found=1; exit }
        END              { exit (found ? 0 : 1) }
    ' "$file"
}

add_user_to_block() {
    local file="$1" block="$2"
    local tmp
    tmp="$(mktemp)"
    awk -v b="[program:${block}]" '
        { print }
        $0 == b { print "user=www-data" }
    ' "$file" > "$tmp"
    cat "$tmp" > "$file"
    rm -f "$tmp"
}

if [[ -f "$SUPERVISORD" ]]; then
    sup_fixed=0
    for blk in scheduler queue-worker; do
        if ! check_user_in_block "$SUPERVISORD" "$blk"; then
            log "DETECTADO supervisord.conf sin user=www-data en [program:${blk}]. Parcheando."
            if [[ ! -f "$SUPERVISORD_BAK" ]]; then
                cp -a "$SUPERVISORD" "${SUPERVISORD}.pre-guardian.$(date +%s)"
            fi
            add_user_to_block "$SUPERVISORD" "$blk"
            sup_fixed=1
        fi
    done

    if [[ "$sup_fixed" -eq 1 ]]; then
        log "supervisord.conf parcheado. Nuevo tamano: $(stat -c%s "$SUPERVISORD") bytes."
        changed=1
    fi

    if [[ ! -f "$SUPERVISORD_BAK" ]] \
        && check_user_in_block "$SUPERVISORD" scheduler \
        && check_user_in_block "$SUPERVISORD" queue-worker; then
        cp -a "$SUPERVISORD" "$SUPERVISORD_BAK"
        log "Creado backup inicial $SUPERVISORD_BAK."
    fi
else
    log "ADVERTENCIA: $SUPERVISORD no existe."
fi

# 3. Reinicio si cambio algo
if [[ "$changed" -eq 1 ]]; then
    container_id="$(docker ps --format '{{.ID}} {{.Names}}' \
        | grep "$CONTAINER_FILTER" \
        | grep "$CONTAINER_NAME_HINT" \
        | awk '{print $1}' | head -n1)"
    if [[ -n "$container_id" ]]; then
        log "Reiniciando contenedor laravel ($container_id)..."
        if docker restart "$container_id" >/dev/null 2>&1; then
            log "Contenedor $container_id reiniciado."
        else
            log "ERROR: fallo docker restart $container_id."
        fi
    else
        log "ERROR: no se encontro contenedor laravel."
    fi
fi

exit 0
