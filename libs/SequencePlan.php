<?php

declare(strict_types=1);

namespace Z2MAutomations;

use InvalidArgumentException;

final class SequencePlan
{
    public static function Expand(array $document, array $actions): array
    {
        $plan = [];
        foreach ($actions as $position => $action) {
            if (($action['type'] ?? '') !== 'sequence') {
                $plan[] = ['action' => $action, 'source_index' => $position];
            } else {
                $sequence = null;
                foreach ($document['sequences'] ?? [] as $index => $entry) {
                    if ($entry['id'] === ($action['sequence_id'] ?? null)) {
                        $sequence = $entry;
                        break;
                    }
                }
                if ($sequence === null) {
                    throw new InvalidArgumentException('sequence_missing');
                }
                foreach ($sequence['actions'] as $inner => $leaf) {
                    if (($leaf['type'] ?? '') === 'sequence') {
                        throw new InvalidArgumentException('sequence_nested');
                    }
                    $delay = ($leaf['delay_seconds'] ?? 0) + ($inner === 0 ? ($action['delay_seconds'] ?? 0) : 0);
                    if ($delay > 86400) {
                        throw new InvalidArgumentException('sequence_delay');
                    }
                    $leaf['delay_seconds'] = $delay;
                    $plan[] = ['action' => $leaf, 'source_index' => $position, 'sequence' => $index + 1, 'sequence_step' => $inner + 1];
                }
            }
            if (count($plan) > 16) {
                throw new InvalidArgumentException('sequence_limit');
            }
        }
        return $plan;
    }

    public static function Error(array $document): string
    {
        $sequences = array_key_exists('sequences', $document) ? $document['sequences'] : [];
        if (!is_array($sequences) || !array_is_list($sequences) || count($sequences) > 32) {
            return 'sequence_invalid';
        }
        $ids = [];
        foreach ($sequences as $sequence) {
            if (!is_array($sequence) || count($sequence) !== 3 || !is_string($sequence['id'] ?? null)
                || preg_match('/\A[a-z][a-z0-9_-]{0,63}\z/D', $sequence['id']) !== 1 || isset($ids[$sequence['id']])
                || !is_string($sequence['name'] ?? null) || preg_match('/\A[^\x00-\x1f\x7f]{1,120}\z/uD', $sequence['name']) !== 1
                || !is_array($sequence['actions'] ?? null) || !array_is_list($sequence['actions']) || count($sequence['actions']) < 1 || count($sequence['actions']) > 16) {
                return 'sequence_invalid';
            }
            $ids[$sequence['id']] = true;
            foreach ($sequence['actions'] as $action) {
                if (!is_array($action) || !in_array($action['type'] ?? '', ['set', 'toggle', 'scene'], true)
                    || !is_array($action['target'] ?? null) || !in_array($action['target']['type'] ?? '', ['device', 'group'], true)
                    || !is_int($action['delay_seconds'] ?? 0) || ($action['delay_seconds'] ?? 0) < 0 || ($action['delay_seconds'] ?? 0) > 86400) {
                    return 'sequence_invalid';
                }
            }
        }
        try {
            foreach ($document['automations'] ?? [] as $rule) {
                if (!is_array($rule) || !is_array($rule['actions'] ?? null)) {
                    return 'sequence_invalid';
                }
                self::Expand($document, $rule['actions']);
            }
        } catch (InvalidArgumentException $error) {
            return $error->getMessage();
        }
        return '';
    }
}
