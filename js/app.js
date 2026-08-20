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
        return fetch(endpoint(path), options).then(function (response) { return response.json().then(function (data) { if (!response.ok) throw new Error(data.error || 'Erreur serveur'); return data; }); });
    };
    var value = function (number) { return number === null || number === undefined ? '-' : Number(number).toLocaleString(undefined, {maximumFractionDigits: 4}); };
    var render = function () {
        body.innerHTML = '';
        document.getElementById('empty-state').hidden = items.length > 0;
        items.forEach(function (item) {
            var row = document.createElement('tr');
            row.innerHTML = '<td><strong>' + item.isin + '</strong><small>' + (item.label || '') + '</small></td><td>' + (item.symbol || 'Résolution...') + '</td><td data-price="' + item.id + '">' + value(item.lastPrice) + '</td><td>' + value(item.lowThreshold) + '</td><td>' + value(item.highThreshold) + '</td><td data-status="' + item.id + '">En attente</td><td><button class="delete-button" data-id="' + item.id + '" title="Supprimer" type="button">Supprimer</button></td>';
            body.appendChild(row);
        });
    };
    var load = function () { return request('/api/watchlist').then(function (data) { items = data; render(); }); };
    var quote = function () {
        return request('/api/quotes').then(function (data) {
            document.getElementById('last-check').textContent = 'Vérifié à ' + new Date(data.checkedAt * 1000).toLocaleTimeString();
            data.quotes.forEach(function (current) {
                var item = items.find(function (entry) { return entry.id === current.id; });
                if (item && !current.error) { item.lastPrice = current.price; item.symbol = current.symbol; var cell = document.querySelector('[data-price="' + item.id + '"]'); if (cell) cell.textContent = value(current.price); }
            });
            data.alerts.forEach(function (alert) {
                var item = items.find(function (entry) { return entry.id === alert.id; });
                var status = document.querySelector('[data-status="' + alert.id + '"]');
                if (status) { status.textContent = alert.direction === 'low' ? 'Sous le seuil bas' : 'Au-dessus du seuil haut'; status.className = alert.direction === 'low' ? 'status-low' : 'status-high'; }
                if ('Notification' in window && Notification.permission === 'granted') new Notification((item && item.label) || alert.isin, {body: (alert.direction === 'low' ? 'Cours sous le seuil bas : ' : 'Cours au-dessus du seuil haut : ') + value(alert.price)});
            });
        }).catch(function (error) { document.getElementById('form-error').textContent = error.message; });
    };
    document.getElementById('settings-form').addEventListener('submit', function (event) { event.preventDefault(); var data = new URLSearchParams(new FormData(event.target)); request('/api/settings', {method: 'PUT', body: data}).then(function () { document.getElementById('settings-status').textContent = 'Clé enregistrée'; }); });
    document.getElementById('watch-form').addEventListener('submit', function (event) { event.preventDefault(); document.getElementById('form-error').textContent = ''; var data = new URLSearchParams(new FormData(event.target)); request('/api/watchlist', {method: 'POST', body: data}).then(function () { event.target.reset(); return load(); }).catch(function (error) { document.getElementById('form-error').textContent = error.message; }); });
    body.addEventListener('click', function (event) { if (!event.target.dataset.id) return; request('/api/watchlist/' + event.target.dataset.id, {method: 'DELETE'}).then(load); });
    document.getElementById('refresh-quotes').addEventListener('click', quote);
    document.getElementById('enable-notifications').addEventListener('click', function () { if ('Notification' in window) Notification.requestPermission().then(function (permission) { document.getElementById('enable-notifications').textContent = permission === 'granted' ? 'Notifications activées' : 'Notifications refusées'; }); });
    load().then(function () { return request('/api/settings'); }).then(function (settings) { if (settings.configured) { quote(); setInterval(quote, 300000); } });
}());