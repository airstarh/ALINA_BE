<?php

namespace zero\mvc\controller;

use alina\Message;

class main
{
    public function actionIndex(){

    	Message::set('Yo! This is the status message of the age!');

    	$content = 'This is Zero Application, built on Alina Framework.';
    	echo (new \alina\mvc\view\html)->page($content);
    }

    public function action404(){
        echo '<pre>';
        print_r('404 Page not found. Zero');
        echo '</pre>';
    }

}
