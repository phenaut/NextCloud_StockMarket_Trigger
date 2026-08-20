<?php

namespace OCA\Stockmarket_trigger\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class WatchlistMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'stockmarket_watchlist', Watchlist::class);
    }

    /**
     * Récupère tous les éléments de la liste de suivi pour un utilisateur donné.
     *
     * @param string $userId
     * @return Watchlist[]
     */
    public function findForUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->tableName)
           ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntities($qb);
    }

    /**
     * Récupère un élément spécifique par son ID et l'ID de l'utilisateur.
     *
     * @param string $userId
     * @param int $id
     * @return Watchlist
     * @throws \OCP\AppFramework\Db\DoesNotExistException
     */
    public function findForUserById(string $userId, int $id): Watchlist {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from($this->tableName)
           ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
           ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));

        return $this->findEntity($qb);
    }
}
