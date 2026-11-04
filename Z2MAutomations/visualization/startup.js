/* Keep a failed module import visible without exposing browser errors or access tokens. */
(() => {
    const button = document.querySelector('#validate-button');
    const status = document.querySelector('#draft-status');
    const feedback = document.querySelector('#feedback');
    for (const selector of ['#save-button', '#activate-button', '#stop-button']) document.querySelector(selector).disabled = true;
    button.disabled = true;
    button.textContent = 'Designer wird geladen …';
    status.textContent = 'Designer wird geladen …';
    import('./reader.js').catch(() => {
        status.textContent = 'Designer konnte nicht starten';
        feedback.textContent = 'Die Designerdateien konnten nicht vollständig geladen werden. Bitte „Designer neu laden“ wählen. Ihre Entwürfe und Z2M bleiben unverändert. Falls der Fehler erneut auftritt, das Modul-Debug prüfen.';
        feedback.dataset.tone = 'error';
        button.textContent = 'Designer neu laden';
        button.disabled = false;
        button.addEventListener('click', () => location.reload());
    });
})();
