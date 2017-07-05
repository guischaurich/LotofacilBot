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
        
        $this->keyboardMenu = '["Cadastrar jogo","Ultimo Sorteio"],["Outro Sorteio","Excluir Jogo"]';

        $this->botToken = $_ENV["TELEGRAM_BOT_TOKEN"];
        
        $this->chatAdmin = $_ENV["CHAT_ID"];

        $this->urlApi = "http://lotodicas.com.br/api/lotofacil/";
    }


    public function callGames(){
        $result = $this->getLatestNumbersDrawn(['1','2','3','5','7','9','11','13','15','19','20','21','23','24','25']);

        echo "Você acertou ".$result["hits"]." números no jogo ".$result["gameNumber"].".";

        $result = $this->getNumbersDrawnOfSpecificGameNumber(['1','2','3','5','7','9','11','13','15','19','20','21','23','24','25'],"1526");

        echo "Você acertou ".$result["hits"]." números no jogo ".$result["gameNumber"].".";
    }
    
    public function getLatestNumbersDrawnAndGameNumber()
    {
        $lastGame = file_get_contents($this->urlApi);

        $lasGameArray = json_decode($lastGame);

        return array("numbersDrawn"=>$lasGameArray->{"sorteio"} , "gameNumber"=>$lasGameArray->{"numero"});
    }

    /*
    *Funcao para pegar os numeros sorteados em um jogo especifico.
    * @access public
    * @param numero do sorteio
    * return array com numeros sortedos
    */
    public function getNumbersDrawnOfSpecificGameNumber($gameNumber)
    {
        $specificGame = file_get_contents($this->urlApi.$gameNumber);

        $specificGameArray = json_decode($specificGame);

        return array("numbersDrawn"=>$specificGameArray->{"sorteio"} , 
                     "gameNumber"=>$lasGameArray->{"numero"},
                     "date"=>$lasGameArray->{"data"});
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
                         array('chat_id' => $chatId,
                         "text" => $text,
                         'reply_markup' => '{"keyboard":['.$this->keyboardMenu.'],
                         "resize_keyboard":true,
                         "one_time_keyboard":true}')
                        );

      $this->deleteUserArchive($chatId.".csv");
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
	
		$this->deleteUserArchive($chatId.".csv");
		
		$this->sendMessage("sendMessage", 
                            array('chat_id' => $chatId, 
                                  "text" => 'Jogo exlcuido.',
                                  'reply_markup' => '{"keyboard":['.$this->keyboardMenu.'],
                                  "resize_keyboard":true}'
                                 )
                        );
	}

    public function deleteUserArchive($archiveName){
        unlink($archiveName);
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

    public function processSpecificGame($chatId){

        if(file_exists($chatId.".csv")){
            $this->sendMessage("sendMessage", 
                        array('chat_id' => $chatId, 
                                "text" => 'com qual jogo você quer conferir?'
                                )
                    );

            $userArchive = fopen($chatId."specific.csv","a");

            fclose($userArchive);
        }else{
            $this->sendMessage("sendMessage", 
                        array('chat_id' => $chatId, 
                                "text" => 'primeiro você deve cadastrar um jogo.'
                                )
                    );
        }
    }

    public function processCheckHitsLastGame($chatId){
        if(file_exists($chatId.".csv")){
            $gameNumbersDrawnaAndGameNumber = $this->getLatestNumbersDrawnAndGameNumber();

            $archive = file_get_contents($chatId.".csv"); 

            $numbers = explode(";",$archive);

            $numberOfHits = $this->CheckNumberOfHits($gameNumbersDrawnaAndGameNumber["numbersDrawn"],$numbers);

            $this->sendMessage("sendMessage", 
                                array('chat_id' => $chatId, 
                                        "text" => 'Você acertou '.$numberOfHits.' números no jogo '.$gameNumbersDrawnaAndGameNumber["gameNumber"].'.'
                                        )
                            );
        }else{
            $this->sendMessage("sendMessage", 
                                    array('chat_id' => $chatId, 
                                            "text" => 'primeiro você deve cadastrar um jogo.'
                                            )
                                );
        }
        
    }

    public function processCheckHitsToGameNumber($chatId,$gameNumber){
        $gameNumbersDrawn = $this->getNumbersDrawnOfSpecificGameNumber($gameNumber);

        $archive = file_get_contents($chatId.".csv"); 

        $numbers = explode(";",$archive);

        $numberOfHits = $this-> CheckNumberOfHits($gameNumbersDrawn["numbersDrawn"],$numbers);

        $this->sendMessage("sendMessage", 
                            array('chat_id' => $chatId, 
                                    "text" => 'Você acertou '.$numberOfHits.' números no jogo '.$gameNumber.'. Este jogo foi realizado em '.$gameNumbersDrawn["date"].'.',
                                    'reply_markup' => '{"keyboard":['.$this->keyboardMenu.'],
                                    "resize_keyboard":true')
                        );

        $this->deleteUserArchive($chatId."specific.csv");
    }

    public function processCheckGame($chatId,$numbers){
        $this->sendMessage("sendMessage", 
                            array('chat_id' => $chatId, 
                                    "text" => 'Ok, números anotados',
                                    'reply_markup' => '{"keyboard":['.$this->keyboardMenu.'],
                                    "resize_keyboard":true}'
                                    )
                        );	
        
        //$userNumbers = implode(",",$numbers);
    }

    public function processMessageReceive($chatId){
            
        if($this->message == "/start")
        {		
            $this->processStartBot($chatId,'Olá, seja bem vindo ao LotofacilBot.');
        }
        else if($this->message == "Cadastrar jogo")
        {
            $this->processNewGame($chatId);    		
        }
        else if($this->message == "Excluir Jogo")
        {
            $this->processDeleteGame($chatId);
        }
        else if($this->message == "Outro Sorteio")
        {
            $this->processSpecificGame($chatId);
        }
        else if($this->message == "Ultimo Sorteio")
        {
            $this->processCheckHitsLastGame($chatId);
        }
        else if(file_exists($chatId."specific.csv"))
        {
            $this->processCheckHitsToGameNumber($chatId,$this->message);
        }
        else if(file_exists($chatId.".csv"))
        {		
            $archive = file_get_contents($chatId.".csv"); 
            
            $numbers = explode(";",$archive);

            if(is_numeric($this->message))
            {
                $userArchive = fopen($chatId.".csv","a");

                fwrite($userArchive, $this->message);                        
            }
            if(count($numbers)<=14){
                fwrite($userArchive, ";");

                fclose($userArchive);
            }else{
                fclose($userArchive);
                
                $this->processCheckGame($chatId,$numbers);
            }
        }
        else
        {
            $this->sendMessage("sendMessage", array('chat_id' => $chatId, 
                                                    "text" => 'Desculpe, não entendi.'));
        }
    }
}