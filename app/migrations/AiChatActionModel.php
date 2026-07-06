<?php

/**
 * Class AiChatActionModel_Migration
 */
class AiChatActionModel_Migration {
    private $database = null;
    private $connection = null;

    public function __construct($pdo)
    {
        $this->connection = $pdo;
    }

    public function up()
    {
        $this->database = new Asatru\Database\Migration('AiChatActionModel', $this->connection);
        $this->database->drop();
        $this->database->add('id INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
        $this->database->add('session_id INT NOT NULL');
        $this->database->add('message_id INT NOT NULL');
        $this->database->add('user_id INT NOT NULL');
        $this->database->add('tool_name VARCHAR(128) NOT NULL');
        $this->database->add('args_json LONGTEXT NOT NULL');
        $this->database->add('preview_json LONGTEXT NOT NULL');
        $this->database->add('status VARCHAR(32) NOT NULL');
        $this->database->add('result_json LONGTEXT NULL');
        $this->database->add('created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->database->add('updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        $this->database->add('executed_at DATETIME NULL');
        $this->database->create();
    }

    public function down()
    {
        if ($this->database) $this->database->drop();
    }
}
