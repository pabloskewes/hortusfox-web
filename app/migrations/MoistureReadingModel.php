<?php

/**
 * Class MoistureReadingModel_Migration
 */
class MoistureReadingModel_Migration {
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
        $this->database = new Asatru\Database\Migration('MoistureReadingModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('plant_id INT NOT NULL');
        $this->database->add('value INT NOT NULL');
        $this->database->add('taken_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->database->add('taken_by_user INT NOT NULL');
        $this->database->add('note TEXT NULL');
        $this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
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
