<?php

namespace App\Services\Export;

class BaseExport
{
    public function getVariables(bool $keys_only = true, string $target = null): array
    {
        if ($target) {
            if (!isset($this->variables[$target])) {
                return [];
            }
            if ($keys_only) {
                return array_keys($this->variables[$target]);
            }
            return $this->variables[$target];
        }

        if ($keys_only) {
            $variables = [];
            foreach ($this->variables as $key => $target) {
                $variables[$key] = array_keys($target);
            }
            return $variables;
        }
        return $this->variables;
    }

    protected function getPdfPath($path)
    {
        return pathinfo($path)['dirname'].'/'.pathinfo($path)['filename'].'.pdf';
    }
}
