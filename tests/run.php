<?php

declare(strict_types=1);

foreach (['check_encoding.php', 'validate_structure.php', 'module.php', 'multisystem.php', 'connection.php', 'connection-status.php', 'workspace.php', 'designer.php', 'mqtt.php', 'health-feedback.php', 'status-refresh.php', 'extension-build.php', 'gateway-topics.php', 'form-size.php', 'runtime-debug-contract.php', 'execution-contract.php', 'editor-work.php', 'editor-calendar.php', 'editor-retrigger.php', 'editor-commands.php', 'module-extension.php'] as $test) {
    $process = proc_open([PHP_BINARY, __DIR__ . '/' . $test], [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__));
    if (!is_resource($process) || proc_close($process) !== 0) {
        exit(1);
    }
}

foreach ([['node', '--check', __DIR__ . '/../Z2MAutomations/visualization/reader.js'], ['node', '--check', __DIR__ . '/../Z2MAutomations/visualization/creator.js'], ['node', '--check', __DIR__ . '/../Z2MAutomations/visualization/editor.js'], ['node', '--check', __DIR__ . '/../Z2MAutomations/visualization/designer.js'], ['node', __DIR__ . '/build-extension.mjs', '--check'], ['node', '--test', __DIR__ . '/designer-creation.test.mjs', __DIR__ . '/designer-editing.test.mjs', __DIR__ . '/extension.test.mjs', __DIR__ . '/extension-build.test.mjs', __DIR__ . '/runtime.test.mjs', __DIR__ . '/mesh.test.mjs', __DIR__ . '/debug.test.mjs', __DIR__ . '/solar.test.mjs']] as $command) {
    $process = proc_open($command, [STDIN, STDOUT, STDERR], $pipes, dirname(__DIR__));
    if (!is_resource($process) || proc_close($process) !== 0) {
        exit(1);
    }
}

echo "All offline checks passed.\n";
