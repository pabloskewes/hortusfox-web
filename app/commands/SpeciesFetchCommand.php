<?php

/*
    Asatru PHP - Command handler
*/

/**
 * Pre-populates the species info cache by fetching from OpenPlantBook
 * without going through the LLM. Useful for warming the cache with the
 * species you actually have, so the chat gets cache: hit on the first
 * question.
 */
class SpeciesFetchCommand implements Asatru\Commands\Command {
    /**
     * Command handler method
     *
     * Usage: php asatru species:fetch "Monstera deliciosa" [--force]
     *
     * @param $args
     * @return void
     */
    public function handle($args)
    {
        $nameArg = $args->get(0);
        if (!$nameArg) {
            echo "Usage: php asatru species:fetch \"<scientific name>\" [--force]\n";
            echo "Example: php asatru species:fetch \"Monstera deliciosa\"\n";
            return;
        }

        $name = $nameArg->getValue(0);
        $normalized = SpeciesInfoCacheModel::normalizeName($name);
        $source = SpeciesInfoCacheModel::SOURCE_OPENPLANTBOOK;

        $force = false;
        for ($i = 1; $i < $args->count(); $i++) {
            $extra = $args->get($i);
            if ($extra && $extra->getValue(0) === '--force') {
                $force = true;
            }
        }

        $cached = SpeciesInfoCacheModel::findBySpecies($source, $normalized);
        if ($cached && !$force && SpeciesInfoCacheModel::isFresh($cached)) {
            echo "Already cached for '{$normalized}' (fetched_at={$cached->get('fetched_at')}, expires_at={$cached->get('expires_at')}). Pass --force to refresh.\n";
            return;
        }

        try {
            $label = $cached ? 'Refreshing' : 'Fetching';
            echo "{$label} from OpenPlantBook: {$normalized}\n";
            $data = OpenPlantBookModule::fetchSpecies($normalized, true);
            SpeciesInfoCacheModel::put($source, $normalized, json_encode($data), SpeciesInfoCacheModel::DEFAULT_TTL_DAYS);
            $fresh = SpeciesInfoCacheModel::findBySpecies($source, $normalized);
            echo "Cached. fetched_at={$fresh->get('fetched_at')}, expires_at={$fresh->get('expires_at')}.\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}
