<?php
namespace OCA\StockMarketTrigger\Service;

use OCP\Http\Client\IClientService;

class FinnhubService {
    public function __construct(private IClientService $clientService) {
    }

    public function resolveSymbol(string $isin, string $apiKey): ?string {
        $data = $this->request('/search', ['q' => $isin, 'token' => $apiKey]);
        foreach ($data['result'] ?? [] as $result) {
            if (strcasecmp((string)($result['isin'] ?? ''), $isin) === 0) {
                return (string)$result['symbol'];
            }
        }
        return isset($data['result'][0]['symbol']) ? (string)$data['result'][0]['symbol'] : null;
    }

    public function quote(string $symbol, string $apiKey): array {
        $data = $this->request('/quote', ['symbol' => $symbol, 'token' => $apiKey]);
        if (!isset($data['c']) || (float)$data['c'] <= 0) {
            throw new \RuntimeException('Finnhub n’a pas renvoyé de cours pour ' . $symbol);
        }
        return ['price' => (float)$data['c'], 'change' => (float)($data['d'] ?? 0), 'percent' => (float)($data['dp'] ?? 0)];
    }

    private function request(string $path, array $query): array {
        $client = $this->clientService->newClient();
        $response = $client->get('https://finnhub.io/api/v1' . $path, [
            'query' => $query,
            'timeout' => 15,
            'connect_timeout' => 8,
            'headers' => ['Accept' => 'application/json', 'User-Agent' => 'Nextcloud Stock Market Trigger'],
        ]);
        if ($response->getStatusCode() >= 400) {
            throw new \RuntimeException('Erreur Finnhub (' . $response->getStatusCode() . ')');
        }
        $data = json_decode($response->getBody(), true);
        if (!is_array($data)) {
            throw new \RuntimeException('Réponse Finnhub invalide');
        }
        return $data;
    }
}