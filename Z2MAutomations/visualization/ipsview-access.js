/* Exchange the optional HTML variable's entry grant inside the same HTTP origin. */
export async function openIPSViewSession(environment = window) {
    const {location, history} = environment;
    const fragment = location.hash.slice(1);
    if (/^[a-f0-9]{64}$/.test(fragment)) return;
    if (!/^entry\.[a-f0-9]{64}$/.test(fragment)) throw new Error('IPSView access unavailable');
    const endpoint = new URL('ipsview-access', location.href); endpoint.hash = '';
    const response = await environment.fetch(endpoint, {method: 'POST', cache: 'no-store', credentials: 'same-origin', redirect: 'error',
        headers: {'Content-Type': 'application/json', 'X-Z2MA-Read-Token': fragment.slice(6)}, body: '{}', signal: AbortSignal.timeout(10000)});
    if (!response.ok) throw new Error('IPSView access rejected');
    const result = await response.json();
    if (result.ok !== true || !/^[a-f0-9]{64}$/.test(result.token ?? '') || !Number.isInteger(result.expires)) throw new Error('Invalid IPSView reply');
    history.replaceState(null, '', '#' + result.token);
}
