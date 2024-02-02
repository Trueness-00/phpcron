<?php

namespace phpcron\CronBot;
use MongoDB\Client;
use Longman\TelegramBot\Request;

class CronJob
{
    /**
     * Version
     *
     * @var string
     */
    protected $version = '0.1.1';

    public const BASE_SPEED = 5.0;
    public const ENDURANCE_FACTOR = 100.0;
    public const JOCKEY_SLOWDOWN = 5.0;
    public const STRENGTH_FACTOR = 0.08;
    public const ALLOWED_RACES = 3;
    public const MAX_DISTANCE = 400.0;
    public const LAST_COMPLETED_RACES = 5;
    public const TOP_COMPLETED_AMOUNT = 3;
    public const PROGRESS_SECONDS = 10.0;
    public const MAX_HORSES_RACE = 8;

    
    /**
     * Group ID
     *
     * @var string
     */
    protected $key = 's109v@#A45adsd';

    /**
     * Game ID
     *
     * @var string
     */
    public $action = '';

    public $usKey = '';
    /**
     * PDO object
     *
     * @var \PDO
     */
    protected $db;


    public function __construct($data)
    {
        if(!is_array($data)){
            die('Block');
        }


        $redis = new  \Predis\Client(array(
            'scheme' => 'tcp',
            'host' => 'localhost',
            'port' => 6379,
            'database' => 5,
            //'password' => "zVp7wzN9vP",
        ));

        $this->redis = $redis;
        $this->collection  = (new Client())->wop;

        $this->action =  $data['action'];
        $this->data = $data;
        $this->usKey = $data['key'];


    }

