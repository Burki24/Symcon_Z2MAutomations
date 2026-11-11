# ADR 0038: Evaluate device conditions in the owning Z2M system

- Date: 2026-11-11
- Status: Implemented for the desktop editor and shared extension; live acceptance pending.

## Decision

Extend schema 1 additively with an optional `system` base topic on
`device_value` conditions. Device triggers remain local. Existing documents
retain their meaning. The canonical schema and extension remain in this
repository; the desktop app consumes their verified artifacts. The Symcon
forms and pinned designer are not changed by this task.

Reuse the existing MQTT mesh and its broker ACL trust boundary. Distinct brokers
need an independently configured bridge for `z2ma/mesh/#`. Both configurations
declare each other in `mesh_peers`; incoming reads require an active receiver.
The application need not remain open.

The source sends a bounded `condition` request with a random transaction ID and
the local condition body. The owning extension validates that body, resolves
the exact local IEEE and compares the requested property against its current
Z2M state cache. It returns only `condition_result`, status and a boolean,
never a device-state payload. This is a fresh evaluation of cached state, not
a physical device read or proof of measurement age.

Results must match the requested source, transaction and response phase within
the existing four-second timeout. Retained messages are rejected. There is no
retry, retained publication or application-side condition cache. Generation
and run cancellation checks prevent a late answer from reviving a stopped run.
Unknown values also fail `ne` comparisons. Conditions are reevaluated before
each action unless marked `start_only`; local conditions are rechecked after
remote latency. Retrigger checks do not overlap within one run.

## Evidence and compatibility

Synthetic tests exercise equal IEEE addresses on different systems, changing
remote values during a wait, start-only and one-shot runs, missing grants,
unknown values, stopped receivers, timeouts, retained/mismatched replies and
late replies after stop. Existing action and runtime suites remain required.
Older extensions reject the new field or do not answer the new phase, so both
ends must be updated explicitly before using remote conditions. No running
extension, broker configuration or real device was changed during implementation.
