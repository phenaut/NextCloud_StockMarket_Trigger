<?php
namespace OCA\StockMarketTrigger\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class WatchlistMapper extends QBMapper {
    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'stockmarket_watchlist', Watchlist::class);
    }

    public function findForUser(string $userId): array {
        $query = $this->db->getQueryBuilder();
        $query->select('*')->from($this->getTableName())->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)))->orderBy('id', 'ASC');
        return $this->findEntities($query);
    }

    public function findForUserById(string $userId, int $id): Watchlist {
        $query = $this->db->getQueryBuilder();
        $query->select('*')->from($this->getTableName())->where($query->expr()->eq('user_id', $query->createNamedParameter($userId)))->andWhere($query->expr()->eq('id', $query->createNamedParameter($id)));
        return $this->findEntity($query);
    }
}