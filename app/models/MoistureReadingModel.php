<?php

/**
 * Class MoistureReadingModel
 * 
 * Stores moisture meter readings (1-9 scale) per plant as a time series.
 */
class MoistureReadingModel extends \Asatru\Database\Model {
    const MIN_VALUE = 1;
    const MAX_VALUE = 9;

    /**
     * Validate that a value is within the allowed 1-9 range.
     * 
     * @param int $value
     * @return void
     * @throws \Exception
     */
    public static function validateValue($value)
    {
        if (($value < self::MIN_VALUE) || ($value > self::MAX_VALUE)) {
            throw new \Exception('Moisture value must be between ' . self::MIN_VALUE . ' and ' . self::MAX_VALUE);
        }
    }

    /**
     * Add a moisture reading for a plant.
     * 
     * @param int $plantId
     * @param int $value
     * @param string|null $note
     * @param string|null $takenAt
     * @param bool $api
     * @return int
     * @throws \Exception
     */
    public static function addReading($plantId, $value, $note = null, $takenAt = null, $api = false, $takenByUser = null)
    {
        try {
            static::validateValue($value);

            $userId = null;
            if ($takenByUser !== null) {
                $userId = (int)$takenByUser;
            } else if (!$api) {
                $user = UserModel::getAuthUser();
                if (!$user) {
                    throw new \Exception('Invalid user');
                }
                $userId = $user->get('id');
            }

            if ($userId === null) {
                throw new \Exception('taken_by_user is required');
            }

            if ($takenAt === null) {
                $takenAt = date('Y-m-d H:i:s');
            }

            static::raw('INSERT INTO `@THIS` (plant_id, value, taken_at, taken_by_user, note) VALUES(?, ?, ?, ?, ?)', [
                $plantId, $value, $takenAt, $userId, $note
            ]);

            $query = static::raw('SELECT * FROM `@THIS` ORDER BY id DESC LIMIT 1')->first();
            return $query->get('id');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get readings for a plant, optionally limited and ordered.
     * 
     * @param int $plantId
     * @param int|null $limit
     * @param string $order
     * @return mixed
     * @throws \Exception
     */
    public static function getForPlant($plantId, $limit = null, $order = 'desc')
    {
        try {
            $order = (strtolower($order) === 'asc') ? 'ASC' : 'DESC';
            $sql = 'SELECT * FROM `@THIS` WHERE plant_id = ? ORDER BY taken_at ' . $order;
            if ($limit !== null) {
                $sql .= ' LIMIT ' . (int)$limit;
            }

            return static::raw($sql, [$plantId]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get the latest reading for a plant.
     * 
     * @param int $plantId
     * @return mixed
     * @throws \Exception
     */
    public static function getLatest($plantId)
    {
        try {
            return static::raw('SELECT * FROM `@THIS` WHERE plant_id = ? ORDER BY taken_at DESC LIMIT 1', [$plantId])->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Remove a specific reading.
     * 
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public static function removeReading($id)
    {
        try {
            $user = UserModel::getAuthUser();
            if (!$user) {
                throw new \Exception('Invalid user');
            }

            static::raw('DELETE FROM `@THIS` WHERE id = ?', [$id]);
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
