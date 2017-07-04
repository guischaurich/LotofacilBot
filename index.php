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

$game->botToken = $_ENV["TELEGRAM_BOT_TOKEN"];

$chatAdmin = $_ENV["CHAT_ID"];

$game->chatAdmin = $_ENV["CHAT_ID"];

if (isset($response["message"])) 
{	
	$game->message = $response["message"]["text"];
	
	$game->userId = $response["message"]["from"]["id"]
		
	if($game->message == "/start")
	{		
		$game->processStartBot($chatAdmin,'Olá, seja bem vindo ao LotofacilBot.');
	}
	else if($game->message == "/novojogo")
	{
    $game->processNewGame($chatAdmin);    		
	}
	else if($game->message == "/excluirjogo")
	{
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Ok, o jogo será excluido'));
	
		unlink($game->userId.".csv");
		
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Jogo exlcuido.','reply_markup' => '{"remove_keyboard":true}'));
	}
	else if(file_exists($game->userId.".csv"))
	{		
		if(is_numeric($game->message))
		{
			$userArchive = fopen($game->userId.".csv","a");

			fwrite($userArchive, $game->message);

			fclose($userArchive);
		}
		$archive = file_get_contents($game->userId.".csv"); 
		
		$numbers = explode(";",$archive);
		//$numbers = fgetcsv($archive,0,";"); echo $numbers;
		
		if(count($numbers) == 15)
		{
			$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Ok, números anotados',
																							'reply_markup' => '{"remove_keyboard":true}'));	
			
			$userNumbers = implode(",",$numbers);
			
            $result = $game->getLastGame($numbers);
                        
			$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Você acertou '.$result["hits"].' números no jogo '.$result["gameNumber"].'.'));
		}else{
			$userArchive = fopen($game->userId.".csv","a");

			fwrite($userArchive, ";");

			fclose($userArchive);
		}
	}
	else
	{
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Desculpe, não entendi.'));
	}
}
else
{
    $game->callGames();
}
