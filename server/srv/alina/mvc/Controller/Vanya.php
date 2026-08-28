<?php

namespace alina\mvc\Controller;

class Vanya
{
    public function actionIndex()
    {
        AlinaEcho($this->currentTime());
    }

    public function currentTime(): string
    {
        return date('Y-m-d H:i:s');
    }
}
