// Own bounded execution plan. Definitions contain leaf actions only, never calls.
export class SequenceError extends Error {
    constructor(reason, step) { super(reason); this.reason = reason; this.step = step; }
}

export function expandActionPlan(document, actions) {
    const plan = [];
    for (const [source_index, action] of actions.entries()) {
        if (action.type !== 'sequence') plan.push({action, source_index});
        else {
            const index = (document.sequences ?? []).findIndex(item => item.id === action.sequence_id);
            if (index < 0) throw new SequenceError('sequence_missing', source_index);
            for (const [inner, leaf] of document.sequences[index].actions.entries()) {
                if (leaf.type === 'sequence') throw new SequenceError('sequence_nested', source_index);
                const delay = (leaf.delay_seconds ?? 0) + (inner === 0 ? (action.delay_seconds ?? 0) : 0);
                if (delay > 86400) throw new SequenceError('sequence_delay', source_index);
                plan.push({action: {...leaf, delay_seconds: delay}, source_index, sequence: index + 1, sequence_step: inner + 1});
            }
        }
        if (plan.length > 16) throw new SequenceError('sequence_limit', source_index);
    }
    return plan;
}
