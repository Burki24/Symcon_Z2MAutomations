<?php

declare(strict_types=1);

namespace Z2MAutomations;

use stdClass;

final class ValidationFeedback
{
    private const FIELDS = ['restart_policy' => 'After a Zigbee2MQTT restart', 'sequences'      => 'Reusable action sequences', 'sequence_id' => 'Action sequence', 'schema_version' => 'File version', 'revision' => 'Revision', 'automations' => 'Automations', 'mesh_peers' => 'Permitted partner systems',
        'id'                                 => 'Identifier', 'name' => 'Automation name', 'enabled' => 'Rule enabled', 'triggers' => 'Trigger', 'conditions' => 'Condition', 'actions' => 'Action',
        'type'                               => 'Type', 'device' => 'Device', 'property' => 'Property or channel', 'operator' => 'Comparison', 'value' => 'Value', 'for_seconds' => 'Minimum duration (s)',
        'on_change'                          => 'Only on value change', 'start_only' => 'Only at start', 'at' => 'Time', 'timezone' => 'Timezone', 'after' => 'Start of time window', 'before' => 'End of time window',
        'weekdays'                           => 'Weekdays', 'target' => 'Target device', 'system' => 'Partner system', 'values' => 'Action values', 'delay_seconds' => 'Wait before step (s)',
        'event'                              => 'Solar event', 'latitude' => 'Latitude', 'longitude' => 'Longitude', 'offset_minutes' => 'Offset (minutes)',
        'scene_id'                           => 'Scene', 'endpoint' => 'Device channel'];
    private const REASONS = [
        'sequence_missing'              => 'This action sequence does not exist. Choose an existing sequence.',
        'sequence_nested'               => 'An action sequence cannot call another action sequence.',
        'sequence_limit'                => 'Including reusable sequences, an automation may execute at most 16 steps.',
        'sequence_delay'                => 'The wait before the sequence plus its first step must not exceed 86400 seconds.',
        'sequence_duplicate'            => 'This action sequence identifier is already used.', 'required' => 'This entry is missing.', 'type' => 'The value has the wrong type (for example text instead of a number).',
        'number'                        => 'Enter a number here.', 'integer' => 'Enter a whole number here.', 'boolean' => 'Choose enabled or disabled here.',
        'string'                        => 'Enter text here.', 'array' => 'A list of entries is required here.', 'object' => 'A group of settings is required here.',
        'enum'                          => 'This selection is not supported.', 'const' => 'This selection is not supported.', 'pattern' => 'The format is invalid.',
        'minimum'                       => 'The value must be at least %s.', 'maximum' => 'The value must not exceed %s.',
        'minItems'                      => 'At least %s entries are required.', 'maxItems' => 'At most %s entries are allowed.',
        'minLength'                     => 'At least %s characters are required.', 'maxLength' => 'At most %s characters are allowed.',
        'minProperties'                 => 'At least %s entries are required.', 'maxProperties' => 'At most %s entries are allowed.',
        'additionalProperties'          => 'An additional entry here is not supported.', 'uniqueItems' => 'An entry occurs more than once.',
        'not'                           => 'This entry is not permitted.', 'discriminator' => 'The type is missing or not supported.',
        'duplicate_id'                  => 'This identifier is already used by another automation.', 'timezone' => 'This timezone is not recognised.',
        'time_window'                   => 'The start and end of the time window must be different.',
        'remote_permission'             => 'The partner system for this action is not permitted. Select the partner module and save the draft again.',
        'yaml_syntax'                   => 'The YAML could not be read. Check its syntax and indentation.',
        'scene_unavailable'             => 'This scene is no longer available on the selected device or group. Reload the device data and select an existing scene.'];
    private const LIMIT_REASONS = ['minimum', 'maximum', 'minItems', 'maxItems', 'minLength', 'maxLength', 'minProperties', 'maxProperties'];

