<?php
/*
 * Dev: Amir Hossein Karimi
 */
namespace phpcron\CronBot;

use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Request;
use MongoDB\Client;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;

class Hook
{
    /**
     * Version
     *
     * @var string
     */
  protected $version = '0.1.1';



    public function __construct($Data,$Emojy)
    {


        $this->text = false;

        $oUpdate = new Update($Data, BOT_USERNAME);
        if(!$oUpdate->getUpdateType()){
            die('block');
        }

        $UpdateType = $oUpdate->getUpdateType();
        $this->creator = false;
        $this->in_game = false;
        $this->type_user = 'user';
        $this->expire = 'نامحدود';
        $this->setgif_kad = false;
        $this->setgif_wolf = false;
        $this->setgif_qatel = false;

        $this->setgif_khab = false;
        $this->setgif_start = false;
        $this->setgif_ahan = false;
        $this->setgif_dard = false;
        $this->setgif_hakem = false;
        $this->settext_dard = false;
        $this->settext_khab = false;
        $this->settext_ahan = false;
        $this->settext_kad = false;
        $this->settext_start = false;
        $this->settext_hakem = false;


        $this->Command = 0;
        if($UpdateType == "inline_query"){
            $oUpdate = $oUpdate->getInlineQuery();
            $from = $oUpdate->getFrom();
            $Query = $oUpdate->getQuery();
            $this->query = $Query;
            $this->inline = $oUpdate;
            $this->typeChat = $UpdateType;

        }elseif($UpdateType == "callback_query"){
            $oUpdate = $oUpdate->getCallbackQuery();
            $from = $oUpdate->getFrom();
            $oMessage = $oUpdate->getMessage();

            $this->callback_query_id = $oUpdate->getId();
            $this->callback_data = $oUpdate->getData();
            $this->callback_id = $oUpdate->getId();
            $this->callback_text = $oUpdate->getText();
            $this->callback_string = $oUpdate->getData();
            $string =  $oUpdate->getData();
            $this->data = $string;

            $ex =  explode('/',$string);
             if(isset($ex['1'])){
                 $this->Command = 1;
                 $chat_ids = $ex['1'];
             }

        }elseif($UpdateType == "edited_message"){
            $oUpdate = $oUpdate->getChannelPost();
            die('Edit');
        }elseif($UpdateType == "edited_channel_post"){
            $oUpdate = $oUpdate->getChannelPost();
            die('Edit');
        }elseif($UpdateType == "channel_post"){
            $oMessage = $oUpdate->getChannelPost();
            die('Edit');

        }else{

            $oMessage = $oUpdate->getMessage();
            if($oMessage->getFrom()) {
                $from = $oMessage->getFrom();
            }else{
                die('Block');
            }
        }

        $this->TokenPayment = "";






        $this->def_lang = false;
        $BOTAdd = false;
        if(isset($oMessage)) {
            $this->message = $oMessage;
            if($oMessage->getNewChatMembers()) {
                if ($oMessage->botAddedInChat()) {
                    $BOTAdd = true;
                }
            }
         }


        //Request::leaveChat(['chat_id'=> -1001249945319]);
        $message_type = false;
        $this->ReplayTo = false;

        $this->ReplayFullname = false;
        $this->PlayerLink = "Unknow";
        if(isset($from)) {
            if (isset($oMessage)) {
                $message_id = $oMessage->getMessageId();
                $ogp = $oMessage->getChat();
                $message_type = $oMessage->getType();



                if ($oMessage->getReplyToMessage()) {
                    $Replay = $oMessage->getReplyToMessage();
                    $this->Replay = $Replay;
                    $user_id = $Replay->getFrom()->getId();
                    $this->ReplayTo = $user_id;
                    $this->ReplayUsername = $Replay->getFrom()->getUsername();
                    $FullnameReplay = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $Replay->getFrom()->getFirstName() . " " . $Replay->getFrom()->getLastName()));
                    $this->ReplayFullname = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $Replay->getFrom()->getFirstName() . " " . $Replay->getFrom()->getLastName()));
                    $this->PlayerLink = '<a href="tg://user?id=' . $user_id . '">' . $FullnameReplay . '</a>';

                }
            }
            if(isset($oMessage)){
                $text = trim($oMessage->getText(true));
                $this->text = $text;
            }
            if (isset($ogp) && isset($oMessage)) {
                $chat_id = (isset($chat_ids)  ? $chat_ids :  $ogp->getId());
                $typeChat = $ogp->getType();
                $this->groupName = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $ogp->getTitle()));
                $this->chat_id = (float)$chat_id;
                $this->typeChat = $typeChat;
            } elseif(isset($chat_ids)) {
                $this->chat_id = $chat_ids;
            }else{
                $this->chat_id = false;
            }


            $username = $from->getUsername() ?? 'null';
            $user_id = $from->getId();
            $firstname = $from->getFirstName();
            $lastname = $from->getLastName();
            $this->firstname = $firstname;
            $this->lastname = $lastname;
            $full_name = $from->getFirstName() . " " . $from->getLastName();
            $lang_code = $from->getLanguageCode();
            $this->userMode = "general";
            $this->username = $username;
            $this->user_id = (float)$user_id;
            $this->admin = 0;
            $this->allow = 0;


            $redis = new  \Predis\Client(array(
                'scheme' => 'tcp',
                'host' => 'localhost',
                'port' => 6379,
                'database' => 5,
             // 'password' => "",
            ));

            if($Emojy){
                if(!is_numeric(strpos($text,'🤪 خبر چینی')) &&
                    !is_numeric(strpos($text,'🔮 اعلام نقش')) &&
                    !is_numeric(strpos($text,'😇 محافظ'))  &&
                    !is_numeric(strpos($text,'👻 روح')) &&
                    !is_numeric(strpos($text,'👥 لیست گروه ها')) &&
                    !is_numeric(strpos($text,'💰 خرید سکه')) &&
                    !is_numeric(strpos($text,'🛍  فروشگاه')) &&
                    !is_numeric(strpos($text,'📞 پشتیبانی')) &&
                    !is_numeric(strpos($text,'📣 اخبار')) &&
                    !is_numeric(strpos($text,'🩸 برترین کاربران کیل')) &&
                    !is_numeric(strpos($text,'🎓 آکادمی مافیا'))
                ) {
                    if (!$redis->exists('PlayerEmojiBuy:' . $this->user_id)) {
                        $redis->del('inUse:'.$user_id);
                        die('block');
                    }
                }
            }

            $this->collection = (new Client())->wop;











            /*

            if (!$redis->exists('forward148:' . $user_id)) {


                    Request::forwardMessage([
                        'chat_id' => $user_id,
                        'from_chat_id' => -1001186572810 ,
                        'message_id' => 123
                    ]);
                    $redis->set('forward148:' . $user_id, true);

            }
            */












            //$redis->getSet('inUse:'.$user_id,true);

            $this->MinLeague = 1000;
            $this->LeagueName = "؟؟؟؟";




            $this->redis = $redis;

            if (isset($message_id)) {
                $this->message_id = $message_id;
            }

           // $this->CheckUserRegister();

            if(isset($oMessage)){

                    if($oMessage->getCommand() ) {
                        $this->Command = 1;


                    if($redis->exists('userBlocked:'.$this->user_id)){
                        $redis->del('userBlocked:'.$this->user_id);
                        die('Block');
                    }

                    $Nop = $redis;

                    if($Nop->exists('user_flood:'.$this->user_id)){
                        $Get = $Nop->get('user_flood:'.$this->user_id);
                        $MinTime = $Get + 3;
                        if($MinTime > time()){
                            $CountSpam = 1;
                            if($Nop->exists('CountSpaming:'.$this->user_id)){
                                $CountSpam = $Nop->get('CountSpaming:'.$this->user_id) + 1;
                            }
                            $Nop->set('CountSpaming:'.$this->user_id,$CountSpam);
                            if($CountSpam > 15){
                                $Nop->set('userBlocked:'.$this->user_id,true);
                                Request::sendMessage([
                                    'chat_id' => $this->user_id,
                                    'text' => "شما بدلیل اسپم نمودن دستورات ربات از ربات بصورت دائمی بن شده اید!",
                                    'parse_mode' => 'HTML',
                                ]);
                            }
                            $redis->del('inUse:'.$user_id);
                            die('block');

                        }
                    }
                    $Nop->set('user_flood:'.$this->user_id,time());
                    $Nop->del('CountSpaming:'.$this->user_id);


                    }

            }


            $this->fullname = preg_replace('/<?/', '', preg_replace('/<*?>/', '', $full_name)) ?? 'null';

            $this->lang_code = $this->checkLangValidate($lang_code);




            if (isset($ogp)) {
                $this->GetGameId();
                $this->game_id = $this->GameCurrentId ?? $this->generate_username(time() . $user_id);
                $this->JoinLink = JOIN_URL . $this->game_id;
                $this->ChallengeJoin = Challenge_URL . $this->game_id;

            }
            $Pl = $this->CountPlayer();
            $this->Coin = [
                'Mighty' => 60,
                'Vampire' => 80,
                'Romantic' => 50,
                'Normal' => 40,

            ];

            $this->user_link = '<a href="tg://user?id=' . $this->user_id . '">' . $this->fullname . '</a>';


            $defultLang = ($this->def_lang ? $this->def_lang  :  $this->lang_code);
            $this->def_mode = $this->default_mode ?? $this->userMode;
            $this->defaultLang = $defultLang;
            $L = new Lang(FALSE);
            $L->load("main_" . $defultLang, FALSE);
            $this->L = $L;



           $CheckUserJoin =  $this->CheckUserJoinChanel();

           if(!$CheckUserJoin && $this->user_id){
               $text = "❗️ دوست عزیز قبل از هر چیزی لطفا جوین چنل زیر بشو :

https://t.me/cmorghvpnIR

مجدد کارت رو انجام بده بعد جوین شدن.";
                Request::sendMessage([
                   'chat_id' => $this->user_id,
                   'text' => $text,
                   'parse_mode' => 'HTML',
               ]);
               exit();

           }

