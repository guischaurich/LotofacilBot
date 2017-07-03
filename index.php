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
	if($response["message"]["text"] == "/start")
	{		
		$game->processStartBot($chatAdmin,'Olá, seja bem vindo ao LotofacilBot.');
	}
	else if($response["message"]["text"] == "/novojogo")
	{
        
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Informe os números que você jogou',
																					 'reply_markup' => '{"keyboard":[["1","2","3","4","5"],["6","7","8","9","10"],["11","12","13","14","15"],["16","17","18","19","20"],["21","22","23","24","25"]],"resize_keyboard":true,"one_time_keyboard":false}'));

		$newArchive = fopen($response["message"]["from"]["id"].".csv","a");
				
		fclose($newArchive);
	}
	else if($response["message"]["text"] == "/excluirjogo")
	{
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Ok, o jogo será excluido'));
	
		unlink($response["message"]["from"]["id"].".csv");
		
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Jogo exlcuido.','reply_markup' => '{"remove_keyboard":true}'));
	}
	else if(file_exists($response["message"]["from"]["id"].".csv"))
	{		
		if(is_numeric($response["message"]["text"]))
		{
			$userArchive = fopen($response["message"]["from"]["id"].".csv","a");

			fwrite($userArchive, $response["message"]["text"]);

			fclose($userArchive);
		}
		$archive = file_get_contents($response["message"]["from"]["id"].".csv"); 
		
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
			$userArchive = fopen($response["message"]["from"]["id"].".csv","a");

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
