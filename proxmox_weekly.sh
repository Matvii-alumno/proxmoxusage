#!/bin/bash

# Rutas
LOG_PRINCIPAL="/home/server/scripts/html/proxmox.json"
LATEST_JSON="/home/server/scripts/html/latest.json"
REPORT_TXT="/home/server/scripts/proxmox_report_actual.txt"

if [ -s "$LOG_PRINCIPAL" ]; then
    # 1. Procesamos las medias (igual que antes)
    python3 -c "
import json
from collections import defaultdict
try:
    with open('$LOG_PRINCIPAL', 'r') as f:
        data = json.load(f)
    
    # Calculamos medias de toda la semana que acaba de pasar
    stats = defaultdict(lambda: {'cpu': 0, 'ram': 0, 'count': 0})
    for e in data:
        for p in e.get('top_list', []):
            n = p['name']
            stats[n]['cpu'] += p['cpu']
            stats[n]['ram'] += p['ram']
            stats[n]['count'] += 1
    
    top_10 = sorted([{'name': k, 'cpu': round(v['cpu']/v['count'], 2), 
                      'ram': round(v['ram']/v['count'], 2)} 
                     for k, v in stats.items()], key=lambda x: x['cpu'], reverse=True)[:10]

    # Guardamos el resumen final para la web
    # Usamos la última entrada de la semana pero con el Top 10 promedio
    final_data = data[-20:]
    if final_data: final_data[-1]['top_list'] = top_10
    
    with open('$LATEST_JSON', 'w') as f:
        json.dump(final_data, f, indent=2)
except Exception as e: print(e)
"

    # 2. Guardamos el historial de la semana en un archivo comprimido (BackUp)
    cp "$LOG_PRINCIPAL" "$BACKUP_DIR/proxmox_$(date +'%Y-%W').json"

    # 3. ¡ESTA ES LA PARTE IMPORTANTE! 
    # Vaciamos el log principal para que la nueva semana empiece de CERO
    echo "[]" > "$LOG_PRINCIPAL"
    
    echo "Semana cerrada. Log reiniciado para la nueva semana."
else
    echo "No hay datos que procesar."
fi
