<?php

namespace alina\mvc\controller;


class egHtmlPage
{
    public function actionIndex()
    {
        $data = '
        <pre>
- Будешь работать криэйтором
- Создателем чтоли?
- Создатели тут нахуй не нужны. Криэйтором будешь... Криэйтором...
        </pre>
        ';
        echo (new \alina\mvc\view\html)->page($data);
    }

}