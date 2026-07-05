(function () {
    let hideTimeout = null;

    function showErrorToast(message) {
        const toast = document.getElementById('error-toast');
        toast.textContent = message;
        toast.hidden = false;
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(function () { toast.hidden = true; }, 6000);
    }

    // htmx swappt bei Fehler-Antworten nichts; ohne diese Meldung
    // sähe eine fehlgeschlagene Aktion wie ein stiller Erfolg aus.
    document.addEventListener('htmx:responseError', function (event) {
        const status = event.detail.xhr ? event.detail.xhr.status : '?';
        showErrorToast('Aktion fehlgeschlagen (HTTP ' + status + '). Details stehen im Server-Log.');
    });

    document.addEventListener('htmx:sendError', function () {
        showErrorToast('Server nicht erreichbar. Bitte Verbindung prüfen und erneut versuchen.');
    });
})();
