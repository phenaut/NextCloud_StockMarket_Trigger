<div id="stockmarket-trigger" class="stockmarket-shell">
    <header class="stockmarket-header">
        <div>
            <p class="eyebrow">MARKET WATCH</p>
            <h1>Mes seuils</h1>
            <p class="subtitle">Les cours sont récupérés auprès de Finnhub toutes les cinq minutes.</p>
        </div>
        <button id="enable-notifications" class="primary-button" type="button">Activer les notifications</button>
    </header>

    <section class="settings-strip" aria-labelledby="settings-title">
        <div>
            <h2 id="settings-title">Connexion Finnhub</h2>
            <p>Votre clé reste enregistrée dans vos préférences Nextcloud.</p>
        </div>
        <form id="settings-form" class="settings-form">
            <label for="api-key">Clé API</label>
            <input id="api-key" name="apiKey" type="password" autocomplete="off" placeholder="Clé Finnhub" required>
            <button class="secondary-button" type="submit">Enregistrer</button>
            <span id="settings-status" role="status"></span>
        </form>
    </section>

    <main class="watchlist-panel">
        <div class="panel-heading">
            <div><h2>Actions surveillées</h2><span id="last-check">Pas encore vérifié</span></div>
            <button id="refresh-quotes" class="secondary-button" type="button">Actualiser les cours</button>
        </div>
        <form id="watch-form" class="watch-form">
            <input name="isin" type="text" maxlength="12" pattern="[A-Za-z0-9]{12}" placeholder="ISIN (ex. FR0000120271)" required>
            <input name="label" type="text" placeholder="Nom / libellé">
            <input name="symbol" type="text" placeholder="Symbole Finnhub (optionnel)">
            <input name="lowThreshold" type="number" min="0" step="0.0001" placeholder="Seuil bas">
            <input name="highThreshold" type="number" min="0" step="0.0001" placeholder="Seuil haut">
            <button class="primary-button" type="submit">Ajouter</button>
        </form>
        <p id="form-error" class="error-message" role="alert"></p>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Action</th><th>Symbole</th><th>Cours</th><th>Seuil bas</th><th>Seuil haut</th><th>Statut</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
                <tbody id="watchlist-body"></tbody>
            </table>
            <p id="empty-state" class="empty-state">Ajoutez un ISIN pour commencer votre surveillance.</p>
        </div>
    </main>
</div>