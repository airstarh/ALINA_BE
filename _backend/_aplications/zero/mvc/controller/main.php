<?php

namespace zero\mvc\controller;

class main
{
    public function actionIndex(){

    	\alina\message::set('Yo! This is the status message of the age!');

    	$content = 'This is Zero Application, built on Alina Framework.';
    	echo (new \alina\mvc\view\html)->page($content);
    }

    public function action404(){
        echo '<pre>';
        print_r('404 Page not found. Zero');
        echo '</pre>';
    }

}