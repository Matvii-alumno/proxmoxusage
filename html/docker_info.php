<?php

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    $target = escapeshellarg($_POST['target']);

    switch ($action) {
        case 'start':
            exec("docker start $target 2>&1", $out, $ret);
            echo json_encode(["ok" => $ret === 0, "output" => $out]);
            exit;

        case 'stop':
            exec("docker stop $target 2>&1", $out, $ret);
            echo json_encode(["ok" => $ret === 0, "output" => $out]);
            exit;

        case 'remove_image':
    $containers_using = [];
    exec("docker ps -a --filter ancestor=$target --format '{{.ID}} {{.Names}}'", $containers_using);

    if (!empty($containers_using)) {
        // Hay contenedores usando la imagen → NO eliminar
        echo json_encode([
            "ok" => false,
            "reason" => "image_in_use",
            "containers" => $containers_using
        ]);
        exit;
    }

    // 2. Si no hay contenedores, eliminar la imagen
    exec("docker rmi $target 2>&1", $out, $ret);

    echo json_encode([
        "ok" => $ret === 0,
        "output" => $out
    ]);
    exit;

    }
}

header('Content-Type: application/json');

// Obtener contenedores
$containers = [];
exec("docker ps -a --format '{{json .}}'", $containers_raw);
foreach ($containers_raw as $line) {
    $containers[] = json_decode($line, true);
}

// Obtener stats
$stats = [];
exec("docker stats --no-stream --format '{{json .}}'", $stats_raw);
foreach ($stats_raw as $line) {
    $stats[] = json_decode($line, true);
}

// Obtener imágenes
$images = [];
exec("docker images --format '{{json .}}'", $images_raw);
foreach ($images_raw as $line) {
    $images[] = json_decode($line, true);
}

echo json_encode([
    "containers" => $containers,
    "stats" => $stats,
    "images" => $images
]);

