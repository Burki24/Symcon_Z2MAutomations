<?php

declare(strict_types=1);

namespace Z2MAutomations;

/** Browser-only presentation metadata. Native text and technical data stay unchanged. */
trait DesignerPresentation
{
    private function DesignerLanguageRequest(): array
    {
        $failure = $this->DesignerWriteHeaders();
        if ($failure !== null) return $failure;
        return $this->WithTransportLock(function (): array
        {
            if (!$this->DesignerTokenAllowed($_SERVER['HTTP_X_Z2MA_READ_TOKEN'] ?? '')) return [403, ['ok' => false]];
            $body = $this->DesignerRequestBody();
            if (strlen($body) > 256) return [400, ['ok' => false]];
            try {
                $data = json_decode($body, true, 4, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return [400, ['ok' => false]];
            }
            if (!is_array($data) || count($data) !== 3 || !in_array($data['language'] ?? null, ['de', 'en'], true)
                || !in_array($data['previousLanguage'] ?? null, ['de', 'en'], true) || !is_bool($data['openEditor'] ?? null)) return [400, ['ok' => false]];
            $this->DebugEvent(
                'AKTION',
                'Designersprache lokal gewechselt. Offene Eingaben und Auswahl bleiben erhalten; kein Entwurf geändert und keine Z2M-Anfrage gesendet.',
                ['Sprache' => $data['language'], 'VorherigeSprache' => $data['previousLanguage'], 'BearbeitungOffen' => $data['openEditor']]
            );
            return [200, ['ok' => true]];
        });
    }

    private function DesignerPresentationBody(string $body): string
    {
        $data = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data)) return $body;
        $catalog = json_decode(file_get_contents(__DIR__ . '/../Z2MAutomations/visualization/locales/messages.json'), true, 16, JSON_THROW_ON_ERROR);
        $exact = [];
        $patterns = [];
        foreach ($catalog as $key => $sources) {
            foreach ($sources as $source) {
                if (preg_match('/%(?:\d+\$)?[sdifu]|\{\{[A-Za-z][A-Za-z0-9_]*\}\}/', $source) === 1) {
                    $literal = preg_replace('/%(?:\d+\$)?[sdifu]|\{\{[A-Za-z][A-Za-z0-9_]*\}\}/', '', $source);
                    if (preg_match_all('/\p{L}/u', $literal) < 4) continue;
                    $quoted = preg_quote($source, '~');
                    $names = [];
                    $pattern = preg_replace_callback('/%(?:\d+\\\\\$)?[sdifu]|\\\\\{\\\\\{([A-Za-z][A-Za-z0-9_]*)\\\\\}\\\\\}/', static function (array $match) use (&$names): string
                    {
                        $names[] = $match[1] ?? 'value' . (count($names) + 1);
                        return '(.{0,2048}?)';
                    }, $quoted);
                    $patterns[] = ['pattern' => '~\A' . $pattern . '\z~usD', 'names' => $names, 'key' => $key];
                } else {
                    $exact[$source] = $key;
                }
            }
        }
        $messages = [];
        $walk = function (array $node, array $path = []) use (&$walk, &$messages, $exact, $patterns): void
        {
            if (count($path) > 10 || count($messages) >= 256) return;
            foreach ($node as $field => $value) {
                if (count($messages) >= 256) return;
                if (in_array($field, ['configuration', 'name', 'sceneNames', 'users'], true)) continue;
                $next = [...$path, $field];
                if (is_array($value)) {
                    $walk($value, $next);
                    continue;
                }
                if (!is_string($value) || $value === '' || strlen($value) > 4096
                    || !in_array($field, ['message', 'notice', 'assessment', 'lastError', 'details', 'blocked', 'createNotice', 'editNotice', 'sequenceNotice', 'healthText', 'InstalledExtensionInfo', 'BundledExtensionInfo', 'ExtensionUpdateInfo'], true)) continue;
                if ($field === 'details' && ($localized = $this->DesignerDetailedPresentation($node)) !== null) {
                    $messages[] = ['path' => $next, 'key' => 'validation.details', 'values' => new \stdClass(), 'localizedValues' => ['detail' => $localized]];
                    continue;
                }
                if (isset($exact[$value])) {
                    $messages[] = ['path' => $next, 'key' => $exact[$value], 'values' => new \stdClass()];
                    continue;
                }
                foreach ($patterns as $entry) {
                    if (preg_match($entry['pattern'], $value, $match) !== 1) continue;
                    $messages[] = ['path' => $next, 'key' => $entry['key'], 'values' => array_combine($entry['names'], array_slice($match, 1))];
                    break;
                }
            }
        };
        $walk($data);
        $data['presentation'] = ['version' => 1, 'messages' => $messages];
        return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function DesignerDetailedPresentation(array $view): ?array
    {
        if (!isset($view['systemId'], $view['operation'], $view['context'])) return null;
        $job = $this->ReadWorkspaceTransfers()['systems'][$view['systemId']] ?? null;
        if (!is_array($job['details'] ?? null) || ($job['request']['operation'] ?? '') !== $view['operation']
            || WorkspaceState::Fingerprint($job) !== $view['context']) return null;
        $translations = json_decode(file_get_contents(__DIR__ . '/../Z2MAutomations/locale.json'), true, 16, JSON_THROW_ON_ERROR)['translations']['de'];
        $details = $job['details'] + ['names' => $job['names']];
        return ['en' => ValidationFeedback::Text($details, static fn (string $text): string => $text),
            'de'     => ValidationFeedback::Text($details, static fn (string $text): string => $translations[$text] ?? $text)];
    }
}
