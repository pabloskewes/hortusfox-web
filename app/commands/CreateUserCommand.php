<?php

/*
    Asatru PHP - Command handler
*/

/**
 * Command handler class
 */
class CreateUserCommand implements Asatru\Commands\Command {
    /**
     * Command handler method
     * 
     * @param $args
     * @return void
     */
    public function handle($args)
    {
        if ($args->count() < 2) {
            echo "Usage: php asatru user:create <name> <email> [password]\n";
            return;
        }

        $name = $args->get(0)->getValue(0);
        $email = $args->get(1)->getValue(0);
        $password = $args->count() >= 3 ? $args->get(2)->getValue(0) : null;

        try {
            $generated = UserModel::createUser($name, $email, false);

            if ($password) {
                UserModel::raw('UPDATE `@THIS` SET password = ? WHERE email = ?', [
                    password_hash($password, PASSWORD_BCRYPT), $email
                ]);
            }

            echo "\033[32mUser created: {$name} ({$email})\033[39m\n";
            if ($password) {
                echo "\033[32mPassword set.\033[39m\n";
            } else {
                echo "\033[32mPassword: {$generated}\033[39m\n";
            }
        } catch (\Exception $e) {
            echo "\033[31mError: " . $e->getMessage() . "\033[39m\n";
        }
    }
}
