<?php
namespace OCA\Stockmarket_trigger\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
    public function __construct(array $urlParams = []) {
        parent::__construct('stockmarket_trigger', $urlParams);
    }
}