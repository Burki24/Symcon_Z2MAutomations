<?php

declare(strict_types=1);

namespace Z2MAutomations {
    function IPS_GetKernelVersion(): string
    {
        return $GLOBALS['visualizationKernelVersion'];
    }
}

namespace {
    require __DIR__ . '/stubs/autoload.php';
    IPS\Kernel::reset();
    $library = json_decode(file_get_contents(dirname(__DIR__) . '/library.json'), true, 16, JSON_THROW_ON_ERROR);
    IPS\ModuleLoader::loadSingleModule(dirname(__DIR__) . '/Z2MAutomations', $library['id']);
    foreach (['9.0' => 1, '9.1' => 2, '10.0' => 2] as $version => $expected) {
        $GLOBALS['visualizationKernelVersion'] = $version;
        $id = IPS_CreateInstance('{23D57681-8424-48DC-9FF0-25C110C533A9}');
        if (IPS_GetInstance($id)['InstanceVisualizationType'] !== $expected) throw new RuntimeException('Create must select the supported native HTML mode.');
        IPS_ApplyChanges($id);
        if (IPS_GetInstance($id)['InstanceVisualizationType'] !== $expected) throw new RuntimeException('Updates must refresh the supported native HTML mode.');
        $module = IPS\InstanceManager::getInstanceInterface($id);
        $shell = $module->GetVisualizationTile();
        if (!str_contains($shell, '"canMaximize":' . ($expected === 2 ? 'true' : 'false'))) throw new RuntimeException('Only 9.1+ may offer native HTML maximization.');
    }
    echo "Native HTML mode: 9.0 embedded tile, 9.1 and newer HTML fullscreen passed.\n";
}
