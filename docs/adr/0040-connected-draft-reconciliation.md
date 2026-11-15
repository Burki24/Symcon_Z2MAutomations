# ADR 0040: Connected draft reconciliation and portable files

- Date: 2026-11-15
- Status: Implemented; practical acceptance remains separate.

The shared browser/tile adapter requests a complete automation snapshot on the
first observed connection and after an observed reconnect. Presentation refreshes
alone do not repeat that read. Existing native ownership/setup remains explicit.
Reading does not stop/start execution, store automations or install extensions.

WorkspaceDraftSync compares stable rule/sequence identities against the local
document and a persistent `mergeBase` containing the accepted remote content.
Independent edits, additions and deletions merge automatically; divergent versions
and edit/delete cases require per-entry local/remote choices. Missing historical
content does not establish a common ancestor. A second complete read verifies the
same storage identity before committing locally. Open forms and binding/CAS
changes reject stale work. Old workspace records without mergeBase remain valid.
Transfer validation/storage is blocked while reconciliation is unresolved.
Unfinished working rules stay editable and portable; runtime validation remains
separate and mandatory before storage.

Every applied merge/import has a private MigrationArchive snapshot and a guarded
local undo. Whole-document replacement remains a compatibility API, while the UI
offers individual version choices. Lower remote revisions cannot silently roll
back local work. New imported rules stay disabled and imported partner declarations
cannot widen destination permissions.

The canonical UI and pure file containers remain in Z2M-Automations. PHP independently
enforces merge, ownership, bounded data and persistence contracts. No editable UI
fork, new helper or runtime/schema change is introduced.

`.z2mad` contains selected automations, required sequences, source-network identity
and, for complete exports, base content. Native `.z2map` snapshots contain local
drafts for all assigned networks, excluding native IDs/authority and broker secrets.
The shared importer accepts native snapshots and standalone project formats 1/2
(including `.z2ma`), and adds selected rules to an explicitly chosen existing target.
Import never creates, claims or reconfigures a native connection.

Native endpoint tests exercise actual authenticated merge/file requests, disabled
imports, unchanged grants, snapshots and undo. Shared capture-free browser/tile
tests exercise initial reads, both languages, rule selection, mapping and downloads.
