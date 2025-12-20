<?php

namespace alina\traits;

trait Applier
{

    protected function applyAsOwnPropsUnsafely($options)
    {
        foreach ($options as $p=>$v) {
            $this->{$p} = $v;
        }

        return $this;
    }

    protected function applyAsOwnPropsSafely($options)
    {
        foreach ($options as $p=>$v) {
            if (property_exists($this, $p)) {
                $this->{$p} = $v;
            }
        }

        return $this;
    }
}
