<?php

namespace alina\mvc\controller;


use alina\message;

class egHtmlPage
{
    public function actionIndex()
    {
        message::set('Hello, World!!!');
        $data = '
        <pre>
- Будешь работать криэйтором
- Создателем чтоли?
- Создатели тут нахуй не нужны. Криэйтором будешь... Криэйтором...
(c) Виктор Пелевин "Generation Пи"
        </pre>
        ';
        echo (new \alina\mvc\view\html)->page($data);
    }

}