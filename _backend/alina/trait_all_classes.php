<?php

namespace alina;

trait trait_all_classes
{
    #region Names Resolver

    public function getShortClassName($subject) {
        if (is_object($subject)) {
            $subject = get_class ($subject);
        }

        return shortClassName($subject);
    }

    #endregion Names Resolver

    #region Getters&Setters
    public function simplySet($name, $value)
    {
        $this->{$name} = $value;

        return $this;
    }

    public function simplyGet($name)
    {
        return $this->{$name};
    }
    #endregion Getters&Setters
}