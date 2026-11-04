<?php

declare(strict_types=1);

$module->Seed(['synthetic/retained-peer']);
$module->ReadyForCreation();
$scheduleToken = explode('#', $module->OpenAutomationDesigner())[1];
$scheduleBefore = json_decode($module->GetDesignerData(), true)['configuration'];
$schedulePackets = count(IPS\InstanceManager::getInstanceInterface($parent)->sent);
$scheduleCounter = 0;
$schedulePost = static function (mixed $source, ?string $context = null) use ($module, $scheduleToken, &$scheduleCounter): array
{
    $view = json_decode($module->GetDesignerData(), true);
    $module->requestBody = json_encode(['requestId' => 'abababab-abab-4bab-8bab-' . sprintf('%012d', ++$scheduleCounter),
        'context'                                   => $context ?? $view['createContext'], 'name' => 'Synthetic scheduled creation', 'source' => $source,
        'target'                                    => ['device' => '0x0000000000000001', 'property' => 'state', 'value' => 'ON']]);
    return $module->Hook('create', 'POST', $scheduleToken);
};
$clockSource = ['type' => 'time', 'times' => ['07:15', '21:30:00'], 'timezone' => 'UTC'];
$check($schedulePost($clockSource)[0] === 200, 'Browser creation accepts clock times without requiring a source device');
$scheduleView = json_decode($module->GetDesignerData(), true);
$check(in_array('Europe/Berlin', $scheduleView['timezones'], true) && in_array('UTC', $scheduleView['timezones'], true), 'Creation offers the timezones accepted by native validation');
$clockRule = $scheduleView['configuration']['automations'][2];
$check(!$clockRule['enabled'] && $clockRule['triggers'] === [
    ['type' => 'time', 'at' => '07:15:00', 'timezone' => 'UTC'], ['type' => 'time', 'at' => '21:30:00', 'timezone' => 'UTC']]
    && $clockRule['conditions'] === [] && $clockRule['actions'][0]['values'] === ['state' => 'ON'], 'Clock creation preserves every time and uses native action semantics in a disabled rule');
$scheduleFingerprint = $module->Fingerprint();
$check($module->Hook('create', 'POST', $scheduleToken)[0] === 200 && $module->Fingerprint() === $scheduleFingerprint, 'Clock creation retries cannot duplicate a rule');
$oldScheduleContext = $scheduleView['createContext'];
$sunSource = ['type' => 'sun', 'event' => 'sunrise', 'latitude' => 0, 'longitude' => 0, 'offset_minutes' => -30, 'timezone' => 'Europe/Berlin'];
$check($schedulePost($sunSource)[0] === 200, 'Sunrise creation accepts zero coordinates and a negative offset');
$sunSource['event'] = 'sunset';
$sunSource['offset_minutes'] = 45;
$sunSource['latitude'] = 12.3456789;
$sunSource['longitude'] = -98.7654321;
$check($schedulePost($sunSource)[0] === 200, 'Sunset creation accepts a positive offset');
$scheduled = json_decode($module->GetDesignerData(), true)['configuration'];
$check(array_slice($scheduled['automations'], 0, 2) === $scheduleBefore['automations']
    && $scheduled['sequences'] === $scheduleBefore['sequences'] && $scheduled['mesh_peers'] === $scheduleBefore['mesh_peers'], 'Scheduled creation retains original rules, shared sequences and peer permissions');
$check($scheduled['automations'][3]['triggers'][0]['offset_minutes'] === -30 && $scheduled['automations'][4]['triggers'] === [$sunSource]
    && !$scheduled['automations'][3]['enabled'] && !$scheduled['automations'][4]['enabled'], 'Solar event, exact signed offset and disabled state survive local creation');
$stableSchedule = $module->Fingerprint();
$badSchedules = [null, [], ['type'                                                                        => 'unknown'], array_replace($clockSource, ['times' => []]), array_replace($clockSource, ['times' => array_fill(0, 9, '07:15')]),
    array_replace($clockSource, ['times' => ['07:15', '07:15:00']]), array_replace($clockSource, ['times' => ['24:00']]),
    array_replace($clockSource, ['times' => [null]]), array_replace($clockSource, ['times'                => ['first' => '07:15']]),
    array_replace($clockSource, ['timezone' => 'Invalid/Zone']), array_replace($clockSource, ['timezone'  => null]),
    $clockSource + ['enabled' => true], array_replace($sunSource, ['event'                                => 'noon']), array_replace($sunSource, ['latitude' => 91]),
    array_replace($sunSource, ['longitude' => -181]), array_replace($sunSource, ['latitude'               => null]), array_replace($sunSource, ['latitude' => '0']),
    array_replace($sunSource, ['offset_minutes' => 721]), array_replace($sunSource, ['offset_minutes'     => -721]),
    array_replace($sunSource, ['offset_minutes' => 0.5]), array_replace($sunSource, ['offset_minutes'     => null]),
    array_diff_key($sunSource, ['longitude' => true]), $sunSource + ['device'                             => '0x0000000000000001']];
foreach ($badSchedules as $badSchedule) {
    $response = $schedulePost($badSchedule);
    $check($response[0] === 422 && $module->Fingerprint() === $stableSchedule, 'Invalid schedules never modify an existing draft');
}
$check($schedulePost($clockSource, $oldScheduleContext)[0] === 409 && $module->Fingerprint() === $stableSchedule, 'Scheduled creation preserves optimistic context checks');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 1);
$check($schedulePost($clockSource)[0] === 422 && $module->Fingerprint() === $stableSchedule, 'Scheduled actions require current Symcon switching rights');
IPS_SetVariableCustomAction(IPS_GetObjectIDByIdent('state', $deviceID), 0);
$scheduleDebug = implode("\n", $module->debug);
$check(str_contains($scheduleDebug, 'Sonnenzeit') && str_contains($scheduleDebug, 'Uhrzeit')
    && str_contains($scheduleDebug, 'ZeitversatzMinuten') && !str_contains($scheduleDebug, '12.3456789') && !str_contains($scheduleDebug, '-98.7654321')
    && !str_contains($scheduleDebug, 'Synthetic scheduled creation') && !str_contains($scheduleDebug, $scheduleToken), 'German creation debug identifies schedule types and offset without coordinates, names or capabilities');
$check(count(IPS\InstanceManager::getInstanceInterface($parent)->sent) === $schedulePackets, 'Clock and solar creation never send MQTT or runtime commands');
unset($schedulePost, $badSchedules, $scheduleDebug);
