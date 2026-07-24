<?php

namespace App\Http\Requests\Traits;

trait ExtendRulesTrait
{
    protected function extendRules(array $baseRules, array $additionalRules): array
    {
        foreach ($additionalRules as $key => $extras) {
            $baseRules[$key] = array_merge($baseRules[$key] ?? [], $extras);
        }

        return $baseRules;
    }
}
