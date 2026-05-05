#!/bin/bash
export LC_NUMERIC=C

# Captura solo los procesos actuales y los guarda en un archivo temporal
TOP_PROCESSES=$(ps -eo comm,%cpu,rss --sort=-%cpu | head -n 11 | tail -n 10 | awk '{printf "{\"name\":\"%s\",\"cpu\":%.1f,\"ram\":%.2f},", $1, $2, $3/1024}' | sed 's/,$//')

# Crea un JSON pequeño que solo sirve para el botón
echo "{\"date\":\"$(date +'%H:%M:%S')\", \"top_list\":[$TOP_PROCESSES]}" > /home/server/scripts/html/live.json

# Darle permiso para que el servidor web lo lea
chmod 644 /home/server/scripts/html/live.json
