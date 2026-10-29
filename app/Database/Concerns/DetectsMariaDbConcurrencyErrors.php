<?php

namespace App\Database\Concerns;

use PDOException;
use Throwable;

trait DetectsMariaDbConcurrencyErrors
{
    protected function causedByConcurrencyError(Throwable $exception)
    {
        if (parent::causedByConcurrencyError($exception)) {
            return true;
        }

        // MariaDB snapshot conflicts abort the whole transaction. Laravel 11
        // does not classify HY000/1020; a nested rollback then masks the cause
        // with a missing savepoint. Follow the cause through DeadlockException
        // so the outer transaction also recognizes it and owns the retry.
        do {
            if ($exception instanceof PDOException
                && ($exception->errorInfo[0] ?? null) === 'HY000'
                && (int) ($exception->errorInfo[1] ?? 0) === 1020) {
                return true;
            }
        } while ($exception = $exception->getPrevious());

        return false;
    }
}
