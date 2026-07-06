<?php

/**
 * Class AiChatMsgModel_Migration
 */
class AiChatMsgModel_Migration {
    private $database = null;
    private $connection = null;

    public function __construct($pdo)
    {
        $this->connection = $pdo;
    }

    public function up()
    {
        $this->database = new Asatru\Database\Migration('AiChatMsgModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('session_id INT NOT NULL');
        $this->database->add('role VARCHAR(32) NOT NULL');
        $this->database->add('content LONGTEXT NOT NULL');
        $this->database->add('metadata_json LONGTEXT NULL');
        $this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->database->create();
    }

    public function down()
    {
        if ($this->database) $this->database->drop();
    }
}
