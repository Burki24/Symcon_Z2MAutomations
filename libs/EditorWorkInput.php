<?php

declare(strict_types=1);

namespace Z2MAutomations;

use InvalidArgumentException;
use Traversable;

/** Only editable values enter the work snapshot, never the complete form or configuration. */
final class EditorWorkInput
{
    public const FIELDS = ['EditorName', 'EditorEnabled', 'EditorTimeMode', 'EditorAt', 'EditorTimes', 'EditorTimezone', 'EditorLatitude', 'EditorLongitude', 'EditorSolarOffset',
        'EditorWeekdays', 'EditorWeekdayTimezone', 'EditorDayMonday', 'EditorDayTuesday', 'EditorDayWednesday', 'EditorDayThursday', 'EditorDayFriday', 'EditorDaySaturday', 'EditorDaySunday',
        'EditorWindow', 'EditorAfter', 'EditorBefore', 'EditorWindowTimezone', 'EditorMonday', 'EditorTuesday', 'EditorWednesday', 'EditorThursday',
        'EditorFriday', 'EditorSaturday', 'EditorSunday', 'EditorActions', 'EditorTriggers', 'EditorConditionList'];

    /** Native form scripts execute outside the module's class-loading context. */
    public static function Callback(string $deletedList = ''): string
    {
        $names = array_map(static fn (string $field): string => preg_quote(substr($field, 6), '/'), array_merge(self::FIELDS, ['EditorRule', 'EditorEditToken']));
        $pattern = '/\A(?:(?:Device|Time|Sequence)?Editor(?:' . implode('|', $names) . ')|Sequence(?:WorkID|EditorID|EditorToken))\z/D';
        return strtr(<<<'PHP'
json_encode(['_form' => array_map(static fn ($v) => $v instanceof \Traversable ? iterator_to_array($v) : $v, array_filter(get_defined_vars(), static fn ($k) => preg_match(%PATTERN%, $k), ARRAY_FILTER_USE_KEY)), '_deleted' => %DELETED%], JSON_THROW_ON_ERROR)
PHP, ['%PATTERN%' => var_export($pattern, true), '%DELETED%' => var_export($deletedList, true)]);
    }

    public static function Capture(array $variables, string $DeletedList = ''): string
    {
        $result = ['_deleted' => $DeletedList];
        foreach (['device' => 'Device', 'time' => 'Time', 'advanced' => ''] as $area => $prefix) {
            if (!array_key_exists($prefix . 'EditorName', $variables)) {
                continue;
            }
            $values = [];
            foreach (self::FIELDS as $field) {
                if (array_key_exists($prefix . $field, $variables)) {
                    $value = $variables[$prefix . $field];
                    $values[$field] = $value instanceof Traversable ? iterator_to_array($value) : $value;
                }
            }
            $values['_id'] = $variables[$prefix . 'EditorRule'] ?? '';
            $values['_token'] = $variables[$prefix . 'EditorEditToken'] ?? '';
            $result[$area] = $values;
        }
        if (array_key_exists('SequenceEditorName', $variables)) {
            $rows = $variables['SequenceEditorActions'] ?? [];
            $result['sequence'] = ['EditorName' => $variables['SequenceEditorName'],
                'EditorActions'                 => $rows instanceof Traversable ? iterator_to_array($rows) : $rows,
                '_id'                           => $variables['SequenceWorkID'] ?? ($variables['SequenceEditorID'] ?? ''), '_token' => $variables['SequenceEditorToken'] ?? ''];
        }
        return json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    public static function Normalize(array $values, string $area): array
    {
        $keys = ['EditorName', 'EditorEnabled'];
        if ($area !== 'advanced') {
            $keys[] = 'EditorActions';
        }
        if ($area === 'device') {
            $keys[] = 'EditorTriggers';
        }
        if ($area === 'time') {
            $keys = array_merge($keys, ['EditorTimeMode', 'EditorTimezone']);
            $keys = array_merge($keys, ($values['EditorTimeMode'] ?? 'clock') === 'clock' ? ['EditorTimes'] : ['EditorLatitude', 'EditorLongitude', 'EditorSolarOffset']);
        }
        if (in_array($area, ['device', 'time'], true)) {
            $keys = array_merge($keys, ['EditorConditionList', 'EditorWindow']);
            $keys[] = 'EditorWeekdays';
            if ($values['EditorWeekdays'] ?? false) {
                $keys = array_merge($keys, ['EditorWeekdayTimezone', 'EditorDayMonday', 'EditorDayTuesday', 'EditorDayWednesday', 'EditorDayThursday',
                    'EditorDayFriday', 'EditorDaySaturday', 'EditorDaySunday']);
            }
            if ($values['EditorWindow'] ?? false) {
                $keys = array_merge($keys, ['EditorAfter', 'EditorBefore', 'EditorWindowTimezone', 'EditorMonday', 'EditorTuesday', 'EditorWednesday',
                    'EditorThursday', 'EditorFriday', 'EditorSaturday', 'EditorSunday']);
            }
        }
        if ($area === 'sequence') {
            $keys = ['EditorName', 'EditorActions'];
        }
        $result = [];
        foreach ($keys as $key) {
            $value = $values[$key] ?? (in_array($key, ['EditorActions', 'EditorTriggers', 'EditorConditionList', 'EditorTimes'], true) ? [] : '');
            if (in_array($key, ['EditorActions', 'EditorTriggers', 'EditorConditionList', 'EditorTimes'], true)) {
                if (!is_array($value) || count($value) > 16) {
                    throw new InvalidArgumentException('editor_work_input');
                }
                $rows = [];
                foreach ($value as $row) {
                    $row = (array) $row;
                    $clean = [];
                    foreach ($key === 'EditorTimes' ? ['At'] : ['Choice', 'Delay', 'Compare', 'Hold', 'Changed'] as $cell) {
                        if (array_key_exists($cell, $row)) {
                            if (!is_scalar($row[$cell]) || (is_string($row[$cell]) && strlen($row[$cell]) > 2048)) {
                                throw new InvalidArgumentException('editor_work_input');
                            }
                            $clean[$cell] = $row[$cell];
                            if ($cell === 'At' && is_string($clean[$cell]) && preg_match('/\A\d{2}:\d{2}\z/D', $clean[$cell])) {
                                $clean[$cell] .= ':00';
                            }
                        }
                    }
                    if ($key === 'EditorTriggers') {
                        $clean['Changed'] ??= false;
                    }
                    $rows[] = $clean;
                }
                $value = $rows;
            } elseif (!is_scalar($value) || (is_string($value) && strlen($value) > 1024)) {
                throw new InvalidArgumentException('editor_work_input');
            }
            if (in_array($key, ['EditorAt', 'EditorAfter', 'EditorBefore'], true) && is_string($value) && preg_match('/\A\d{2}:\d{2}\z/D', $value)) {
                $value .= ':00';
            }
            $result[$key] = $value;
        }
        return $result;
    }
}
