<?php
namespace OCA\StockMarketTrigger\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
    public function __construct(string $appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    public function index(): TemplateResponse {
        Util::addScript($this->appName, 'app');
        Util::addStyle($this->appName, 'app');
        return new TemplateResponse($this->appName, 'main');
    }
}