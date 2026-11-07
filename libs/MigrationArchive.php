<?php

declare(strict_types=1);

namespace Z2MAutomations;

use JsonException;
use RuntimeException;

/** Private, bounded, atomic files. Paths are never supplied by an API caller. */
final class MigrationArchive
{
    public function __construct(private string $directory)
    {
        if (!is_dir($directory) && !mkdir($directory, 0700, true)) throw new RuntimeException('archive_failed');
        if (is_link($directory) || !is_writable($directory)) throw new RuntimeException('archive_failed');
    }

    public function Write(string $key, array $data, bool $immutable = false): void
    {
        $path = $this->Path($key);
        if ($immutable && is_file($path)) {
            if ($this->Read($key) !== $data) throw new RuntimeException('archive_conflict');
            return;
        }
        $payload = json_encode($data, WorkspaceState::JSON_FLAGS);
        if (strlen($payload) > WorkspaceState::MAX_BYTES) throw new RuntimeException('archive_failed');
        $text = json_encode(['format' => 'Z2MA.local-migration', 'version' => 1, 'checksum' => hash('sha256', $payload), 'payload' => $payload], WorkspaceState::JSON_FLAGS);
        $temporary = tempnam($this->directory, '.write-');
        if ($temporary === false) throw new RuntimeException('archive_failed');
        try {
            if (!chmod($temporary, 0600)) throw new RuntimeException('archive_failed');
            $file = fopen($temporary, 'wb');
            if ($file === false) throw new RuntimeException('archive_failed');
            try {
                if (fwrite($file, $text) !== strlen($text) || !fflush($file) || !fsync($file)) throw new RuntimeException('archive_failed');
            } finally {
                fclose($file);
            }
            if (!rename($temporary, $path)) throw new RuntimeException('archive_failed');
        } finally {
            if (is_file($temporary)) unlink($temporary);
        }
        if ($this->Read($key) !== $data) throw new RuntimeException('archive_failed');
    }

    public function Read(string $key): array
    {
        $path = $this->Path($key);
        if (!is_file($path) || is_link($path) || filesize($path) > WorkspaceState::MAX_BYTES * 2) throw new RuntimeException('archive_failed');
        try {
            $envelope = json_decode(file_get_contents($path), true, 4, JSON_THROW_ON_ERROR);
            if (!is_array($envelope) || ($envelope['format'] ?? null) !== 'Z2MA.local-migration' || ($envelope['version'] ?? null) !== 1
                || !is_string($envelope['payload'] ?? null) || strlen($envelope['payload']) > WorkspaceState::MAX_BYTES
                || !is_string($envelope['checksum'] ?? null)
                || !hash_equals(hash('sha256', $envelope['payload']), $envelope['checksum'])) throw new RuntimeException('archive_failed');
            $data = json_decode($envelope['payload'], true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new RuntimeException('archive_failed');
        }
        if (!is_array($data)) throw new RuntimeException('archive_failed');
        return $data;
    }

    public function Exists(string $key): bool
    {
        return file_exists($this->Path($key));
    }

    private function Path(string $key): string
    {
        if (preg_match('/\A[a-z0-9-]{1,100}\z/D', $key) !== 1) throw new RuntimeException('invalid_request');
        return $this->directory . DIRECTORY_SEPARATOR . $key . '.json';
    }
}
