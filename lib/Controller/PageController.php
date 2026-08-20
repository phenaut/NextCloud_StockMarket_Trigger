<?php
namespace OCA\Stockmarket_trigger\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Util;

class PageController extends Controller {
	public function __construct(string $appName, IRequest $request) {
	parent::__construct($appName, $request);
	}

/**
* @NoAdminRequired
* @NoCSRFRequired
*/

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index(): TemplateResponse {
	Util::addScript($this->appName, 'app');
	Util::addStyle($this->appName, 'app');
	return new TemplateResponse($this->appName, 'main');
    }
}
