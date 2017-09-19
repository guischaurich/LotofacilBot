<?php
/*
Bot para conferir jogos da lotofacil.
Autor: Guilherme Schaurich
Data: 02/07/2017
Versão: 1.2
*/

include_once("lotofacil.php");

$update_response = file_get_contents('php://input');

$response = json_decode($update_response, true);

$game = new confersLotofacil();

$chatAdmin = $game->chatAdmin;


if (isset($response["message"])) 
{	
	$game->message = $response["message"]["text"];
        
	$game->userId = $response["message"]["from"]["id"];

	$game->processMessageReceive($game->userId);
}
else
{
    $game->callGames();
}
