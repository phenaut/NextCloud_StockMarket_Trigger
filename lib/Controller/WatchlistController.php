<?php
namespace OCA\StockMarketTrigger\Controller;

use OCA\StockMarketTrigger\Db\Watchlist;
use OCA\StockMarketTrigger\Db\WatchlistMapper;
use OCA\StockMarketTrigger\Service\FinnhubService;
use OCP\AppFramework\Controller;
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

    public function index(): JSONResponse {
        return new JSONResponse(array_map([$this, 'asArray'], $this->mapper->findForUser($this->userId())));
    }

    public function create(string $isin, ?string $label = null, ?float $lowThreshold = null, ?float $highThreshold = null, ?string $symbol = null): JSONResponse {
        $isin = strtoupper(trim($isin));
        if (!preg_match('/^[A-Z]{2}[A-Z0-9]{10}$/', $isin)) return new JSONResponse(['error' => 'Le code ISIN doit contenir 12 caractères.'], 400);
        if ($lowThreshold !== null && $highThreshold !== null && $lowThreshold >= $highThreshold) return new JSONResponse(['error' => 'Le seuil bas doit être inférieur au seuil haut.'], 400);
        $item = new Watchlist();
        $item->setUserId($this->userId());
        $item->setIsin($isin);
        $item->setLabel(trim((string)$label));
        $item->setSymbol($symbol ? strtoupper(trim($symbol)) : null);
        $item->setLowThreshold($lowThreshold);
        $item->setHighThreshold($highThreshold);
        $item->setUpdatedAt(time());
        return new JSONResponse($this->asArray($this->mapper->insert($item)), 201);
    }

    public function update(int $id, ?string $label = null, ?float $lowThreshold = null, ?float $highThreshold = null, ?string $symbol = null): JSONResponse {
        $item = $this->mapper->findForUserById($this->userId(), $id);
        if ($lowThreshold !== null && $highThreshold !== null && $lowThreshold >= $highThreshold) return new JSONResponse(['error' => 'Le seuil bas doit être inférieur au seuil haut.'], 400);
        $item->setLabel(trim((string)$label));
        $item->setSymbol($symbol ? strtoupper(trim($symbol)) : null);
        $item->setLowThreshold($lowThreshold);
        $item->setHighThreshold($highThreshold);
        return new JSONResponse($this->asArray($this->mapper->update($item)));
    }

    public function destroy(int $id): JSONResponse {
        $this->mapper->delete($this->mapper->findForUserById($this->userId(), $id));
        return new JSONResponse([]);
    }

    public function quotes(): JSONResponse {
        $key = $this->config->getUserValue($this->userId(), $this->appName, 'finnhub_api_key', '');
        if ($key === '') return new JSONResponse(['error' => 'Configurez d’abord votre clé Finnhub.'], 400);
        $alerts = [];
        $quotes = [];
        foreach ($this->mapper->findForUser($this->userId()) as $item) {
            try {
                $symbol = $item->getSymbol() ?: $this->finnhub->resolveSymbol($item->getIsin(), $key);
                if (!$symbol) throw new \RuntimeException('Symbole Finnhub introuvable');
                $item->setSymbol($symbol);
                $quote = $this->finnhub->quote($symbol, $key);
                $oldPrice = $item->getLastPrice();
                $alert = $this->crossing($item, $oldPrice, $quote['price']);
                $item->setLastPrice($quote['price']);
                $item->setUpdatedAt(time());
                $this->mapper->update($item);
                $quotes[] = ['id' => $item->getId(), 'isin' => $item->getIsin(), 'symbol' => $symbol, 'price' => $quote['price'], 'change' => $quote['change'], 'percent' => $quote['percent']];
                if ($alert) $alerts[] = ['id' => $item->getId(), 'isin' => $item->getIsin(), 'label' => $item->getLabel(), 'direction' => $alert, 'price' => $quote['price']];
            } catch (\Throwable $exception) {
                $quotes[] = ['id' => $item->getId(), 'isin' => $item->getIsin(), 'error' => $exception->getMessage()];
            }
        }
        return new JSONResponse(['quotes' => $quotes, 'alerts' => $alerts, 'checkedAt' => time()]);
    }

    private function crossing(Watchlist $item, ?float $old, float $current): ?string {
        $direction = null;
        if ($old !== null && $item->getLowThreshold() !== null && $old >= $item->getLowThreshold() && $current < $item->getLowThreshold()) $direction = 'low';
        if ($old !== null && $item->getHighThreshold() !== null && $old <= $item->getHighThreshold() && $current > $item->getHighThreshold()) $direction = 'high';
        if ($direction && $item->getLastAlert() !== $direction) {
            $item->setLastAlert($direction);
            return $direction;
        }
        if ($current >= (float)$item->getLowThreshold() && $current <= (float)$item->getHighThreshold()) $item->setLastAlert(null);
        return null;
    }

    private function asArray(Watchlist $item): array {
        return ['id' => $item->getId(), 'isin' => $item->getIsin(), 'symbol' => $item->getSymbol(), 'label' => $item->getLabel(), 'lowThreshold' => $item->getLowThreshold(), 'highThreshold' => $item->getHighThreshold(), 'lastPrice' => $item->getLastPrice(), 'updatedAt' => $item->getUpdatedAt()];
    }

    private function userId(): string {
        return (string)$this->userSession->getUser()->getUID();
    }
}