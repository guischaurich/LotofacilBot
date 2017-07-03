<?php
/*
Bot para conferir jogos da lotofacil.
Autor: Guilherme Schaurich
Data: 28/06/2017
Versão: 1.1
*/

class confersLotofacil{ 
	
    public function callGames(){
        $result = $this->getLastGame(['1','2','3','5','7','9','11','13','15','19','20','21','23','24','25']);

        echo "Você acertou ".$result["hits"]." números no jogo ".$result["gameNumber"].".";

        $result = $this->getSpecificGame(['1','2','3','5','7','9','11','13','15','19','20','21','23','24','25'],"1526");

        echo "Você acertou ".$result["hits"]." números no jogo ".$result["gameNumber"].".";
    }
    
    public function getLastGame($numbersPlayed){
        $url = "http://lotodicas.com.br/api/lotofacil/";

        $lastGame = file_get_contents($url);

        $lasGameArray = json_decode($lastGame);

        $gameNumber = $lasGameArray->{"numero"};

        $gameNumbersDrawn = $lasGameArray->{"sorteio"};

        $countNumbersRight = 0;

        foreach ($gameNumbersDrawn as $number) {

            if(in_array($number,$numbersPlayed)){

                $countNumbersRight ++;
            }
        } 

        $result = array("hits" => $countNumbersRight, "gameNumber" => $lasGameArray->{"numero"});

        return $result;
    }

    public function getSpecificGame($numbersPlayed,$gameNumber){

        $url = "http://lotodicas.com.br/api/lotofacil/";

        $lastGame = file_get_contents($url.$gameNumber);

        $lasGameArray = json_decode($lastGame);

        $gameNumber = $lasGameArray->{"numero"};

        $gameNumbersDrawn = $lasGameArray->{"sorteio"};

        $countNumbersRight = 0;

        foreach ($gameNumbersDrawn as $number) {
            
            if(in_array($number,$numbersPlayed)){

                $countNumbersRight ++;
            }
        } 

        $result = array("hits" => $countNumbersRight, "gameNumber" => $lasGameArray->{"numero"});

        return $result;
    }

    function sendMessage($method, $parameters) {
        $options = array(
        'http' => array(
            'method'  => 'POST',
            'content' => json_encode($parameters),
            'header'=>  "Content-Type: application/json\r\n" .
                        "Accept: application/json\r\n"
            )
        );

        $context  = stream_context_create( $options );
        file_get_contents('https://api.telegram.org/bot'.$this->botToken.'/'.$method, false, $context );

    }
}

$update_response = file_get_contents('php://input');

$response = json_decode($update_response, true);

$game = new confersLotofacil();

$game->botToken = $_ENV["TELEGRAM_BOT_TOKEN"];

$game->chatAdmin = $_ENV["CHAT_ID"];

if (isset($response["message"])) 
{	
	if($response["message"]["text"] == "/start")
	{		
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Olá, seja bem vindo ao LotofacilBot.',
																						 'reply_markup' => '{"remove_keyboard":true}'));
		
		unlink($response["message"]["from"]["id"].".csv");
	}
	else if($response["message"]["text"] == "/novojogo")
	{
		$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Informe os números que você jogou',
																					 'reply_markup' => '{"keyboard":[["1","2","3","4","5"],["6","7","8","9","10"],["11","12","13","14","15"],["16","17","18","19","20"],["21","22","23","24","25"]],"resize_keyboard":true,"one_time_keyboard":false}'));

		$newArchive = fopen($response["message"]["from"]["id"].".csv","a");
		
		//fwrite($newArchive, '');
		
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

			fwrite($userArchive, $response["message"]["text"].";");

			fclose($userArchive);
		}
		$archive = file_get_contents($response["message"]["from"]["id"].".csv"); 
		
		$numbers = explode(";",$archive);
		//$numbers = fgetcsv($archive,0,";"); echo $numbers;
		
		if(count($numbers) == 16)
		{
			$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Ok, números anotados',
																							'reply_markup' => '{"remove_keyboard":true}'));	
			
			$userNumbers = implode(",",$numbers);
			
			$game->sendMessage("sendMessage", array('chat_id' => $game->chatAdmin, "text" => 'Seus números são: '.$userNumbers));
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
