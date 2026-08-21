/* global OC */
(function () {
    'use strict';
    var root = document.getElementById('stockmarket-trigger');
    if (!root) return;
    var body = document.getElementById('watchlist-body');
    var items = [];
    var endpoint = function (path) { return OC.generateUrl('/apps/stockmarket_trigger' + path); };
    var request = function (path, options) {
        options = options || {};
        options.headers = Object.assign({'Content-Type': 'application/x-www-form-urlencoded'}, options.headers || {});
        return fetch(endpoint(path), options).then(function (response) {
            if (response.status === 204) return null;
            return response.json().then(function (data) { if (!response.ok) throw new Error(data.error || 'Erreur serveur'); return data; });
        });
    };
    var value = function (number) { return number === null || number === undefined ? '-' : Number(number).toLocaleString(undefined, {maximumFractionDigits: 4}); };
    var render = function () {
        body.innerHTML = '';
        document.getElementById('empty-state').hidden = items.length > 0;
        items.forEach(function (item) {
            var row = document.createElement('tr');
            var isin = item.isin || item.ISIN || '';
            row.innerHTML = '<td><strong>' + isin + '</strong><small>' + (item.label || '') + '</small></td><td data-symbol="' + item.id + '">' + (item.symbol || 'Résolution...') + '</td><td data-price="' + item.id + '">' + value(item.lastPrice) + '</td><td>' + value(item.lowThreshold) + '</td><td>' + value(item.highThreshold) + '</td><td data-status="' + item.id + '">En attente</td><td><button class="delete-button" data-id="' + item.id + '" title="Supprimer" type="button">Supprimer</button></td>';
            body.appendChild(row);
        });
    };
    var load = function () { return request('/api/watchlist').then(function (data) { items = data; render(); }); };
    var notificationsEnabled = localStorage.getItem('stockmarket-notifications') === 'true';
    var updateNotificationsButton = function () {
        var button = document.getElementById('enable-notifications');
        var permissionGranted = 'Notification' in window && Notification.permission === 'granted';
        button.textContent = notificationsEnabled && permissionGranted ? 'Désactiver les notifications' : 'Activer les notifications';
        button.setAttribute('aria-pressed', notificationsEnabled && permissionGranted ? 'true' : 'false');
        button.title = notificationsEnabled && permissionGranted ? 'Désactiver les notifications dans cette application' : 'Activer les notifications dans cette application';
    };
    var quote = function () {
        return request('/api/quotes').then(function (data) {
            var statusIndicator = document.getElementById('finnhub-status');
            var successfulQuotes = 0;
            document.getElementById('last-check').textContent = 'Vérifié à ' + new Date(data.checkedAt * 1000).toLocaleTimeString();
            data.quotes.forEach(function (current) {
                var item = items.find(function (entry) { return entry.id === current.id; });
                if (item && !current.error) {
                    successfulQuotes += 1;
                    item.lastPrice = current.price;
                    item.symbol = current.symbol;
                    var cell = document.querySelector('[data-price="' + item.id + '"]');
                    var symbol = document.querySelector('[data-symbol="' + item.id + '"]');
                    var status = document.querySelector('[data-status="' + item.id + '"]');
                    if (cell) cell.textContent = value(current.price);
                    if (symbol) { symbol.textContent = current.symbol; symbol.removeAttribute('title'); }
                    if (status) { status.textContent = 'Dans les seuils'; status.className = ''; }
                } else if (item && current.error) {
                    var errorSymbol = document.querySelector('[data-symbol="' + item.id + '"]');
                    var errorStatus = document.querySelector('[data-status="' + item.id + '"]');
                    if (errorSymbol) errorSymbol.title = current.error;
                    if (errorStatus) { errorStatus.textContent = 'Erreur Finnhub'; errorStatus.className = 'status-low'; errorStatus.title = current.error; }
                }
            });
            statusIndicator.className = 'finnhub-status ' + (successfulQuotes > 0 ? 'finnhub-status-ok' : 'finnhub-status-error');
            statusIndicator.title = successfulQuotes > 0 ? 'Finnhub a répondu correctement' : 'Finnhub a répondu sans cotation exploitable';
            statusIndicator.setAttribute('aria-label', statusIndicator.title);
            data.alerts.forEach(function (alert) {
                var item = items.find(function (entry) { return entry.id === alert.id; });
                var status = document.querySelector('[data-status="' + alert.id + '"]');
                if (status) { status.textContent = alert.direction === 'low' ? 'Sous le seuil bas' : 'Au-dessus du seuil haut'; status.className = alert.direction === 'low' ? 'status-low' : 'status-high'; }
                if (notificationsEnabled && 'Notification' in window && Notification.permission === 'granted') new Notification((item && item.label) || alert.isin, {body: (alert.direction === 'low' ? 'Cours sous le seuil bas : ' : 'Cours au-dessus du seuil haut : ') + value(alert.price)});
            });
        }).catch(function (error) {
            var statusIndicator = document.getElementById('finnhub-status');
            statusIndicator.className = 'finnhub-status finnhub-status-error';
            statusIndicator.title = 'Erreur de communication avec Finnhub : ' + error.message;
            statusIndicator.setAttribute('aria-label', statusIndicator.title);
            document.getElementById('form-error').textContent = error.message;
        });
    };
    document.getElementById('settings-form').addEventListener('submit', function (event) { event.preventDefault(); var data = new URLSearchParams(new FormData(event.target)); request('/api/settings', {method: 'PUT', body: data}).then(function () { document.getElementById('settings-status').textContent = 'Clé enregistrée'; }); });
    document.getElementById('watch-form').addEventListener('submit', function (event) { event.preventDefault(); document.getElementById('form-error').textContent = ''; var data = new URLSearchParams(new FormData(event.target)); request('/api/watchlist', {method: 'POST', body: data}).then(function () { event.target.reset(); return load(); }).catch(function (error) { document.getElementById('form-error').textContent = error.message; }); });
    body.addEventListener('click', function (event) { if (!event.target.dataset.id) return; request('/api/watchlist/' + event.target.dataset.id, {method: 'DELETE'}).then(load).catch(function (error) { document.getElementById('form-error').textContent = error.message; }); });
    document.getElementById('refresh-quotes').addEventListener('click', quote);
    document.getElementById('enable-notifications').addEventListener('click', function () {
        if (notificationsEnabled) {
            notificationsEnabled = false;
            localStorage.setItem('stockmarket-notifications', 'false');
            updateNotificationsButton();
            return;
        }
        if (!('Notification' in window)) return;
        Notification.requestPermission().then(function (permission) {
            notificationsEnabled = permission === 'granted';
            localStorage.setItem('stockmarket-notifications', String(notificationsEnabled));
            updateNotificationsButton();
        });
    });
    updateNotificationsButton();
    load().then(function () { return request('/api/settings'); }).then(function (settings) { if (settings.configured) { quote(); setInterval(quote, 300000); } });
}());