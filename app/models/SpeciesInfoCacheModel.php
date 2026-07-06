<?php

/**
 * Class SpeciesInfoCacheModel
 *
 * Local cache of plant species info (watering, light, etc.) fetched from
 * external sources. Per the wishlist's goal #6: cache external lookups
 * locally so we don't depend on live calls each time and survive upstream
 * changes.
 *
 * The (source, scientific_name) pair is the natural key. Uniqueness is
 * enforced at the application layer, not via a DB constraint, because
 * Asatru's Migration helper only exposes add() / create() and no other
 * table in the codebase uses UNIQUE keys.
 */
class SpeciesInfoCacheModel extends \Asatru\Database\Model {
    const SOURCE_OPENPLANTBOOK = 'openplantbook';
    const DEFAULT_TTL_DAYS = 30;

    /**
     * Find a cache row by source + scientific_name.
     *
     * @param string $source
     * @param string $scientificName Normalized (lowercase, trimmed)
     * @return mixed Row or null
     */
    public static function findBySpecies($source, $scientificName)
    {
        try {
            return static::raw(
                'SELECT * FROM `@THIS` WHERE source = ? AND scientific_name = ? LIMIT 1',
                [$source, $scientificName]
            )->first();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Insert or update a cache row. Sets fetched_at = now, expires_at = now + ttl.
     *
     * @param string $source
     * @param string $scientificName Normalized
     * @param string $dataJson Raw JSON string
     * @param int $ttlDays
     * @return void
     */
    public static function put($source, $scientificName, $dataJson, $ttlDays = self::DEFAULT_TTL_DAYS)
    {
        try {
            $now = date('Y-m-d H:i:s');
            $expires = date('Y-m-d H:i:s', time() + ((int)$ttlDays) * 86400);

            $existing = static::findBySpecies($source, $scientificName);
            if ($existing) {
                static::raw(
                    'UPDATE `@THIS` SET data_json = ?, fetched_at = ?, expires_at = ? WHERE id = ?',
                    [$dataJson, $now, $expires, $existing->get('id')]
                );
            } else {
                static::raw(
                    'INSERT INTO `@THIS` (source, scientific_name, data_json, fetched_at, expires_at) VALUES(?, ?, ?, ?, ?)',
                    [$source, $scientificName, $dataJson, $now, $expires]
                );
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Check whether a cached row is still fresh.
     *
     * @param mixed $row
     * @return bool
     */
    public static function isFresh($row)
    {
        if ($row === null) {
            return false;
        }
        $expiresAt = strtotime((string)$row->get('expires_at'));
        return ($expiresAt !== false) && ($expiresAt > time());
    }

    /**
     * Normalize a scientific name for use as a cache key.
     * Lowercase, trimmed, internal whitespace collapsed.
     *
     * @param string $name
     * @return string
     */
    public static function normalizeName($name)
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/\s+/', ' ', $name);
        return $name;
    }
}
