<?php

namespace ByJG\ApiTools\Base;

class Parameter extends Body
{
    public function match(mixed $body): bool
    {
        return $this->matchSchema($this->name, $this->structure, $body) ?? false;
    }
}