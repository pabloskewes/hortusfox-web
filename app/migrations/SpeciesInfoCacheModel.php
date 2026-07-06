<?php

/**
 * Class SpeciesInfoCacheModel_Migration
 */
class SpeciesInfoCacheModel_Migration {
    private $database = null;
    private $connection = null;

    /**
     * Store the PDO connection handle
     *
     * @param \PDO $pdo The PDO connection handle
     * @return void
     */
    public function __construct($pdo)
    {
        $this->connection = $pdo;
    }

    /**
     * Called when the table shall be created or modified
     *
     * @return void
     */
    public function up()
    {
        $this->database = new Asatru\Database\Migration('SpeciesInfoCacheModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('source VARCHAR(64) NOT NULL');
        $this->database->add('scientific_name VARCHAR(255) NOT NULL');
        $this->database->add('data_json LONGTEXT NOT NULL');
        $this->database->add('fetched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->database->add('expires_at DATETIME NOT NULL');
        $this->database->create();
    }

    /**
     * Called when the table shall be dropped
     *
     * @return void
     */
    public function down()
    {
        if ($this->database)
            $this->database->drop();
    }
}
