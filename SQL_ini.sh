/* Le script n'initiale pas la base de données Nextcloud, il ne fait que créer les tables nécessaires pour l'application stockmarket */ 
nextcloud.mysql-client -D nextcloud -e "
CREATE TABLE oc_stockmarket_watchlist (
  id INT NOT NULL AUTO_INCREMENT,
  user_id VARCHAR(64) NOT NULL,
  isin VARCHAR(32) NOT NULL,
  symbol VARCHAR(32) DEFAULT NULL,
  label VARCHAR(128) DEFAULT NULL,
  low_threshold DOUBLE DEFAULT NULL,
  high_threshold DOUBLE DEFAULT NULL,
  last_price DOUBLE DEFAULT NULL,
  last_alert VARCHAR(16) DEFAULT NULL,
  updated_at INT NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY stockmarket_user_isin (user_id, isin)
) ENGINE=InnoDB;
"
