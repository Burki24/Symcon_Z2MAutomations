// Read only Z2M's persisted scene metadata; discovery never sends Zigbee commands.
const sceneObject = value => value !== null && typeof value === 'object' && !Array.isArray(value);
const sceneEndpoint = value => Number.isInteger(value) && value >= 1 && value <= 240;

export function entityScenes(entity, type) {
    const result = [];
    const endpoints = type === 'group' ? entity?.zh?.members : entity?.zh?.endpoints;
    if (!Array.isArray(endpoints)) return result;
    const seen = new Set();
    for (const endpoint of endpoints.slice(0, 256)) {
        if (type === 'device' && !sceneEndpoint(endpoint?.ID)) continue;
        const metadata = endpoint?.meta?.scenes;
        if (!sceneObject(metadata)) continue;
        for (const key of Object.keys(metadata).slice(0, 512)) {
            const match = /^(0|[1-9][0-9]{0,2})_(0|[1-9][0-9]{0,4})$/.exec(key);
            if (!match || Number(match[1]) > 255 || Number(match[2]) !== (type === 'group' ? entity.ID : 0)) continue;
            const id = Number(match[1]);
            const channel = type === 'device' ? endpoint.ID : 0;
            const identity = channel + '.' + id;
            if (seen.has(identity) || result.length >= 64 || !sceneObject(metadata[key])) continue;
            seen.add(identity);
            const raw = metadata[key].name;
            const name = typeof raw === 'string' && !/[\u0000-\u001f\u007f]/.test(raw) ? [...raw].slice(0, 120).join('') : '';
            result.push({id, name, ...(channel ? {endpoint: channel} : {})});
        }
    }
    return result.sort((a, b) => (a.endpoint ?? 0) - (b.endpoint ?? 0) || a.id - b.id);
}

export function hasScene(entity, type, action) {
    return entityScenes(entity, type).some(scene => scene.id === action.scene_id && scene.endpoint === action.endpoint);
}

export function buildEditorScenes(zigbee, devices, groups) {
    const result = [];
    for (const entry of [...devices.map(device => ({type: 'device', id: device.id, entry: device})),
        ...groups.map(group => ({type: 'group', id: group.id, entry: group}))]) {
        const entity = entry.type === 'device' ? zigbee.resolveEntity?.(entry.id)
            : [...(zigbee.groupsIterator?.() ?? [])].find(group => group.ID === entry.id);
        for (const scene of entityScenes(entity, entry.type)) {
            if (result.length >= 512) return result;
            let control = 'state';
            if (entry.type === 'device') {
                control = entry.entry.properties.find(property => {
                    if (!(property.access & 2) || !/^state(?:_[A-Za-z0-9_]+)?$/.test(property.property)) return false;
                    try { return entity.endpoint?.(property.endpoint || 'default')?.ID === scene.endpoint; }
                    catch { return false; }
                })?.property;
                if (!control) continue;
            }
            result.push({...scene, target: {type: entry.type, id: entry.id}, control_property: control});
        }
    }
    return result;
}
