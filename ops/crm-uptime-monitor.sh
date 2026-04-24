#!/usr/bin/env bash
# crm-uptime-monitor.sh — monitor externo del CRM con alerta WhatsApp.
set -u

URL="https://crm.apartamentosalgeciras.com/"
TIMEOUT=10
FAIL_THRESHOLD=3

ADMIN_PHONE="34605621704"
PHONE_ID="102360642838173"
GRAPH_VERSION="v19.0"
TEMPLATE_DOWN="alerta_doble_reserva"
TEMPLATE_UP="alerta_doble_reserva"
TEMPLATE_LANG="es"

LARAVEL_CONTAINER="laravel-f6irzmls5je67llxtivpv7lx"

STATE_DIR="/var/lib/crm-uptime-monitor"
TOKEN_CACHE="$STATE_DIR/.whatsapp_token"
STATE_FILE="$STATE_DIR/state"
LOG_FILE="/var/log/crm-uptime-monitor.log"

mkdir -p "$STATE_DIR"
touch "$LOG_FILE"

log() {
    printf '%s %s\n' "$(date '+%Y-%m-%dT%H:%M:%S%z')" "$*" >> "$LOG_FILE" 2>/dev/null || true
}

FAIL_COUNT=0
FIRST_FAIL_AT=""
ALERTED=0
if [[ -f "$STATE_FILE" ]]; then
    source "$STATE_FILE" || true
fi

save_state() {
    {
        echo "FAIL_COUNT=${FAIL_COUNT}"
        echo "FIRST_FAIL_AT=\"${FIRST_FAIL_AT}\""
        echo "ALERTED=${ALERTED}"
    } > "$STATE_FILE"
}

get_whatsapp_token() {
    # Prioridad 1: leer del .env dentro del container laravel (fuente canonica)
    local token
    token=$(docker exec "$LARAVEL_CONTAINER" sh -c "grep -E '^TOKEN_WHATSAPP=' /var/www/html/.env | head -1 | cut -d= -f2-" 2>/dev/null \
        | tr -d '"' | tr -d "'" | tr -d '\r\n')
    if [[ -n "$token" ]]; then
        # Cache local para sobrevivir caidas del container
        printf '%s' "$token" > "$TOKEN_CACHE"
        chmod 600 "$TOKEN_CACHE" 2>/dev/null || true
        printf '%s' "$token"
        return 0
    fi

    # Prioridad 2: cache local (si el container esta caido)
    if [[ -r "$TOKEN_CACHE" ]]; then
        token=$(cat "$TOKEN_CACHE" | tr -d '\r\n')
        if [[ -n "$token" ]]; then printf '%s' "$token"; return 0; fi
    fi

    return 1
}

json_escape() { printf '%s' "$1" | sed -e 's/\\/\\\\/g' -e 's/"/\\"/g'; }

send_whatsapp() {
    local template="$1" v1="$2" v2="$3" v3="$4" v4="$5"
    local token
    token=$(get_whatsapp_token) || {
        log "WARN no se pudo obtener whatsapp_token - omito envio"
        return 1
    }
    v1=$(json_escape "$v1"); v2=$(json_escape "$v2"); v3=$(json_escape "$v3"); v4=$(json_escape "$v4")

    local payload
    printf -v payload '{"messaging_product":"whatsapp","to":"%s","type":"template","template":{"name":"%s","language":{"code":"%s"},"components":[{"type":"body","parameters":[{"type":"text","text":"%s"},{"type":"text","text":"%s"},{"type":"text","text":"%s"},{"type":"text","text":"%s"}]}]}}' \
        "$ADMIN_PHONE" "$template" "$TEMPLATE_LANG" "$v1" "$v2" "$v3" "$v4"

    local http_code body tmp
    tmp=$(mktemp)
    http_code=$(curl -sS -o "$tmp" -w '%{http_code}' \
        --max-time 15 \
        -X POST "https://graph.facebook.com/${GRAPH_VERSION}/${PHONE_ID}/messages" \
        -H "Authorization: Bearer ${token}" \
        -H "Content-Type: application/json" \
        -d "$payload" 2>/dev/null || echo "000")
    body=$(cat "$tmp"); rm -f "$tmp"
    if [[ "$http_code" == "200" ]]; then
        log "WA OK template=$template vars=[$v1|$v2|$v3|$v4]"
        return 0
    else
        log "WA FAIL http=$http_code body=$body"
        return 1
    fi
}

NOW_ISO=$(date '+%Y-%m-%d %H:%M:%S')
HTTP_CODE=$(curl -s -o /dev/null -w '%{http_code}' \
    --max-time "$TIMEOUT" \
    "$URL" 2>/dev/null)
# curl sin -L no sigue redirects; %{http_code} devuelve el del primer hop
# (200/301/302 directamente). Si curl falla de red / timeout, %{http_code}
# devuelve 000 y exit code != 0 — recogemos el 000 limpiamente.
if [[ -z "$HTTP_CODE" ]]; then HTTP_CODE="000"; fi

case "$HTTP_CODE" in
    200|301|302) STATUS="OK" ;;
    *)           STATUS="FAIL" ;;
esac

log "CHECK url=$URL http=$HTTP_CODE status=$STATUS fail_count=$FAIL_COUNT alerted=$ALERTED"

if [[ "$STATUS" == "FAIL" ]]; then
    FAIL_COUNT=$((FAIL_COUNT + 1))
    [[ -z "$FIRST_FAIL_AT" ]] && FIRST_FAIL_AT="$NOW_ISO"
    if (( FAIL_COUNT >= FAIL_THRESHOLD )) && (( ALERTED == 0 )); then
        log "ALERT disparado: $FAIL_COUNT fallos consecutivos desde $FIRST_FAIL_AT"
        if send_whatsapp "$TEMPLATE_DOWN" \
            "CRM CAIDO" \
            "$FIRST_FAIL_AT" \
            "$URL" \
            "HTTP ${HTTP_CODE} (timeout ${TIMEOUT}s)"; then
            ALERTED=1
        fi
    fi
else
    if (( ALERTED == 1 )); then
        log "RECOVERY tras incidente iniciado $FIRST_FAIL_AT"
        send_whatsapp "$TEMPLATE_UP" \
            "CRM RECUPERADO" \
            "$FIRST_FAIL_AT" \
            "$URL" \
            "HTTP ${HTTP_CODE} OK a las ${NOW_ISO}" || true
    fi
    FAIL_COUNT=0
    FIRST_FAIL_AT=""
    ALERTED=0
fi

save_state
exit 0
