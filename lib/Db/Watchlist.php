<?php
namespace OCA\StockMarketTrigger\Db;

use OCP\AppFramework\Db\Entity;

class Watchlist extends Entity {
    protected $userId;
    protected $isin;
    protected $symbol;
    protected $label;
    protected $lowThreshold;
    protected $highThreshold;
    protected $lastPrice;
    protected $lastAlert;
    protected $updatedAt;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('lowThreshold', 'float');
        $this->addType('highThreshold', 'float');
        $this->addType('lastPrice', 'float');
        $this->addType('updatedAt', 'integer');
    }
}