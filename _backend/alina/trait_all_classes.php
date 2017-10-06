<?php

namespace alina;

trait trait_all_classes
{
    public function s($name, $value)
    {
        $this->{$name} = $value;

        return $this;
    }

    public function g($name)
    {
        return $this->{$name};
    }
}