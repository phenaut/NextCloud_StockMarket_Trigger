<?php
namespace OCA\StockMarketTrigger\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller {
    public function __construct(string $appName, IRequest $request, private IConfig $config, private IUserSession $userSession) {
        parent::__construct($appName, $request);
    }

    public function get(): JSONResponse {
        return new JSONResponse(['configured' => $this->key() !== '']);
    }

    public function save(string $apiKey = ''): JSONResponse {
        $apiKey = trim($apiKey);
        if ($apiKey === '') return new JSONResponse(['error' => 'La clé Finnhub est obligatoire.'], 400);
        $this->config->setUserValue($this->userId(), $this->appName, 'finnhub_api_key', $apiKey);
        return new JSONResponse(['configured' => true]);
    }

    public function key(): string {
        return $this->config->getUserValue($this->userId(), $this->appName, 'finnhub_api_key', '');
    }

    private function userId(): string {
        return (string)$this->userSession->getUser()->getUID();
    }
}