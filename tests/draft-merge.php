<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/DraftMerge.php';

use Z2MAutomations\DraftMerge;

$check = static function (bool $ok, string $message): void
{ if (!$ok) throw new RuntimeException($message); };
$rule = static fn (string $id, ?string $name = null): array => ['id' => $id, 'name' => $name ?? $id, 'enabled' => false, 'triggers' => [], 'conditions' => [], 'actions' => []];
$config = static fn (array ...$rules): array => ['schema_version' => 1, 'revision' => 1, 'automations' => $rules, 'sequences' => [], 'mesh_peers' => []];
$local = $config($rule('new'));
$remote = $config($rule('old'));
$remote['automations'][0]['enabled'] = true;
$plan = DraftMerge::Plan($local, $remote);
$check($plan['conflicts'] === [] && array_column($plan['configuration']['automations'], 'id') === ['new', 'old'], 'First read combines unsent drafts and remote rules.');
$check($plan['configuration']['automations'][0]['enabled'] === false && $plan['configuration']['automations'][1]['enabled'] === true, 'New drafts remain disabled and remote flags are preserved.');
$base = $config($rule('one'), $rule('two'), $rule('three'));
$plan = DraftMerge::Plan($config($rule('one', 'Local'), $rule('three')), $config($rule('one'), $rule('two'), $rule('three', 'Remote'), $rule('four')), $base);
$check($plan['conflicts'] === [] && array_column($plan['configuration']['automations'], 'name') === ['Local', 'Remote', 'four'], 'Independent edits, additions and deletions are reconciled against content.');
$plan = DraftMerge::Plan($config($rule('one', 'Local')), $config($rule('one', 'Remote')), $config($rule('one')));
$check(count($plan['conflicts']) === 1 && $plan['conflicts'][0]['key'] === 'automations:one', 'Divergent edits require a choice.');
$resolved = DraftMerge::Plan($config($rule('one', 'Local')), $config($rule('one', 'Remote')), $config($rule('one')), ['automations:one' => 'remote']);
$check($resolved['conflicts'] === [] && $resolved['configuration']['automations'][0]['name'] === 'Remote', 'Choices apply only to their exact stable identity.');
$check(count(DraftMerge::Plan($config(), $config($rule('one', 'Remote')), $config($rule('one')))['conflicts']) === 1, 'A deletion against an edited remote rule is a conflict.');
$check(count(DraftMerge::Plan($config($rule('one', 'Same')), $config($rule('two', 'Same')))['configuration']['automations']) === 2, 'Names never identify rules.');
$check(count(DraftMerge::Plan(array_replace($config(), ['mesh_peers' => ['a']]), array_replace($config(), ['mesh_peers' => ['b']]))['conflicts']) === 1, 'Partner permissions cannot be silently widened.');
echo "Draft merge: identities, additions, edits, deletions, unknown bases and permissions passed.\n";
$base = $config();
$base['sequences'] = [['id' => 'needed', 'name' => 'Needed', 'actions' => []]];
$local = $base;
$local['automations'] = [array_replace($rule('new'), ['actions' => [['type' => 'sequence', 'sequence_id' => 'needed']]])];
$plan = DraftMerge::Plan($local, $config(), $base);
$check(count($plan['conflicts']) === 1 && $plan['conflicts'][0]['key'] === 'sequences:needed' && $plan['conflicts'][0]['dependency'], 'A deleted dependency required by a retained automation requires a choice.');
$check(count(DraftMerge::Plan($local, $config(), $base, ['sequences:needed' => 'local'])['configuration']['sequences']) === 1, 'The local version keeps its needed sequence.');