    public  function searchForId($id, $lang, $array)
    {
        $re = [];
        foreach ($array as $key => $val) {
            if ($val['_id']['game_mode'] === $id and $val['_id']['group_lang'] === $lang) {
                array_push($re, $array[$key]);
            }
        }
        return $re;
    }
    public  function Stand_Deviation($arr)
    {
        $num_of_elements = count($arr);

        $variance = 0.0;

        // calculating mean using array_sum() method
        $average = array_sum($arr) / $num_of_elements;

        foreach ($arr as $i) {
            // sum of squares of differences between
            // all numbers and means.
            $variance += pow(($i - $average), 2);
        }

        return (float)sqrt($variance / $num_of_elements);
    }
    public  function SaveGroupList($game_mode, $lang, $group_id, $score, $data, $groupname)
    {
        $cn = $this->collection->group_list;
        $cn->insertOne([
            'grou_name' => $groupname,
            'group_id' => $group_id,
            'game_mode' => $game_mode,
            'lang' => $lang,
            'avg_PlayerCount' => floor($data['avg_PlayerCount']),
            'avg_gameTime' => floor($data['avg_gameTime']),
            'avg_nobeplayer' => floor($data['avg_nobeplayer']),
            'avg_joinTime' => floor($data['avg_joinTime']),
            'avg_afkedplayer' => floor($data['avg_afkedplayer']),
            'count' => floor($data['count']),
            'PlayerCount' => floor($data['PlayerCount']),
            'gameTime' => floor($data['gameTime']),
            'JoinTime' => (isset($data['JoinTime']) ? floor($data['JoinTime']) : 0),
            'nobeplayer' => floor($data['nobeplayer']),
            'afkedplayer' => floor($data['afkedplayer']),
            'in_list' => true,
            'score' => $score,
            'in' => jdate('Y-m-d H:i:s'),
            'in_amd' => date('Y-m-d H:i:s'),
        ]);
    }
    public  function SaveGroupList_history($game_mode, $lang, $group_id,$score, $data, $groupname)
    {
        $score = (int) $score;
        $cn = $this->collection->group_list_history;
        $result = $cn->findOne(['group_id' => (float) $group_id]);
        if ($result) {
            $array = iterator_to_array($result);
            $UPScore = 0;
            if($score > 0 ) {
                $UPScore = $array['score'] + $score;
            }else{
                $UPScore = $array['score'] - str_replace('-','',$score);
            }
            $cn->updateOne(
                ['group_id' => (float) $group_id],
                ['$set' => ['score' => (int) $UPScore  ]],
            );
            $cn->updateOne(array("group_id"=> (int) $group_id),array('$push' => array("listData" => array(
                'date' => jdate("Y-m-d H:i:s"),
                'avg_PlayerCount' => floor($data['avg_PlayerCount']),
                'avg_gameTime' => floor($data['avg_gameTime']),
                'avg_nobeplayer' => floor($data['avg_nobeplayer']),
                'avg_joinTime' => floor($data['avg_joinTime']),
                'avg_afkedplayer' => floor($data['avg_afkedplayer']),
                'count' => floor($data['count']),
                'PlayerCount' => floor($data['PlayerCount']),
                'gameTime' => floor($data['gameTime']),
                'JoinTime' => (isset($data['JoinTime']) ? floor($data['JoinTime']) : 0),
                'nobeplayer' => floor($data['nobeplayer']),
                'afkedplayer' => floor($data['afkedplayer']),
                'score' => (int) $score
            ))));

        }else {
            $cn->insertOne([
                'group_name' => $groupname,
                'group_id' => $group_id,
                'game_mode' => $game_mode,
                'lang' => $lang,
                'listData' => [array(
                    'date' => jdate("Y-m-d H:i:s"),
                    'avg_PlayerCount' => floor($data['avg_PlayerCount']),
                    'avg_gameTime' => floor($data['avg_gameTime']),
                    'avg_nobeplayer' => floor($data['avg_nobeplayer']),
                    'avg_joinTime' => floor($data['avg_joinTime']),
                    'avg_afkedplayer' => floor($data['avg_afkedplayer']),
                    'count' => floor($data['count']),
                    'PlayerCount' => floor($data['PlayerCount']),
                    'gameTime' => floor($data['gameTime']),
                    'JoinTime' => (isset($data['JoinTime']) ? floor($data['JoinTime']) : 0),
                    'nobeplayer' => floor($data['nobeplayer']),
                    'afkedplayer' => floor($data['afkedplayer']),
                    'score' => (int) $score
                )],
                'in_list' => true,
                'score' => (int) $score,
                'in' => jdate('Y-m-d H:i:s'),
                'in_amd' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    
    public  function FilterN($data){
        return preg_replace('/<?/', '', preg_replace('/<*?>/', '', $data));
    }

    public function handler(){
        if( $this->key !== $this->usKey ){
            die('Block Request');
        }

        if($this->action === 'group_list_history'){

            $ops = [
                ['$group' => ['_id' => [
                    "game_mode" => '$game_mode',
                    "group_id" => '$group_id',
                    "group_lang" => '$group_lang',

                ],
                    'avg_gameTime' => ['$avg' => '$game_time'],
                    'avg_nobeplayer' => ['$avg' => '$nobes_Player'],
                    'avg_joinTime' => ['$avg' => '$joinTime'],
                    'avg_afkedplayer' => ['$avg' => '$afked_player'],
                    'avg_PlayerCount' => ['$avg' => '$player_count'],
                    'gameTime' => ['$sum' => '$game_time'],
                    'nobeplayer' => ['$sum' => '$nobes_Player'],
                    'afkedplayer' => ['$sum' => '$afked_player'],
                    'PlayerCount' => ['$sum' => '$player_count'],
                    'count' => ['$sum' => 1]]],
            ];

            $result = $this->collection->group_stats->aggregate($ops);
            if ($result) {

                $Avg = iterator_to_array($result);

                if ($Avg) {
                    foreach ($Avg as $row) {
                        $NoPerfix = $this->redis;
                        if ($NoPerfix->get("{$row['_id']['group_id']}:group_link")) {

                            $searchObject = $row['_id']['game_mode'];
                            $keys = $this->searchForId($searchObject, $row['_id']['group_lang'], $Avg);


                            $STD = [];
                            $JoinTime = array_column($keys, 'avg_joinTime');
                            if ($JoinTime) {
                                $JoinTimeS = $this->Stand_Deviation($JoinTime);
                            } else {
                                $JoinTimeS = 0;
                            }

                            $STD['JoinTime'] = ($JoinTimeS > 0 ? $JoinTimeS : 1);
                            $STD['SumJoinTime'] = 1;
                            if ($JoinTimeS > 0) {
                                $STD['SumJoinTime'] = array_sum($JoinTime) / count(array_filter($JoinTime));
                            }

                            $GameTime = array_column($keys, 'avg_gameTime');
                            $GameTimeS = $this->Stand_Deviation($GameTime);
                            $STD['GameTime'] = ($GameTimeS > 0 ? $GameTimeS : 1);
                            $STD['SumGameTime'] = 1;
                            if ($GameTimeS > 0) {
                                $STD['SumGameTime'] = array_sum($GameTime) / count(array_filter($GameTime));
                            }

                            $NobesPlayer = array_column($keys, 'avg_nobeplayer');
                            $NobesPlayerS = $this->Stand_Deviation($NobesPlayer);
                            $STD['NobesPlayer'] = ($NobesPlayerS > 0 ? $NobesPlayerS : 1);
                            $STD['SumNobesPlayer'] = 1;
                            if ($NobesPlayerS > 0) {
                                $STD['SumNobesPlayer'] = array_sum($NobesPlayer) / count(array_filter($NobesPlayer));
                            }

                            $AfkedPlayer = array_column($keys, 'avg_afkedplayer');
                            $AfkedPlayerS = $this->Stand_Deviation($AfkedPlayer);
                            $STD['AfkedPlayer'] = ($AfkedPlayerS > 0 ? $AfkedPlayerS : 1);
                            $STD['SumAfkedPlayer'] = 1;
                            if ($AfkedPlayerS > 0) {
                                $STD['SumAfkedPlayer'] = array_sum($AfkedPlayer) / count(array_filter($AfkedPlayer));
                            }


                            $PlayerCount = array_column($keys, 'avg_PlayerCount');
                            $PlayerCountS = $this->Stand_Deviation($PlayerCount);
                            $STD['PlayerCount'] = ($PlayerCountS > 0 ? $PlayerCountS : 1);
                            $STD['SumPlayerCount'] = 1;
                            if ($PlayerCountS > 0) {
                                $STD['SumPlayerCount'] = array_sum($PlayerCount) / count(array_filter($PlayerCount));
                            }
                            $GameCount = array_column($keys, 'count');
                            if($GameCount < 10){
                                continue;
                            }
                            $GameCountS = $this->Stand_Deviation($GameCount);
                            $STD['count'] = ($GameCountS > 0 ? $GameCountS : 1);
                            $STD['SumCount'] = 0;
                            if ($GameCountS > 0) {
                                $STD['SumCount'] = array_sum($GameCount) / count(array_filter($GameCount));
                            }

                            $STD['count'] = ($STD['count'] == 0 ? 1 : $STD['count']);

                            $STD['PlayerCount'] = ($STD['PlayerCount'] == 0 ? 1 : $STD['PlayerCount']);
                            $STD['NobesPlayer'] = ($STD['NobesPlayer'] == 0 ? 1 : $STD['NobesPlayer']);
                            $STD['AfkedPlayer'] = ($STD['AfkedPlayer'] == 0 ? 1 : $STD['AfkedPlayer']);
                            $STD['GameTime'] = ($STD['GameTime'] == 0 ? 1 : $STD['GameTime']);

                            $JoinTimS = ($row['avg_joinTime'] < 2 ? true : false);

                            $score =
                                $row['avg_PlayerCount'] * 5
                                +$row['avg_gameTime'] * 4
                                + $row['avg_joinTime']  * ($JoinTimS ? 2 : -2)
                                + $row['avg_nobeplayer']  * -1
                                + $row['avg_afkedplayer']  * -2
                                + $row['count'] * 2;

                            $this->SaveGroupList_history($row['_id']['game_mode'], $row['_id']['group_lang'], $row['_id']['group_id'], $score, $row, $this->FilterN($NoPerfix->get("{$row['_id']['group_id']}:group_name")));
                            $this->collection->group_stats->deleteMany([]);
                        }
                    }
                }

            }
           }
        if($this->action === 'group_list_update'){
            $this->SaveGroupListUpdateTime();
            $this->collection->group_list->deleteMany([]);

             $result = $this->collection->group_list_history->find([]);
             if($result){
                 $array = iterator_to_array($result);
                 foreach($array as $row){
                  $this->collection->group_list->insertOne([
                         'group_name' => $row['group_name'],
                         'group_id' =>  (float) $row['group_id'],
                         'game_mode' => $row['game_mode'],
                         'lang' => $row['lang'],
                         'listData' => $row['listData'],
                         'in_list' => true,
                         'score' => (int) $row['score'],
                         'in' => jdate('Y-m-d H:i:s'),
                         'in_amd' => date('Y-m-d H:i:s'),
                     ]);
                 }
                 $this->collection->group_list_history->deleteMany([]);
             }

     }

        if($this->action == "reset_send_list"){
            $Keys = $this->redis->keys('sendCoinTo:*');
            foreach ($Keys as $row){
                $this->redis->del([$row]);
            }
        }

        if($this->action == 'bet_update'){
          $findGame = $this->FindBetGame();
          
          if($findGame){
               if($findGame['game_status'] == 'join'){
                   $currentTime = time();
                   $StartTime = $findGame['start_time'];
                   if($StartTime < $currentTime ){
                       $TimePlus = time() + 20;
                       $this->UpdateGameTime($findGame['starter'],(float) $TimePlus,'wait_for_start');
                   }
               }
               if($findGame['game_status'] == 'wait_for_start'){
                   $currentTime = time();
                   $StartTime = $findGame['start_time'];
                   $leftTime= (float)     $StartTime  -  (float)  $currentTime;
                   echo $leftTime;
                   if($leftTime > 0) {
                       Request::sendMessage([
                           'chat_id' => -1001713075877,
                           'text' => "⏰ " . $leftTime . "ثانیه تا شروع ...",
                           'parse_mode' => 'HTML',
                       ]);
                   }else{
                       $this->UpdateGameTime($findGame['starter'],0,'game_started');
                   }
               }

               if($findGame['game_status'] == 'game_started'){
                   $this->UpdateGameTime($findGame['starter'],0,'game_is_progress');
                   $this->StartBetGame();
               }
           //    $this->StartBetGame();
          }
        }
    }
    public   function generateRandom()
    {
        return rand(0, 100) / 10;
    }
    public function StartBetGame(){
        $winHourseIndex = false;
        $winHourseId  = "";
        $HourseList = [
            [
                'id' => 1,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],
            [
                'id' => 2,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],
            [
                'id' => 3,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],
            [
                'id' => 4,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],
            [
                'id' => 5,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],
            [
                'id' => 6,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],
            [
                'id' => 7,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],
            [
                'id' => 8,
                'speed' => 0,
                'strength' => 0,
                'endurance' => 0,
                'text' => '🐴'
            ],

        ];

        $attemp =0;

        $ReStart = [];
        foreach($HourseList as $key => $row){
            $speed =$this->generateRandom();
            $strength =$this->generateRandom();
            $HourseList[$key]['speed'] = $speed;
            $HourseList[$key]['status'] = 0;
            $HourseList[$key]['strength'] = $strength;
            $HourseList[$key]['endurance'] =$this->generateRandom();
            $HourseList[$key]['bestspeed'] = ($HourseList[$key]['speed'] + self::BASE_SPEED);
            $HourseList[$key]['autonomy'] = $HourseList[$key]['endurance'] * self::ENDURANCE_FACTOR;
            $HourseList[$key]['slowdown'] = self::JOCKEY_SLOWDOWN - ($HourseList[$key]['strength'] * self::STRENGTH_FACTOR * self::JOCKEY_SLOWDOWN);
            $HourseList[$key]['timespent'] = 0;
            $HourseList[$key]['distancecovered'] = 0;

        }
        foreach($HourseList as $key => $row) {
            $List = "";
            $List .= "|" . $row['id'] . "|⇨";
            $List .= $row['text'];
            array_push($ReStart, $List);
        }
        $message =  Request::sendMessage([
            'chat_id' => -1001713075877,
            'text' =>  implode(PHP_EOL,$ReStart),
            'parse_mode' => 'HTML',
        ]);
        $win = false;
        $firstId = false;
        $completedHorsesCount = 0;
        $getMaxDistance =  self::MAX_DISTANCE;
        $progressSeconds = self::PROGRESS_SECONDS;
        do {
            $res_in = [];
            foreach($HourseList as $key => $row) {
                $Re =  "";
                $Re .= "|" . $row['id'] . "|⇨";
                $currentDistance = $row['distancecovered'];
                if ($currentDistance < $getMaxDistance) {
                    $horseAutonomy = ['autonomy'];
                    $horseRealSpeed = $this->getHorseRealSpeed($row, $currentDistance);
                    $calculatedDistance = $currentDistance + $horseRealSpeed * $progressSeconds;
                    $calculatedSeconds = $row['timespent'] + $progressSeconds;
                    if ($calculatedDistance > $horseAutonomy && $currentDistance < $horseAutonomy) {
                        $gapMeters = $calculatedDistance - $horseAutonomy;
                        $gapSeconds = $gapMeters / $horseRealSpeed;
                        $horseRealSpeed = $this->getHorseRealSpeed($row, $calculatedDistance);
                        $calculatedDistance = $currentDistance + $gapSeconds * $horseRealSpeed;
                    }

                    if ($calculatedDistance > $getMaxDistance) {
                        $gapMeters = $getMaxDistance - $currentDistance;
                        $calculatedDistance = $currentDistance + $gapMeters;
                        $gapSeconds = $gapMeters / $horseRealSpeed;
                        $calculatedSeconds = $row['timespent'] + $gapSeconds;
                    }
                    $HourseList[$key]['distancecovered'] = round($calculatedDistance, 2);
                    $HourseList[$key]['timespent'] = round($calculatedSeconds, 2);

                } else {
                    $HourseList[$key]['status'] = 1;
                    if(!$firstId) {
                        $firstId = $HourseList[$key]['id'];
                    }
                    ++$completedHorsesCount;
                }
                $splash = floor(($HourseList[$key]['distancecovered'] * 10)  / $getMaxDistance);

                $Re .= str_repeat('#',$splash)."($splash)".$row['text'];
                array_push($res_in,$Re);
            }


            if($completedHorsesCount >= self::MAX_HORSES_RACE){
                $win = true;
            }
            Request::editMessageText([
                'chat_id' => -1001713075877,
                'message_id' => $message->getResult()->getMessageId(),
                'text' =>  implode(PHP_EOL,$res_in)
            ]);

        } while (!$win);

        $List = "";
        $ReNew = [];

        $this->PlayerBetWin($firstId);

        foreach($HourseList as $key => $row) {
            $List = "";
            $List .= "|" . $row['id'] . "|⇨";
            $List .= $row['text'].($firstId == $row['id'] ? '  <strong>برنده</strong>' : '')."(".floor($row['timespent'])." ثانیه )";
            array_push($ReNew, $List);
        }

        Request::sendMessage([
            'chat_id' => -1001713075877,
            'text' =>  "بازی به اتمام رسید نتیجه بازی: ".PHP_EOL.implode(PHP_EOL,$ReNew),
            'parse_mode' => 'HTML',
        ]);
        $this->DelBet();
    }

    public function GetName($user_id){
        $Re = $this->collection->Players->findOne(['user_id' =>(float) $user_id ]);
        if($Re){
            return iterator_to_array($Re);
        }
        return false;
    }


    public function PlayerBetWin($id){
        $Re = $this->collection->player_bets->find(['status' => 'in_game']);
        if($Re){
            $array = iterator_to_array($Re);
            foreach($array as $row) {
                $PLName = $this->GetName($row['user_id']);
                if ($row['hourse_'.$id] > 0){
                    $coin = ($row['hourse_'.$id] * ($id  == 7 ? 8 : ($id == 8 ? 10 : 5)));
                    if ($PLName) {
                    $EndCredit = (((float) $coin) + ((float) $PLName['credit']));

                          $Msg = "✅ کاربر " . $PLName['fullname'] ." مقدار ".$coin." سکه 💰 برنده شد.";
                           $this->UpdateCoin($EndCredit,$row['user_id']);

                        Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => "<strong>میزان ".$coin." سکه به شما اضافه شد برد در شرط بندی.</strong>",
                            'parse_mode' => 'HTML',
                        ]);

                           Request::sendMessage([
                               'chat_id' => -1001713075877,
                               'text' => "<strong>$Msg</strong>",
                               'parse_mode' => 'HTML',
                           ]);
                       }
                }
            }
        }
    }
    public   function UpdateCoin($Coin,$user_id){
        $this->collection->Players->updateOne(array("user_id"=>(float) $user_id),  ['$set' => ['credit' => (float) $Coin]] );

        return true;
    }

    public  function DelBet(){
        $this->collection->bet_game->deleteMany([]);
        $this->collection->player_bets->deleteMany(['status' => 'in_game']);
    }

    public  function getHorseRealSpeed($horse,$distance){
        if ($distance <= $horse['autonomy']) {
            return $horse['bestspeed'];
        }

        return $horse['bestspeed'] -  $horse['slowdown'];
    }
    public function  UpdateGameTime($starter,$time,$status){
        $this->collection->bet_game->updateOne(array("starter" => $starter), ['$set' => ['start_time' => $time,'game_status' => $status]]);
    }
    
    public  function FindBetGame(){
        $result = $this->collection->bet_game->findOne([]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }

    public  function SaveGroupListUpdateTime()
    {
        $cn = $this->collection->api_update;
        $cn->insertOne([
            'jdate' => jdate('Y/F/d H:i:s'),
            'time' => time(),
        ]);
    }
    
    
    


}




