<?php
/*
Bot para conferir jogos da lotofacil.
Autor: Guilherme Schaurich
Data: 26/06/2017
Versão: 1.l
*/

class confersLoto{
    private $gameNumbers = ['1','2','3','5','7','9','11','13','15','19','20','21','23','24','25'];
    private $url = "lotodicas.com.br/api/lotofacil/";
    private function __construc($gameNumbers){
        
        $lastGame = file_get_contents($url);
        var_dump($lastGame);
    }
}

$confersLotoGame = new confersLoto();