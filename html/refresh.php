<?php
date_default_timezone_set('Europe/Madrid'); 
header('Content-Type: application/json');

$cmd = "ps -eo comm,%cpu,rss --no-headers | grep -v 'ps' | grep -v 'awk' | grep -v 'grep' | grep -v 'sh'";
$output = shell_exec($cmd);

$processes = [];
$lines = explode("\n", trim($output));

foreach ($lines as $line) {
    $data = preg_split('/\s+/', trim($line));
    if (count($data) < 3) continue;

    $name = $data[0];
    $cpu = (float)$data[1];
    $ram = (float)$data[2] / 1024; 

    if (isset($processes[$name])) {
        $processes[$name]['cpu'] += $cpu;
        $processes[$name]['ram'] += $ram;
    } else {
        $processes[$name] = ['name' => $name, 'cpu' => $cpu, 'ram' => $ram];
    }
}

// Ordenar por CPU
usort($processes, function($a, $b) {
    return $b['cpu'] <=> $a['cpu'];
});

// REDONDEO: Limpiamos los decimales antes de enviar
$top_10 = array_slice($processes, 0, 10);
foreach ($top_10 as &$p) {
    $p['cpu'] = round($p['cpu'], 1); // Solo 1 decimal para el %
    $p['ram'] = round($p['ram'], 2); // 2 decimales para los MB
}

echo json_encode([
    "date" => date('H:i:s'),
    "top_list" => array_values($top_10)
]);
