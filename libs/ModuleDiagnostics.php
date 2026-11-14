<?php

declare(strict_types=1);

namespace Z2MAutomations;

trait ModuleDiagnostics
{
    private function TraceModuleCall(string $function, int $line, callable $callback): mixed
    {
        $this->editorActionVariables = null;
        $this->editorGroupBindings = [];
        $this->gatewayZ2MInstances = null;
        $this->peerActionCatalog = null;
        $void = in_array($function, ['RefreshWorkspaceDrafts', 'RequestAction', 'RefreshWorkspaceDeviceCatalogs', 'ProcessHookData', 'ConfigureEditorWeekdays', 'ConfigureSequenceCall', 'SelectExecutionRule', 'RefreshExecutionStatus', 'ConfigureEditorSolar', 'ExpireRuntimeDebug', 'RefreshStatus', 'Create', 'ApplyChanges', 'MessageSink', 'ExpireRequest', 'SelectEditorDevice', 'ShowYamlEditor', 'ConfigureEditorSchedule', 'ConfigureEditorItem', 'SelectEditorConditionProperty'], true);
        $results = in_array($function, ['InspectEditorTimes', 'FilterAutomations', 'CopyAutomation', 'TrackEditorInput', 'NavigateEditor', 'ResolveEditorNavigation', 'EditorWorkReady', 'SelectReusableSequence', 'SaveReusableSequence', 'DeleteReusableSequence', 'InspectReusableSequenceActions', 'ScanEventImports', 'PreviewEventImport', 'ImportSymconEvent', 'InspectEditorConditions', 'InspectEditorTriggers', 'OpenAutomationEditor', 'CreateSimpleAutomation', 'SelectAutomation', 'UpdateAutomation', 'DeleteAutomation', 'SaveScheduledAutomation', 'InspectEditorActions'], true)
            ? ['Editoraktion abgelehnt; Grund siehe Ablauf.', 'Editoraktion abgeschlossen; Z2M noch unverändert.'] : null;
        return $this->DebugFlowCall($function, $line, fn (): mixed => $this->WithOwnershipEntry($function, $callback), $void, [], $results);
    }

    private function DebugEvent(string $event, string $message, array $details = []): void
    {
        $source = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $this->DebugFlowEvent($source[1]['function'] ?? __FUNCTION__, $source[0]['line'] ?? __LINE__, $event, $message, $details);
    }
}
