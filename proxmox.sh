#!/bin/bash

# Obligamos a usar puntos decimales
export LC_NUMERIC=C

DATE=$(date +"%Y-%m-%d %H:%M:%S")
LOG="/home/server/scripts/html/proxmox.json"

# --- MÉTRICAS ---
CPU=$(top -bn1 | grep "Cpu(s)" | awk '{print $2 + $4}')
RAM_USED=$(free -m | awk '/Mem:/ {print $3}')
RAM_TOTAL=$(free -m | awk '/Mem:/ {print $2}')
DISK=$(df -h / | awk 'NR==2 {print $5}' | tr -d '%')
IFACE=$(ip route | grep default | awk '{print $5}')
RX=$(cat /proc/net/dev | grep "$IFACE" | awk '{print $2}')
TX=$(cat /proc/net/dev | grep "$IFACE" | awk '{print $10}')
LOAD=$(uptime | awk -F'load average:' '{print $2}' | cut -d',' -f1 | xargs)
SWAP_USED=$(free -m | awk '/Swap:/ {print $3}')
[ -z "$SWAP_USED" ] && SWAP_USED=0

# TOP PROCESS (Formato limpio)
TOP_PROCESSES=$(ps -eo comm,%cpu,rss --sort=-%cpu | head -n 11 | tail -n 10 | awk '{printf "{\"name\":\"%s\",\"cpu\":%.1f,\"ram\":%.2f},", $1, $2, $3/1024}' | sed 's/,$//')

# Montamos el objeto JSON
NEW_ENTRY="{\"date\":\"$DATE\",\"cpu\":$CPU,\"ram_used\":$RAM_USED,\"ram_total\":$RAM_TOTAL,\"disk\":$DISK,\"rx\":$RX,\"tx\":$TX,\"load\":$LOAD,\"swap\":$SWAP_USED,\"top_list\":[$TOP_PROCESSES]}"

# --- GUARDADO SEGURO ---
if [ ! -f "$LOG" ] || [ ! -s "$LOG" ] || [ "$(cat $LOG)" == "[]" ]; then
    # Si el archivo no existe, está vacío o es un array vacío, creamos uno nuevo
    echo "[$NEW_ENTRY]" > "$LOG"
else
    # 1. Quitamos el último corchete ']' cuidando que no queden espacios
    # 2. Añadimos la coma y el nuevo registro
    # 3. Cerramos el corchete
    sed -i 's/\]$//' "$LOG"
    echo ",$NEW_ENTRY]" >> "$LOG"
fi

# Limpieza opcional: Mantener solo las últimas 50 líneas para que no crezca infinito
tail -n 50 "$LOG" > "$LOG.tmp" && mv "$LOG.tmp" "$LOG"
