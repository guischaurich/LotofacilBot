<?php
/*
Bot para conferir jogos da lotofacil.
Autor: Guilherme Schaurich
Data: 02/07/2017
Versão: 1.2
*/

class confersLotofacil{ 
	
	private $keyboardNumbers = '["1","2","3","4","5"],["6","7","8","9","10"],["11","12","13","14","15"],["16","17","18","19","20"],["21","22","23","24","25"]';
	
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

    public function sendMessage($method, $parameters) {
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

    public function processStartBot($chatId,$text){
       $this->sendMessage("sendMessage", 
                           array('chat_id' => ''.$chatId.'',
																 "text" => $text,
																 'reply_markup' => '{"remove_keyboard":true}')
												 );
			
			$this->deleteUserArchive($response["message"]["from"]["id"]);
    }
	
		public function processNewGame($chatId){
			$this->sendMessage("sendMessage", 
												  array('chat_id' => $chatId,
																"text" => ''.$keyboardNumbers.'',
															  'reply_markup' => '{"keyboard":['.$keyboardNumbers.'],
																"resize_keyboard":true,
																"one_time_keyboard":false}'));

			$newArchive = fopen($response["message"]["from"]["id"].".csv","a");

			fclose($newArchive);
		}

    function deleteUserArchive($userId){
        unlink("{$userId}.csv");
    }



}