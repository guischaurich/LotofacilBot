<?php
/*
Bot para conferir jogos da lotofacil.
Autor: Guilherme Schaurich
Data: 02/07/2017
Versão: 1.2
*/

class confersLotofacil{ 
	
	function __construct(){
        $this->keyboardNumbers = '["1","2","3","4","5"],["6","7","8","9","10"],["11","12","13","14","15"],["16","17","18","19","20"],["21","22","23","24","25"]';
        
        $this->botToken = $_ENV["TELEGRAM_BOT_TOKEN"];
        
        $this->chatAdmin = $_ENV["CHAT_ID"];

        $this->urlApi = "http://lotodicas.com.br/api/lotofacil/";
    }


    public function callGames(){
        $result = $this->getLatestNumbersDrawn(['1','2','3','5','7','9','11','13','15','19','20','21','23','24','25']);

        echo "Você acertou ".$result["hits"]." números no jogo ".$result["gameNumber"].".";

        $result = $this->getSpecificGame(['1','2','3','5','7','9','11','13','15','19','20','21','23','24','25'],"1526");

        echo "Você acertou ".$result["hits"]." números no jogo ".$result["gameNumber"].".";
    }
    
    public function getLatestNumbersDrawnAndGameNumber($numbersPlayed)
    {
        $lastGame = file_get_contents($this->urlApi);

        $lasGameArray = json_decode($lastGame);

        return = array("numbersDrawn"=>$lasGameArray->{"sorteio"} , "gameNumber"=>$lasGameArray->{"numero"});
    }

    public function getSpecificGame($numbersPlayed,$gameNumber)
    {
        $lastGame = file_get_contents($this->urlApi.$gameNumber);

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
			
      $this->deleteUserArchive($chatId);
    }
	
		public function processNewGame($chatId){
			$this->sendMessage("sendMessage", 
                                array('chat_id' => $chatId,
                                            "text" => 'Informe os números que você jogou',
                                            'reply_markup' => '{"keyboard":['.$this->keyboardNumbers.'],
                                            "resize_keyboard":true,
                                            "one_time_keyboard":false}')
                            );

			$newArchive = fopen($chatId.".csv","a");

			fclose($newArchive);
		}
	
	public function processDeleteGame($chatId){
		$this->sendMessage("sendMessage", 
                            array('chat_id' => $chatId, 
                                  "text" => 'Ok, o jogo será excluido')
                        );
	
		$this->deleteUserArchive($chatId);
		
		$this->sendMessage("sendMessage", 
                            array('chat_id' => $chatId, 
                                  "text" => 'Jogo exlcuido.',
                                  'reply_markup' => '{"remove_keyboard":true}'
                                  )
                        );
	}

    public function deleteUserArchive($userId){
        unlink($userId.".csv");
    }

    public function CheckNumberOfHits($gameNumbersDrawn,$numbersPlayed){
        $countNumbersRight = 0;

        foreach ($gameNumbersDrawn as $number) {

            if(in_array($number,$numbersPlayed)){

                $countNumbersRight ++;
            }
        }

        return $countNumbersRight;
    }

    public function processMessageReceive($chatId){
            
        if($this->message == "/start")
        {		
            $this->processStartBot($chatId,'Olá, seja bem vindo ao LotofacilBot.');
        }
        else if($this->message == "/novojogo")
        {
            $this->processNewGame($chatId);    		
        }
        else if($this->message == "/excluirjogo")
        {
            $this->processDeleteGame($chatId);
        }
        else if(file_exists($chatId.".csv"))
        {		
            if(is_numeric($this->message))
            {
                $userArchive = fopen($chatId.".csv","a");

                fwrite($userArchive, $this->message);

                fclose($userArchive);
            }
            $archive = file_get_contents($chatId.".csv"); 
            
            $numbers = explode(";",$archive);
            
            if(count($numbers) == 15)
            {
                $this->sendMessage("sendMessage", 
                                    array('chat_id' => $chatId, 
                                          "text" => 'Ok, números anotados',
                                          'reply_markup' => '{"remove_keyboard":true}'
                                          )
                                );	
                
                //$userNumbers = implode(",",$numbers);
                
                $gameNumbersDrawnaAndGameNumber = $this->getLatestNumbersDrawnAndGameNumber($numbers);

                $numberOfHits = $this->CheckNumberOfHits($gameNumbersDrawnaAndGameNumber["hits"],$numbers);

                $this->sendMessage("sendMessage", 
                                    array('chat_id' => $chatId, 
                                          "text" => 'Você acertou '.$numberOfHits.' números no jogo '.$gameNumbersDrawnaAndGameNumber["gameNumber"].'.'
                                          )
                                );
            }else{
                $userArchive = fopen($chatId.".csv","a");

                fwrite($userArchive, ";");

                fclose($userArchive);
            }
        }
        else
        {
            $this->sendMessage("sendMessage", array('chat_id' => $chatId, "text" => 'Desculpe, não entendi.'));
        }
    }
}