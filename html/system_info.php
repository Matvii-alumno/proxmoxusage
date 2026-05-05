<?php

function clean($str) {
    if ($str === null || $str === false) return "";
    return trim(preg_replace('/\s+/', ' ', $str));
}

$data = [];

/* 1. SISTEMA OPERATIVO */
$real_hostname = @file_get_contents('/etc/host_hostname');

if ($real_hostname !== false) {
    $data['hostname'] = clean($real_hostname);
} else {
    $data['hostname'] = clean(shell_exec("hostname"));
}
$data['os'] = clean(shell_exec("grep PRETTY_NAME /etc/os-release | cut -d= -f2 | tr -d '\"'"));
$data['kernel'] = clean(shell_exec("uname -r"));
$data['architecture'] = clean(shell_exec("uname -m"));

/* 2. UPTIME DEL CONTENEDOR */
$uptime_raw = shell_exec("cat /proc/uptime | awk '{print $1}'");
$uptime_sec = floor(floatval($uptime_raw));
if ($uptime_sec > 0) {
    $d = floor($uptime_sec / 86400);
    $h = floor(($uptime_sec % 86400) / 3600);
    $m = floor(($uptime_sec % 3600) / 60);
    $data['uptime'] = "{$d}d {$h}h {$m}m";
} else {
    $data['uptime'] = "N/A";
}

/* 3. PROCESADOR (INFO DETALLADA) */
$data['cpu_vendor'] = clean(shell_exec("lscpu | grep 'Vendor ID' | awk '{print $3}'"));
$data['cpu_model'] = clean(shell_exec("lscpu | grep 'Model name:' | sed 's/Model name:[ ]*//'"));
$data['cpu_speed_mhz'] = clean(shell_exec("lscpu | grep 'CPU MHz' | awk '{print $3}'")) . " MHz";

/* 4. CPU (NÚCLEOS, HILOS, CARGA) */
$data['cpu_cores'] = clean(shell_exec("nproc"));
$data['cpu_threads_per_core'] = clean(shell_exec("lscpu | grep 'Thread(s) per core' | awk '{print $4}'"));
$load = sys_getloadavg();
$data['cpu_load'] = $load ? "{$load[0]}, {$load[1]}, {$load[2]}" : "N/A";

/* 6. RED (INTERFACES, MAC, ESTADO) */
$interfaces = explode("\n", trim(shell_exec("ls /sys/class/net")));
$netinfo = [];

foreach ($interfaces as $iface) {
    if ($iface === "lo") continue;

    $mac = clean(shell_exec("cat /sys/class/net/$iface/address"));
    $state = clean(shell_exec("cat /sys/class/net/$iface/operstate"));

    $speed_file = "/sys/class/net/$iface/speed";
    $speed = file_exists($speed_file)
        ? clean(shell_exec("cat $speed_file")) . " Mbps"
        : "N/A";

    $netinfo[$iface] = [
        "mac" => $mac,
        "state" => $state,
        "speed" => $speed
    ];
}

$data['network_interfaces'] = $netinfo;

/* 5. DISCO */
$disk_name = trim(shell_exec("lsblk -no NAME | grep -E 'sd|nvme' | head -n 1"));
if ($disk_name) {
    $model = clean(shell_exec("lsblk -dno MODEL /dev/$disk_name")) ?: "Unknown";
    $rota = trim(shell_exec("cat /sys/block/$disk_name/queue/rotational 2>/dev/null"));
    $type = ($rota === "0") ? "SSD" : "HDD";
    $data['disk'] = "$model ($type)";
} else {
    $data['disk'] = "Virtual/Unknown";
}


$data['network_interfaces'] = $netinfo;

/* 7. INFORMACIÓN DOCKER */
$data['docker_container_id'] = clean(shell_exec("cat /etc/hostname"));
$data['docker_cgroup'] = clean(shell_exec("cat /proc/self/cgroup | head -n 1"));

echo json_encode($data, JSON_PRETTY_PRINT);