    public static function Normalize(mixed $data): ?array
    {
        if (!$data instanceof stdClass || !is_bool($data->truncated ?? null) || !is_array($data->issues ?? null)
            || count($data->issues) < 1 || count($data->issues) > 20) {
            return null;
        }
        $issues = [];
        foreach ($data->issues as $issue) {
            if (!$issue instanceof stdClass || !is_string($issue->path ?? null) || strlen($issue->path) > 180
                || !is_string($issue->reason ?? null) || !isset(self::REASONS[$issue->reason])) {
                return null;
            }
            foreach (explode('/', ltrim($issue->path, '/')) as $part) {
                if ($part !== '' && !isset(self::FIELDS[$part]) && preg_match('/\A(?:0|[1-9][0-9]{0,2})\z/D', $part) !== 1) {
                    return null;
                }
            }
            if ($issue->path !== '' && !str_starts_with($issue->path, '/')) {
                return null;
            }
            $item = ['path' => $issue->path, 'reason' => $issue->reason];
            if (in_array($issue->reason, self::LIMIT_REASONS, true)) {
                if (!is_int($issue->limit ?? null) || abs($issue->limit) > 9007199254740991) {
                    return null;
                }
                $item['limit'] = $issue->limit;
            }
            foreach (['line', 'column'] as $position) {
                if (property_exists($issue, $position)) {
                    if ($issue->reason !== 'yaml_syntax' || !is_int($issue->{$position}) || $issue->{$position} < 1 || $issue->{$position} > 1000000) {
                        return null;
                    }
                    $item[$position] = $issue->{$position};
                }
            }
            $issues[] = $item;
        }
        return ['issues' => $issues, 'truncated' => $data->truncated];
    }

    public static function Names(string $yaml): array
    {
        $document = json_decode($yaml, true, 32);
        $names = [];
        foreach (is_array($document['automations'] ?? null) ? array_slice($document['automations'], 0, 100) : [] as $index => $rule) {
            $name = $rule['name'] ?? null;
            if (is_string($name) && strlen($name) <= 480 && preg_match('/\A[^\x00-\x1f\x7f]{1,120}\z/uD', $name) === 1) {
                $names[$index] = $name;
            }
        }
        foreach (is_array($document['sequences'] ?? null) ? array_slice($document['sequences'], 0, 32) : [] as $index => $sequence) {
            $name = $sequence['name'] ?? null;
            if (is_string($name) && strlen($name) <= 480 && preg_match('/\A[^\x00-\x1f\x7f]{1,120}\z/uD', $name) === 1) {
                $names['sequences'][$index] = $name;
            }
        }
        return $names;
    }

    public static function Text(array $details, callable $translate): string
    {
        $lines = [$translate('Z2M rejected the draft. Correct the following entries:')];
        foreach ($details['issues'] as $issue) {
            $parts = $issue['path'] === '' ? [] : explode('/', substr($issue['path'], 1));
            $location = [];
            for ($i = 0; $i < count($parts); $i++) {
                $part = $parts[$i];
                $label = $translate($part === 'name' && ($parts[0] ?? '') === 'sequences' ? 'Action sequence name' : (self::FIELDS[$part] ?? 'Entry'));
                if (isset($parts[$i + 1]) && ctype_digit($parts[$i + 1])) {
                    $index = (int) $parts[++$i];
                    $label = ($part === 'automations' ? $translate('Automation') : $label) . ' ' . ($index + 1);
                    if ($part === 'sequences' && isset($details['names']['sequences'][$index])) {
                        $label .= ' „' . $details['names']['sequences'][$index] . '“';
                    }
                    if ($part === 'automations' && isset($details['names'][$index])) {
                        $label .= ' „' . $details['names'][$index] . '“';
                    }
                }
                $location[] = $label;
            }
            if (isset($issue['line'])) {
                $location[] = sprintf($translate('Line %d'), $issue['line']);
                if (isset($issue['column'])) {
                    $location[] = sprintf($translate('Column %d'), $issue['column']);
                }
            }
            $lines[] = '• ' . ($location === [] ? $translate('Automation file') : implode(' → ', $location)) . ': ' . self::Reason($issue, $translate);
        }
        if ($details['truncated']) {
            $lines[] = $translate('Only the first 20 problems are shown. Correct them and validate again.');
        }
        return implode("\n", $lines);
    }

    public static function Reason(array $issue, callable $translate): string
    {
        $reason = $translate(self::REASONS[$issue['reason']]);
        return isset($issue['limit']) ? sprintf($reason, (string) $issue['limit']) : $reason;
    }
}