/*
            if (!$redis->exists('forward15499:' . $user_id)) {
                $count =  $redis->keys('forward15499:*');
                if(count($count) <= 10000) {

                    $re = Request::forwardMessage([
                        'chat_id' => $user_id,
                        'from_chat_id' => -1001232069909,
                        'message_id' => 49,
                    ]);

                    if($re->isOk()) {
                        $redis->set('forward15499:' . $user_id, true);
                    }
                }
            }

*/

            RC::initialize($this);
            if ($UpdateType !== "inline_query" ) {
                $mode = "general";
                if($this->Command) {
                    $mode = RC::Get('game_mode') ?? "general";
                }
                $this->CheckUserIsAdmin();

                $this->GroupGameMode = ($this->GameModeList($mode) ? $mode :  "general");
                $this->GroupDefLang = $this->checkLangValidate(RC::Get('lang'));

                $LG = new Lang(FALSE);
                $LG->load($this->GroupGameMode . "_" . $this->GroupDefLang, FALSE);
                $this->LG = $LG;
                $this->checkGroup();

            }



            $time = time();
            GR::initialize($this);
            $cns = $this->collection->leagueData;
            if($this->redis->get('LeaugeStart')  - $time < 0 ){
                $this->redis->set('LeaugeStart',strtotime('+1 Week'));
                $this->redis->expire('LeaugeStart',strtotime('+1 Week'));
                GR::LegueSave();
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => "لیگ این هفته به اتمام رسید!",
                    'parse_mode' => 'HTML',
                ]);


              $cns->deleteMany([]);
            }

            $this->checkUser($Pl);

            CM::initialize($this);


            if($message_type){
                if($this->typeChat == "private") {
                    if (in_array($message_type, ['document'], true)) {
                        if ($redis->exists('account_status:' . $this->user_id)) {
                            $State = $redis->get('account_status:' . $this->user_id);
                            CM::SendDoc($State);
                        }
                    }
                    if ($redis->exists('account_status_set_text:' . $this->user_id)) {
                        $State = $redis->get('account_status_set_text:' . $this->user_id);
                        CM::SetTextD($State);
                    }

                }
            }

           /// $redis->del('inUse:'.$user_id);
        }


    }




    public static function GameModeList($Mode){
        $ArrayMode = [
            'general',
            'nightclub'
        ];
        return (in_array($Mode,$ArrayMode) ?  true : false);
    }
   public  function checkLangValidate($lang){
        $Lang = array('fa','en','fr','in');
        if(in_array($lang,$Lang)){
            return $lang;
        }else{
            return 'fa';
        }
   }
    public function CheckUserIsAdmin(){




        $chatUser = Request::getChatMember([
            'user_id' => $this->user_id,
            'chat_id' => $this->chat_id,
        ])->getResult();
        $status = 0;
        if($chatUser) {
            $status = $chatUser->getStatus();



        }

        switch ($status){
            case 'creator':
                $this->creator = true;
                $this->allow = 1;
                $this->admin = 1;
            break;
            case 'administrator':
                $this->allow = 1;
                $this->admin = 1;
             break;
            case 'member':
                $this->allow = 1;
                break;
            case 'restricted':
                $this->allow = 1;
                break;
            default:
                $this->allow = 0;
            break;
        }
        if($this->user_id == ADMIN_ID){
            $this->allow = 1;
            $this->admin = 1;
            return true;
        }
    }

    function generate_username($string_name="wop", $rand_no = 200){
        $username_parts = array_filter(explode(" ", strtolower($string_name))); //explode and lowercase name
        $username_parts = array_slice($username_parts, 0, 2); //return only first two arry part

        $part1 = (!empty($username_parts[0]))?substr($username_parts[0], 0,8):""; //cut first name to 8 letters
        $part2 = (!empty($username_parts[1]))?substr($username_parts[1], 0,5):""; //cut second name to 5 letters
        $part3 = ($rand_no)?rand(0, $rand_no):"";

        $username = $part1. str_shuffle($part2). $part3; //str_shuffle to randomly shuffle all characters
        return $username;
    }

    public function randomPassword(
       $length,
       $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
   )
   {
       $str = '';
       $max = mb_strlen($keyspace, '8bit') - 1;
       if ($max < 1) {
           throw new Exception('$keyspace must be at least two characters long');
       }
       for ($i = 0; $i < $length; ++$i) {
           $str .= $keyspace[random_int(0, $max)];
       }
       return $str;
   }

   public function GetGameId(){
       $data = $this->text;
        if($data){
            if(strpos($this->text, 'joinToGAME_') !== false){
                $EX = explode('_',$data);
                $this->GameCurrentId = $EX['1'];
                $cn = $this->collection->games;
                $result = $cn->findOne(['game_id' => $EX['1']]);
                if($result) {
                    $this->chat_id = $result['group_id'];
                    return true;
                }
            }
        }
       $cn = $this->collection->games;
       $result = $cn->findOne(['group_id' => (float) $this->chat_id]);
       if($result) {
           if ($result['game_id']) {
               $this->GameCurrentId = $result['game_id'];
               return true;
           }
       }
       return false;
   }
   public function CountPlayer(){
       $this->Player = [];

           // Database connection
           $cn = $this->collection->Players;
           /*Checking is there anyone with this name
             * Retun Number Integer
            */
           $count = $cn->findOne(['user_id' => $this->user_id]);

           $Cn_Data = ($count ? 1 : 0);
           $this->ExitPlayer = $Cn_Data;
           $array = [];
           if ($Cn_Data) {
               $array = iterator_to_array($count);
               $this->Player =$array;
               $this->def_lang = $array['def_lang'] ?? "fa";
               $this->default_mode = $array['game_mode'] ?? "general";
               $this->type_user = (isset($this->Player['user_role']) ? $this->Player['user_role'] : 'user');
               $this->expire = (isset($this->Player['expire']) ?   jdate('Y-m-d',$this->Player['expire']) : 'نامحدود');



               if($this->type_user == 'vip') {
                   // gif
                   if(isset($this->Player['setgif_kad'])) {
                       $this->setgif_kad = ($this->Player['setgif_kad'] !== 'remove' ? $this->Player['setgif_kad'] : false);
                   }
                   if(isset($this->Player['setgif_wolf'])) {
                       $this->setgif_wolf = ($this->Player['setgif_wolf'] !== 'remove' ? $this->Player['setgif_wolf'] : false);;
                   }
                   if(isset($this->Player['setgif_qatel'])) {
                       $this->setgif_qatel = ($this->Player['setgif_qatel'] !== 'remove' ? $this->Player['setgif_qatel'] : false);;
                   }
                   if(isset($this->Player['setgif_khab'])) {
                       $this->setgif_khab = ($this->Player['setgif_khab'] !== 'remove' ? $this->Player['setgif_khab'] : false);
                   }
                   if(isset($this->Player['setgif_start'])) {
                       $this->setgif_start = ($this->Player['setgif_start'] !== 'remove' ? $this->Player['setgif_start'] : false);
                   }
                   if(isset($this->Player['setgif_ahan'])) {
                       $this->setgif_ahan = ($this->Player['setgif_ahan'] !== 'remove' ? $this->Player['setgif_ahan'] : false);
                   }
                   if(isset($this->Player['setgif_dard'])) {
                       $this->setgif_dard = ($this->Player['setgif_dard'] !== 'remove' ? $this->Player['setgif_dard'] : false);
                   }
                   if(isset($this->Player['setgif_hakem'])) {
                       $this->setgif_hakem = ($this->Player['setgif_hakem'] !== 'remove' ? $this->Player['setgif_hakem'] : false);
                   }
                   // text
                   if(isset($this->Player['settext_dard'])) {
                       $this->settext_dard = ($this->Player['settext_dard'] !== 'remove' ? $this->Player['settext_dard'] : false);
                   }
                   if(isset($this->Player['settext_khab'])) {
                       $this->settext_khab = ($this->Player['settext_khab'] !== 'remove' ? $this->Player['settext_khab'] : false);
                   }
                   if(isset($this->Player['settext_ahan'])) {
                       $this->settext_ahan = ($this->Player['settext_ahan'] !== 'remove' ? $this->Player['settext_ahan'] : false);
                   }
                   if(isset($this->Player['settext_kad'])) {
                       $this->settext_kad = ($this->Player['settext_kad'] !== 'remove' ? $this->Player['settext_kad'] : false);
                   }
                   if(isset($this->Player['settext_start'])) {
                       $this->settext_start = ($this->Player['settext_start'] !== 'remove' ? $this->Player['settext_start'] : false);
                   }
                   if(isset($this->Player['settext_hakem'])) {
                       $this->settext_hakem = ($this->Player['settext_hakem'] !== 'remove' ? $this->Player['settext_hakem'] : false);
                   }



               }

           }


           return ['cn' => $Cn_Data];



   }
    public function checkGroup(){

        /*
        if($this->typeChat == "group" || $this->typeChat == "supergroup"){
        $Array = [];
        if(in_array($this->chat_id,$Array)){
            Request::leaveChat(['chat_id'=> $this->chat_id]);
            Request::sendMessage([
                'chat_id' => ADMIN_ID,
                'text' => "leaved",
            ]);
        }
        if(empty($this->groupName)){
            return false;
        }
        $cn = $this->collection->groups;
        $count = $cn->count(['chat_id' => $this->chat_id]);

        if($count > 0) {
            $find = $cn->findOne(['chat_id' => $this->chat_id]);
            $array = iterator_to_array($find);
            if ($array) {

                if ($this->groupName !== $array['group_name']) {
                    $cn->updateOne(
                        ['chat_id' => $this->chat_id],
                        ['$set' => ['group_name' => $this->groupName]]
                    );

                    RC::GetSet($this->groupName, 'group_name');
                    $L = "Last Group Name :" . $array['group_name'] . " New Group Name Update To:  " . $this->groupName;
                   return Request::sendMessage([
                        'chat_id' => $this->chat_id,
                        'text' => $L,
                    ]);

                }
            }
        }
       }
        return false;
        */
    }
    public function CheckUserJoinChanel(){
       $result =  Request::getChatMember([
           'chat_id' => -1001988570166,
           'user_id' => $this->user_id,
        ]);

       if($result->isOk()){
           $Status = $result->getResult()->getStatus();
           $Array = array('creator','administrator','member','restricted','kicked');
           if(in_array($Status,$Array)) {
               return true;
           }
       }

           return  false;
    }
    public function CheckUserJoinChanel2(){
        $result =  Request::getChatMember([
            'chat_id' => -1001299380813,
            'user_id' => $this->user_id,
        ]);

        if($result->isOk()){
            $Status = $result->getResult()->getStatus();
            $Array = array('creator','administrator','member','restricted','kicked');
            if(in_array($Status,$Array)) {
                return true;
            }
        }

        return  false;
    }
   public function CheckUserJoinChanel3(){
        $result =  Request::getChatMember([
            'chat_id' => -1001308739151,
            'user_id' => $this->user_id,
        ]);

        if($result->isOk()){
            $Status = $result->getResult()->getStatus();
            $Array = array('creator','administrator','member','restricted','kicked');
            if(in_array($Status,$Array)) {
                return true;
            }
        }

        return  false;
    }
    public function CheckUserJoinChanel4(){
        $result =  Request::getChatMember([
            'chat_id' => -1001411431692,
            'user_id' => $this->user_id,
        ]);

        if($result->isOk()){
            $Status = $result->getResult()->getStatus();
            $Array = array('creator','administrator','member','restricted','kicked');
            if(in_array($Status,$Array)) {
                return true;
            }
        }

        return  false;
    }


    public function checkUser($countPlayer){
        // Database connection



        $l = $countPlayer;
        $count = $l['cn'];

        // Check if the Message is in a private message
        if($this->typeChat == "private"){

            $cn = $this->collection->Players;
            // Add user to player list if not available in database
             if($count === 0){



                 $cn->insertOne([
                     'username'          =>      $this->username,
                     'fullname'          =>      $this->fullname,
                     'user_id'           =>      $this->user_id,
                     'lang_code'         =>      $this->lang_code,
                     'def_lang'          =>      $this->lang_code,
                     'game_mode'         =>      $this->userMode,
                     'coin'              =>       500,
                     'top'               =>      0,
                     'credit'            =>      0,
                     'total_game'        =>      0,
                     'SurviveTheGame'    =>      0,
                     'SlaveGames'        =>      0,
                     'LoserGames'        =>      0,
                     'TheFirstGame'      =>      jdate('Y-m-d H:i:s'),
                     'TheFirstGameGMT'   =>      date('Y-m-d H:i:s'),
                     'TheLastGame'       =>      0,
                     'Site_Username'     =>      0,
                     'Site_Password'     =>      0,
                     'LoginToSite'       =>      0,
                     'ActivePhone'       =>      "",
                     'PhoneNumber'       =>      0,
                 ]);


                 $Admin = GR::CheckUserGlobalAdmin(ADMIN_ID);
                 $inline_keyboard =  GR::GetBanlistKeyboard($Admin,$this->user_id);

                 Request::sendMessage([
                     'chat_id' => ADMIN_ID,
                     'text' => "حساب کاربری جدید: ".$this->user_link."\n در تاریخ : ".jdate('Y F d H:i:s')."\n ای دی کاربری : ".$this->user_id,
                     'reply_markup' => $inline_keyboard,
                     'parse_mode' => 'HTML'
                 ]);

             }else{

                 if($this->Player['username'] !== $this->username || $this->Player['fullname'] !== $this->fullname){
                   if(!isset($this->Player['set_laqab'])) {
                       $this->collection->Players->updateOne(array("user_id" => $this->user_id), ['$set' => ['fullname' => $this->fullname, 'username' => $this->username]]);
                   }else{
                       $this->firstname = $this->Player['set_laqab'];
                       $this->lastname = "";
                   }
                 }




             }


        }

        if($this->typeChat == "private" || $this->typeChat == "callback_query"){

        /*
         * چک کردن آیا کاربر در بازی وجود دارد یا نه
         * اگر بود اطلاعاتش در یک ارایه ذخیره بشه
         */
        $Pl = $this->collection->games_players;
        $countPl = $Pl->count(['user_id' => $this->user_id,'user_state' => 1]);

         if($countPl){
             $result = $Pl->findOne(['user_id' => $this->user_id,'user_state' => 1]);
             $array = iterator_to_array($result);
                 $this->user_role = $array['user_role'] ?? "Unknow";
                 $this->team = $array['team'] ?? "Unknow";
                 $this->user_state = $array['user_state'];
                 $this->game_id = $array['game_id'];
                  $this->in_game = 1;
                  $this->group_id = $array['group_id'];
                  $this->chat_id = $array['group_id'];
                  $this->type_user = (isset($array['user_role']) ? $array['user_role'] : 'user');
                  $this->expire = (isset($array['expire']) ?   $array['expire'] : 'نامحدود');

                 $this->fullname_game = $array['fullname_game'] ?? "Unknow";

                 /*
             if(!$this->redis->exists('PlayerSendKeyboard:'.$this->user_id) ) {
               $groupName = ($this->redis->get($array['group_id'].":group_name") ? $this->redis->get($array['group_id'].":group_name") : 'اسم نداره!');

              $GetLastSear = ($this->redis->exists('MajikSearPlayer:'.$this->user_id) ? (int) $this->redis->get('MajikSearPlayer:'.$this->user_id) : 0);
              $GetLastkhabar = ($this->redis->exists('MajiKhabarPlayer:'.$this->user_id) ? (int) $this->redis->get('MajiKhabarPlayer:'.$this->user_id) : 0);
              $GetLastGhost = ($this->redis->exists('GhostPlayer:'.$this->user_id) ? (int) $this->redis->get('GhostPlayer:'.$this->user_id) : 0);
              $GetLastHiller = ($this->redis->exists('MajiKHilPlayer:'.$this->user_id) ? (int) $this->redis->get('MajiKHilPlayer:'.$this->user_id) : 0);

             // Digits with operations
             $keyboards[] = new Keyboard(
                 ['توی گروه : '.$groupName],
                 ["🔮 اعلام نقش ({$GetLastSear})","🤪 خبر چینی ({$GetLastkhabar})"],
                 ["👻 روح ({$GetLastGhost})","😇 محافظ ({$GetLastHiller})"]
             );


                 $keyboard = end($keyboards)
                     ->setResizeKeyboard(true)
                     ->setOneTimeKeyboard(true)
                     ->setSelective(false);

                     Request::sendMessage([
                         'text' => $this->L->_('KeyBoardUseMajik'),
                         'chat_id' => $this->user_id,
                         'reply_markup' => $keyboard,
                     ]);
                     $this->redis->set('PlayerSendKeyboard:' . $this->user_id, true);
             }
             */

         }else{
             $this->user_state = 0;
             $this->in_game = 0 ;
             $this->user_role = "none";
             $this->team = "no";
             $this->group_id  = false;
             $this->fullname_game = "Unknow";


         }

         /*
            $keyboards[] = new Keyboard(
                []
            );
            $keyboard = end($keyboards)
                ->remove();
            Request::sendMessage([
                'text' => $this->L->_('NotInGameCloseKeyboard'),
                'chat_id' => $this->user_id,
                'reply_markup' => $keyboard,
            ]);
            $this->redis->del(['PlayerSendKeyboard:'.$this->user_id]);
         */
        }





    }
    // End Check User Function



}




