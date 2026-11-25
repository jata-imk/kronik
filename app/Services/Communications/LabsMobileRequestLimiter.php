<?php

namespace App\Services\Communications;

use Illuminate\Support\Facades\Cache;
use Throwable;

final class LabsMobileRequestLimiter
{
    /** Hold the account lock across domain preflight and HTTP, without sleeping or retrying. */
    public function run(callable $request): mixed
    {
        $entered = false;
        try {
            $store = config('comunicaciones.sms.rate_cache_store');
            if ($store === 'array' && ! app()->environment('testing')) {
                return null;
            }
            $cache = Cache::store($store);
            // All teams using the same account share a quota, including balance checks.
            $key = 'labsmobile:'.hash('sha256', mb_strtolower(trim((string) config('comunicaciones.sms.username'))));

            $result = $cache->lock($key.':lock', 120)->get(function () use ($cache, $key, $request, &$entered) {
                $time = (float) now()->format('U.u');
                if ((float) $cache->get($key, 0) > $time) {
                    return null;
                }
                $rate = min(10, max(1, (int) config('comunicaciones.sms.requests_per_second', 5)));
                // Fail closed even if the post-request cache write fails or the worker dies.
                if (! $cache->put($key, $time + 120, 180)) {
                    return null;
                }
                $entered = true;
                try {
                    return ['result' => $request()];
                } finally {
                    // Space actual API calls, even if a preceding DB transaction was slow.
                    $cache->put($key, (float) now()->format('U.u') + 1 / $rate, 180);
                }
            });

            return is_array($result) ? $result['result'] : null;
        } catch (Throwable $exception) {
            if ($entered) {
                // A domain/audit failure is not quota contention; preserve transactional failure.
                throw $exception;
            }

            // An unavailable/shared-lock-incompatible cache must never open the gate.
            return null;
        }
    }
}
