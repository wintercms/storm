<?php namespace Winter\Storm\Console\Traits;

use Winter\Storm\Database\Builder;

/**
 * Console Command Trait that provides the "processQuery($query, $callback, $chunkSize, $limit)"
 * helper method to cleanly handle processing a large number of records in the console.
 *
 * @package winter\storm
 * @author Luke Towers
 */
trait ProcessesQuery
{
    /**
     * Processes the provided query by rendering a progress bar, chunking the
     * query by the provided chunkSize, running the callback on each record and
     * limiting number of records processed to the provided limit
     */
    public function processQuery(Builder $query, callable $callback, int $chunkSize = 100, int $limit = null): void
    {
        $totalRecords = $query->count();

        if (!$totalRecords) {
            $this->warn("No records were found to process.");
            return;
        }

        $progress = $this->output->createProgressBar($totalRecords);
        $progress->setFormat('%current%/%max% [%bar%] %percent:3s%% (%elapsed:6s%/%estimated:-6s%)');

        $recordsProcessed = 0;
        $limitReached = false;

        $query->chunkById($chunkSize, function ($records) use ($callback, $progress, &$recordsProcessed, $limit, &$limitReached) {
            foreach ($records as $record) {
                // Handle the limit being reached
                if ($limit && $recordsProcessed >= $limit) {
                    $progress->finish();
                    $this->info('');
                    $this->error("Limit reached, " . number_format($recordsProcessed) . " records were processed.");
                    $limitReached = true;
                    return false;
                }

                try {
                    // Process the record
                    $callback($record);
                } catch (\Throwable $e) {
                    $recordsProcessed--;
                    $this->error(sprintf(
                        "Failed to process ID %s: %s",
                        $record->getKey(),
                        $e->getMessage()
                    ));
                }

                // Attempt to avoid out of memory issues
                unset($record);

                // Update the UI
                $recordsProcessed++;
                $progress->advance();
            }
        });

        if (!$limitReached) {
            $progress->finish();
            $this->info('');
        }

        $this->info("Processed " . number_format($recordsProcessed) . " of " . number_format($totalRecords) . " records.");
        $this->info('');
    }
}
