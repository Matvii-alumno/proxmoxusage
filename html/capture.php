<?php
date_default_timezone_set('Europe/Madrid');
header('Content-Type: application/json');

// 1. MÉTRICAS DE RED (Leyendo del host gracias a los volúmenes)
$net_data = shell_exec("awk '/eth0|ens|enp|eno|vmbr/ {rx+=$2; tx+=$10} END {print rx \",\" tx}' /proc/net/dev");
$net_exploded = explode(",", trim($net_data));

// 2. MÉTRICAS DEL SISTEMA
$cpu = shell_exec("top -bn1 | grep 'Cpu(s)' | awk '{print $2 + $4}'");
$ram = shell_exec("free -m | awk '/Mem:/ {print $3}'");
$disk = shell_exec("df -h / | awk 'NR==2 {print $5}' | tr -d '%'");
$load = shell_exec("awk '{print $1}' /proc/loadavg");
$swap = shell_exec("free -m | awk '/Swap:/ {print $3}'");

// 3. LA CLAVE: OBTENER LOS 10 PROCESOS REALES
// Con pid: host en el yaml, este comando verá TODO el equipo
$processes_raw = shell_exec("ps -eo comm,%cpu,rss --sort=-%cpu | head -n 11 | tail -n +2");
$processes = [];

if ($processes_raw) {
    $lines = explode("\n", trim($processes_raw));
    foreach ($lines as $line) {
        $parts = preg_split('/\s+/', trim($line));
        if (count($parts) >= 3) {
            $processes[] = [
                "name" => $parts[0],
                "cpu" => $parts[1] . "%",
                "ram" => number_format($parts[2] / 1024, 2) . " MB" // Convertimos KB a MB
            ];
        }
    }
}

// 4. RESPUESTA FINAL
echo json_encode([
    "date" => date('H:i:s'),
    "cpu" => (float)trim($cpu),
    "ram_used" => (int)trim($ram),
    "disk" => (int)trim($disk),
    "load" => (float)trim($load),
    "swap" => (int)trim($swap),
    "rx" => (float)$net_exploded[0] / 1024,
    "tx" => (float)$net_exploded[1] / 1024,
    "top_processes" => $processes // <--- Esto es lo que rellenará tu tabla
]);
