<?php

namespace OCA\Stockmarket_trigger\Controller;

use OCA\Stockmarket_trigger\Db\Watchlist;
use OCA\Stockmarket_trigger\Db\WatchlistMapper;
use OCA\Stockmarket_trigger\Service\FinnhubService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

class WatchlistController extends Controller {
    public function __construct(
        string $appName,
        IRequest $request,
        private WatchlistMapper $mapper,
        private IUserSession $userSession,
        private IConfig $config,
        private FinnhubService $finnhub
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): JSONResponse {
        return new JSONResponse($this->mapper->findForUser($this->userId()));
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function create(string $isin, string $label = '', string $symbol = '', ?float $lowThreshold = null, ?float $highThreshold = null): JSONResponse {
        $isin = strtoupper(trim($isin));
        $error = $this->validate($isin, $lowThreshold, $highThreshold);
        if ($error !== null) return new JSONResponse(['error' => $error], 400);

        $watchlist = new Watchlist();
        $watchlist->setUserId($this->userId());
        $watchlist->setIsin($isin);
        $watchlist->setLabel(trim($label));
        $watchlist->setSymbol(trim($symbol) !== '' ? trim($symbol) : null);
        $watchlist->setLowThreshold($lowThreshold);
        $watchlist->setHighThreshold($highThreshold);
        $watchlist->setUpdatedAt(time());
        try {
            return new JSONResponse($this->mapper->insert($watchlist), 201);
        } catch (\Throwable $exception) {
            return new JSONResponse(['error' => 'Cet ISIN est déjà surveillé ou ne peut pas être enregistré.'], 409);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function update(int $id, string $isin, string $label = '', string $symbol = '', ?float $lowThreshold = null, ?float $highThreshold = null): JSONResponse {
        $isin = strtoupper(trim($isin));
        $error = $this->validate($isin, $lowThreshold, $highThreshold);
        if ($error !== null) return new JSONResponse(['error' => $error], 400);
        try {
            $watchlist = $this->mapper->findForUserById($this->userId(), $id);
        } catch (\Throwable $exception) {
            return new JSONResponse(['error' => 'Élément introuvable.'], 404);
        }
        $watchlist->setIsin($isin);
        $watchlist->setLabel(trim($label));
        $watchlist->setSymbol(trim($symbol) !== '' ? trim($symbol) : null);
        $watchlist->setLowThreshold($lowThreshold);
        $watchlist->setHighThreshold($highThreshold);
        $watchlist->setUpdatedAt(time());
        return new JSONResponse($this->mapper->update($watchlist));
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function destroy(int $id): JSONResponse {
        try {
            $watchlist = $this->mapper->findForUserById($this->userId(), $id);
            $this->mapper->delete($watchlist);
        } catch (\Throwable $exception) {
            return new JSONResponse(['error' => 'Élément introuvable.'], 404);
        }
        return new JSONResponse([], 204);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function quotes(): JSONResponse {
        $apiKey = $this->config->getUserValue($this->userId(), $this->appName, 'finnhub_api_key', '');
        if ($apiKey === '') return new JSONResponse(['error' => 'Configurez votre clé Finnhub avant de récupérer les cours.'], 400);

        $quotes = [];
        $alerts = [];
        foreach ($this->mapper->findForUser($this->userId()) as $watchlist) {
            try {
                $symbol = $watchlist->getSymbol();
                if (!$symbol) {
                    $symbol = $this->finnhub->resolveSymbol($watchlist->getIsin(), $apiKey);
                    if (!$symbol) throw new \RuntimeException('Symbole introuvable');
                    $watchlist->setSymbol($symbol);
                }
                $quote = $this->finnhub->quote($symbol, $apiKey);
                $direction = $this->direction($watchlist, $quote['price']);
                if ($direction !== null && $direction !== $watchlist->getLastAlert()) {
                    $alerts[] = ['id' => $watchlist->getId(), 'isin' => $watchlist->getIsin(), 'price' => $quote['price'], 'direction' => $direction];
                }
                $watchlist->setLastPrice($quote['price']);
                $watchlist->setLastAlert($direction);
                $watchlist->setUpdatedAt(time());
                $this->mapper->update($watchlist);
                $quotes[] = ['id' => $watchlist->getId(), 'symbol' => $symbol, 'price' => $quote['price'], 'change' => $quote['change'], 'percent' => $quote['percent']];
            } catch (\Throwable $exception) {
                $quotes[] = ['id' => $watchlist->getId(), 'error' => $exception->getMessage()];
            }
        }
        return new JSONResponse(['checkedAt' => time(), 'quotes' => $quotes, 'alerts' => $alerts]);
    }

    private function direction(Watchlist $watchlist, float $price): ?string {
        if ($watchlist->getLowThreshold() !== null && $price <= $watchlist->getLowThreshold()) return 'low';
        if ($watchlist->getHighThreshold() !== null && $price >= $watchlist->getHighThreshold()) return 'high';
        return null;
    }

    private function validate(string $isin, ?float $lowThreshold, ?float $highThreshold): ?string {
        if (!preg_match('/^[A-Z0-9]{12}$/', $isin)) return 'L’ISIN doit contenir exactement 12 caractères alphanumériques.';
        if (($lowThreshold !== null && $lowThreshold < 0) || ($highThreshold !== null && $highThreshold < 0)) return 'Les seuils doivent être positifs.';
        if ($lowThreshold !== null && $highThreshold !== null && $lowThreshold >= $highThreshold) return 'Le seuil bas doit être inférieur au seuil haut.';
        return null;
    }

    private function userId(): string {
        return (string)$this->userSession->getUser()->getUID();
    }
}
