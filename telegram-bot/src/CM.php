<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;

class CM
{
    /**
     * Cron object
     *
     * @var \phpcron\CronBot\cron
     */
    private static $Dt;
    public const BASE_SPEED = 5.0;
    public const ENDURANCE_FACTOR = 100.0;
    public const JOCKEY_SLOWDOWN = 5.0;
    public const STRENGTH_FACTOR = 0.08;
    public const ALLOWED_RACES = 3;
    public const MAX_DISTANCE = 5000.0;
    public const LAST_COMPLETED_RACES = 5;
    public const TOP_COMPLETED_AMOUNT = 3;
    public const PROGRESS_SECONDS = 10.0;
    public const MAX_HORSES_RACE = 8;

    public static function initialize(Hook $Dt)
    {

        if (!($Dt instanceof Hook)) {
            throw new Exception\CronException('Invalid Hook Pointer!');
        }

        self::$Dt = $Dt;
    }



    public static function CM_FreeCoin(){
        return false;
        if(!isset(self::$Dt->Player['credit'])){
            return   Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'شما هنوز بازی در اونیکس ندارید  دوست عزیز!',
                'parse_mode' => 'HTML'
            ]);
        }
        if((int) self::$Dt->Player['total_game'] < 100){
            return   Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'حداقل بایستی 50 بازی داشته باشید!!',
                'parse_mode' => 'HTML'
            ]);
        }


        $NoP = RC::NoPerfix();
        if($NoP->exists('get_free_coin1:'.self::$Dt->user_id)){
            return   Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('GetFreeCoinLast'),
                'parse_mode' => 'HTML'
            ]);
        }
        $last = (int) self::$Dt->Player['credit'];
        GR::MinCreditCredit($last + 100);
        $NoP->set('get_free_coin1:'.self::$Dt->user_id,true);
        Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' => self::$Dt->user_id.PHP_EOL.self::$Dt->fullname,
            'parse_mode' => 'HTML'
        ]);

        return   Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('FreeCoinSuccess'),
            'parse_mode' => 'HTML'
        ]);

    }
    /*
     * Start Command Code
     */
    public static function CM_Start(){

        if(!isset(self::$Dt->text)) {
            if(self::$Dt->user_id) {
                $keyboards[] = new Keyboard(
                    ["🩸 برترین کاربران کیل"],
                    ["👥 لیست گروه ها", "🎓 آکادمی مافیا"],
                    ["💰 خرید سکه", "🛍  فروشگاه"],
                    ["📞 پشتیبانی", "📣 اخبار"]
                );


                $keyboard = end($keyboards)
                    ->setResizeKeyboard(true)
                    ->setOneTimeKeyboard(true)
                    ->setSelective(false);

                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('StartBot'),
                    'reply_markup' => $keyboard,
                    'parse_mode' => 'HTML'
                ]);
            }
        }elseif(strpos(self::$Dt->text, 'joinToGAME_') !== false) {
            /*
            if(RC::CheckExit('GamePl:PlayerJoin:' . self::$Dt->user_id)){
                return false;
            }
*/
            $CheckBan = GR::CheckUserInBan(self::$Dt->user_id);
            if($CheckBan){
                if($CheckBan['state'] == false){
                    if(isset($CheckBan['key'])) {
                        switch ($CheckBan['key']) {
                            case 'ban_ever':
                                $UserLang = self::$Dt->L->_($CheckBan['key']);
                                Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                    'text' => $UserLang,
                                    'parse_mode' => 'HTML']);
                                self::ClearUse();
                                die('Block');
                                break;
                            case 'ban_to':
                                $UserLang = self::$Dt->L->_($CheckBan['key'],array("{0}" => jdate('Y-m-d H:i:s',$CheckBan['time'])));
                                Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                    'text' => $UserLang,
                                    'parse_mode' => 'HTML']);
                                self::ClearUse();
                                die('Block');
                                break;
                        }
                    }
                }
            }



            $Player = GR::CheckGroup();
            $Nop = RC::NoPerfix();
            /*
            if($Player == false){

                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('UserPlayerInGame',self::$Dt->user_link,$Nop->get(self::$Dt->chat_id.":group_name")),
                    'parse_mode' => 'HTML'
                ]);
            }else if(is_array($Player)){
                $keyBoard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('changeBtn'), 'callback_data' => "gpgchplayer/". self::$Dt->chat_id]
                    ]

                );

               return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('PalyerChangeGroup',$Nop->get($Player['chat_id'].":group_name")),
                    'reply_markup' => $keyBoard,
                    'parse_mode' => 'HTML'
                ]);
            }

            */


            $checkLastGame = GR::CheckPlayerInGame();
            if($checkLastGame || RC::CheckExit('GamePl:join_user:'.self::$Dt->user_id)){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('YouInGame'),
                    'parse_mode' => 'HTML'
                ]);
            }


            if(self::$Dt->allow > 0){
                $checkName = GR::CheckNameInGame();
                if($checkName == 0){
                    $max =  (RC::Get("max_player") ? RC::Get("max_player")  :  45);
                    if($max <= GR::CountPlayer()){
                        return Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => self::$Dt->LG->_('MaxPlayer',array("{0}" => GR::CountPlayer())),
                            'parse_mode' => 'HTML'
                        ]);
                    }
                    if(GR::CheckGameId()){

                        $Mode = RC::Get('GamePl:gameModePlayer');

                        /*

                          $Cha_idNot = [-1001257703456];

                         if(isset(self::$Dt->Coin[$Mode]) && !in_array(self::$Dt->chat_id,$Cha_idNot)){
                             $Cr = GR::GetUserCredit();
                             $Coin = self::$Dt->Coin[$Mode];
                             if($Coin > $Cr){
                                 return Request::sendMessage([
                                     'chat_id' => self::$Dt->user_id,
                                     'text' => self::$Dt->L->_('NotCredit',$Coin,$Cr),
                                     'parse_mode' => 'HTML'
                                 ]);
                             }else{
                                 $MinCr = $Cr - $Coin;
                                 GR::MinCreditCredit($MinCr);
                                 Request::sendMessage([
                                     'chat_id' => self::$Dt->user_id,
                                     'text' => self::$Dt->L->_('CreditM',$Coin,$MinCr),
                                     'parse_mode' => 'HTML'
                                 ]);
                             }
                         }

                        */


                        $time = RC::Get( 'timer');
                        $leftTime = $time - time();
                        if($leftTime <= 0 || RC::Get( 'game_state') !== "join"){
                            return false;
                        }

                        RC::GetSet(true, 'GamePl:PlayerJoin:' . self::$Dt->user_id);
                        $GroupLink = RC::Get('group_link') ?? 0;
                        $gp_name = RC::Get('group_name') ?? 'Unknow';
                        if($GroupLink) {
                            $group_name = '<a href="' . $GroupLink . '">' . $gp_name . '</a>';
                        }else{
                            $group_name = $gp_name;
                        }
                        if($Mode == 'coin'){
                           if( (int) self::$Dt->Player['credit'] < 10){
                               return Request::sendMessage([
                                   'chat_id' => self::$Dt->user_id,
                                   'text' => self::$Dt->L->_('NotAnogthCoin'),
                                   'disable_web_page_preview' => 'true',
                                   'parse_mode' => 'HTML'
                               ]);
                           }
                            GR::UpdateCoin(((int) self::$Dt->Player['credit'] - 10), self::$Dt->user_id);
                            Request::sendMessage([
                                'chat_id' => self::$Dt->user_id,
                                'text' => self::$Dt->L->_('MinCoin'),
                                'disable_web_page_preview' => 'true',
                                'parse_mode' => 'HTML'
                            ]);
                        }


                        GR::PlayerJoinTheGame();
                        return Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => self::$Dt->LG->_('JoinTheGame', array("{0}" => $group_name)),
                            'disable_web_page_preview' => 'true',
                            'parse_mode' => 'HTML'
                        ]);

                    }

                    return Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->LG->_('NotFoundGameId'),
                        'parse_mode' => 'HTML'
                    ]);
                }else{
                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->LG->_('NotNameAllow',  array("{0}" => self::$Dt->fullname) ),
                        'parse_mode' => 'HTML'
                    ]);
                }
            }else{
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->LG->_('NotAllowToJoin'),
                    'parse_mode' => 'HTML'
                ]);
            }

        }

    }



    public static function ChangeGroup(){
        $NOp = RC::NoPerfix();
        $UserLastGroup = GR::GetUserLastGroupId();
        Request::editMessageReplyMarkup([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'reply_markup' =>  new InlineKeyboard([]),
        ]);

        if($UserLastGroup['chat_id'] == self::$Dt->chat_id){
            return false;
        }
        GR::ChangeUserGroup();

        if($NOp->exists('change_group:'.self::$Dt->user_id)) {
            $lastChange = $NOp->get('change_group:' . self::$Dt->user_id);
            if($lastChange >= 5){
                return   Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('ErrorGroupChange'),
                    'parse_mode' => 'HTML'
                ]);
            }
            $NOp->set('change_group:' . self::$Dt->user_id, (int) $lastChange + 1);

        }else{
            $lastChange = 1;
            $NOp->set('change_group:' . self::$Dt->user_id,   1);
            $NOp->expire('change_group:' . self::$Dt->user_id,28800);
        }





        return   Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('ChangeSuccessGroup',
                $NOp->get($UserLastGroup['chat_id'].":group_name")
                ,$NOp->get(self::$Dt->chat_id.":group_name")
                ,4 - $lastChange
            ),
            'parse_mode' => 'HTML'
        ]);

    }
    public static function SendMessageGroup(){

        $groups = GR::GetGroups();

        foreach ($groups as $row){

            $WhiteList = GR::GetWhiteList($row['chat_id']);
            if(!$WhiteList){
                Request::sendMessage([
                    'chat_id' =>$row['chat_id'],
                    'text' => self::$Dt->L->_('NotGroupAvi'),
                    'parse_mode' => 'HTML'
                ]);

                //Request::leaveChat(['chat_id' => $row['chat_id']]);
            }
        }


    }
    public static function BotAddToGroup(){

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('BotWelcomeToGroup'),
            'parse_mode' => 'HTML'
        ]);
    }
    public static function CM_SetLink(){

        if(self::$Dt->text == "" || self::$Dt->typeChat == "private"){
            return false;
        }
        if(GR::is_url(self::$Dt->text) == false){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('NotValidUrl'),
                'parse_mode' => 'HTML'
            ]);
        }
        RC::GetSet(self::$Dt->text,'group_link');
        $group_name  =  '<a href="'.self::$Dt->text.'">'.self::$Dt->groupName.'</a>';
        RC::GetSet(self::$Dt->groupName,'group_name');
        GR::UpdateGroupLink(self::$Dt->chat_id,self::$Dt->text);
        return Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('SetLinkOk',array("{0}" =>$group_name)),
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true'
        ]);
    }
    public static function ReCodeLang($code){
        switch ($code){
            case 'fa':
                return 'فارسی';
                break;
            case 'en':
                return 'English';
                break;
            case 'fr':
                return 'French';
                break;
            case 'in':
                return 'Indonesia';
                break;
            default:
                return "Unknown : [{$code}]";
                break;
        }
    }

    /*
     * Set Lang Command Code
     */

    public static function GetLangKeyboad($Callback){
        $allow_LangCode = [];
        $re = [];
        $files = preg_grep('~^main_.*\.ini~', scandir(BASE_DIR . "Strong/Game_Mode/"));
        foreach($files as $file){
            $file = str_replace('main_','',$file);
            $file = str_replace('.ini','',$file);
            if(!in_array($file,$allow_LangCode)){
                array_push($allow_LangCode,$file);
                $re[] =
                    ['text' => self::ReCodeLang($file), 'callback_data' => $Callback.$file ]
                ;

            }
        }

        if($allow_LangCode) {
            $max_per_row = 2; // or however many you want!
            $per_row = sqrt(count($re));
            $rows = array_chunk($re, $per_row === floor($per_row) ? $per_row : $max_per_row);
            $reply_markup = new InlineKeyboard(...$rows);
            return $reply_markup;
        }
        return false;
    }
    public static function CM_Setlang(){

        $reply_markup = self::GetLangKeyboad('UserLang_');
        if($reply_markup) {
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('ChangeUserLang',array("{0}" => self::ReCodeLang(self::$Dt->defaultLang))),
                'reply_markup' => $reply_markup,
            ]);
            if($re->isOk()) {
                if (self::$Dt->typeChat !== "private") {
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('pmSendToPrivate') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }else{
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }

        }


    }




    public static function GetGameMode($for){
        self::$Dt->collection->Players->updateOne(
            ['user_id' => self::$Dt->user_id],
            ['$set' => ['def_lang' => $for]]
        );
        $reply_markup = self::_getGameMode($for,'UserGameMode_');
        if($reply_markup) {
            self::$Dt->LM = new Lang(FALSE);
            self::$Dt->LM->load("main_".$for, FALSE);

            $re = Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => self::$Dt->L->_('ChangeGameModeUser', array("{0}" => self::ReCodeLang($for), "{1}" => self::$Dt->LM->_('game_mode'))),
                'reply_markup' => $reply_markup,
            ]);
        }

    }

    public static function _getGameMode($for,$Callback,$AddAll = false){
        $re = [];
        $Allows = [];
        $files = preg_grep('~^.*_'.$for.'\.ini~', scandir(BASE_DIR . "Strong/Game_Mode/"));
        $lst = new Lang(FALSE);

        if($AddAll){
            $re[] =
                ['text' => self::$Dt->L->_('AllGroup'), 'callback_data' => $Callback."all"];
        }

        foreach($files as $file){
            $file = str_replace('_'.$for,'',$file);
            $file = str_replace('.ini','',$file);
            if(!in_array($file,$Allows)  && $file !== "main"){
                array_push($Allows,$file);
                $lst->load($file."_".$for, FALSE);
                $re[] =
                    ['text' => $lst->_('game_mode'), 'callback_data' => $Callback. $file];
            }
        }


        if($Allows) {
            $max_per_row = 2; // or however many you want!
            $per_row = sqrt(count($re));
            $rows = array_chunk($re, $per_row === floor($per_row) ? $per_row : $max_per_row);
            $reply_markup = new InlineKeyboard(...$rows);
            return $reply_markup;
        }

        return false;
    }
    public static function ChangeGameMode($to){
        self::$Dt->collection->Players->updateOne(
            ['user_id' => self::$Dt->user_id],
            ['$set' => ['game_mode' => $to]]
        );
        self::$Dt->LM = new Lang(FALSE);
        self::$Dt->LM->load($to."_".self::$Dt->defaultLang, FALSE);
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('changedUserLangTo',array("{0}" => self::ReCodeLang(self::$Dt->defaultLang),"{1}" => self::$Dt->LM->_('game_mode'))),
            'parse_mode' => 'HTML',
        ]);

    }


    public static function CM_Help(){
        $site_link = 'https://wolfofpersia.ir';
        $sup_link = 'https://t.me/OnyxWereWolfSupport';
        $group_link = 'https://t.me/OnyxWerewolf';
        $edu_link = "https://t.me/Onyx_edu";

        $array = array("{0}" =>$site_link ,"{1}" =>  $sup_link ,"{2}" =>$group_link ,"{3}" =>$edu_link  );
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  self::$Dt->L->_('HelpCommand',$array),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true'
        ]);

    }

    public static function CM_Config(){

        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

        if(self::$Dt->admin == 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }


        $reply_markup = self::GroupConfigKeyboard();
        $re = Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('whoconfig'),
            'reply_markup' => $reply_markup,
        ]);
        if($re->isOk()) {
            if (self::$Dt->typeChat !== "private") {
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('ConfigSendPrvaite') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }
        }else{
            Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }



    }


    public static function GroupConfigKeyboard(){

        return  new InlineKeyboard([
            ['text' => self::$Dt->L->_('Config_time'), 'callback_data' => 'setting_time/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_roles') , 'callback_data' => 'setting_role/'.self::$Dt->chat_id]
        ],[
            ['text' => self::$Dt->L->_('config_games'), 'callback_data' => 'setting_game/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_group') , 'callback_data' => 'setting_group/'.self::$Dt->chat_id]
        ],[
            ['text' => self::$Dt->L->_('config_save'), 'callback_data' => 'config_done']
        ]);

    }

    public static function configDone(){
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('config_done'),
            'parse_mode' => 'HTML',
        ]);
    }

    public static function BackToConfig(){
        $keyBoard = self::GroupConfigKeyboard();
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('whoconfig'),
            'reply_markup' => $keyBoard,
        ]);
    }
    public static function GetConfigKeyboard($type){

        switch ($type){
            case 'role':
                $keyboard =  new InlineKeyboard([
                    ['text' =>  self::$Dt->L->_('config_role_fool'), 'callback_data' => 'configRoles_Fool/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_role_hypocrite') , 'callback_data' => 'configRoles_hypocrite/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_role_cult'), 'callback_data' => 'configRoles_Cult/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_role_Lucifer'), 'callback_data' => 'configRoles_lucifer/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_Back'), 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                ]);
                break;
            case 'game':
                $keyboard =   new InlineKeyboard([
                    ['text' => self::$Dt->L->_('config_game_cultHunterExposeRole'), 'callback_data' => 'configGame_cultHunterExposeRole/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_game_cultHunterCountNightShow') , 'callback_data' => 'configGame_cultHunterCountNightShow/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_game_RandomeMode'), 'callback_data' => 'configGame_RandomeMode/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_game_Voting_secretly') , 'callback_data' => 'configGame_VotingSecretly/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_game_CountSecretVoting'), 'callback_data' => 'configGame_CountSecretVoting/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_game_PlayerNameSecretVoting') , 'callback_data' => 'configGame_PlayerNameSecretVoting/'.self::$Dt->chat_id]
                ],
                    [
                        ['text' => self::$Dt->L->_('config_game_MuteDie') , 'callback_data' => 'configGame_MuteDie/'.self::$Dt->chat_id]
                    ]
                    ,[
                        ['text' => self::$Dt->L->_('config_Back'), 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                    ]);
                break;
            case 'time':
                $keyboard =  new InlineKeyboard([
                    ['text' =>  self::$Dt->L->_('config_time_NightTimer'), 'callback_data' => 'configTimer_night/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_time_DayTimer') , 'callback_data' => 'configTimer_day/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_time_VotingTimer'), 'callback_data' => 'configTimer_Vote/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_time_SecretVoteTimer') , 'callback_data' => 'configTimer_SectetVote/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_time_JoinTimer'), 'callback_data' => 'configTimer_join/'.self::$Dt->chat_id], ['text' =>  self::$Dt->L->_('config_time_ExtendTimer') , 'callback_data' => 'configTimer_Extend/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_Back'), 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                ]);
                break;
            case 'group':
                $keyboard =   new InlineKeyboard([
                    ['text' =>  self::$Dt->L->_('config_group_Language'), 'callback_data' => 'configGroup_Languge/'.self::$Dt->chat_id], ['text' => self::$Dt->L->_('config_group_gameMode') , 'callback_data' => 'configGroup_GameMode/'.self::$Dt->chat_id]
                ],[
                    ['text' =>  self::$Dt->L->_('config_group_ExposeRole'), 'callback_data' => 'configGroup_ExposeRole/'.self::$Dt->chat_id], ['text' =>self::$Dt->L->_('config_group_ExposeRoleOn') , 'callback_data' => 'configGroup_ExposeRoleOn/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_group_showId'), 'callback_data' => 'configGroup_showId/'.self::$Dt->chat_id], ['text' =>self::$Dt->L->_('config_group_Flee') , 'callback_data' => 'configGroup_Flee/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_group_MaxPlayer'), 'callback_data' => 'configGroup_MaxPlayer/'.self::$Dt->chat_id], ['text' =>self::$Dt->L->_('config_group_Extend') , 'callback_data' => 'configGroup_Extend/'.self::$Dt->chat_id]
                ],[
                    ['text' =>self::$Dt->L->_('config_group_PinMessage') , 'callback_data' => 'configGroup_PinMessage/'.self::$Dt->chat_id] , ['text' => self::$Dt->L->_('config_group_Roles'), 'callback_data' => 'configGroup_Roles/'.self::$Dt->chat_id]
                ],[
                    ['text' => self::$Dt->L->_('config_viprole'), 'callback_data' => 'setting_viprole/'.self::$Dt->chat_id]
                ],[
                    ['text' => 'بازگشت', 'callback_data' => 'backtoconfig/'.self::$Dt->chat_id]
                ]);
                break;
            case 'viprole':
                $allowdRole = [
                    'role_BlackKnight',
                    'role_BrideTheDead',
                    //'role_hipo',
                    'role_dian',
                    'role_Chiang',
                    'role_kentvampire',
                    'role_betaWolf',
                    'role_iceWolf',
                    'role_Lilis',
                    'role_Magento',
                    'role_franc',
                    'role_Mummy',
                    'role_Joker',
                    'role_Harly',
                    'role_Archer',
                    'role_davina',
                    //      'role_Botanist',
                    'role_Phoenix',
                    'role_babr',
                    'role_qhost',
                   // 'role_javidShah',
                    'role_Princess',
                    //     'role_Mouse',
                    //     'role_Watermelon',
                    //     'role_Bomber',
                    'role_dinamit',
                    //     'role_hellboy',
                ];
                $re = [];
                foreach ($allowdRole as $role){
                    $checkBuy = GR::findLastAddRole(self::$Dt->chat_id,$role);
                    $configKey = "(🔓)";
                    $callBack = 'BuyRoleGroup/'.self::$Dt->chat_id."/$role";
                    if($checkBuy){
                        if($checkBuy['status']){
                            $configKey = "(🟢)";
                            $callBack = 'OfOnStatusRo/'.self::$Dt->chat_id."/$role";
                        }else{
                            $configKey = "(🔴)";
                            $callBack = 'OfOnStatusRo/'.self::$Dt->chat_id."/$role";
                        }
                    }

                    $re[] =
                        ['text' => self::$Dt->LG->_($role."_n")."  ".$configKey, 'callback_data' => $callBack]
                    ;

                }

                $re[] =
                    ['text' => self::$Dt->L->_('RolesBack'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                ;

                $max_per_row = 2; // or how many you want!
                $per_row = sqrt(count($re));
                $rows = array_chunk($re, $per_row === floor($per_row) ? $per_row : $max_per_row);
                $text = self::$Dt->L->_('BuyRoleHelp', array("{0}" => RC::Get('group_name')));
                $keyboard =   new InlineKeyboard(...$rows);
                break;
            case 'unlockAll':
                GR::UnlockAllRole();
                return self::ConfigGroup("Roles");
                break;
        }

        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('whoconfig'),
            'reply_markup' => $keyboard,
        ]);

    }

    public static function ConfigRole($type){
        switch ($type){
            case 'Fool':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_fool"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_fool"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_fool");
                $text = self::$Dt->L->_('allowNaqshAhmaq',  array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'hypocrite':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_hypocrite"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_hypocrite"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_hypocrite");
                $text = self::$Dt->L->_('allowNaqshMonfeq', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'Cult':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_Cult"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_Cult"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_Cult");
                $text = self::$Dt->L->_('allowNaqshferqe', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'lucifer':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/role_Lucifer"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/role_Lucifer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_role/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("role_Lucifer");
                $text = self::$Dt->L->_('allow_lucifer', array("{0}" => self::$Dt->L->_($current)));
                break;
        }

        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);

    }

    public static function ConfigGroup($type){
        switch ($type){
            case 'GameMode':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('Normal'), 'callback_data' => 'configureGroup_Normal/' . self::$Dt->chat_id."/type_mode"]
                    ], [
                    ['text' => self::$Dt->L->_('Chaos'), 'callback_data' => 'configureGroup_Chaos/' . self::$Dt->chat_id."/type_mode"]
                ], [
                    ['text' => self::$Dt->L->_('Players'), 'callback_data' => 'configureGroup_Players/' . self::$Dt->chat_id."/type_mode"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("type_mode") ?? "Players";
                $text = self::$Dt->L->_('chnageGameMode', array("{0}" => self::ReCodeLang($current)));
                break;
            case 'Languge':
                $inline_keyboard = self::GetLangKeyboad('GroupLang/'.self::$Dt->chat_id."/");
                $current = GR::GetGroupSe("lang") ?? "fa";
                $text = self::$Dt->L->_('ChangeGroupLang', array("{0}" => self::ReCodeLang($current)));
                break;
            case 'ExposeRoleOn':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('show'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/expose_role_after_dead"]
                    ], [
                    ['text' => self::$Dt->L->_('hidden'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/expose_role_after_dead"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("expose_role_after_dead");
                $text = self::$Dt->L->_('efshaNaqshSetting',array("{0}"=>  self::$Dt->L->_($current)));
                break;
            case 'PinMessage':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/PinMessage_on_group"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/PinMessage_on_group"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("PinMessage_on_group");
                $text = self::$Dt->L->_('PinMessage_on_group', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'Roles':
                $inline_keyboard = GR::RolesKeyboard();
                $text = self::$Dt->L->_('HowToCustomRole');
                break;
            case 'ExposeRole':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onlyUp'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/expose_role"]
                    ], [
                    ['text' => self::$Dt->L->_('rolNo'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/expose_role"]
                ], [
                    ['text' => self::$Dt->L->_('all'), 'callback_data' => 'configureGroup_all/' . self::$Dt->chat_id."/expose_role"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("expose_role");
                $text = self::$Dt->L->_('HowToshowRol', array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'Flee':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/Flee"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/Flee"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("allow_flee");
                $text = self::$Dt->L->_('allowFleeAtGame', array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'showId':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('show'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/show_user_id"]
                    ], [
                    ['text' => self::$Dt->L->_('hidden'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/show_user_id"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("show_user_id");
                $text = self::$Dt->L->_('allowShowId', array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'Extend':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/allow_extend"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/allow_extend"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("allow_extend");
                $text = self::$Dt->L->_('extendForPlayer',array("{0}"=>self::$Dt->L->_($current)));
                break;
            case 'MaxPlayer':
                $allowGroup = ALLOW_60;
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 15, 'callback_data' => 'configureGroup_15/' . self::$Dt->chat_id."/max_player"]
                    ], [
                    ['text' => 20, 'callback_data' => 'configureGroup_20/' . self::$Dt->chat_id."/max_player"]
                ], [
                    ['text' => 30, 'callback_data' => 'configureGroup_30/' . self::$Dt->chat_id."/max_player"]
                ], [
                    ['text' => 35, 'callback_data' => 'configureGroup_35/' . self::$Dt->chat_id."/max_player"]
                ], [
                    ['text' => 45, 'callback_data' => 'configureGroup_45/' . self::$Dt->chat_id."/max_player"]
                ]
                    ,
                    [
                        ['text' => "50 ".(in_array(self::$Dt->chat_id,$allowGroup) ? "" : "🔐"), 'callback_data' => (in_array(self::$Dt->chat_id,$allowGroup) ? 'configureGroup_50/' . self::$Dt->chat_id."/max_player" : "NotAllow")]
                    ],
                    [
                         ['text' => "60 ".(in_array(self::$Dt->chat_id,$allowGroup) ? "" : "🔐"), 'callback_data' => (in_array(self::$Dt->chat_id,$allowGroup) ? 'configureGroup_60/' . self::$Dt->chat_id."/max_player" : "NotAllow")]
                    ]
                    , [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_group/' . self::$Dt->chat_id]
                    ]

                );

                $current = (GR::GetGroupSe("max_player") ? GR::GetGroupSe("max_player") :  35);
                $text = self::$Dt->L->_('MaxPlayerJoin',array("{0}"=>$current));
                break;

        }

        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);

    }


    public static function ConfigTimer($type){
        switch ($type){
            case 'day':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/day_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/day_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/day_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/day_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/day_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("day_timer") ?? 90;
                $text = self::$Dt->L->_('timeDayFaq',array("{0}"=>$current));
                break;
            case 'night':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/night_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/night_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/night_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/night_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/night_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("night_timer") ?? 90;
                $text = self::$Dt->L->_('timeNightTimer',array("{0}"=>$current));
                break;
            case 'Vote':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/vote_timer"]
                    ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("vote_timer") ?? 90;
                $text = self::$Dt->L->_('lynchTimerFq',array("{0}"=>$current));
                break;
            case 'SectetVote':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/secret_timer"]
                    ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/secret_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/secret_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/vote_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_timer") ?? 90;
                $text = self::$Dt->L->_('lynchFqt',array("{0}"=>$current));
                break;
            case 'join':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/join_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/join_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/join_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/join_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/join_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("join_timer") ?? 90;
                $text = self::$Dt->L->_('timeJoinTimer',array("{0}"=>$current));
                break;
            case 'Extend':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 60, 'callback_data' => 'configureGroup_60/' . self::$Dt->chat_id."/max_extend_timer"]
                    ], [
                    ['text' => 90, 'callback_data' => 'configureGroup_90/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                    ['text' => 120, 'callback_data' => 'configureGroup_120/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                    ['text' => 180, 'callback_data' => 'configureGroup_180/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                    ['text' => 300, 'callback_data' => 'configureGroup_300/' . self::$Dt->chat_id."/max_extend_timer"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_time/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("max_extend_timer") ?? 90;
                $text = self::$Dt->L->_('maxTimesetting',array("{0}"=>$current));
                break;

        }
        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);
    }

    public static function ConfigGame($type){
        switch ($type){
            case 'cultHunterExposeRole':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/cult_hunter_expose_role"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/cult_hunter_expose_role"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("cult_hunter_expose_role");
                $text = self::$Dt->L->_('Hunting_shekar', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'cultHunterCountNightShow':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => 1, 'callback_data' => 'configureGroup_1/' . self::$Dt->chat_id."/cultHunter_NightShow"]
                    ], [
                    ['text' => 2, 'callback_data' => 'configureGroup_2/' . self::$Dt->chat_id."/cultHunter_NightShow"]
                ], [
                    ['text' => 3, 'callback_data' => 'configureGroup_3/' . self::$Dt->chat_id."/cultHunter_NightShow"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("cultHunter_NightShow") ?? 2;
                $text = self::$Dt->L->_('Hunting_shekar', array("{0}" => $current));
                break;
            case 'MuteDie':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/mute_die"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/mute_die"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("mute_die");
                $text = self::$Dt->L->_('MuteDieConfig', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'VotingSecretly':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/secret_vote"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/secret_vote"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_vote");
                $text = self::$Dt->L->_('SecretVoteEnable',array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'RandomeMode':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/randome_mode"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/randome_mode"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("randome_mode");
                $text = self::$Dt->L->_('allowRandomMode', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'CountSecretVoting':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/secret_vote_count"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/secret_vote_count"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_vote_count");
                $text = self::$Dt->L->_('type_hide_vote_end', array("{0}" => self::$Dt->L->_($current)));
                break;
            case 'PlayerNameSecretVoting':
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('onr'), 'callback_data' => 'configureGroup_onr/' . self::$Dt->chat_id."/secret_vote_name"]
                    ], [
                    ['text' => self::$Dt->L->_('offr'), 'callback_data' => 'configureGroup_offr/' . self::$Dt->chat_id."/secret_vote_name"]
                ], [
                        ['text' => self::$Dt->L->_('cancel'), 'callback_data' => 'setting_game/' . self::$Dt->chat_id]
                    ]
                );
                $current = GR::GetGroupSe("secret_vote_name");
                $text = self::$Dt->L->_('type_hide_vote_show_userName', array("{0}" => self::$Dt->L->_($current)));
                break;
        }

        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);
    }
    public static function ChangeGroupConfig($key,$val){


        $back = true;
        switch ($key){
            case 'role_fool':
                $change = self::$Dt->L->_('role_fool_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_hypocrite':
                $change = self::$Dt->L->_('role_hypocrite_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_Cult':
                $change = self::$Dt->L->_('role_Cult_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_Lucifer':
                $change = self::$Dt->L->_('role_Lucifer_change',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'type_mode':
                $change = self::$Dt->L->_('TypeModeChangedTo',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'expose_role_after_dead':
                $change = self::$Dt->L->_('changeEfshaNaqsh',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'expose_role':
                $change = self::$Dt->L->_('rolChnaged',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'PinMessage_on_group':
                $change = self::$Dt->L->_('PinMessage_on_groupChange', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'Flee':
                $change = self::$Dt->L->_('fleeSettingChanged',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'show_user_id':
                $change = self::$Dt->L->_('chnagedShowId',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'allow_extend':
                $change = self::$Dt->L->_('extendforPlayerChang',  array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'max_player':
                $change = self::$Dt->L->_('changeMaxPlayer',array("{0}"=> $val));
                break;
            case 'day_timer':
                $change = self::$Dt->L->_('chnageDayTimerSetting',array("{0}"=> $val));
                break;
            case 'night_timer':
                $change = self::$Dt->L->_('chnageNightTimerSetting',array("{0}"=> $val));
                break;
            case 'vote_timer':
                $change = self::$Dt->L->_('ChangeVoteTimer',array("{0}"=> $val));
                break;
            case 'secret_timer':
                $change = self::$Dt->L->_('changelynchTimer',array("{0}"=> $val));
                break;
            case 'join_timer':
                $change = self::$Dt->L->_('changeJoinTimer',array("{0}"=> $val));
                break;
            case 'max_extend_timer':
                $change = self::$Dt->L->_('chnagedMaxTimeJoin',array("{0}"=> $val));
                break;
            case 'cultHunter_NightShow':
                $change = self::$Dt->L->_('changeHuntingShekarDay', array("{0}"=> $val));
                break;
            case 'cult_hunter_expose_role':
                $change = self::$Dt->L->_('changeHuntingShekar', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'secret_vote':
                $change = self::$Dt->L->_('SecretVoteEnableChange', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'mute_die':
                $change = self::$Dt->L->_('chnagedMuteDie', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'randome_mode':
                $change = self::$Dt->L->_('chnagedRandMode', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'secret_vote_count':
                $change = self::$Dt->L->_('type_hide_vote_end_l', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'secret_vote_name':
                $change = self::$Dt->L->_('type_hide_vote_show_userName_l', array("{0}"=> self::$Dt->L->_($val)));
                break;
            case 'role_rosta':
            case 'role_feramason':
            case 'role_pishgo':
            case 'role_karagah':
            case 'role_elahe':
            case 'role_tofangdar':
            case 'role_rishSefid':
            case 'role_Gorgname':
            case 'role_Nazer':
            case 'role_Hamzad':
            case 'role_kalantar':
            case 'role_Fereshte':
            case 'role_Ahangar':
            case 'role_KhabGozar':
            case 'role_Khaen':
            case 'role_Kadkhoda':
            case 'role_Mast':
            case 'role_Vahshi':
            case 'role_Shahzade':
            case 'role_faheshe':
            case 'role_ngativ':
            case 'role_ahmaq':
            case 'role_PishRezerv':
            case 'role_PesarGij':
            case 'role_NefrinShode':
            case 'role_Solh':
            case 'role_shekar':
            case 'role_clown':
            case 'role_Ruler':
            case 'role_Spy':
            case 'role_Sweetheart':
            case 'role_Knight':
            case 'role_Botanist':
            case 'role_Watermelon':
            case 'role_monafeq':
            case 'role_ferqe':
            case 'role_Royce':
            case 'role_Qatel':
            case 'role_Archer':
            case 'role_lucifer':
            case 'role_WolfJadogar':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
            case 'role_Honey':
            case 'role_enchanter':
            case 'role_WhiteWolf':
            case 'role_forestQueen':
            case 'role_Firefighter':
            case 'role_IceQueen':
            case 'role_Vampire':
            case 'role_Bloodthirsty':
            case 'role_trouble':
            case 'role_Chemist':
            case 'role_Augur':
            case 'role_GraveDigger':

                $getKey = (RC::CheckExit($key) ?  RC::Get($key) : "off");
                $val = ($getKey == "on" ? "off" : "on");
                $back = false;
                break;
        }


        if(isset($change)) {
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('changedSetting', array("{0}" => $change)),
                'parse_mode' => 'HTML',
            ]);
        }

        GR::ChangeConfig($val,$key);

        if($back) {
            self::BackToConfig();
        }else{
            self::ConfigGroup("Roles");
        }
    }


    public static function ChangeGroupLang($to){
        RC::GetSet($to,'lang');
        $inline_keyboard = self::_getGameMode($to,'ChangeGroupGameMode/'.self::$Dt->chat_id."/");
        $data = [
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('config_changeLang',array("{0}" => self::$Dt->L->_((RC::CheckExit('game_mode') ? RC::Get('game_mode') : "general") ))),
            'message_id' => self::$Dt->message_id,
            'reply_markup' => $inline_keyboard,
        ];
        return Request::editMessageText($data);
    }

    public static function ChangeGroupGameMode($to){
        RC::GetSet($to,'game_mode');
        self::$Dt->LM = new Lang(FALSE);
        self::$Dt->LM->load($to."_".RC::Get('lang'), FALSE);
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  self::$Dt->L->_('langChangeTo',array("{0}" => self::$Dt->LM->_('game_mode'))),
            'parse_mode' => 'HTML',
        ]);
        self::BackToConfig();
        return true;
    }

    public static function CM_Players(){
        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 2:
                case 1:
                    $Message_id = RC::Get('Player_ListMessage_ID');
                    if($Message_id){
                        $re = Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->LG->_('playerList'),
                            'reply_to_message_id' => $Message_id,
                        ]);
                        if($re->isOk()) {
                            RC::rpush($re->getResult()->getMessageId(),'deleteMessage');
                        }
                    }
                    break;
                default:
                    return false;
                    break;
            }
        }
        return false;
    }
    public static function CM_Join(){
        if(!self::$Dt->typeChat){
            return false;
        }
        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('GameNotCreate'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('startLastGame'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'deleteMessage');
                    }

                    break;
                case 3:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('JoinChallenge'), 'url' => self::$Dt->ChallengeJoin]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('StartLastChallenge'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'ch:deleteMessage');
                    }
                    break;
            }
        }
    }

    public static function ClearUse(){
        $Nop = RC::NoPerfix();
        $Nop->del('inUse:'.self::$Dt->user_id);
    }

    public static function CM_StartGame($Mode){





        if(!self::$Dt->typeChat){
            self::ClearUse();
            die('');
        }
        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }



        $Array = BANNED_GROUP;
        if(in_array(self::$Dt->chat_id,$Array)){
            return Request::sendMessage(['chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('BotInMen'),
                'parse_mode' => 'HTML']);
        }

        /*
                $NoP= RC::NoPerfix();

                if(!$NoP->exists(self::$Dt->chat_id.':group_link')){

                    return  Request::sendMessage(['chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotLinkSet'),
                        'parse_mode' => 'HTML']);
                }

               */




        /*
           $CheckWhite = GR::GetWhiteList(self::$Dt->chat_id);
           if(!$CheckWhite){
               $Gap = self::$Dt->L->_('NOtAllowGroup');

                 Request::sendMessage(['chat_id' => self::$Dt->chat_id,
                   'text' => $Gap,
                   'parse_mode' => 'HTML']);
              return Request::leaveChat(['chat_id'=> self::$Dt->chat_id]);

           }
        */
           /*
        //-1001455711586
        $BanGroup = [-1001475010092,-1001504037652,-1001652455062,-1001764181675,-1001304201820,-1001642719464];

        if(in_array(self::$Dt->chat_id,$BanGroup) ){
            Request::leaveChat(['chat_id'=> self::$Dt->chat_id]);
            return  Request::sendMessage(['chat_id' => self::$Dt->chat_id,
                'text' => 'گروه مسدود میباشد!',
                'parse_mode' => 'HTML']);
        }
           */

        $CheckBan = GR::CheckUserInBan(self::$Dt->user_id);
        if($CheckBan){
            if($CheckBan['state'] === false){
                if(isset($CheckBan['key'])) {
                    switch ($CheckBan['key']) {
                        case 'ban_ever':
                            $UserLang = self::$Dt->L->_($CheckBan['key']);
                            Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                'text' => $UserLang,
                                'parse_mode' => 'HTML']);
                            self::ClearUse();
                            die('Block');
                            break;
                        case 'ban_to':
                            $UserLang = self::$Dt->L->_($CheckBan['key'],array("{0}" => jdate('Y-m-d H:i:s',$CheckBan['time'])));
                            Request::sendMessage(['chat_id' => self::$Dt->user_id,
                                'text' => $UserLang,
                                'parse_mode' => 'HTML']);
                            self::ClearUse();
                            die('Block');
                            break;
                    }
                }
            }
        }

        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    if(!RC::CheckExit('SetUpRoles')){
                        GR::UnlockAllRole();
                        RC::GetSet(true,'SetUpRoles');
                    }

                    if($Mode == "Vampire"){
                        if(RC::Get('role_Vampire') == "off" || RC::Get('role_Bloodthirsty') == "off"){
                            return   $results = Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('DisabledVampireMode'),
                            ]);
                        }
                    }

                    GR::StartGameForGroup();
                    RC::GetSet($Mode,'GamePl:gameModePlayer');
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink ]
                        ]
                    );
                    $result = Request::sendVideo([
                        'chat_id' => self::$Dt->chat_id,
                        'video' => (self::$Dt->setgif_start ? self::$Dt->setgif_start : RC::RandomGif('start_game',$Mode)),
                        'caption' => self::$Dt->LG->_('startAtGame_'.$Mode, array("{0}" => '<a href="tg://user?id=' . self::$Dt->user_id . '">' . self::$Dt->fullname . '</a>' )).PHP_EOL.self::$Dt->LG->_('StartGameFooter').PHP_EOL.(self::$Dt->settext_start ? self::$Dt->settext_start: '' ),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    RC::GetSet(time(),'GamePl:StartGameAt');
                    if($result->isOk()){
                        RC::rpush($result->getResult()->getMessageId(),'EditMarkup');
                    }else{
                        Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('NotBotEnableGifOnGroup'),

                        ]);
                    }
                    $results = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('player'),
                    ]);
                    if($results->isOk()){
                        if(RC::Get('PinMessage_on_group') == "onr") {
                            Request::pinChatMessage(['chat_id' => self::$Dt->chat_id, "message_id" => $results->getResult()->getMessageId()]);
                        }
                        RC::GetSet($results->getResult()->getMessageId(),'Player_ListMessage_ID');
                    }
                    break;
                case 2:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('startLastGame'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'deleteMessage');
                    }

                    break;
                case 3:
                    $inline_keyboard = new InlineKeyboard(
                        [
                            ['text' => self::$Dt->LG->_('JoinChallenge'), 'url' => self::$Dt->ChallengeJoin]
                        ]

                    );
                    $result = Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('StartLastChallenge'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::rpush($result->getResult()->getMessageId(),'ch:deleteMessage');
                    }
                    break;
                default:

                    return false;
                    break;
            }
        }else{
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>  self::$Dt->LG->_('GameStartOnGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

    }


    public static function CM_Extend(){
        $status = GR::CheckGPGameState();
        switch ($status){
            case 0:
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('GameNotCreate'),
                    'parse_mode' => 'HTML'
                ]);
                break;
            case 2:
                if(RC::Get('allow_extend') == "offr" and self::$Dt->admin == 0){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('AllowExtendForAdmin') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                if(!is_numeric(self::$Dt->text)){
                    self::$Dt->text = 30;
                }
                if(self::$Dt->admin == 0 and self::$Dt->text < 0){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('NotAllowUserminusExtend') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                $times = RC::Get('timer') - time();
                if($times <= 0 ){
                    return false;
                }
                $re = GR::ExtendToGame();
                $text = ($re['extTime'] < 0) ? self::$Dt->LG->_('ExtendToTimeManfi',array("{0}"=> $re['extTime'],"{1}" => $re['ToLeft'])) : self::$Dt->LG->_('ExtendToTime',array("{0}"=> $re['extTime'], "{1}" =>$re['ToLeft']));
                $re = Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                ]);
                if($re->isOk()) {
                    RC::rpush($re->getResult()->getMessageId(),'deleteMessage');
                }
                break;
            default:
                return false;
                break;
        }
    }

    public static function CM_Flee(){
        $status = GR::CheckGPGameState();
        switch ($status) {
            case 2:
                if(RC::Get('allow_flee') == "offr" and self::$Dt->admin == 0){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotAllowFlee'),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                if(!GR::CheckPlayerJoined()){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotInGameForFlee'),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
                GR::UserFlee();
                Request::deleteMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'message_id' => self::$Dt->message_id,
                ]);
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('okFlee',array("{0}" => self::$Dt->user_link)).PHP_EOL.self::$Dt->LG->_('FleeCoutPlayer',array("{0}" => GR::CountPlayer())),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 1:
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('NotAllowFleeInGame') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
                break;
            default:
                return false;
                break;
        }
    }


    public static function CM_Nextgame(){
        if(self::$Dt->typeChat !== "private"){

            $GroupName = ( RC::Get('group_link') !== "") ? '<a href="' . RC::Get('group_link') . '">' . RC::Get('group_name') . '</a>' : GR::FilterN( RC::Get('group_name')) ;
            $checkPlayerNextGame = GR::CheckPlayerInNextGame();
            if($checkPlayerNextGame){
                $inline_keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('cancele_ok'), 'callback_data' => 'cancel_nextgame/'.self::$Dt->chat_id]
                    ]
                );
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('AlreadyOnWaitList',array("{0}" => $GroupName)),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                    'disable_web_page_preview' => 'true',
                ]);
            }
            GR::AddPlayerToNextGame();
            $inline_keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('cancele_ok'), 'callback_data' => 'cancel_nextgame/'.self::$Dt->chat_id]
                ]
            );
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('AddedToWaitList',array("{0}" => $GroupName)),
                'parse_mode' => 'HTML',
                'reply_markup' => $inline_keyboard,
                'disable_web_page_preview' => 'true',
            ]);
        }
    }

    public static function cancel_nextgame(){
        Request::editMessageReplyMarkup([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'reply_markup' =>  new InlineKeyboard([]),
        ]);
        GR::RemoveFromNextGame();
    }

    public static function CM_ForceStart(){
        if(self::$Dt->typeChat !== "private") {

            if(self::$Dt->admin == 0){
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotAllowForUser'),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }

            $status = GR::CheckGPGameState();
            switch ($status) {
                case 0:
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->LG->_('GameNotCreate'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:


                    RC::GetSet(0, 'timer');
                    break;
                case 1:
                    return false;
                    break;
                default:
                    return false;
                    break;
            }


        }
    }

    public static function CM_Addtest(){
        if(self::$Dt->admin == 0){
            return false;
        }


    }

    public static function NightSelectedCheck($Selected){
        $Ex = explode('/',self::$Dt->data);
        if(isset($Ex['2'])){
            $user_id =  $Ex['2'];
        }else{
            $user_id = self::$Dt->user_id;
        }
        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user")){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if($Selected == "LuciferSelectTeam"){
            $user_id = self::$Dt->user_id;
        }

        if(self::$Dt->in_game == 0){
            self::Error(self::$Dt->L->_('Error_NotInGame'));
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }
        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if(RC::Get('game_state') !== "night"){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');

            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }
        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);

        $MeRole = self::$Dt->user_role."_n";


        $Team = false;

        switch ($Selected){
            case 'babr':
                if(self::$Dt->user_role !== "role_babr"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Hamzad':
                // چک کن نقشش با درخواست ارسالی هماهنگ باشه
                if(self::$Dt->user_role !== "role_Hamzad"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Hamzad');
                break;
            case 'khenyager':
                // چک کن نقشش با درخواست ارسالی هماهنگ باشه
                if(self::$Dt->user_role !== "role_khenyager"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(((int) RC::Get('GamePl:KenyagerCount') - 1) ,'GamePl:KenyagerCount');
                RC::GetSet(true,'GamePl:Kenyager');
                $U_D['fullname'] = "بله";
             break;
            case 'Lucifer':
                // چک کن نقشش با درخواست ارسالی هماهنگ باشه
                if(self::$Dt->user_role !== "role_lucifer"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet(true,'GamePl:role_lucifer:checkLucifer');
                break;
            case 'Joker':
                if(self::$Dt->user_role !== "role_Joker"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Harly':
                if(self::$Dt->user_role !== "role_Harly"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
             break;
            case 'KentVampire':
                if(self::$Dt->user_role !== "role_kentvampire"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Feranc':
                if(self::$Dt->user_role !== "role_franc"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                if(!RC::CheckExit('GamePl:FrancNightOk')) {
                    RC::GetSet($Name, 'GamePl:role_franc:AngelNameSaved');
                    RC::GetSet(self::$Dt->user_id, 'GamePl:role_franc:AngelIn:' . $user_id);
                    RC::GetSet($user_id, 'GamePl:UserInHome:' . self::$Dt->user_id);
                    RC::GetSet(self::$Dt->user_link, 'GamePl:UserInHome:' . self::$Dt->user_id . ":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole), 'GamePl:UserInHome:' . self::$Dt->user_id . ":role");
                }
                break;

            case 'LuciferSelectTeam':
                // چک کن نقشش با درخواست ارسالی هماهنگ باشه
                if(self::$Dt->user_role !== "role_lucifer"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                $Team =  $Ex['2'];
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($Team,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Cupe':
                if(self::$Dt->user_role !== "role_elahe"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::CheckExit("GamePl:namer:love")){
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:lover');
                RC::GetSet($Name,'GamePl:namer:love');

                $rows = GR::GetPlayerNonKeyboard([$user_id], 'NightSelect_Cupe2');
                $inline_keyboard = new InlineKeyboard(...$rows);
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->LG->_('AskCupid2'),
                    'message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                break;
            case 'Cupe2':
                if(self::$Dt->user_role !== "role_elahe"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::CheckExit('GamePl:love:'.$user_id)){
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:love:'.RC::Get('GamePl:lover'));
                RC::GetSet($Name,'GamePl:name:love:'.RC::Get('GamePl:lover'));

                RC::GetSet(RC::Get('GamePl:lover'),'GamePl:love:'.$user_id);
                RC::GetSet(RC::Get('GamePl:namer:love'),'GamePl:name:love:'.$user_id);

                break;
            case 'Phoenix':
                if(self::$Dt->user_role !== "role_Phoenix"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
                case 'BrideTheDead':
                if(self::$Dt->user_role !== "role_BrideTheDead"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'LiLis':
                if(self::$Dt->user_role !== "role_Lilis"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Vahshi':
                if(self::$Dt->user_role !== "role_Vahshi"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Olgo');
                RC::GetSet($Name,'GamePl:OlgoName');
                break;
            case 'Bomber':
                if(self::$Dt->user_role !== "role_Bomber"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                $countTeam = GR::_GetByTeam('Bomber');
                if($countTeam){
                    $BombrMessage = self::$Dt->LG->_('BombPlantedMulti',array("{0}" => self::$Dt->user_link,"{1}"=> $Name));
                    GR::SendForBomberTeam($BombrMessage,true);
                }
                /*
                $BombPlanted = ((int) R::Get('GamePl:BombPlanted') + 1);
                RC::GetSet($BombPlanted,'GamePl:BombPlanted');
                */
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Firefighter':
                if(self::$Dt->user_role !== "role_Firefighter"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:FirefighterList','json');
                break;
            case 'Honey':
                if(self::$Dt->user_role !== "role_Honey"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'IceQueen':
                if(self::$Dt->user_role !== "role_IceQueen"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:role_IceQueen:'.$user_id,'json');
                break;

            case 'Shekar':
                if(self::$Dt->user_role !== "role_shekar"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'IceWolf':
                if(self::$Dt->user_role !== "role_iceWolf"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Fool':
                if(self::$Dt->user_role !== "role_ahmaq"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break ;
            case 'Dozd':
                if(self::$Dt->user_role !== "role_dozd"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Negativ':
                if(self::$Dt->user_role !== "role_ngativ"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Mouse':
                if(self::$Dt->user_role !== "role_Mouse"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Natasha':
                if(self::$Dt->user_role !== "role_faheshe"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                RC::GetSet($user_id,'GamePl:role_faheshe:inhome:'.$user_id);

                break;

            case 'Archer':
                if(self::$Dt->user_role !== "role_Archer"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Watermelon':
                if(self::$Dt->user_role !== "role_Watermelon"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'qhost':
                if(self::$Dt->user_role !== "role_qhost"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'dinamit':
                if(self::$Dt->user_role !== "role_dinamit"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Knight':
                if(self::$Dt->user_role !== "role_Knight"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Killer':
                if(self::$Dt->user_role !== "role_Qatel"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Angel':
                if(self::$Dt->user_role !== "role_Fereshte"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($Name,'GamePl:role_angel:AngelNameSaved');
                RC::GetSet(self::$Dt->user_id,'GamePl:role_angel:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'WhiteWolf':
                if(self::$Dt->user_role !== "role_WhiteWolf"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::CheckExit("GamePl:role_WhiteWolf:AngelNameSaved")){
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                RC::GetSet($Name,'GamePl:role_WhiteWolf:AngelNameSaved');
                RC::GetSet(self::$Dt->user_id,'GamePl:role_WhiteWolf:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Mummy':
                if(self::$Dt->user_role !== "role_Mummy"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($Name,'GamePl:role_Mummy:AngelNameSaved');
                RC::GetSet(self::$Dt->user_id,'GamePl:role_Mummy:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                break;
            case 'Wolf':
                $Wolf_role = SE::WolfRole();

                if(self::$Dt->user_role == "role_forestQueen"){
                    if (RC::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        array_push($Wolf_role,'role_forestQueen');
                    }
                }

                if(!in_array(self::$Dt->user_role,$Wolf_role)){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                $countTeam = GR::_GetByTeam('wolf');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('eatUser',array("{0}"=>self::$Dt->user_link,"{1}" => $Name));
                    GR::SendForWolfTeam($msg,true);
                    RC::rpush(self::$Dt->user_id,'GamePl:Selected:Wolf:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                    RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }

                break;
            case 'Magento':
                if(self::$Dt->user_role !== "role_Magento"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                $countTeam = GR::_GetByTeam('Firefighter');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('MagentoAskedS',array("{0}"=>self::$Dt->user_link,"{1}" => $Name));
                    GR::SendForMagentoTeam($msg,true);
                    RC::rpush(self::$Dt->user_id,'GamePl:Selected:Magento:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                    RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }

                break;
            case 'Vampire':
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                $countTeam = GR::_GetByTeam('vampire');
                if(count($countTeam) > 1){
                    $msg = (RC::CheckExit('GamePl:VampireFinded') ? self::$Dt->LG->_('MessageGoHomeFinde',array("{0}"=> self::$Dt->user_link, "{1}" => $Name)) : self::$Dt->LG->_('MessageGoHome',array("{0}"=> self::$Dt->user_link, "{1}" =>$Name)));
                    GR::SendForVampireTeam($msg,self::$Dt->user_id);
                    RC::rpush(self::$Dt->user_id,'GamePl:Selected:Vampire:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.self::$Dt->user_id);
                    RC::GetSet(self::$Dt->user_link,'GamePl:UserInHome:'.self::$Dt->user_id.":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.self::$Dt->user_id.":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }
                break;
            case 'Enchanter':
                if(self::$Dt->user_role !== "role_enchanter"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Chemist':
                if(self::$Dt->user_role !== "role_Chemist"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Ferqe':
                if(self::$Dt->user_role !== "role_ferqe"){
                    if(self::$Dt->user_role !== "role_Royce") {
                        self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                    }
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user");
                $countTeam = GR::_GetByTeam('ferqeTeem');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('CultistVotedConvert',array("{0}" => self::$Dt->user_link,"{1}" => $Name));
                    GR::SendForCultTeam($msg,true);
                    RC::rpush(self::$Dt->user_id,'GamePl:Selected:Cult:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }else{
                    RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                }
                break;
            case 'Sear':
                if(self::$Dt->user_role !== "role_pishgo"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Cow':
                if(self::$Dt->user_role !== "role_Cow"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Huntsman':
                if(self::$Dt->user_role !== "role_Huntsman"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Jado':
                if(self::$Dt->user_role !== "role_WolfJadogar"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
        }

        RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);


        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => ($Team ? self::GetTeam($Team) : $U_D['fullname']))),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }

    public static function GetTeam($Team){
        switch ($Team){
            case 'wolf':
                return "تیم گرگ";
                break;
            case 'rosta':
                return "تیم روستا";
                break;
            case 'vampire':
                return "تیم ومپایر";
                break;
            case 'ferqeTeem':
                return "تیم فرقه";
                break;
            case 'qatel':
                return "تیم قاتل";
                break;
            default:
                return "نامشخص";
                break;
        }
    }

    public static function FighterFight(){
        if(RC::Get('GamePl:Day_no') > 1 && RC::CheckExit('GamePl:FirefighterList')) {

            if(RC::CheckExit('GamePl:FirefighterOk') || RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user") ){
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
            }
            RC::GetSet(true,'GamePl:FirefighterOk');
            RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('SelectOk',array("{0}" => self::$Dt->LG->_('ButtenFireFighter'))),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        return false;
    }

    public static function NightSelectDodge($Selected){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }

        $ForUser = RC::Get('GamePl:role_lucifer:NightSelect');
        $Me_user = GR::_GetPlayer((float) $ForUser);
        $Me_userLink = GR::ConvertName($Me_user['user_id'],$Me_user['fullname_game']);
        if(self::$Dt->in_game == 0){
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }
        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" => $user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::Get('game_state') !== "night"){
            RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }
        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);

        $MeRole = $Me_user['user_role']."_n";

        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user:dodge")){
            return false;
        }

        switch ($Selected){
            case 'role_Firefighter':
                if($Me_user['user_role'] !== "role_Firefighter"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:FirefighterList','json');
                break;
            case 'role_Phoenix':
                if($Me_user['user_role']  !== "role_Phoenix"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Honey':
                if($Me_user['user_role'] !== "role_Honey"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_IceQueen':
                if($Me_user['user_role'] !== "role_IceQueen"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::rpush(['user_id'=>$user_id,'fullname'=> $U_D['fullname_game'],'link' => $Name,'role'=> $U_D['user_role']],'GamePl:role_IceQueen:'.$user_id,'json');
                break;
            case 'role_kentvampire':
                if($Me_user['user_role'] !== "role_kentvampire"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_ahmaq':
                if($Me_user['user_role']  !== "role_ahmaq"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_ngativ':
                if($Me_user['user_role'] !== "role_ngativ"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_faheshe':
                if($Me_user['user_role'] !== "role_faheshe"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                RC::GetSet($user_id,'GamePl:role_faheshe:inhome:'.$user_id);

                break;
            case 'role_Archer':
                if($Me_user['user_role'] !== "role_Archer"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Chemist':
                if($Me_user['user_role'] !== "role_Chemist"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            //Watermelon
            case 'role_Watermelon':
                if($Me_user['user_role'] !== "role_Watermelon"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Knight':
                if($Me_user['user_role']  !== "role_Knight"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_Qatel':
                if($Me_user['user_role'] !== "role_Qatel"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_Huntsman':
                if($Me_user['user_role'] !== "role_Huntsman"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_Fereshte':
                if($Me_user['user_role'] !== "role_Fereshte"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_angel:AngelNameSaved');
                RC::GetSet($Me_user['user_id'],'GamePl:role_angel:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_WhiteWolf':
                if($Me_user['user_role'] !== "role_WhiteWolf"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_WhiteWolf:AngelNameSaved');
                RC::GetSet($Me_user['user_id'],'GamePl:role_WhiteWolf:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;

            case 'role_Mummy':
                if($Me_user['user_role'] !== "role_Mummy"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($Name,'GamePl:role_Mummy:AngelNameSaved');
                RC::GetSet($Me_user['user_id'],'GamePl:role_Mummy:AngelIn:'.$user_id);
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                break;
            case 'role_forestQueen':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':

                $Wolf_role = SE::WolfRole();

                if($Me_user['user_role']  == "role_forestQueen"){
                    if (RC::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        array_push($Wolf_role,'role_forestQueen');
                    }
                }

                if(!in_array($Me_user['user_role'] ,$Wolf_role)){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }

                RC::GetSet(true,'GamePl:Selected:'.$Me_user['user_id'].":user:dodge");
                $countTeam = GR::_GetByTeam('wolf');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('eatUser',array("{0}"=>$Me_userLink,"{1}" => $Name));
                    GR::SendForWolfTeam($msg,true);

                    RC::Lrem('GamePl:Selected:Wolf:'.$user_id,1,$Me_user['user_id']);

                    RC::rpush($Me_user['user_id'],'GamePl:Selected:Wolf:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                    RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }

                break;
            case 'role_Vampire':
            case 'role_Bloodthirsty':
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:dodge");
                $countTeam = GR::_GetByTeam('vampire');
                if(count($countTeam) > 1){
                    $msg = (RC::CheckExit('GamePl:VampireFinded') ? self::$Dt->LG->_('MessageGoHomeFinde',array("{0}" => $Me_userLink,"{1}" => $Name)) : self::$Dt->LG->_('MessageGoHome',array("{0}" => $Me_userLink,"{1}" =>$Name)));
                    GR::SendForVampireTeam($msg,$Me_user['user_id']);
                    RC::Lrem('GamePl:Selected:Vampire:'.$user_id,1,$Me_user['user_id']);

                    RC::rpush($Me_user['user_id'],'GamePl:Selected:Vampire:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }else{
                    RC::GetSet($user_id,'GamePl:UserInHome:'.$Me_user['user_id']);
                    RC::GetSet($Me_userLink,'GamePl:UserInHome:'.$Me_user['user_id'].":name");
                    RC::GetSet(self::$Dt->LG->_($MeRole),'GamePl:UserInHome:'.$Me_user['user_id'].":role");
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }
                break;
            case 'role_enchanter':
                if($Me_user['user_role'] !== "role_enchanter"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_ferqe':
            case 'role_Royce':
                if($Me_user['user_role'] !== "role_ferqe"){
                    if($Me_user['user_role'] !== "role_Royce") {
                        return self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    }
                }
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:dodge");
                $countTeam = GR::_GetByTeam('ferqeTeem');
                if(count($countTeam) > 1){
                    $msg = self::$Dt->LG->_('CultistVotedConvert',array("{0}" => $Me_userLink,"{1}" =>$Name));
                    GR::SendForCultTeam($msg,true);
                    RC::Lrem('GamePl:Selected:Cult:'.$user_id,1,$Me_user['user_id']);

                    RC::rpush($Me_user['user_id'],'GamePl:Selected:Cult:'.$user_id);
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }else{
                    RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                }
                break;
            case 'role_pishgo':
                if($Me_user['user_role'] !== "role_pishgo"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'role_WolfJadogar':
                if($Me_user['user_role'] !== "role_WolfJadogar"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
        }

        RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }
    public static function DaySelectedDodge($Type){
        $Ex = explode('/',self::$Dt->data);
        $ForUser = RC::Get('GamePl:role_lucifer:DodgeDay');
        $Me_user = GR::_GetPlayer($ForUser);
        $Me_userLink = GR::ConvertName($Me_user['user_id'],$Me_user['fullname_game']);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }

        if(self::$Dt->in_game == 0){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }

        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user") && $Me_user['user_role'] !== "role_Solh"){
            return false;
        }

        if(self::$Dt->user_role !== "role_Solh") {
            RC::GetSet(true, 'GamePl:Selected:' . self::$Dt->user_id . ":user");
        }
        $MeRole = $Me_user['user_role']."_n";
        $EdaitMarkup = false;
        switch ($Type){
            case 'Karagah':
                if($Me_user['user_role'] !== "role_karagah"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'Princess':
                if($Me_user['user_role'] !== "role_Princess"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'BlackKnight':
                if($Me_user['user_role'] !== "role_BlackKnight"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'Spy':
                if($Me_user['user_role'] !== "role_Spy"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'KentVampire':
                if($Me_user['user_role'] !== "role_kentvampire"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;
            case 'Gunner':
                if($Me_user['user_role'] !== "role_tofangdar"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.$Me_user['user_id']);
                break;

            default:
                break;
        }
        RC::GetSet(self::$Dt->message_id,'GamePl:new:MessageNightSend:'.self::$Dt->user_id);
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);

    }

    public static function DodgeVote(){
        $Ex = explode('/',self::$Dt->data);
        $user_id =  (float) $Ex['2'];
        $ForUser = RC::Get('GamePl:role_lucifer:DodgeVote');
        $Me_user = GR::_GetPlayer($ForUser);
        if($Me_user) {
            $Me_userLink = GR::ConvertName($Me_user['user_id'], $Me_user['fullname_game']);
        }else {
            $Me_user = false;
            $Me_userLink = "یافت نشد!";
        }
        if(self::$Dt->in_game == 0){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            return self::Error(self::$Dt->L->_('Error_NotInGame'));
        }
        $U_D = GR::_GetPlayer($user_id);

        $U_F_fullname = $U_D['fullname'];
        if($U_D == false){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" => $user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }

        if($Me_user) {
            RC::Del('GamePl:DontVote:' . $Me_user['user_id']);
        }
        // چک میکنیم صلح شده یا نه
        if(RC::CheckExit('GamePl:role_Solh:GroupInSolh')){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('selectSolh'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if(RC::Get('game_state') !== "vote"){
            RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }


        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user:vote:Dodge")){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:vote:Dodge");

        if($Me_user) {
            if ($Me_user['user_role'] == "role_PesarGij") {
                if (mt_rand(0, 100) < 50) {
                    $Random = GR::GetRoleRandom([$user_id, self::$Dt->user_id]);
                    $U_D = GR::_GetPlayer($Random['user_id']);
                    $user_id = $Random['user_id'];
                }
            }
        }
        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);


        GR::SaveVoteMessageDodge($Name,$Me_userLink);

        RC::GetSet(true,'GamePl:VoteList:'.$user_id);
        RC::GetSet((RC::Get('GamePl:VoteCount') + 1 ) ,'GamePl:VoteCount');
        if($Me_user) {
            if ($Me_user['user_role'] == "role_Kadkhoda" and RC::CheckExit('GamePl:role_Kadkhoda:MayorReveal')) {
                RC::rpush(['user_id' => $Me_user['user_id'], 'name' => $Me_userLink], 'GamePl:Selected:Vote:' . $user_id, 'json');
            }
        }
        if($Me_user) {
            RC::rpush(['user_id' => $Me_user['user_id'], 'name' => $Me_userLink], 'GamePl:Selected:Vote:' . $user_id, 'json');
        }
       RC::Del('GamePl:MessageNightSendDodgeVote:'.self::$Dt->user_id);


        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_F_fullname)),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }
    public static function VoteUser(){
        $Ex = explode('/',self::$Dt->data);
        $user_id =  $Ex['2'];

        if(self::$Dt->in_game == 0){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            self::Error(self::$Dt->L->_('Error_NotInGame'));
            RC::Del('GamePl:DontVote:'.self::$Dt->user_id);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }
        $U_D = GR::_GetPlayer($user_id);


        if($U_D == false){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            RC::Del('GamePl:DontVote:'.self::$Dt->user_id);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }
        $U_F_fullname = $U_D['fullname'];
        RC::Del('GamePl:DontVote:'.self::$Dt->user_id);
        // sleep(1);
        // چک میکنیم صلح شده یا نه
        if(RC::CheckExit('GamePl:role_Solh:GroupInSolh')){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('selectSolh'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if(RC::Get('game_state') !== "vote"){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }



        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user:vote")){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if(self::$Dt->user_role == "role_PesarGij"){
            if(mt_rand(0,100) < 50 ) {
                $Random = GR::GetRoleRandom([$user_id,self::$Dt->user_id]);
                $U_D = GR::_GetPlayer($Random['user_id']);
                $user_id = $Random['user_id'];
            }
        }


        $Name = GR::ConvertName($user_id,$U_D['fullname_game']);


        GR::SaveVoteMessage($Name);
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');

        RC::GetSet(true,'GamePl:VoteList:'.$user_id);
        RC::GetSet((RC::Get('GamePl:VoteCount') + 1 ) ,'GamePl:VoteCount');

        // GR::SaveVoteUser((int) $user_id,self::$Dt->user_id,self::$Dt->user_link);
        if(self::$Dt->user_role == "role_Kadkhoda" and RC::CheckExit('GamePl:role_Kadkhoda:MayorReveal')){
            RC::rpush(['user_id' => self::$Dt->user_id ,'name' => self::$Dt->user_link],'GamePl:Selected:Vote:'.$user_id,'json');
        }

        RC::rpush(['user_id' => self::$Dt->user_id ,'name' => self::$Dt->user_link],'GamePl:Selected:Vote:'.$user_id,'json');
        RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:vote");

        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_F_fullname)),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }


    public static function DaySelectedCheck($Selected){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }

        if(self::$Dt->in_game == 0){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            self::Error(self::$Dt->L->_('Error_NotInGame'));
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if(RC::CheckExit('GamePl:Selected:'.self::$Dt->user_id.":user") && self::$Dt->user_role !== "role_Solh"){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if(self::$Dt->user_role == "role_Solh" && RC::CheckExit('GamePl:role_Solh:GroupInSolh')){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }
        if(self::$Dt->user_role !== "role_Solh") {
            RC::GetSet(true, 'GamePl:Selected:' . self::$Dt->user_id . ":user");
        }
        $MeRole = self::$Dt->user_role."_n";
        $EdaitMarkup = false;
        switch ($Selected){
            case 'Karagah':
                if(self::$Dt->user_role !== "role_karagah"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Princess':
                if(self::$Dt->user_role !== "role_Princess"){
                    return  self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'dinamit':
                if(self::$Dt->user_role !== "role_dinamit"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'BlackKnight':
                if(self::$Dt->user_role  !== "role_BlackKnight"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
             case 'Dian':
              if(self::$Dt->user_role  !== "role_dian"){
                    return   self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
               }
              RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
             break;
            case 'KentVampire':
                if(self::$Dt->user_role !== "role_kentvampire"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Spy':
                if(self::$Dt->user_role !== "role_Spy"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Tofangdar':
                if(self::$Dt->user_role !== "role_tofangdar"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
                break;
            case 'Solh':
                if(self::$Dt->user_role !== "role_Solh"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(Rc::CheckExit('GamePl:solhIsSolh')) {
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }


                $UnlockIn = (RC::Get('GamePl:Day_no') + 1);
                RC::GetSet($UnlockIn,'GamePl:role_Solh:GroupInSolh');
                RC::GetSet(true,'GamePl:solhIsSolh');
                $GroupMessage =  self::$Dt->LG->_('PacifistNoLynch',array("{0}"=>self::$Dt->user_link));
                RC::GetSet(true,'GamePl:Selected:'.self::$Dt->user_id.":user:vote");
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                if(RC::Get('game_state') == "vote"){
                    RC::GetSet( time(),'timer');
                }
                $EdaitMarkup = true;
                break;

            case 'Kadkhoda':
                if(self::$Dt->user_role !== "role_Kadkhoda"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::CheckExit('GamePl:role_Kadkhoda:MayorReveal')){
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:role_Kadkhoda:MayorReveal');
                $GroupMessage =  self::$Dt->LG->_('MayorReveal',array("{0}"=>self::$Dt->user_link));
                GR::SendMs(self::$Dt->chat_id,$GroupMessage,self::$Dt->setgif_kad);

                $EdaitMarkup = true;
                break;

            case 'Ruler':
                if(self::$Dt->user_role !== "role_Ruler"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::CheckExit('GamePl:RulerOkAndUse')){
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:RulerOkAndUse');
                RC::GetSet(RC::Get('GamePl:Day_no') + 1,'GamePl:role_Ruler:RulerOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('RulerNowRul',array("{0}" =>self::$Dt->user_link));
                GR::SendMs(self::$Dt->chat_id,$GroupMessage,self::$Dt->setgif_hakem);

                $EdaitMarkup = true;
                break;

            case 'Khabgozar_Yes':
                if(self::$Dt->user_role !== "role_KhabGozar"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                if(RC::CheckExit('GamePl:KhabgozarOkUse')){
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                RC::GetSet(true,'GamePl:KhabgozarOkUse');
                RC::GetSet(RC::Get('GamePl:Night_no'),'GamePl:KhabgozarOk_in');
                RC::GetSet(RC::Get('GamePl:Night_no') + 1,'GamePl:NotSendNight');
                RC::GetSet(RC::Get('GamePl:Night_no') + 1,'GamePl:KhabgozarOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('SandmanSleepAll',array("{0}" => self::$Dt->user_link));
                GR::SendMs(self::$Dt->chat_id,$GroupMessage,self::$Dt->setgif_khab);
                $EdaitMarkup = true;
                break;
            case 'Khabgozar_No':
                $EdaitMarkup = true;
                break;
            case 'davina_Yes':
                if(self::$Dt->user_role !== "role_davina"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                if(RC::CheckExit('GamePl:DavinaOkUse')){
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                RC::GetSet(true,'GamePl:DavinaOkUse');
                RC::GetSet(RC::Get('GamePl:Day_no'),'GamePl:DavinaOk_in');
                RC::GetSet(RC::Get('GamePl:Day_no') + 1,'GamePl:NotSendDay');
                RC::GetSet(RC::Get('GamePl:Day_no') + 1,'GamePl:DavinaOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('DavinaGroupMessage');
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $GroupMessage,
                    'parse_mode'=> 'HTML'
                ]);
                $EdaitMarkup = true;
                break;
            case 'davina_No':
                $EdaitMarkup = true;
                break;
            case 'SendBittenYes':
                $Player = GR::_GetPlayerByrole('role_Botanist');
                if($Player){
                    $inline_keyboard = new InlineKeyboard([
                        ['text' => self::$Dt->LG->_('btnOkUser'), 'callback_data' => "DaySelect_BotanistOk/" . self::$Dt->chat_id],
                        ['text' => self::$Dt->LG->_('btnNoUser'), 'callback_data' => "DaySelect_BotanistNo/" . self::$Dt->chat_id]
                    ]);
                    $result = Request::sendMessage([
                        'chat_id' => $Player['user_id'],
                        'text' => self::$Dt->LG->_('BotanistMessage',RC::Get('GamePl:FllowCount') ?? 1),
                        'parse_mode' => 'HTML',
                        'reply_markup' => $inline_keyboard,
                    ]);
                    if($result->isOk()) {
                        RC::GetSet(self::$Dt->user_link,'GamePl:role_Botanist:link');
                        Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => self::$Dt->LG->_('OkSendToBotanist'),
                            'parse_mode'=> 'HTML'
                        ]);
                        RC::GetSet($result->getResult()->getMessageId(), 'GamePl:EditMarkup:' . $Player['user_id']);
                    }
                }
                $EdaitMarkup = true;
                break;
            case 'SendBittenNo':
                $EdaitMarkup = true;
                break;
            case 'BotanistOk':
                $for = RC::Get('GamePl:role_Botanist:bittaned:for');
                $MessagePl = self::$Dt->LG->_('BotanistMessageOk',RC::Get('GamePl:role_Botanist:link'));
                if($for == "wolf"){
                    RC::Del('GamePl:EnchanterBittanPlayer');
                    RC::Del('GamePl:BittanPlayer');
                    GR::SendForWolfTeam($MessagePl);
                }elseif($for == "vampire"){
                    RC::Del('GamePl:VampireBitten');
                    GR::SendForVampireTeam($MessagePl);
                }

                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->LG->_('BotanistM',RC::Get('GamePl:role_Botanist:link')),
                    'parse_mode'=> 'HTML'
                ]);

                $UserId = RC::Get('GamePl:role_Botanist:bittaned');
                Request::sendMessage([
                    'chat_id' => $UserId,
                    'text' => self::$Dt->LG->_('OkMessagePlayer',self::$Dt->user_link),
                    'parse_mode'=> 'HTML'
                ]);
                RC::DelKey('GamePl:role_Botanist:*');
                $EdaitMarkup = true;
                break;
            case 'BotanistNo':
                $UserId = RC::Get('GamePl:role_Botanist:bittaned');
                Request::sendMessage([
                    'chat_id' => $UserId,
                    'text' => self::$Dt->LG->_('BotanistNo'),
                    'parse_mode'=> 'HTML'
                ]);
                RC::DelKey('GamePl:role_Botanist:*');
                $EdaitMarkup = true;
                break;
            case 'SendBittenNo':
                $EdaitMarkup = true;
                break;
            case 'Ahangar_no':
                $EdaitMarkup = true;
                break;
            case 'Ahangar_Yes':
                if(self::$Dt->user_role !== "role_Ahangar"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                if(RC::CheckExit('GamePl:AhangarOkUse')){
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:AhangarOkUse');
                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                // آهنگری که شب اعلام نقش خواب گذار نقره پخش کند
                if(RC::Get('GamePl:KhabgozarOk_in') == RC::Get('GamePl:Night_no') ){
                    GR::SavePlayerAchivment(self::$Dt->user_id,'Wasted_Silver');
                }

                RC::GetSet((RC::Get('GamePl:Night_no') + 1),'GamePl:AhangarOk');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('BlacksmithSpreadSilver',array("{0}" => self::$Dt->user_link));
                GR::SendMs(self::$Dt->chat_id,$GroupMessage,self::$Dt->setgif_ahan);
                $EdaitMarkup = true;
                break;

            case 'trouble_no':
                $EdaitMarkup = true;
                break;
            case 'trouble_yes':
                if(self::$Dt->user_role !== "role_trouble"){
                    self::Error(self::$Dt->LG->_('ErrorSelect',array("{0}"=>self::$Dt->LG->_($MeRole))));
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                if(RC::Get('game_state') !== "day"){
                    RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                if(RC::CheckExit('GamePl:troubleOkUse')){
                    Request::editMessageReplyMarkup([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                RC::GetSet(true,'GamePl:troubleOkUse');
                RC::GetSet(true,'GamePl:trouble');
                RC::GetSet(true,'GamePl:'.self::$Dt->user_role.':notSend');
                $GroupMessage =  self::$Dt->LG->_('troubleGroupMessage',array("{0}" => self::$Dt->user_link));
                GR::SendMs(self::$Dt->chat_id,$GroupMessage,self::$Dt->setgif_dard);
                $EdaitMarkup = true;
                break;

        }
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');

        if($EdaitMarkup){
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('SelectOk_no'),
                'parse_mode'=> 'HTML'
            ]);

            Request::editMessageReplyMarkup([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }


    public static function RemoveMarkUp(){
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => 'روز خوبی داشته باشید',
            'message_id' => self::$Dt->message_id,
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }
    public static function Error($msg){
        if(empty($msg)){
            return false;
        }
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => $msg,
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
    }


    public static function Skip(){
        if(self::$Dt->in_game == 0 && self::$Dt->user_role !== "role_kalantar" && !RC::CheckExit('GamePl:HunterKill')){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            self::Error(self::$Dt->L->_('Error_NotInGame'));
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if(self::$Dt->user_role == "role_kalantar" && RC::CheckExit('GamePl:HunterKill')){
            RC::GetSet( time(),'timer');
            RC::GetSet(self::$Dt->user_link,'GamePl:kalantar_fullname');
            RC::Del('GamePl:HunterKill');
        }

        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => 'skip')),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }

    public static function KalanShot(){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }

        $U_D = GR::_GetPlayer($user_id);

        if($U_D == false){
            RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->LG->_('NotFoundPlayer',array("{0}" =>$user_id)),
                'message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        RC::GetSet(self::$Dt->user_id,'GamePl:kalantar_userid');
        RC::GetSet(self::$Dt->user_link,'GamePl:kalantar_fullname');
        RC::GetSet($user_id,'GamePl:Selected:'.self::$Dt->user_id);
        RC::LRem(self::$Dt->message_id."_".self::$Dt->user_id,1,'GamePl:MessageNightSend');
        RC::Del('GamePl:CheckNight');
        RC::GetSet( time(),'timer');
        //  RC::Del('GamePl:HunterKill');
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->LG->_('SelectOk',array("{0}" => $U_D['fullname'])),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }
    public static function CM_Ping(){
        $starttime = microtime(true);
        $host = 'www.bot.onyxwerewolf.ir';
        $ping = new Ping($host);
        self::$Dt->Latency = $ping->ping();
        self::$Dt->LatencyM = (self::$Dt->Latency['time'] ?  self::$Dt->Latency['time'] : 'Host could not be reached.');
        $stoptime  = microtime(true);
        $status = ($stoptime - $starttime) * 1000;
        $MessageRe = self::$Dt->L->_('PingT', array("{0}" => self::$Dt->LatencyM." ms" , "{1}" => date("i:s", floor($status) )));

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $MessageRe,
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
        ]);
    }

    public static function CM_Smite(){
        $status = GR::CheckGPGameState();
        switch ($status) {
            case 0:
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('GameNotCreate'),
                    'parse_mode' => 'HTML'
                ]);
                break;
            case 2:
                if(self::$Dt->admin == 0){
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }

                if(isset(self::$Dt->message->getEntities()[1])){
                    if(self::$Dt->message->getEntities()[1]->getUser()) {
                        $user_id = self::$Dt->message->getEntities()[1]->getUser()->getId();
                    }
                }

                $Text = self::$Dt->text;
                if(isset($Text)) {
                    if(is_numeric($Text) and strlen($Text) > 7) {
                        $user_id = (float) trim(self::$Dt->text);
                    }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                        $username = $matches[0];
                    }
                    // اگه با ای دی بود
                    if(isset($user_id)){
                        if(GR::CheckPlayerJoined($user_id)){
                            $Player = GR::_GetPlayerName($user_id);
                            GR::UserSmiteInGame($user_id);
                            return  Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('PlayerSmite',array("{0}" => GR::ConvertName($user_id,$Player), "{1}" => GR::CountPlayer())),
                                'parse_mode' => 'HTML'
                            ]);
                        }
                        return  Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('NotFindeSmiteUserId',array("{0}" => $user_id)),
                            'reply_to_message_id' => self::$Dt->message_id,
                            'parse_mode' => 'HTML'
                        ]);
                    }
                    if(isset($username)){
                        $check = GR::CheckUserByUsername($username);
                        if(!$check){
                            return  Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}" => $username)),
                                'reply_to_message_id' => self::$Dt->message_id,
                                'parse_mode' => 'HTML'
                            ]);
                        }

                        GR::UserSmiteInGame($check['user_id']);
                        return  Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('PlayerSmite', array("{0}" => GR::ConvertName($check['user_id'],$check['fullname_game']), "{1}" => GR::CountPlayer())),
                            'parse_mode' => 'HTML'
                        ]);
                    }

                    if(!self::$Dt->ReplayTo) {
                        return Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('PleaseInsetValueForSmite'),
                            'reply_to_message_id' => self::$Dt->message_id,
                            'parse_mode' => 'HTML'
                        ]);
                    }
                }

                if(self::$Dt->ReplayTo) {
                    $user_id = self::$Dt->ReplayTo;
                }
                if(GR::CheckPlayerJoined($user_id)) {
                    $Player = GR::_GetPlayerName($user_id);
                    GR::UserSmiteInGame($user_id);
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('PlayerSmite', array("{0}" => GR::ConvertName($user_id, $Player), "{1}" => GR::CountPlayer())),
                        'parse_mode' => 'HTML'
                    ]);
                }
                if(!isset($user_id)){
                    $user_id = "نام کاربری را وارد نمایید مانند  /smite @new";
                }
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotFindeSmiteUserId',array("{0}" => $user_id)),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML'
                ]);
                break;
            case 1:
                if(self::$Dt->admin == 0){
                    return Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }

                $Text = self::$Dt->text;
                if(isset($Text) && !self::$Dt->ReplayTo) {
                    if(is_numeric($Text) and strlen($Text) > 7) {
                        $user_id = (float) self::$Dt->text;
                    }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                        $username = $matches[0];
                    }
                    // اگه با ای دی بود
                    if(isset($user_id)){
                        if(RC::CheckExit('GamePl:join_user:'.$user_id)){
                            RC::rpush($user_id,'GamePl:SmitePlayer');
                            return true;
                        }
                        return  Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->L->_('NotFindeSmiteUserId',array("{0}" => $user_id)),
                            'reply_to_message_id' => self::$Dt->message_id,
                            'parse_mode' => 'HTML'
                        ]);
                    }
                    if(isset($username)){
                        $check = GR::CheckUserByUsername($username);
                        if(!$check){
                            return  Request::sendMessage([
                                'chat_id' => self::$Dt->chat_id,
                                'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}"=> $username)),
                                'reply_to_message_id' => self::$Dt->message_id,
                                'parse_mode' => 'HTML'
                            ]);
                        }
                        RC::rpush($check['user_id'],'GamePl:SmitePlayer');
                        return true;
                    }

                    if(self::$Dt->ReplayTo) {
                        if(isset($user_id)) {
                            if ($user_id !== self::$Dt->ReplayTo) {
                                return Request::sendMessage([
                                    'chat_id' => self::$Dt->chat_id,
                                    'text' => self::$Dt->L->_('PleaseInsetValueForSmite'),
                                    'reply_to_message_id' => self::$Dt->message_id,
                                    'parse_mode' => 'HTML'
                                ]);
                            }
                        }
                    }

                }

                if(self::$Dt->ReplayTo) {
                    $user_id = self::$Dt->ReplayTo;
                    if (RC::CheckExit('GamePl:join_user:' . $user_id)) {
                        RC::rpush($user_id, 'GamePl:SmitePlayer');
                        return true;
                    }
                }
                $user_id = "None";
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotFindeSmiteUserId',array("{0}" => $user_id)),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML'
                ]);

                break;
            default:
                return false;
                break;
        }
    }


    public static function CM_Stats(){
        $user_id = (self::$Dt->ReplayTo ? self::$Dt->ReplayTo :  self::$Dt->user_id);

        $inline_keyboard = new InlineKeyboard(
            [
                ['text' => self::$Dt->L->_('StatsShow'), 'url' => "http://wolfofpersia.ir/Stats/".$user_id],
                ['text' => self::$Dt->L->_('StatsAll'), 'url' => "http://wolfofpersia.ir/players"]
            ]

        );
        $result = Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('GetStatsText'),
            'reply_markup' => $inline_keyboard,
        ]);

    }


    public static function CM_Score(){

        $Score = GR::GetScore();
        if(!$Score){
            return false;
        }
        $re = Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $Score,
            'parse_mode' => 'HTML',
        ]);

        if(!$re->isOk()) {
            Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }


    }


    public static function CM_Killme(){
        $user_id = (self::$Dt->ReplayTo ?  self::$Dt->ReplayTo : self::$Dt->user_id);
        $KillMe  = GR::GetKillMe($user_id);

        if($KillMe){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $KillMe,
                'parse_mode' => 'HTML',
            ]);
        }

    }

    public static function CM_Kills(){
        $user_id = self::$Dt->ReplayTo ?? self::$Dt->user_id;
        $Kills  = GR::GetKills($user_id);

        if($Kills){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $Kills,
                'parse_mode' => 'HTML',
            ]);
        }
    }


    public static function CM_Myideals(){

        $Lang = false;
        (RC::CheckExit('AfkedPlayer:'.self::$Dt->user_id) ? $Lang .= self::$Dt->L->_('AfkedIdels',array("{0}" => self::$Dt->user_link, "{1}" => RC::Get('AfkedPlayer:'.self::$Dt->user_id))) : false);
        $checkTop = RC::LRange(0,-1,'UserIdles:'.self::$Dt->user_id);
        if($checkTop){
            $re = [];
            $REArray = array_reverse($checkTop);
            $slice = array_slice($REArray,0,5);
            foreach ($slice as $row){
                array_push($re,$row);
            }
            if($re){
                $Lang .= PHP_EOL.implode(PHP_EOL,$re);
            }
        }
        if($Lang){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $Lang,
                'parse_mode' => 'HTML',
            ]);
        }
    }


    public static function CM_RoleList($l = 10, $m = 0){
        $result = self::$Dt->collection->role_list->find(['state' => 1],[
            "limit" => $l,
            "skip" => $m
        ]);
        if($result) {
            $array = iterator_to_array($result);
            $defultLang = self::$Dt->defaultLang;
            $defultMode = self::$Dt->def_mode ?? "general";
            $L = new Lang(FALSE);
            $L->load($defultMode."_".$defultLang, FALSE);

            if($array){
                $total = self::$Dt->collection->role_list->count(['state' => 1]);
                $send = 0;
                $re = [];
                foreach ($array as $item) {
                    if($send <= 10) {
                        $txt = "/" . $item['Key'] . " - " . $L->_($item['role']."_n");
                        array_push($re,$txt);
                    }
                }

                $allSend = $l + $m;
                $sends = $total - $l;
                $data = [
                    'chat_id' => self::$Dt->user_id,
                    'text' => implode(PHP_EOL,$re),
                ];
                Request::sendMessage($data);
                if($total >= $sends){
                    self::CM_RoleList(10, $allSend);
                }

            }
        }
    }

    public static function CM_Command($Command){

        $Command =  GR::_GetCommand($Command);
        if($Command){
            $defultLang = self::$Dt->defaultLang;
            $defultMode = self::$Dt->def_mode ?? "general";
            $L = new Lang(FALSE);
            $L->load($defultMode."_".$defultLang, FALSE);
            $Message = $L->_($Command['Key']);
            $data = [
                'chat_id' => self::$Dt->user_id,
                'text' => $Message,
                'parse_mode'=> 'HTML'
            ];
            Request::sendMessage($data);
        }
    }

    public static function BanPlayer($str){
        $Ex = explode('/',self::$Dt->data);
        $user_id = self::$Dt->user_id;
        if(isset($Ex['2'])) {
            $user_id = $Ex['2'];
        }
        $BanDetial = GR::BanDetial($user_id);

        switch ($str){
            case 'remove':
            case 'No':
                $UserMessage = "شما توسط %s به لیست بن به دلیل %s اضافه شده بودید ولی اینبار %s شما رو بخشیدن و اکنون میتوانید بازی کنید";
                self::EditMarkupBan('No',['name'=> $BanDetial['link']]);
                GR::RemoveFromBanList($user_id);
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[self::$Dt->user_link,$BanDetial['ban_for'],self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '30min':
                $time = strtotime('+30 minute');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('30m',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s دقیقه دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در ساعت %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[30,jdate('H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1d':
                $time = strtotime('+1 day');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1d',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s روز دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1w':
                $time = strtotime('+1 week');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1w',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s هفته دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1m':
                $time = strtotime('+1 month');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1m',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s ماه دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case '1y':
                $time = strtotime('+1 years');
                GR::ChangeBanUntilTime($time,$user_id);
                self::EditMarkupBan('1y',['name'=> $BanDetial['link']]);
                $UserMessage = "شما تا %s سال دیگر در لیست بن میباشد و نمتوانید بازی کنید.
                 در تاریخ %s مجدد میتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[1,jdate('Y-m-d H:i:s',$time),self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'ban':
                GR::ChangeBanUntilTime(1,$user_id);
                self::EditMarkupBan('ban',['name'=> ($BanDetial ? $BanDetial['link'] : "")]);
                $UserMessage = "شما برای همیشه  در لیست بن میباشد و نمتوانید بازی کنید.
                  مدیر محدود کننده : %s";
                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf($UserMessage,[self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
                break;
        }
    }

    public static function EditMarkupBan($type,$data){
        switch ($type){
            case 'No':
                $L = "شما از خطای %s گذشت نمودید و اکنون در لیست بن نمیباشد.";
                GR::AddActivity( vsprintf('مدیر %s به از خطای کاربر %s گذشت کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                $text = vsprintf($L,[$data['name']]);
                break;
            case '30m':
                $L = "شما  30 دقیقه %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 30 دقیقه کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1d':
                $L = "شما  1 روز %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 روز کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1w':
                $L = "شما  1 هفته %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 هفته کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1m':
                $L = "شما  1 ماه %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 ماه کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case '1y':
                $L = "شما  1 سال %s را در لیست بن قرار دادید.";
                $text = vsprintf($L,[$data['name']]);
                GR::AddActivity( vsprintf('مدیر %s به مدت 1 سال کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                break;
            case 'ban':
                $L = "شما  برای همیشه %s را در لیست بن قرار دادید.";
                GR::AddActivity( vsprintf('مدیر %s برای همیشه کاربر %s رو به لیست بن اضافه کرد.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                $text = vsprintf($L,[$data['name']]);
                break;
        }
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => $text,
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);


    }

    public static function CM_BanPlayer(){

        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin){
            if($Admin['ban_player'] == 0){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }
            // $user_id = self::$Dt->ReplayTo;


            $Text = self::$Dt->text;
            if(isset($Text)) {
                if (preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/", $Text, $matches)) {
                    $username = $matches[0];
                }
            }

            $user_id = false;

            if(isset($username)){
                $check = GR::CheckPlayerByUsername($username);
                if(!$check){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}"=>$username ?: $Text)),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML'
                    ]);
                }

                $user_id = $check['user_id'];
                $fullname = $check['fullname'];
                $link = GR::ConvertName($user_id,$fullname);
            }elseif(self::$Dt->ReplayTo) {
                $user_id =  self::$Dt->ReplayTo;
            }else {
                $user_id = (float) $Text;
            }

            if(!$user_id){
                return false;
            }


            if(isset($user_id)){
                $checkInBanList = GR::CheckPlayerInBanList($user_id);
                if($checkInBanList){
                    if($checkInBanList['state'] == true) {
                        if(isset($checkInBanList['key'])) {
                            switch ($checkInBanList['key']) {
                                case 'ban_ever':
                                    $UserLang = "همیشه";
                                    break;
                                case 'ban_to':
                                    $UserLang = jdate('Y-m-d H:i:s',$checkInBanList['time']);
                                    break;
                            }
                        }

                        $Lang = "کاربر %s از قبل در لیست بن میباشد.".PHP_EOL;
                        $Lang .= PHP_EOL."توضیحات لیست بن :".PHP_EOL;
                        $Lang .= "مدت زمان بن : ".$UserLang;
                        $Lang .= PHP_EOL." بن توسط : ".$checkInBanList['ban_by'];
                        $Lang .= PHP_EOL."به دلیل : ".$checkInBanList['for'];

                        return Request::sendMessage([
                            'chat_id' => self::$Dt->user_id,
                            'text' => vsprintf($Lang, [(isset($link) ? $link : self::$Dt->PlayerLink)]),
                            'parse_mode' => 'HTML',
                        ]);
                    }
                }
                GR::AddPlayerBanList($user_id);

                //  GR::AddActivity( vsprintf('مدیر %s به لیست بن از بازی اضافه کرد %s  به دلیل : %s رو.',[self::$Dt->user_link,self::$Dt->PlayerLink,$Text]));
                $inline_keyboard =  GR::GetBanlistKeyboard($Admin,$user_id);
                $Lang = "افزودن کاربر %s به لیست بن به دلیل : %s";
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf($Lang,[(isset($fullname) ? $fullname : self::$Dt->ReplayFullname),$Text]),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $inline_keyboard,
                ]);
            }
        }

    }
    public static function CM_BAnme(){
        if (self::$Dt->typeChat !== "private") {

            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "تو خصوصی بفرست!",
                'parse_mode' => 'HTML',
            ]);

        }
        $checkInBanList = GR::CheckPlayerInBanList(self::$Dt->user_id);
        if($checkInBanList){
            return false;
        }
        self::$Dt->text = "خودش خواست !";
        GR::AddPlayerBanList(self::$Dt->user_id);

        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => "خوشحالمون کردی مرسی :)",
            'parse_mode' => 'HTML',
        ]);

    }

    public static function PromateGlobalAdmin(){
        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin){
            if($Admin['onwer'] !== "Creator"){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }
            if(!self::$Dt->ReplayTo){
                return false;
            }
            $user_id = self::$Dt->ReplayTo;
            $Admin = GR::CheckUserGlobalAdmin(self::$Dt->ReplayTo);
            if($Admin){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf('مدیر %s از قبل در لیست مدیران موجود میباشد',[self::$Dt->ReplayFullname]),
                    'parse_mode' => 'HTML',
                ]);
            }

            GR::AddActivity( vsprintf('مدیر %s به لیست مدیران اضافه کرد %s رو.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
            GR::AddToAdminList();
            GR::GetAdminSetting($user_id);
            return true;
        }
    }

    public static function AdminSetting(){
        $Ex = explode('/',self::$Dt->data);
        $Key = $Ex['1'];
        $user_id = $Ex['2'];
        $adminDetial  = GR::CheckUserGlobalAdmin($user_id);
        $Val = ($adminDetial[$Key] == 1 ? 0 : 1);
        GR::ChangeAdminSetting($Key,$Val,$user_id);
        $adminDetial2  = GR::CheckUserGlobalAdmin($user_id);
        $InlineKeyboard = GR::GetAdminKeyboard($adminDetial2);
        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => vsprintf('تنظیمات دسترسی مدیر : %s',[$adminDetial['fullname']]),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => $InlineKeyboard,
        ]);
    }

    public static function CM_AdminSetting(){
        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin){
            if($Admin['onwer'] !== "Creator"){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }


            $user_id = (self::$Dt->ReplayTo ? self::$Dt->ReplayTo :  self::$Dt->text);
            $name = (self::$Dt->ReplayTo ? self::$Dt->ReplayTo :  "null");
            $Admin = GR::CheckUserGlobalAdmin($user_id);
            if(!$Admin){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf('%s در لیست مدیریت وجود ندارد',[self::$Dt->ReplayFullname ?? self::$Dt->text]),
                    'parse_mode' => 'HTML',
                ]);
            }

            GR::GetAdminSetting($user_id);
            return true;
        }
    }
    public static function RemoveAsBanList(){
        $Admin = GR::CheckUserGlobalAdmin(self::$Dt->user_id);
        if($Admin) {
            if ($Admin['remove_ban'] == 0) {
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "دسترسی به این بخش برای شما محدود شده است",
                    'parse_mode' => 'HTML',
                ]);
            }

            $user_id = self::$Dt->ReplayTo;
            if ($user_id) {
                $checkInBanList = GR::CheckPlayerInBanList($user_id);
                if(!$checkInBanList){
                    $Lang = "کاربر %s در لیست بن نمیباشد.";
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => vsprintf($Lang,[self::$Dt->ReplayFullname]),
                        'parse_mode' => 'HTML',
                    ]);
                }



                GR::RemoveFromBanList($user_id);
                GR::AddActivity( vsprintf('مدیر %s از لیست بن بازی خارج کرد کاربر %s رو.',[self::$Dt->user_link,self::$Dt->PlayerLink]));
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => vsprintf('کاربر %s با موفقیت از لیست بن خارج شد',[self::$Dt->PlayerLink]),
                    'parse_mode' => 'HTML',
                ]);

                return  Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => vsprintf('تبریک میگم الان دیگه توی لیست سیاه ربات نویسی و توسط %s از لیست بن خارج شدی.',[self::$Dt->user_link]),
                    'parse_mode' => 'HTML',
                ]);
            }
        }
    }


    public static function CM_Achievement(){
        $Achio = GR::GetAchievement();

    }

    public static function CM_NewChatTitle($title){

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => vsprintf('Changed Group Name : %s To : (%s)',[GR::FilterN(RC::Get('group_name')) ?? "null",$title]),
            'parse_mode' => 'HTML',
        ]);
        RC::GetSet(GR::FilterN($title),'group_name');
        return true;
    }

    public static function CM_ChatId(){

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->chat_id,
            'parse_mode' => 'HTML',
        ]);
    }
    public static function CM_Normal(){


        if((int) self::$Dt->user_id !== ADMIN_ID){
            return false;
        }



        $result =  self::$Dt->collection->group_list_history->find([]);
        if($result) {
            $array = iterator_to_array($result);
            foreach ($array as $row) {
                self::$Dt->collection->group_list->insertOne([
                    'group_name' => $row['group_name'],
                    'group_id' => (float)$row['group_id'],
                    'game_mode' => $row['game_mode'],
                    'lang' => $row['lang'],
                    'listData' => $row['listData'],
                    'in_list' => true,
                    'score' => (int)$row['score'],
                    'in' => jdate('Y-m-d H:i:s'),
                    'in_amd' => date('Y-m-d H:i:s'),
                ]);
            }
            self::$Dt->collection->group_list_history->deleteMany([]);
        }

    }


    public static function perform_task($row) {
        $start_time = time();
        while(true) {
            if ((time() - $start_time) > 3) {
                return false; // timeout, function took longer than 300 seconds
            }
            if(RC::CheckExit('SendPlayer2:'.$row['user_id'])) {

                Request::sendMessage([
                    'chat_id' => -1001162150617,
                    'text' =>  'last send for: '.$row['user_id']."|".$row['fullname'],
                    'parse_mode' => 'HTML',
                ]);
                return true;
            }

            $re = Request::forwardMessage([
                'chat_id' => $row['user_id'],
                'from_chat_id' => -1001411379620,
                'message_id' => 803
            ]);
            RC::GetSet(true,'SendPlayer2:'.$row['user_id']);
            if ($re->isOk()) {

                Request::sendMessage([
                    'chat_id' => -1001162150617,
                    'text' =>  'send for: '.$row['user_id']."|".$row['fullname'],

                ]);

            }else{
                Request::sendMessage([
                    'chat_id' => -1001162150617,
                    'text' =>  var_export($re,true),
                    'parse_mode' => 'HTML',
                ]);
                Request::sendMessage([
                    'chat_id' => -1001162150617,
                    'text' =>  'Not Can Send:'.$row['user_id']."|".$row['fullname'],
                    'parse_mode' => 'HTML',
                ]);
            }

            return true;
        }
    }

    public static function SendGroupList($lang,$mode,$td = false){
        self::$Dt->LM = new Lang(FALSE);
        if($mode !== "all") {
            self::$Dt->LM->load("{$mode}_" . $lang, FALSE);
        }

        if(!$td) {
            $re = Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => self::$Dt->L->_('ListGroupFor', array("{0}" => self::ReCodeLang($lang), "{1}" => ($mode !== "all" ? self::$Dt->LM->_('game_mode') : 'همه'))),
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }
        GR::GetGroupList($lang,$mode,$td);
    }

    public static function SelectGroupList($for){
        $reply_markup = self::_getGameMode($for,"GroupGameMode_{$for}_",true);
        if($reply_markup) {
            self::$Dt->LM = new Lang(FALSE);
            self::$Dt->LM->load("main_".$for, FALSE);

            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => self::$Dt->LM->_('GetListForMode',array("{0}" => self::ReCodeLang($for))),
                'reply_markup' => $reply_markup,
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }
    }
    public static function CM_GroupList(){
        $reply_markup = self::GetLangKeyboad('Grouplist_');
        if($reply_markup) {
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('GetGroupList_Step_Lang'),
                'reply_markup' => $reply_markup,
            ]);
            if($re->isOk()) {
                if (self::$Dt->typeChat !== "private") {
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => "<strong>" . self::$Dt->L->_('pmSendToPrivate') . "</strong>",
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }else{
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => "<strong>" . self::$Dt->L->_('PleaseStartBot') . "</strong>",
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }

        }

    }

    public static function CM_Sync(){
        /*
        $SyncData = self::SyncUser(self::$Dt->user_id);
        if($SyncData){

            $array = array("{0}" => $SyncData['total_game_play'] ,"{1}" => $SyncData['game_won'] , "{2}" => $SyncData['game_lost']  ,"{3}" => $SyncData['game_survived']);
            $Stats = self::$Dt->L->_('StateS',$array);
            $Nop = RC::NoPerfix();
            $Nop->set('user:stats:'.self::$Dt->user_id,$Stats);
            $PlayerM = self::$Dt->L->_('SyncUser',$array);
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $PlayerM,
                'parse_mode' => 'HTML',
            ]);
        }
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => 'هوز بازی برای شما ثبت نشده است',
            'parse_mode' => 'HTML',
        ]);
        */

    }

    public static function CM_Gets(){

        $NoP = RC::NoPerfix();
        if(self::$Dt->ReplayTo){
            if($NoP->exists('user:stats:'.self::$Dt->ReplayTo)){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('StatsG',array("{0}" => self::$Dt->PlayerLink, "{1}" => $NoP->get('user:stats:'.self::$Dt->ReplayTo))),
                    'parse_mode' => 'HTML',
                ]);
            }else{
                return Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NoStateInW',array("{0}"=> self::$Dt->user_link)),
                    'parse_mode' => 'HTML',
                ]);
            }
        }

        if($NoP->exists('user:stats:'.self::$Dt->user_id)){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('StatsG',array("{0}" => self::$Dt->user_link, "{1}" => $NoP->get('user:stats:'.self::$Dt->user_id))),
                'parse_mode' => 'HTML',
            ]);
        }else{
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('NoStateInW',array("{0}"=> self::$Dt->user_link)),
                'parse_mode' => 'HTML',
            ]);
        }


    }

    public static function is_404($url) {
        $handle = curl_init($url);
        curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);
        return $httpCode;
    }

    public static function SyncUser($user_id){
        if( self::is_404("https://www.tgwerewolf.com/Stats/PlayerStats/?pid=" . $user_id) == "200" ) {
            $data = file_get_contents("https://www.tgwerewolf.com/Stats/PlayerStats/?pid=" . $user_id);
            if ($data) {
                $re = json_decode($data);
                if (empty($re)) {

                    return 0;
                } else {
                    preg_match_all('!\d+!', $re, $matches);
                    return ['total_game_play' => $matches['0']['0'], 'game_won' => $matches['0']['1'], 'game_lost' => $matches['0']['3'], 'game_survived' => $matches['0']['5']];
                }
            } else {
                return 0;
            }
        }else{
            return 0;
        }

    }



    public static function CM_ModeInfo(){
        if(self::$Dt->typeChat !== "private") {
            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotGameMode'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:
                case 1:
                    $GameMode = RC::Get('GamePl:gameModePlayer');
                    $Lang = self::$Dt->L->_($GameMode.'_modinfo');
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => $Lang,
                        'parse_mode' => 'HTML'
                    ]);
                    break;
            }
        }
    }


    public static function SendMessageToPV($from_chat_id,$Message_id){
        //$data = GR::GetPlayerLists();

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $from_chat_id."|".$Message_id,
            'message_id' => $Message_id
        ]);

        //   $NoP = RC::NoPerfix();
        //   $countSend =  0;
        //  foreach ($data as $row) {

        //     if($NoP->exists('SendPvUser2:'.$row['user_id'])){
        //     continue;
        //   }

        //  $re = role_trouble
        //      'chat_id' => $row['user_id'],
        //      'from_chat_id' => $from_chat_id,
        //      'message_id' => $Message_id
        //   ]);
        //  if($re->isOk()){
        // $countSend++;
        //  }
        //  $NoP->set('SendPvUser2:'.$row['user_id'],true);
        /// }

        //  Request::sendMessage([
        //      'chat_id' => self::$Dt->user_id,
        //     'text' => "Send For: ".$countSend,
        //     'parse_mode'=> 'HTML',
        //  ]);

    }


    public static function CM_Reset(){
        if((int) self::$Dt->user_id !== ADMIN_ID){
            return false;
        }
        self::$Dt->collection->Players->updateMany(array(),  ['$set' => ['credit' => 0]] );

        $NoP = RC::NoPerfix();
        $Keys = $NoP->keys('userGameTime:*');
        foreach ($Keys as $key){
            $NoP->set($key,0);
        }

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => "Reset Count: ".count($Keys),
            'parse_mode'=> 'HTML',
        ]);

    }

    public static function CM_Getstatus(){
        $info = Request::getWebhookInfo();
        if($info->ok == true){
            $state = self::$Dt->L->_('status_ok');
        }else{
            $state = self::$Dt->L->_('status_off');
        }

        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $state,
            'parse_mode'=> 'HTML',
        ]);

    }


    public static function CM_RunInfo(){
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('RunInfo',array('{0}' => GR::GetUptime() , '{1}' => GR::get_tgame() ,'{2}' => GR::get_tplayer())),
            'parse_mode'=> 'HTML',
        ]);

    }



    // Challenge Game


    public static function CM_KillGame(){
        if(self::$Dt->typeChat !== "private") {

            if(self::$Dt->admin == 0){
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotAllowForUser'),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML',
                ]);
            }


            $checkStartGame = GR::CheckGPGameState();
            switch ($checkStartGame){
                case 0:
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotGameForKill'),
                        'parse_mode' => 'HTML'
                    ]);
                    break;
                case 2:
                case 1:

                    GR::KillGame();
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('KillGame',array("{0}" => self::$Dt->user_link)),
                        'parse_mode' => 'HTML'
                    ]);

                    break;
            }
        }
    }


    public static function CM_Live(){



        $List = GR::GetLive();

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $List,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => 'true',
        ]);

    }

    public static  function CallBackQuery(){

        $Nop = RC::NoPerfix();

        $data    = ['inline_query_id' => self::$Dt->inline->getId(),'cache_time ' => 0 ,'is_personal'=> true];

        $results = [];

        $List = GR::GetUserdeaths();

        if($Nop->exists('user_state_chache:'.self::$Dt->user_id)){
            $Stats = $Nop->get('user_state_chache:'.self::$Dt->user_id);
        }else {
            $Stats = GR::GetStats(self::$Dt->user_id);
            $Nop->set('user_state_chache:' . self::$Dt->user_id, $Stats);
            $Nop->expire('user_state_chache:' . self::$Dt->user_id, 300);
        }

        if($Stats){
            $Stats = $Stats;
        }else{
            $Stats = self::$Dt->L->_('emptyStates');
        }




        if($Nop->exists('user_Social:'.self::$Dt->user_id)){
            $Social = $Nop->get('user_Social:'.self::$Dt->user_id);
        }else {
            $Social = GR::GetSocialUser();
            $Nop->set('user_Social:'.self::$Dt->user_id,$Social) ;
            $Nop->expire('user_Social:'.self::$Dt->user_id,1500);
        }




        $articles = [
            [
                'id'                    => '001',
                'title'                 => 'فعالیت ها',
                'description'           => 'کجا ها مردید و چند درصد',
                'input_message_content' => new InputTextMessageContent(['message_text' => $List,'parse_mode'=> 'html']),
            ],
            [
                'id'                    => '002',
                'title'                 => 'وضعیت بازی',
                'description'           => 'وضعیت بازی شما در اونیکس ورولف',
                'input_message_content' => new InputTextMessageContent(['message_text' => ' ' . $Stats,'parse_mode'=> 'html']),
            ],
            [
                'id'                    => '003',
                'title'                 => 'آمار بازی',
                'description'           => 'آمار بازی شما با دوستان بیشترین لاوری  و...' ,
                'input_message_content' => new InputTextMessageContent(['message_text' => $Social ,'parse_mode'=> 'html']),
            ],
        ];

        foreach ($articles as $article) {
            $results[] = new InlineQueryResultArticle($article);
        }


        $data['results'] = '[' . implode(',', $results) . ']';

        return Request::answerInlineQuery($data);
    }


    public static function CM_GroupStats(){

        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

        if(self::$Dt->admin == 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => GR::GroupStats(),
            'parse_mode' => 'HTML',
        ]);


        if (self::$Dt->typeChat !== "private") {
            Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('pmSendToPrivate') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

    }

    public static function CM_GetCoin(){
        $NoP = RC::NoPerfix();
        return false;
        if($NoP->exists('userGetCoin:'.self::$Dt->user_id)){
            $InTime  = $NoP->get('userGetCoin:'.self::$Dt->user_id);
            $Left = time() - $InTime;
            $Minux = 10 - floor($Left / 60) ;
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('LastGetCoins',$Minux),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        $UserCr = GR::GetUserCredit();
        $New = $UserCr + 60;
        GR::MinCreditCredit($New);

        $NoP->set('userGetCoin:'.self::$Dt->user_id,time());
        $NoP->expire('userGetCoin:'.self::$Dt->user_id,600);

        return  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('GetCoin',$New),
            'parse_mode' => 'HTML',
        ]);

    }


    public static function CM_MyCoin(){

        $UserCr = GR::GetUserCredit();
        return  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('MyCoinD',$UserCr),
            'parse_mode' => 'HTML',
        ]);

    }

    public static function CM_Dontate(){
        if(!self::$Dt->text) {
            $inline_keyboard = new InlineKeyboard(
                [
                    ['text' => "پرداخت غیر مستقیم ♥", 'url' => "https://me.pay.ir/onyxwerewolf"]
                ]

            );
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('DonateText', array("{0}" => self::$Dt->user_link)),
                'reply_markup' => $inline_keyboard,
                'parse_mode' => 'html'
            ]);
        }

        $GetText = self::$Dt->text;
        if(!is_numeric($GetText)){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "لطفا مبلغ را بصورت عددی و به تومان وارد نمایید.",
                'parse_mode' => 'html'
            ]);
        }

        $amount = (int) $GetText."0";
        $mobile = "";
        $factorNumber = "";
        $description = "";
        $redirect = 'https://onyxwerewolf.com/verify';
        $result = GR::send(self::$Dt->TokenPayment, $amount, $redirect, $mobile, $factorNumber, $description);
        $result = json_decode($result);
        if(isset($result->id)) {
            $GetText = self::$Dt->L->_('DonateItemText',array("{0}" => number_format($amount),"{1}" => "https://pay.ir/pg/$result->token"));
            $Keyboard = new InlineKeyboard(
                [
                    ['text' => 'ورود به درگاه پرداخت','url' => $result->link ]
                ]
            );

            GR::SaveTransectionPay((float) $amount,$result->link,"sponser");
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $GetText,
                'parse_mode' => 'HTML',
                'reply_markup' => $Keyboard,
            ]);
        } else {
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'متاسفانه اتصال به درگاه مقدور نیست!'.$result->errorMessage,
                'parse_mode' => 'HTML',
            ]);
        }





    }


    public static function CM_addfriend(){



        $Text = self::$Dt->text;
        if(isset($Text)) {
            if (is_numeric($Text) and strlen($Text) > 7) {
                $user_id = self::$Dt->text;
            } elseif (preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/", $Text, $matches)) {
                $username = $matches[0];
                $CheckUsername  = GR::CheckPlayerByUsername($username);
                if(!$CheckUsername){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('NotFoundUser'),
                        'parse_mode' => 'HTML'
                    ]);
                }

                $user_id = $CheckUsername['user_id'];
            }
        }



        if(self::$Dt->ReplayTo){
            $user_id = self::$Dt->ReplayTo;
            $fullname = self::$Dt->fullname;
        }


        if(!isset($user_id)){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotFoundUser'),
                'parse_mode' => 'HTML'
            ]);
        }


        $CheckUser = GR::CheckUserById($user_id);
        if(!$CheckUser){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotFoundUser'),
                'parse_mode' => 'HTML'
            ]);
        }
        if($user_id == self::$Dt->user_id){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NotYouFriend'),
                'parse_mode' => 'HTML'
            ]);
        }


        $fullname =  GR::ConvertName($CheckUser['user_id'],$CheckUser['fullname']);

        $CheckLastFriend = GR::CheckLastFriend($user_id);
        if($CheckLastFriend){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('LastIn',$fullname),
                'parse_mode' => 'HTML'
            ]);
        }

        $Np = RC::NoPerfix();

        if($Np->exists("userAddReq:{$user_id}:".self::$Dt->user_id)){
            $msg_id = $Np->get("userAddReq:{$user_id}:".self::$Dt->user_id);
            $Ex = explode("|",$msg_id);
            $inline_keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('AddedFriendNo'), 'callback_data' => "AddFriend_remove/" . $user_id."/".$Ex['1']]
                ]
            );
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('LastSendReq', $fullname),
                'reply_markup' => $inline_keyboard,
                'parse_mode' => 'html'
            ]);
        }

        $inline_keyboard2 = new InlineKeyboard(
            [['text' => self::$Dt->L->_('AddFriendBackNo'),'callback_data' => "AddFriend_no/".self::$Dt->user_id ],['text' => self::$Dt->L->_('AddFriendBackOk'),'callback_data' => "AddFriend_ok/".self::$Dt->user_id ]],
            [['text' => self::$Dt->L->_('AddFriendBackOkBack'),'callback_data' => "AddFriend_addback/".self::$Dt->user_id ]]
        );
        $re = Request::sendMessage([
            'chat_id' => $user_id,
            'text' => self::$Dt->L->_('AddFriendCallBack',self::$Dt->user_link),
            'reply_markup' => $inline_keyboard2,
            'parse_mode' => 'html'
        ]);

        if($re->isOk()) {
            $msg_id = $re->getResult()->getMessageId();
            $inline_keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('AddedFriendNo'), 'callback_data' => "AddFriend_remove/" . $user_id."/".$msg_id]
                ]

            );
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('AddedFriendToList', self::$Dt->user_link, $fullname),
                'reply_markup' => $inline_keyboard,
                'parse_mode' => 'html'
            ]);
            if($re->isOk()){
                $Np->set("userAddReq:".$user_id.":".self::$Dt->user_id,$re->getResult()->getMessageId()."|".$msg_id."|".self::$Dt->fullname);
            }
            return true;
        }
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('NotSend', $fullname),
            'parse_mode' => 'html'
        ]);


    }

    public static function FriendR($cm,$user_id,$msg_id = false){
        $Np = RC::NoPerfix();
        switch ($cm){
            case 'AddFriend_remove':
                if($Np->exists("userAddReq:{$user_id}:".self::$Dt->user_id)){
                    $Get = $Np->get("userAddReq:{$user_id}:".self::$Dt->user_id);
                    $Ex = explode("|",$Get);
                    $msg_id = $Ex['1'];

                    $re = Request::deleteMessage([
                        'chat_id' => $user_id,
                        'message_id' => $msg_id,
                    ]);

                    $Np->del("userAddReq:{$user_id}:".self::$Dt->user_id);
                    $inline_keyboard = new InlineKeyboard([]);
                    return Request::editMessageText([
                        'chat_id' => self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'text' => self::$Dt->L->_('RemoveSuccess'),
                        'reply_markup' => $inline_keyboard,
                    ]);
                }

                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('RemoveNotFind'),
                    'parse_mode' => 'html'
                ]);
                break;

            case 'AddFriend_no':
                if($Np->exists("userAddReq:".self::$Dt->user_id.":".$user_id)) {
                    $Get = $Np->get("userAddReq:".self::$Dt->user_id.":".$user_id);
                    $Ex = explode("|", $Get);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('AddFriendNoBacks',self::$Dt->user_link),
                        'parse_mode' => 'html'
                    ]);
                    Request::editMessageReplyMarkup([
                        'chat_id' =>  $user_id,
                        'message_id' => $Ex['0'],
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    $Np->del("userAddReq:".self::$Dt->user_id.":".$user_id);

                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('RemoveRequestS',$Ex['2']),
                        'parse_mode' => 'html'
                    ]);
                    return Request::editMessageReplyMarkup([
                        'chat_id' =>  self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);

                }
                break;
            case 'AddFriend_ok':
                if($Np->exists("userAddReq:".self::$Dt->user_id.":".$user_id)) {
                    $Get = $Np->get("userAddReq:".self::$Dt->user_id.":".$user_id);
                    $Ex = explode("|", $Get);

                    Request::editMessageReplyMarkup([
                        'chat_id' =>  $user_id,
                        'message_id' => $Ex['0'],
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('AddFriendIn',self::$Dt->user_link),
                        'parse_mode' => 'html'
                    ]);

                    GR::AddToFriendS($user_id,self::$Dt->user_id);

                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('AddFriendOk',$Ex['2']),
                        'parse_mode' => 'html'
                    ]);
                    return Request::editMessageReplyMarkup([
                        'chat_id' =>  self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);

                }
                break;
            case 'AddFriend_addback':
                if($Np->exists("userAddReq:".self::$Dt->user_id.":".$user_id)) {
                    $Get = $Np->get("userAddReq:".self::$Dt->user_id.":".$user_id);
                    $Ex = explode("|", $Get);

                    Request::editMessageReplyMarkup([
                        'chat_id' =>  $user_id,
                        'message_id' => $Ex['0'],
                        'reply_markup' => new InlineKeyboard([]),
                    ]);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('AddFriendIn',self::$Dt->user_link),
                        'parse_mode' => 'html'
                    ]);

                    GR::AddToFriendS($user_id,self::$Dt->user_id);
                    GR::AddToFriendS(self::$Dt->user_id,$user_id);
                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => self::$Dt->L->_('AddFriendOk',$Ex['2']),
                        'parse_mode' => 'html'
                    ]);
                    return Request::editMessageReplyMarkup([
                        'chat_id' =>  self::$Dt->user_id,
                        'message_id' => self::$Dt->message_id,
                        'reply_markup' => new InlineKeyboard([]),
                    ]);

                }
                break;

        }
    }


    public static function CM_AddGroup(){


        $Text = self::$Dt->text;

        if(isset($Text)) {
            if (is_numeric($Text) and strlen($Text) > 7) {
                $chat_id = self::$Dt->text;
            }else {
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "لطفا ای دی گروه را همراه با کامند ارسال کنید",
                    'parse_mode' => 'HTML',
                ]);
            }

        }else {
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "لطفا ای دی گروه را همراه با کامند ارسال کنید",
                'parse_mode' => 'HTML',
            ]);
        }



        GR::AddWhiteList($chat_id);
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => "گروه مورد نظر با موفقیت به لیست اضافه شد.",
            'parse_mode' => 'HTML',
        ]);


        return Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => "گروه شما با موفقیت تا تاریخ".jdate('Y-m-d H:i:s',strtotime('+30 day', time()))." به فهرست مجار برای بازی اضافه شد از این پس میتوانید بازی کنید در این گروه ♥.",
            'parse_mode' => 'HTML',
        ]);


    }

    public static function CM_MyLevel(){

        $L = GR::GetLevel();

        if($L){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>$L,
                'parse_mode' => 'html'
            ]);
        }

        return false;
    }

    public static function CM_setcultmessage(){

        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

        if(self::$Dt->admin == 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        return true;

    }



    public static function CM_JoinTornumet(){

        if(self::$Dt->typeChat !== "private") {
            Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('pmSendToPrivate'),
                'parse_mode' => 'HTML',
            ]);
        }

        $cn = self::$Dt->collection->PlayerTornumets;

        $cns = self::$Dt->collection->tournumets;

        $count = $cn->countDocuments([]);

        $find_tornumet = $cns->findOne(['tornumet_id' => 1]);
        $time = time();
        $timeLeft = $find_tornumet['tornumet_expire'] - $time;
        if($timeLeft <= 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>  self::$Dt->L->_('TimeJoinTornumetExpire'),
                'parse_mode' => 'HTML',
            ]);
        }

        $find = $cn->findOne(['player_id' => self::$Dt->user_id]);
        if($find){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>  self::$Dt->L->_('ErrorYouLastJoinToTournumet').PHP_EOL.($find['pay'] ? self::$Dt->L->_('successPayOk') : self::$Dt->L->_('ErrorNotPay',array("{0}" => number_format($find_tornumet['price_tornumet']),"{1}" => $count )) ),
                'parse_mode' => 'HTML',
            ]);
        }

        $cn->insertOne([
            'player_id' => self::$Dt->user_id,
            'user_top' => 0,
            'pay' => 0,
            'status' => 1,
            'tornumet_id' => 1,
            'joinIn' => time(),
            'JoinAt' => jdate('Y-m-d H:i:s'),
        ]);

        $array = [
            "{0}" =>  "<strong>".$find_tornumet['tornumet_name']."</strong>",
            "{1}" => "<strong>".number_format($find_tornumet['price_tornumet'])."</strong>",
            "{2}" => jdate("Y/m/d H:i:s",$find_tornumet['tornumet_expire']),
            "{3}" => '<a href="https://t.me/OnyxWereWolf/268">جزئیات تورنومت </a>',
            "{4}" => $count
        ];
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  self::$Dt->L->_('JoinSuccessTornumet',$array),
            'parse_mode' => 'HTML',
        ]);


    }


    public static function CM_ChangeState(){
        if((int) self::$Dt->user_id !== ADMIN_ID){
            return false;
        }

        $Text = (self::$Dt->text ? self::$Dt->text : false );

        if(!$Text){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' =>  "لطفا ای دی حساب کاربری قبلی و جدید را وارد کنید.",
                'parse_mode' => 'HTML',
            ]);
        }

        $ExplodeText = explode(' ',$Text);

        if(!isset($ExplodeText[0])) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "لطفا ای دی حساب کاربری قبلی را وارد کنید.",
            'parse_mode' => 'HTML',
        ]);
        if(!isset($ExplodeText[1])) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "لطفا ای دی حساب کاربری جدید را وارد کنید.",
            'parse_mode' => 'HTML',
        ]);

        $LastUserID = (float)  $ExplodeText[0];
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "1️⃣ در حال یافتن حساب کاربری قبلی ...",
            'parse_mode' => 'HTML',
        ]);

        $CheckLastID = GR::CheckUserById($LastUserID);
        if(!$CheckLastID) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "حساب کاربری قبلی یافت نشد.",
            'parse_mode' => 'HTML',
        ]);

        $NewUserId = (float)  $ExplodeText[1];

        Request::sendMessage([
            'chat_id' => $NewUserId,
            'text' =>  "حساب کاربری شما در حال انتقال از اکانت قبلی به جدید میباشد پایان عملیات پیام انتقال را دریافت خواهید کرد.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "2️⃣ در حال یافتن حساب کاربری جدید ...",
            'parse_mode' => 'HTML',
        ]);

        $CheckNewUserID = GR::CheckUserById($NewUserId);
        if(!$CheckNewUserID) return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "حساب کاربری جدید یافت نشد.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "3️⃣ درحال انقال استیت قبلی به جدید...",
            'parse_mode' => 'HTML',
        ]);

        self::$Dt->collection->Players->updateOne(
            ['user_id' => $NewUserId],
            ['$set' => [
                'total_game' => ((float) $CheckNewUserID['total_game'] + (float)  $CheckLastID['total_game']),
                'SurviveTheGame' => ((float) $CheckNewUserID['SurviveTheGame'] + (float)  $CheckLastID['SurviveTheGame']),
                'SlaveGames' => ((float) $CheckNewUserID['SlaveGames'] + (float)  $CheckLastID['SlaveGames']),
                'LoserGames' => ((float) $CheckNewUserID['LoserGames'] + (float)  $CheckLastID['LoserGames']),
                'credit' => ((float) $CheckNewUserID['credit'] + (float)  $CheckLastID['credit']),
                'top' => ((float) $CheckNewUserID['top'] + (float)  $CheckLastID['top']),
                'Site_Password' => ((float) $CheckNewUserID['Site_Password'] + (float)  $CheckLastID['Site_Password']),
                'Site_Username' => ((float) $CheckNewUserID['Site_Username'] + (float)  $CheckLastID['Site_Username']),
            ]]
        );

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "✅ استیت با موفقیت انتقال یافت.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "4️⃣ در حال انتقال کیل ها ...",
            'parse_mode' => 'HTML',
        ]);

        self::$Dt->collection->game_activity->updateMany(
            ['player_id' => $LastUserID],
            ['$set' => [
                'player_id' => $NewUserId,
            ]]
        );


        self::$Dt->collection->game_activity->updateMany(
            ['to' => $LastUserID],
            ['$set' => [
                'to' => $NewUserId,
            ]]
        );

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "✅ کیل ها با موفقیت منقل شد.",
            'parse_mode' => 'HTML',
        ]);

        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "✅ اطلاعات حساب کاربری با موفقیت منتقل شد.",
            'parse_mode' => 'HTML',
        ]);


        return Request::sendMessage([
            'chat_id' => $NewUserId,
            'text' =>  "حساب کاربری قبلی شما به حساب کاربری جدید شما انتقال یافت ".jdate('Y F d H:i:s." چنانچه مشکلی بوجود آمد در روند انتقال به حساب کاربری @dev_amirk پیام دهید.'),
            'parse_mode' => 'HTML',
        ]);



    }

    public static function CM_MyGroupState(){
        $GroupState = GR::GetGroupState();
        if(!$GroupState)  return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "شما دسترسی ندارید!",
            'parse_mode' => 'HTML',
        ]);

        if($GroupState === 2)  return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "دسترسی شما منقضی شده است جهت تمدید دسترسی به ای دی  @dev_amirk پیام دهید.",
            'parse_mode' => 'HTML',
        ]);

        if($GroupState === 3)  return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  "گروهی برای شما یافت نشد چنانچه مشکلی دارید به ای دی  @dev_amirk پیام دهید.",
            'parse_mode' => 'HTML',
        ]);

        $Array = array("{0}" => $GroupState['in'],"{1}" => self::$Dt->L->_($GroupState['game_mode']),"{2}" =>  self::ReCodeLang($GroupState['lang']),"{3}" => $GroupState['avg_PlayerCount'],"{4}" => $GroupState['PlayerCount'],"{5}" => $GroupState['avg_gameTime'],"{6}" => $GroupState['gameTime'],"{7}" => $GroupState['avg_nobeplayer'],"{8}" => $GroupState['nobeplayer'],"{9}" => $GroupState['score'],"{10}" => $GroupState['grou_name']);
        $Lang = self::$Dt->L->_('GroupStateGet',$Array);

        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  $Lang,
            'parse_mode' => 'HTML',
        ]);
    }

    public static function CM_Report(){
        if (self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('SendToGroup') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }


        if(!self::$Dt->ReplayTo){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('ReportError') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        $CheckLastReport = GR::CheckLastReport();
        if($CheckLastReport){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('ReportFiled') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        if((float) self::$Dt->user_id === (float) self::$Dt->ReplayTo){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('ReportFiledMsg') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }
        $ReportId = rand(0,999999);

        $GetDetialWarn = GR::GetPlayerWarn(self::$Dt->ReplayTo);

        // Send UserMessage

        Request::sendMessage([
            'chat_id' => self::$Dt->ReplayTo,
            'text' => "<strong>" . self::$Dt->L->_('ReportUser',array("{0}" => ($GetDetialWarn ? $GetDetialWarn[0]['count']+1 : 1),"{1}" => ($GetDetialWarn ? $GetDetialWarn[0]['sumWarn'] : 0) )) . "</strong>",
            'parse_mode' => 'HTML',
        ]);
        //Admin Message
        $inline_keyboard = GR::GetAdminKeyboardReport($ReportId);
        $Array = array(
            "{0}" => self::$Dt->user_link." <code>(".self::$Dt->user_id.")</code>",
            "{1}" => self::$Dt->PlayerLink." <code>(".self::$Dt->ReplayTo.")</code>",
            "{2}" => (self::$Dt->text ? self::$Dt->text  : '---'),
            "{3}" => ($GetDetialWarn ? $GetDetialWarn[0]['count'] : 0),
            "{4}" => GR::GetUserReportCount(self::$Dt->user_id),
            "{5}" => jdate('Y F d H:i:s'),
            "{6}" => "<strong>⚠️بررسی نشده</strong>",
            "{7}" =>  GR::FilterN(RC::Get('group_name')),
            "{8}" => ($GetDetialWarn ? $GetDetialWarn[0]['sumWarn'] : 0),
            "{9}" => "#$ReportId",
        );
        Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' => self::$Dt->L->_('ReportPlayerAdmin',$Array),
            'reply_markup' => $inline_keyboard,
            'parse_mode' => 'HTML'
        ]);

        GR::SaveReport($ReportId);
        return Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('ReportSuccess',array("{0}" => $ReportId)),
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
        ]);
    }

    public static function ReportUserAdmin($reportId,$section){

        if((int) self::$Dt->user_id !== ADMIN_ID){
            return false;
        }

        $CheckReport = GR::CheckReportId($reportId);
        if(!$CheckReport){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "گزارش یافت نشد !",
                'parse_mode' => 'HTML',
            ]);
        }

        $GetDetialWarn = GR::GetPlayerWarn($CheckReport['report_to']);


        $Array = array(
            "{0}" => $CheckReport['reporter_id_fullname']." <code>(".$CheckReport['reporter_id'].")</code>",
            "{1}" => $CheckReport['report_to_fullname']." <code>(".$CheckReport['report_to'].")</code>",
            "{2}" => $CheckReport['description'],
            "{3}" => ($GetDetialWarn ? $GetDetialWarn[0]['count'] : 0),
            "{4}" => GR::GetUserReportCount($CheckReport['reporter_id']),
            "{5}" => $CheckReport['report_jdate'],
            "{6}" => "<strong>✅ بررسی شده</strong>",
            "{7}" =>  $CheckReport['group_name'],
            "{8}" => ($GetDetialWarn ? $GetDetialWarn[0]['sumWarn'] : 0),
            "{9}" => "#$reportId",
        );
        $Warn = 0;
        $Nothing = false;
        switch ($section) {
            case 'ban_all':
                $BanData = ['group_id' => $CheckReport['group_id'], 'user_id' => $CheckReport['report_to'], 'textData' => $CheckReport['description'], 'fullname' => $CheckReport['report_to_fullname'], 'link' => $CheckReport['report_to_fullname'], 'ban_antilto' => 1];
                GR::CustomAddPlayerBanList($BanData);
                $ResultCheck = "🔆 نتیجه بررسی : کاربر بن دائمی شد.";
                $resultUser = "<strong>* همیشه</strong>";
                break;
            case 'ban_1_day':
                $BanData = ['group_id' => $CheckReport['group_id'], 'user_id' => $CheckReport['report_to'], 'textData' => $CheckReport['description'], 'fullname' => $CheckReport['report_to_fullname'], 'link' => $CheckReport['report_to_fullname'], 'ban_antilto' =>  strtotime('+1 day')];
                GR::CustomAddPlayerBanList($BanData);
                $ResultCheck = "🔆 نتیجه بررسی : کاربر 1 روز بن شد.";
                $resultUser = "<strong>1 روز</strong>";
                break;
            case 'ban_7_day':
                $BanData = ['group_id' => $CheckReport['group_id'], 'user_id' => $CheckReport['report_to'], 'textData' => $CheckReport['description'], 'fullname' => $CheckReport['report_to_fullname'], 'link' => $CheckReport['report_to_fullname'], 'ban_antilto' =>  strtotime('+1 week')];
                GR::CustomAddPlayerBanList($BanData);
                $ResultCheck = "🔆 نتیجه بررسی : کاربر 1 هفته بن شد.";
                $resultUser = "<strong>1 هفته</strong>";
                break;
            case 'ban_1_hou':
                $BanData = ['group_id' => $CheckReport['group_id'], 'user_id' => $CheckReport['report_to'], 'textData' => $CheckReport['description'], 'fullname' => $CheckReport['report_to_fullname'], 'link' => $CheckReport['report_to_fullname'], 'ban_antilto' =>  strtotime('+1 hour')];
                GR::CustomAddPlayerBanList($BanData);
                $ResultCheck = "🔆 نتیجه بررسی : کاربر 1 ساعت بن شد.";
                $resultUser = "<strong>1 ساعت</strong>";
                break;
            case 'warn_1':
                $Warn = 1;
                $ResultCheck = "🔆 نتیجه بررسی : کاربر 1 اخطار داده شد.";
                $resultUser = "<strong>1 ساعت</strong>";
                break;
            case 'warn_2':
                $Warn = 2;
                $ResultCheck = "🔆 نتیجه بررسی : کاربر 2 اخطار داده شد.";
                $resultUser = "<strong>2 اخطار</strong>";
                break;
            case 'resolve':
                $ResultCheck = "🔆 نتیجه بررسی : مشکلی نبود.";
                $Nothing = true;
                break;
            default:
                $ResultCheck = "";
                $resultUser = "";
                break;
        }
        // Send Reporter Message
        Request::sendMessage([
            'chat_id' => $CheckReport['reporter_id'],
            'text' => self::$Dt->L->_('ResolveReport',array("{0}" => $CheckReport['report_to_fullname'])),
            'parse_mode' => 'HTML',
        ]);
        // Send Report To Message
        Request::sendMessage([
            'chat_id' => $CheckReport['report_to'],
            'text' => ($Warn ? self::$Dt->L->_('WarnUser',array("{0}" => $Warn , "{1}" => ($GetDetialWarn ? (int) $GetDetialWarn[0]['sumWarn']+$Warn : $Warn) ))  : ($Nothing ? self::$Dt->L->_('ReportNotIssu') : self::$Dt->L->_('Banplayer', array("{0}" => $resultUser)))),
            'parse_mode' => 'HTML',
        ]);

        GR::UpdateReportStatus($reportId,$Warn);

        return Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('ReportPlayerAdmin',$Array).PHP_EOL.$ResultCheck,
            'reply_markup' =>  new InlineKeyboard([]),
            'parse_mode' => 'HTML'
        ]);

    }

    public static function CM_SetGif(){
        if((int) self::$Dt->user_id !== ADMIN_ID){
            return false;
        }
        $GapID = [-1001452272402];

        if(!self::$Dt->text){
            return Request::sendMessage([
                'chat_id' => ADMIN_ID,
                'text' => "داده ارسالی نامعتبر",
                'parse_mode' => 'HTML',
            ]);
        }
        $AllowKey = ['start_game','startchoas','Romantic','Bomber','win_rosta','win_qatel','win_ferqe','win_wolf','nothing','win_lover','win_trap','win_firefighter','win_vampire','winner_monafeq','winner_Bomber'];
        if(self::$Dt->text === 'help'){
            return Request::sendMessage([
                'chat_id' => ADMIN_ID,
                'text' => implode(PHP_EOL,$AllowKey),
                'parse_mode' => 'HTML',
            ]);
        }
        $Ex = explode(' ',self::$Dt->text);
        if(count($Ex) !== 2){
            return Request::sendMessage([
                'chat_id' => ADMIN_ID,
                'text' => "داده ارسالی نامعتبر",
                'parse_mode' => 'HTML',
            ]);
        }
        $Key = $Ex[0];
        if(!in_array($Key,$AllowKey)){
            return Request::sendMessage([
                'chat_id' => ADMIN_ID,
                'text' => "کد دسترسی نامعتبر",
                'parse_mode' => 'HTML',
            ]);
        }
        $Gif = $Ex[1];
        $siteHeader =  @get_headers($Gif);
        if($siteHeader['0'] !== "HTTP/1.1 200 OK"){
            return Request::sendMessage([
                'chat_id' => ADMIN_ID,
                'text' => "تصویر یافت نشد!",
                'parse_mode' => 'HTML',
            ]);
        }

        $NoP = RC::NoPerfix();
        $NoP->set(self::$Dt->chat_id.":Gif:".$Key,$Gif);
        return Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' => "تصویر ".$Key." با موفقیت ثبت شد!",
            'parse_mode' => 'HTML',
        ]);

    }

    public static function CM_IP(){
        if((int) self::$Dt->user_id !== ADMIN_ID){
            return false;
        }
        if(!self::$Dt->text){
            return false;
        }

        $Find = GR::FindUserip(self::$Dt->text);
        if(!$Find){
            return Request::sendMessage([
                'chat_id' => ADMIN_ID,
                'text' => "کاربری با ای پی ".self::$Dt->text." یافت نشد !",
                'parse_mode' => 'HTML',
            ]);
        }

        return Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' => "کاربر با آی پی : ".self::$Dt->text.PHP_EOL." ای دی کاربری : ".$Find['user_id'].PHP_EOL."نام :".$Find['fullname'].PHP_EOL." نام کاربری : ".$Find['username'].PHP_EOL." نام کامل : ".GR::ConvertName($Find['user_id'],$Find['fullname']).PHP_EOL."تمامی داده ها :".PHP_EOL.implode(PHP_EOL,$Find),
            'parse_mode' => 'HTML',
        ]);
    }

    public static function CM_Coin(){
        $coin = (self::$Dt->Player ? "<code>".number_format(self::$Dt->Player['credit'])."</code>" : 0);
        $Msg = self::$Dt->L->_('MyCoin',array("{0}" => $coin));
        $Keyboard = new InlineKeyboard(
            [['text' => self::$Dt->L->_('100_coin'),'callback_data' => 'GetCoin_100' ]],
            [['text' => self::$Dt->L->_('300_coin'),'callback_data' => 'GetCoin_300' ]],
            [['text' => self::$Dt->L->_('600_coin'),'callback_data' => 'GetCoin_600' ]],
            [['text' => self::$Dt->L->_('1000_coin'),'callback_data' => 'GetCoin_1000' ]],

        );
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $Msg,
            'parse_mode' => 'HTML',
            'reply_markup' =>  $Keyboard,
        ]);
    }

    public static function GetChargeItem($Item){
        $Price = ['100' => 100000,'300' => 280000,'600' => 540000,'1000' => 920000,'dozd' => 100000];
        // $Price = ['100' => 60000,'300' => 180000,'600' => 360000,'1000' => 600000,'dozd' => 100000];

        $amount = $Price[$Item];
        $mobile = "";
        $factorNumber = "";
        $description = "";
        $redirect = 'http://onyxwerewolf.com/verify';
        $result = GR::send(self::$Dt->TokenPayment, $amount, $redirect, $mobile, $factorNumber, $description);
        $result = json_decode($result);
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => var_export($result,true),
                'parse_mode' => 'HTML',
            ]);

        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }

    public static function CM_Shop(){



        $NoP = RC::NoPerfix();
        if($NoP->exists('PlayerEmojiBuy:'.self::$Dt->user_id)){
            $Keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('CloseBuy'),'callback_data' => 'BTNSP_NO' ]
                ],
            );
            $coin = (self::$Dt->Player ? "<code>".number_format(self::$Dt->Player['credit'])."</code>" : 0);
            $Text = self::$Dt->L->_("ShopItemBeforItem",array("{0}" => $coin));
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $Text,
                'parse_mode' => 'HTML',
                'reply_markup' => $Keyboard,
            ]);
        }
        $coin = (self::$Dt->Player ? "<code>".number_format(self::$Dt->Player['credit'])."</code>" : 0);
        $Text = self::$Dt->L->_("ShopDetial",array("{0}" => $coin));

        $Keyboard = new InlineKeyboard(
            [['text' => self::$Dt->L->_('ShopItem_Dozd'),'callback_data' => 'ShopItem_Dozd' ]],
            [['text' => self::$Dt->L->_('ShopItem_Emoji'),'callback_data' => 'ShopItem_Emoji' ]],
            [['text' => self::$Dt->L->_('ShopItem_MajikSear'),'callback_data' => 'ShopItem_MajikSear' ]],
            [['text' => self::$Dt->L->_('ShopItem_MajiKhabar'),'callback_data' => 'ShopItem_MajiKhabar' ]],
            [['text' => self::$Dt->L->_('ShopItem_MajiKGhost'),'callback_data' => 'ShopItem_MajiKGhost' ]],
            [['text' => self::$Dt->L->_('ShopItem_MajiKHil'),'callback_data' => 'ShopItem_MajiKHil' ]],
            [['text' => self::$Dt->L->_('ShopItem_MajiKLaqab'),'callback_data' => 'ShopItem_MajiKLaqab' ]],
            [['text' => self::$Dt->L->_('ShopItem_Xp500'),'callback_data' => 'ShopItem_Xp500' ]],
            [['text' => self::$Dt->L->_('ShopItem_Xp1000'),'callback_data' => 'ShopItem_Xp1000' ]],
            [['text' => self::$Dt->L->_('ShopItem_Xp5000'),'callback_data' => 'ShopItem_Xp5000' ]],
            [['text' => self::$Dt->L->_('ShopItem_Xp10000'),'callback_data' => 'ShopItem_Xp10000' ]],
        );

        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $Text,
            'parse_mode' => 'HTML',
            'reply_markup' =>  $Keyboard,
        ]);

    }
    public static function ShopItemSet($item){
        $NoP = RC::NoPerfix();
        if($NoP->exists('PlayerEmojiBuy:'.self::$Dt->user_id)){
            $Keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('CloseBuy'),'callback_data' => 'BTNSP_NO' ]
                ],
            );
            $Text = self::$Dt->L->_("ShopItemBeforItem",array("{0}" => 0));
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $Text,
                'parse_mode' => 'HTML',
                'reply_markup' => $Keyboard,
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => self::$Dt->L->_('NotFoundPlayer'),'show_alert' => true]);

        }

        $Coin = self::GetCoin($item);
        $Player = GR::FindUserId(self::$Dt->user_id);
        if(!$Player){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => self::$Dt->L->_('NotFoundPlayer'),'show_alert' => true]);
        }
        $PlayerCoin = (float) (isset($Player['credit']) ? $Player['credit'] : 0);
        if($PlayerCoin < $Coin){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => self::$Dt->L->_('PleaseChargeAccount'),'show_alert' => true]);

        }
        $Keyboard = new InlineKeyboard(
            [
                ['text' => self::$Dt->L->_('ShopBtnNo'),'callback_data' => 'BTNSP_NO' ]
                ,['text' => self::$Dt->L->_('ShopBtnYes'),'callback_data' => 'BTNSP_YES_'.$item ]
            ],
        );
        $LangShop = self::$Dt->L->_('ShopItemTitle_'.$item).PHP_EOL.self::$Dt->L->_('ShopItemTitleDoc');
        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $LangShop,
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' =>  $Keyboard,
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }
    public static function GetCoin($item){
        $Coin = 600000;
        switch ($item){
            case 'Xp500':
                $Coin = 20;
                break;
            case 'Dozd':
                $Coin = 400;
                break;
            case 'Xp1000':
                $Coin = 40;
                break;
            case 'Xp5000':
                $Coin = 200;
                break;
            case 'Xp10000':
                $Coin = 400;
                break;
            case 'Emoji':
                $Coin = 5;
                break;
            case 'MajikSear':
                $Coin = 2;
                break;
            case 'MajiKhabar':
                $Coin = 5;
                break;
            case 'MajiKGhost':
                $Coin = 11;
                break;
            case 'MajiKHil':
                $Coin = 9;
                break;
            case 'MajiKLaqab':
                $Coin = 40;
                break;

        }

        return $Coin;
    }
    public static function ShopCheckout($ex){
        $NoP = RC::NoPerfix();
        if(count($ex) === 2){
            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => self::$Dt->L->_('ShopCloseMsg'),
                'parse_mode' => 'HTML',
            ]);
            $NoP->del(['PlayerEmojiBuy:'.self::$Dt->user_id,'PlayerEmojiBuyMessageID:'.self::$Dt->user_id]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
        }

        if($NoP->exists('PlayerEmojiBuy:'.self::$Dt->user_id)){
            $Keyboard = new InlineKeyboard(
                [
                    ['text' => self::$Dt->L->_('CloseBuy'),'callback_data' => 'BTNSP_NO' ]
                ],
            );
            $Text = self::$Dt->L->_("ShopItemBeforItem",array("{0}" => 0));
            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => $Text,
                'parse_mode' => 'HTML',
                'reply_markup' => $Keyboard,
            ]);
        }
        $Item = $ex[2];
        $Coin = self::GetCoin($Item);
        $Player = GR::FindUserId(self::$Dt->user_id);
        if(!$Player){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => self::$Dt->L->_('NotFoundPlayer'),'show_alert' => true]);
        }

        if($Item == "Dozd"){
            $CheckLast = GR::checkLastByRole(self::$Dt->user_id,'role_dozd');
            if($CheckLast){
                $Keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('CloseBuy'),'callback_data' => 'BTNSP_NO' ]
                    ],
                );
                $Text = self::$Dt->L->_("FiledByRole",array("{0}" => 0));
                return Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $Text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $Keyboard,
                ]);
            }
        }
        $PlayerCoin = (float) (isset($Player['credit']) ? $Player['credit'] : 0);
        if($PlayerCoin < $Coin){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => self::$Dt->L->_('PleaseChargeAccount'),'show_alert' => true]);
        }
        $Code = time();

        if($Item !== "Emoji" && $Item !== "MajiKLaqab") {
            GR::UpdateCoin($PlayerCoin - $Coin, self::$Dt->user_id);
        }


        switch ($Item){
            case 'Xp500':
            case 'Xp1000':
            case 'Xp5000':
            case 'Xp10000':
                $REs = (int) str_replace('Xp','',$Item);
                $LastXp = (float) $Player['Site_Password'];
                $NewXp = ($LastXp+$REs);
                GR::UpdateXp($NewXp,self::$Dt->user_id);
                $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_Xp',array("{0}" => "<code>$Code</code>","{1}" => $LastXp ,"{2}" => $NewXp,"{3}" => jdate('Y-m-d H:i:s'),"{4}" => $REs ));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>self::$Dt->L->_('ShopItem_'.$Item),"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin - $Coin,'{6}' => self::$Dt->username)),
                    'parse_mode' => 'HTML'
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                break;
            case 'Dozd':
                GR::ByRole(self::$Dt->user_id,'role_dozd');
                $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_Dozd',array("{0}" => "<code>$Code</code>", "{3}" => jdate('Y-m-d H:i:s') ));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>self::$Dt->L->_('ShopItem_Dozd'),"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin - $Coin,'{6}' => self::$Dt->username)),
                    'parse_mode' => 'HTML'
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                break;
                case 'MajiKLaqab':
                $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_Laqab');
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => GR::GetLaqabList(),
                ]);

                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
            break;
            case 'MajiKGhost':
                $GetLastMajik  = ($NoP->exists('GhostPlayer:'.self::$Dt->user_id) ? (int) $NoP->get('GhostPlayer:'.self::$Dt->user_id) : 0);
                $GhostCount = $GetLastMajik + 1;
                $NoP->set('GhostPlayer:'.self::$Dt->user_id,$GhostCount);
                $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_MajiKGhost',array("{0}" => "<code>$Code</code>","{1}" => $GhostCount, "{3}" => jdate('Y-m-d H:i:s') ));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>self::$Dt->L->_('ShopItem_MajiKGhost'),"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin - $Coin,'{6}' => self::$Dt->username)),
                    'parse_mode' => 'HTML'
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                break;
            case 'MajiKhabar':
                $GetLastMajik  = ($NoP->exists('MajiKhabarPlayer:'.self::$Dt->user_id) ? (int) $NoP->get('MajiKhabarPlayer:'.self::$Dt->user_id) : 0);
                $GhostCount = $GetLastMajik + 1;
                $NoP->set('MajiKhabarPlayer:'.self::$Dt->user_id,$GhostCount);
                $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_MajiKhabar',array("{0}" => "<code>$Code</code>","{1}" => $GhostCount, "{3}" => jdate('Y-m-d H:i:s') ));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>self::$Dt->L->_('ShopItem_MajiKhabar'),"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin - $Coin,'{6}' => self::$Dt->username)),
                    'parse_mode' => 'HTML'
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                break;
            case 'MajiKHil':
                $GetLastMajik  = ($NoP->exists('MajiKHilPlayer:'.self::$Dt->user_id) ? (int) $NoP->get('MajiKHilPlayer:'.self::$Dt->user_id) : 0);
                $GhostCount = $GetLastMajik + 1;
                $NoP->set('MajiKHilPlayer:'.self::$Dt->user_id,$GhostCount);
                $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_MajiKHil',array("{0}" => "<code>$Code</code>","{1}" => $GhostCount, "{3}" => jdate('Y-m-d H:i:s') ));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>self::$Dt->L->_('ShopItem_MajiKHil'),"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin - $Coin,'{6}' => self::$Dt->username)),
                    'parse_mode' => 'HTML'
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                break;
            case 'MajikSear':
                $GetLastMajik  = ($NoP->exists('MajikSearPlayer:'.self::$Dt->user_id) ? (int) $NoP->get('MajikSearPlayer:'.self::$Dt->user_id) : 0);
                $GhostCount = $GetLastMajik + 1;
                $NoP->set('MajikSearPlayer:'.self::$Dt->user_id,$GhostCount);
                $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_MajikSear',array("{0}" => "<code>$Code</code>","{1}" => $GhostCount, "{3}" => jdate('Y-m-d H:i:s') ));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>self::$Dt->L->_('ShopItem_MajikSear'),"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin - $Coin,'{6}' => self::$Dt->username)),
                    'parse_mode' => 'HTML'
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                break;
            case 'Emoji':
                $Keyboard = new InlineKeyboard(
                    [
                        ['text' => self::$Dt->L->_('CloseBuy'),'callback_data' => 'BTNSP_NO' ]
                    ],
                );
                $Text = self::$Dt->L->_("ShopItemSelectEmoji",array("{0}" => 0));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $Text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $Keyboard,
                ]);
                $NoP = RC::NoPerfix();


                $NoP->set('PlayerEmojiBuy:'.self::$Dt->user_id,true);
                $NoP->set('PlayerEmojiBuyMessageID:'.self::$Dt->user_id,self::$Dt->message_id);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

                break;
        }



    }
    public static function has_emojis_old( $string ) {


        preg_match_all( '([*#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3|\\xC2[\\xA9\\xAE]|\\xE2..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?(?>\\xEF\\xB8\\x8F)?|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])(?>\\xEF\\xB8\\x8F)?|\\xF0\\x9F(?>[\\x80-\\x86].(?>\\xEF\\xB8\\x8F)?|\\x87.\\xF0\\x9F\\x87.|..(\\xF0\\x9F\\x8F[\\xBB-\\xBF])?|(((?<zwj>\\xE2\\x80\\x8D)\\xE2\\x9D\\xA4\\xEF\\xB8\\x8F\k<zwj>\\xF0\\x9F..(\k<zwj>\\xF0\\x9F\\x91.)?|(\\xE2\\x80\\x8D\\xF0\\x9F\\x91.){2,3}))?))', $string, $matches_emo );

        return (count( $matches_emo[0] ) === 1 ? $matches_emo[0] : false);
    }

    public static function EmojySend(){
        if(self::$Dt->typeChat !== "private"){
            return false;
        }
        $NoP = RC::NoPerfix();
        if(!$NoP->exists('PlayerEmojiBuy:'.self::$Dt->user_id)){
            return false;
        }
        $Coin = self::GetCoin('Emoji');
        $Player = GR::FindUserId(self::$Dt->user_id);
        if(!$Player){
            $NoP->del(['PlayerEmojiBuy:'.self::$Dt->user_id,'PlayerEmojiBuyMessageID:'.self::$Dt->user_id]);

            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => $NoP->get('PlayerEmojiBuyMessageID:'.self::$Dt->user_id),
                'text' => self::$Dt->L->_('NotFoundPlayer'),
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }
        $PlayerCoin = (float) (isset($Player['credit']) ? $Player['credit'] : 0);
        if($PlayerCoin < $Coin){
            $NoP->del(['PlayerEmojiBuy:'.self::$Dt->user_id,'PlayerEmojiBuyMessageID:'.self::$Dt->user_id]);

            return Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => $NoP->get('PlayerEmojiBuyMessageID:'.self::$Dt->user_id),
                'text' => self::$Dt->L->_('PleaseChargeAccount'),
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
        }



        $GetEmoji = self::has_emojis_old(self::$Dt->text);
        $Emoji = implode(',',$GetEmoji);
        GR::UpdateEmoji($Emoji,self::$Dt->user_id);
        $Code = time();
        $PlayerMessage = self::$Dt->L->_('ShopCheckOutMessagePlayer_Emoji',array("{0}" => "<code>$Code</code>","{4}" => $Emoji ,"{3}" => jdate('Y-m-d H:i:s')));
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => $NoP->get('PlayerEmojiBuyMessageID:'.self::$Dt->user_id),
            'text' => $PlayerMessage,
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>self::$Dt->L->_('ShopItem_Emoji'),"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin - $Coin,'{6}' => self::$Dt->username)),
            'parse_mode' => 'HTML'
        ]);
        GR::UpdateCoin($PlayerCoin - $Coin,self::$Dt->user_id);
        $NoP->del(['PlayerEmojiBuy:'.self::$Dt->user_id,'PlayerEmojiBuyMessageID:'.self::$Dt->user_id]);
        return true;
    }

    public static function CM_AddCoin(){
        if((int) self::$Dt->user_id !== ADMIN_ID){
            return false;
        }

        if(isset(self::$Dt->message->getEntities()[1])){
            if(self::$Dt->message->getEntities()[1]->getUser()) {
                $user_id = self::$Dt->message->getEntities()[1]->getUser()->getId();
            }
        }

        $Text = self::$Dt->text;
        $Explode = explode(' ',$Text);
        if(isset($Text) && count($Explode) > 1) {
            if(is_numeric($Explode[0]) and strlen($Explode[0]) > 7) {
                $user_id = (float) trim(self::$Dt->text);
            }elseif(preg_match("/^(?:[a-zA-Z0-9?. ]?)+@([a-zA-Z0-9]+)(.+)?$/",$Text,$matches)){
                $username = $matches[0];
            }
            // اگه با ای دی بود
            if(isset($user_id)){
                if($playerDetial = GR::FindUserId($user_id)){
                    $lastCoin = (isset($playerDetial['credit']) ? (int) $playerDetial['credit'] : 0);
                    $CheckF = substr($Explode[1],0,1);
                    $Fainals = str_replace(['-','+'],'',$Explode[1]);
                    $FainalCoin =  ($CheckF === '+' ? $lastCoin +$Fainals : $lastCoin - $Fainals);
                    $Name = GR::ConvertName($user_id,$playerDetial['fullname']);
                    GR::UpdateCoin($FainalCoin,$playerDetial['user_id']);
                    Request::sendMessage([
                        'chat_id' => ADMIN_ID,
                        'text' => self::$Dt->L->_('AdminMessageCredit',array("{0}" => $Fainals, "{1}" => ($CheckF === '-' ? self::$Dt->L->_('Min') : self::$Dt->L->_('Plus')),'{2}' => (float) $FainalCoin,"{3}" => $Name )),
                        'parse_mode' => 'HTML'
                    ]);

                    return  Request::sendMessage([
                        'chat_id' => $playerDetial['user_id'],
                        'text' => self::$Dt->L->_('PlayerMessageCreditS',array("{0}" => $Fainals, "{1}" => ($CheckF === '-' ? self::$Dt->L->_('Min') : self::$Dt->L->_('Plus')),'{2}' => (float) $FainalCoin )),
                        'parse_mode' => 'HTML'
                    ]);
                }
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotFoundUserById',array("{0}" => $user_id)),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML'
                ]);
            }
            if(isset($username)){
                $check = GR::CheckUserByUsername($username);
                if(!$check){
                    return  Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => self::$Dt->L->_('NotFindeSmiteUserName',array("{0}" => $username)),
                        'reply_to_message_id' => self::$Dt->message_id,
                        'parse_mode' => 'HTML'
                    ]);
                }

                GR::UserSmiteInGame($check['user_id']);
                return  Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('PlayerSmite', array("{0}" => GR::ConvertName($check['user_id'],$check['fullname_game']), "{1}" => GR::CountPlayer())),
                    'parse_mode' => 'HTML'
                ]);
            }

            if(!self::$Dt->ReplayTo) {
                return Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('PleaseInsetValueForSmite'),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        if(self::$Dt->ReplayTo) {
            $user_id = self::$Dt->ReplayTo;
        }
        if(GR::CheckPlayerJoined($user_id)) {
            $Player = GR::_GetPlayerName($user_id);
            GR::UserSmiteInGame($user_id);
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('PlayerSmite', array("{0}" => GR::ConvertName($user_id, $Player), "{1}" => GR::CountPlayer())),
                'parse_mode' => 'HTML'
            ]);
        }
        if(!isset($user_id)){
            $user_id = "نام کاربری را وارد نمایید مانند  /smite @new";
        }
        return  Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->L->_('NotFindeSmiteUserId',array("{0}" => $user_id)),
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML'
        ]);
    }

    public static function GetMajicKeybaord(){
        $Nop = RC::NoPerfix();
        $groupName = (RC::Get('group_name') ? RC::Get('group_name') : 'اسم نداره!');

        $GetLastSear = ($Nop->exists('MajikSearPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('MajikSearPlayer:'.self::$Dt->user_id) : 0);
        $GetLastkhabar = ($Nop->exists('MajiKhabarPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('MajiKhabarPlayer:'.self::$Dt->user_id) : 0);
        $GetLastGhost = ($Nop->exists('GhostPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('GhostPlayer:'.self::$Dt->user_id) : 0);
        $GetLastHiller = ($Nop->exists('MajiKHilPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('MajiKHilPlayer:'.self::$Dt->user_id) : 0);

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

        return $keyboard;
    }
    public static function UseMajik($type){
        $Nop = RC::NoPerfix();
        if(!self::$Dt->in_game){
            return true;
        }

        switch ($type){
            case 'MajiKhabar':
                $GetLastkhabar = ($Nop->exists('MajiKhabarPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('MajiKhabarPlayer:'.self::$Dt->user_id) : 0);
                if($GetLastkhabar == 0){
                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('NotBuy'),
                        'chat_id' => self::$Dt->user_id,
                    ]);
                }
                if($Nop->exists(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id)){
                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('LastUserMajic'),
                        'chat_id' => self::$Dt->user_id,
                        'reply_markup' => self::GetMajicKeybaord(),
                    ]);
                }

                $Nop->set('MajiKhabarPlayer:'.self::$Dt->user_id,$GetLastkhabar - 1);
                $Nop->set(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id,'MajiKhabarPlayer');
                Request::editMessageReplyMarkup([
                    'chat_id' =>  self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                return Request::sendMessage([
                    'text' => self::$Dt->L->_('SuccessActive_'.$type),
                    'chat_id' => self::$Dt->user_id,
                ]);


                break;
            case 'MajikSear':
                return false;
                $MajikSearPlayer = ($Nop->exists('MajikSearPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('MajikSearPlayer:'.self::$Dt->user_id) : 0);
                if($MajikSearPlayer == 0){
                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('NotBuy'),
                        'chat_id' => self::$Dt->user_id,
                    ]);
                }

                if($Nop->exists(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id)){
                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('LastUserMajic'),
                        'chat_id' => self::$Dt->user_id,
                    ]);
                }

                $Nop->set('MajikSearPlayer:'.self::$Dt->user_id,$MajikSearPlayer - 1);
                $Nop->set(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id,'MajikSearPlayer');
                Request::editMessageReplyMarkup([
                    'chat_id' =>  self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                return Request::sendMessage([
                    'text' => self::$Dt->L->_('SuccessActive_'.$type),
                    'chat_id' => self::$Dt->user_id,
                ]);

                break;
            case 'MajiKGhost':
                $GhostPlayer = ($Nop->exists('GhostPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('GhostPlayer:'.self::$Dt->user_id) : 0);
                if($GhostPlayer == 0){
                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('NotBuy'),
                        'chat_id' => self::$Dt->user_id,
                    ]);
                }

                if($Nop->exists(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id)){
                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('LastUserMajic'),
                        'chat_id' => self::$Dt->user_id,
                    ]);
                }

                $Nop->set('GhostPlayer:'.self::$Dt->user_id,$GhostPlayer - 1);
                $Nop->set(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id,'GhostPlayer');
                Request::editMessageReplyMarkup([
                    'chat_id' =>  self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                return Request::sendMessage([
                    'text' => self::$Dt->L->_('SuccessActive_'.$type),
                    'chat_id' => self::$Dt->user_id,
                ]);

                break;
            case 'MajiKHil':
                $MajiKHilPlayer = ($Nop->exists('MajiKHilPlayer:'.self::$Dt->user_id) ? (int) $Nop->get('MajiKHilPlayer:'.self::$Dt->user_id) : 0);
                if($MajiKHilPlayer == 0){

                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('NotBuy'),
                        'chat_id' => self::$Dt->user_id,
                        'reply_markup' => self::GetMajicKeybaord(),
                    ]);
                }
                if($Nop->exists(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id)){
                    return Request::sendMessage([
                        'text' => self::$Dt->L->_('LastUserMajic'),
                        'chat_id' => self::$Dt->user_id,
                        'reply_markup' => self::GetMajicKeybaord(),
                    ]);
                }

                $Nop->set('MajiKHilPlayer:'.self::$Dt->user_id,$MajiKHilPlayer - 1);
                $Nop->set(self::$Dt->group_id.':GamePl:UseMajik:'.self::$Dt->user_id,'MajiKHilPlayer');
                Request::editMessageReplyMarkup([
                    'chat_id' =>  self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                return Request::sendMessage([
                    'text' => self::$Dt->L->_('SuccessActive_'.$type),
                    'chat_id' => self::$Dt->user_id,
                    'reply_markup' => self::GetMajicKeybaord(),
                ]);

                break;
        }
    }

    public static function SendCoin(){

        $Nop = RC::NoPerfix();
        /*
        if(((int) $Nop->get('sendCoinTo:'.self::$Dt->user_id)) >= 5 && (int) self::$Dt->user_id !== 1469227822 ){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('SendNotEx'),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML'
            ]);
        }

        */
        if(!self::$Dt->ReplayTo) {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('PleaseReplayForSCoin'),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML'
            ]);
        }
        if(!self::$Dt->text){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('PleaseAddSCoin'),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML'
            ]);
        }


        $Expl = explode(' ',self::$Dt->text);

        $TextD = (isset($Expl[1]) ? $Expl[1] : 'چیزی وارد نشده!');

        $userId = self::$Dt->ReplayTo;

        if(isset($Expl[0])){
            if(!is_numeric($Expl[0]) || $Expl[0] < 0){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->L->_('NotValidate'),
                    'reply_to_message_id' => self::$Dt->message_id,
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        $Coin = floor($Expl[0]);


        if($Coin == 0){
            return false;
        }


        if($Coin < 4){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => 'حداقل مقدار انتقال  4 سکه میباشد!',
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML'
            ]);
        }
        $CheckValidate = GR::CheckValidateSendCoin($Coin);

        if(!$CheckValidate){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('NotValidateSendCoin'),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML'
            ]);
        }

        if(!is_array($CheckValidate)){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('NotValidateSendCoinCredit'),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML'
            ]);
        }

        $checkPlayer =  GR::GetPlayer($userId);
        if(!$checkPlayer){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->L->_('NotPlayerInG'),
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML'
            ]);
        }
        $FainalCoin = (int) $checkPlayer['credit'] + (int) $Coin;
        $FainalCoinSender = (int) $CheckValidate['credit'] - (int) $Coin;
        GR::UpdateCoin($FainalCoin,$userId);
        $GeterMessage = self::$Dt->L->_('MessageForPlayerGet',array("{0}" => self::$Dt->user_link ,"{1}" => $Coin,"{2}" =>  $FainalCoin ,"{3}" => $TextD));

        Request::sendMessage([
            'chat_id' => $userId,
            'text' => $GeterMessage,
            'parse_mode' => 'HTML'
        ]);

        $AdminMsg = self::$Dt->L->_('AdminMessageCoinAdd',array("{0}" =>  self::$Dt->user_link , "{1}" => $Coin,"{2}" =>self::$Dt->PlayerLink ));
        Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' => $AdminMsg,
            'parse_mode' => 'HTML'
        ]);



        GR::UpdateCoin($FainalCoinSender,self::$Dt->user_id);


        $Nop->set('sendCoinTo:'.self::$Dt->user_id,((int)$Nop->get('sendCoinTo:'.self::$Dt->user_id) + 1));


        $SenderMessage = self::$Dt->L->_('MessageForSender',array("{0}" =>$Coin ,"{1}" => self::$Dt->PlayerLink ));
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $SenderMessage,
            'parse_mode' => 'HTML'
        ]);
    }

    public static function CM_MyLeagueScore(){
        $check = GR::GetLeagueScore();
        if(!$check){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('NoScore'),
                'parse_mode' => 'HTML'
            ]);
        }
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('MyLeagueScore',array("{0}" => number_format($check['score'])  ,"{1}" => self::$Dt->MinLeague ,"{2}" =>  self::$Dt->LeagueName )),
            'parse_mode' => 'HTML'
        ]);
    }

    public static function CM_HelpShop(){
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('HelpShop'),
            'parse_mode' => 'HTML'
        ]);
    }

    public static function CM_GetLeague(){
        $NoP = RC::NoPerfix();
        if($NoP->exists('LeagueData')) {
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $NoP->get('LeagueData'),
                'parse_mode' => 'HTML'
            ]);
        }
    }

    public static function CM_MySetting(){
        $BuyPlayer =  GR::GetRoleBuy(self::$Dt->user_id);
        if(!$BuyPlayer){
            return  self::CM_Shop();
        }

        $re = [];
        foreach ($BuyPlayer as $row){
            $Active = (isset($row['active']) ?  ($row['active'] ? "✅" : "⛔️") : "⛔️");
            $re[] = [
                ['text' => "نقش :   ".self::$Dt->LG->_($row['role']."_n")."     ".$Active, 'callback_data' => "SGFDRol|" . $row['role']]
            ];
        }


        $inline_keyboard = new InlineKeyboard(...$re);

        return  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('MyRoleSetting'),
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_keyboard,
        ]);



    }

    public static function ChangeRoleSetting($Ex){
        $role = $Ex[1];
        GR::UpdateSettingRole($role);
        $BuyPlayer =  GR::GetRoleBuy(self::$Dt->user_id);
        if(!$BuyPlayer){
            return  self::CM_Shop();
        }

        $re = [];
        foreach ($BuyPlayer as $row){
            $Active = (isset($row['active']) ?  ($row['active'] ? "✅" : "⛔️") : "⛔️");
            $re[] = [
                ['text' => "نقش :   ".self::$Dt->LG->_($row['role']."_n")."     ".$Active, 'callback_data' => "SGFDRol|" . $row['role']]
            ];
        }


        $inline_keyboard = new InlineKeyboard(...$re);
        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('MyRoleSetting'),
            'message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
            'reply_markup' => $inline_keyboard,
        ]);


    }

    public static function CM_Sponsers(){
        $GetSponserList = GR::GetTopSponsers();
        return  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('SponserList',array("{0}" => $GetSponserList['list'] ,"{1}" => number_format($GetSponserList['total']) )),
            'parse_mode' => 'HTML',
        ]);
    }
    public static function GetTodayList($Ex){
        $Mode = $Ex['2'];
        $lang = $Ex['3'];
        GR::GetTopList($Mode,$lang);
    }
    public static function ClearMark($name){
        $name = str_replace(['[',']','(',')','*','”','˜','_','/',"!","#","+",'`','`','.',"-","=",'|',':','?','`',':','~','{','}',"'","~~~~"],'',$name);
        return $name;
    }
    public static function SendPrivateMessage(){

            if(!self::$Dt->in_game) {
            return false;
            }
              if(!self::$Dt->user_state){
                  Request::sendMessage([
                      'chat_id' => self::$Dt->user_id,
                      'text' => "<b>تا زمانی که زنده هستی پیامت میره واسه هم تیمیت!!</b>",
                      'parse_mode' => 'HTML',
                  ]);

                 return false;
              }
            $Team = self::$Dt->team;
            if($Team == "rosta"
                || $Team == "monafeq"
                || $Team == "dozd"
                || $Team == "dinamit"
                || $Team == "hamzad"
                || $Team == "Bomber"
                || $Team == "lucifer"
            )  {
                return false;
            }
            if(self::$Dt->user_role == "role_Bloodthirsty"){
                if (!RC::CheckExit('GamePl:Bloodthirsty') && !RC::CheckExit('GamePl:VampireFinded')) {
                    Request::sendMessage([
                        'chat_id' => self::$Dt->user_id,
                        'text' => "<b>شما تا زمانی که آزاد نشده اید امکان ارسال پیام  را ندارید!!</b>",
                        'parse_mode' => 'HTML',
                    ]);

                    return false;
                }
            }

            $GetTeam = GR::_GetByTeamOnline($Team);
            if(!$GetTeam){
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "<b>چون هم تیمی جز خودت ندارید پیامت رو نمیتونم بفرستم واسه کسی!</b>",
                    'parse_mode' => 'HTML',
                ]);
                return false;
            }
        $text = "<i><b>%s</b></i>:<code> %s </code>";
        $msg = vsprintf($text,[self::ClearMark(self::$Dt->fullname_game),self::ClearMark(self::$Dt->text)]);
           if(self::$Dt->text == ""){
               Request::sendMessage([
                   'chat_id' => self::$Dt->user_id,
                   'text' => "<b>تنها متن و اموجی مجاز به ارسال است!</b>",
                   'parse_mode' => 'HTML',
               ]);
               return false;
           }
            foreach($GetTeam as $row){
                if($row['user_role'] == "role_Bloodthirsty"){
                    if (!RC::CheckExit('GamePl:Bloodthirsty')  && !RC::CheckExit('GamePl:VampireFinded')) {
                        continue;
                    }
                }

                Request::sendMessage([
                    'chat_id' => $row['user_id'],
                    'text' => $msg,
                    'parse_mode' => 'HTML',
                ]);
            }
        Request::sendMessage([
            'chat_id' => -1001299797067,
            'text' =>   $msg.PHP_EOL.PHP_EOL."<code>".self::$Dt->user_id."</code>".'   <a href="tg://openmessage?user_id='.self::$Dt->user_id.'">💬 </a>'.PHP_EOL."👤 @".self::$Dt->username.PHP_EOL.self::$Dt->chat_id,
            'parse_mode' => 'HTML',
        ]);
        Request::deleteMessage([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
        ]);
          Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $msg,
            'parse_mode' => 'HTML',
           ]);


    }

    public static function SendPrivateMessageCoin(){


        if(!self::$Dt->in_game) {
            return false;
        }
        if(!self::$Dt->user_state){
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "<b>تا زمانی که زنده هستی پیامت میره واسه هم تیمیت!!</b>",
                'parse_mode' => 'HTML',
            ]);

            return false;
        }

        if(!self::$Dt->message->getReplyToMessage()){
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "<b>برای ارسال پیام خصوصی لطفا بر روی پیام مورد نظر ریپلای کنید!!</b>",
                'parse_mode' => 'HTML',
            ]);

            return false;
        }

        if(self::$Dt->user_role == "role_Bloodthirsty"){
            if (!RC::CheckExit('GamePl:Bloodthirsty')) {
                Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => "<b>شما تا زمانی که آزاد نشده اید امکان ارسال پیام  را ندارید!!</b>",
                    'parse_mode' => 'HTML',
                ]);

                return false;
            }
        }


        $checkPlayer =  GR::GetPlayer(self::$Dt->user_id);
        if(!$checkPlayer){
            return false;
        }
        if((int) $checkPlayer['credit'] < 3){
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "<b>سکه شما برای ارسال پیام خصوصی کافی نمیباشد هر پیام نیاز به 3 سکه دارد!!</b>",
                'parse_mode' => 'HTML',
            ]);

            return false;
        }
       $en =  self::$Dt->message->getReplyToMessage()->getEntities()[0];
       $off = $en->getOffset();
       $Len =  $en->getLength();
       $ex = explode(":",self::$Dt->message->getReplyToMessage()->getText());
       $GetName = mb_substr($ex[0],$off,$Len);
        if(self::$Dt->text == ""){
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "<b>تنها متن و اموجی مجاز به ارسال است!</b>",
                'parse_mode' => 'HTML',
            ]);
            return false;
        }
        $FindPlayer = GR::FindPlayerByName($GetName);
        if(!$FindPlayer){
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => '<b>این بازیکن در این بازی یافت نشد!</b>',
                'parse_mode' => 'HTML',
            ]);
        }
        $text = "<i><b>%s</b></i>: <code>%s</code>";
        $msg = vsprintf($text,[self::ClearMark(self::$Dt->fullname_game),self::ClearMark(self::$Dt->text)]);
        $msg = str_replace("پخ:","پیام خصوصی »",$msg);
        Request::sendMessage([
            'chat_id' => $FindPlayer['user_id'],
            'text' => $msg,
            'parse_mode' => 'HTML',
        ]);
        $FainalCoinSender = (int) $checkPlayer['credit'] - 3;
        GR::UpdateCoin($FainalCoinSender,self::$Dt->user_id);


        Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' =>  vsprintf("کسر  3 سکه از %s بابت ارسال 💬 پیام خصوصی",[self::$Dt->fullname_game]),
            'parse_mode' => 'HTML',
        ]);

        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  vsprintf("<b><i>✅ پیام بصورت خصوصی ارسال شد برای %s .</i></b>",[$FindPlayer['fullname_game']]),
            'parse_mode' => 'HTML',
        ]);

    }

    public static function CM_News(){

         return Request::sendMessage([
             'chat_id' => self::$Dt->user_id,
             'text' => self::$Dt->L->_('LAstNews'),
             'parse_mode' => 'HTML',
         ]);
    }
    public static function CM_Support(){

         return Request::sendMessage([
             'chat_id' => self::$Dt->user_id,
             'text' => self::$Dt->L->_('Support'),
             'parse_mode' => 'HTML',
         ]);
    }

    public static function CM_Accademy(){

         return Request::sendMessage([
             'chat_id' => self::$Dt->user_id,
             'text' => self::$Dt->L->_('Accademy'),
             'parse_mode' => 'HTML',
         ]);
    }

    public static function CM_OnlineGame(){

         return Request::sendMessage([
             'chat_id' => self::$Dt->user_id,
             'text' => self::$Dt->L->_('CM_OnlineGame'),
             'parse_mode' => 'HTML',
         ]);
    }

    public static function CM_RemoveLink(){

        if(self::$Dt->typeChat == "private") {
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' =>  self::$Dt->L->_('SendToGroup'),
                'parse_mode' => 'HTML',
            ]);
        }

        if(self::$Dt->admin == 0){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>" . self::$Dt->L->_('YouNotAdminGp') . "</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }

        if(!RC::CheckExit("group_link")){
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => "<strong>شما هنوز برای گروه خود لینکی ثبت نکرده اید !</strong>",
                'reply_to_message_id' => self::$Dt->message_id,
                'parse_mode' => 'HTML',
            ]);
        }
        RC::Del("group_link");
        return Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => "<strong>لینک گروه شما با موفقییت حذف شد !</strong>",
            'reply_to_message_id' => self::$Dt->message_id,
            'parse_mode' => 'HTML',
        ]);

    }

    public static function SetLaqab($laqab_id){

        $find = GR::FindLaqab($laqab_id);
        if(!$find){
            return Request::answerCallbackQuery([
                'text' => 'چنین لقبی موجود نیست!',
                'show_alert' => true,
                'callback_query_id' => self::$Dt->callback_id
            ]);
        }

        if($find['active']){
            return Request::answerCallbackQuery([
                'text' => 'این لقب توسط کاربر دیگیری انتخاب شده است!',
                'show_alert' => true,
                'callback_query_id' => self::$Dt->callback_id
            ]);
        }

        GR::SetLaqabStatus(true,$laqab_id);

        $Player = GR::FindUserId(self::$Dt->user_id);
        $Coin = 30;
        if(!$Player){
            return Request::answerCallbackQuery([
                'text' =>  self::$Dt->L->_('NotFoundPlayer'),
                'show_alert' => true,
                'callback_query_id' => self::$Dt->callback_id
            ]);
        }
        $PlayerCoin = (float) (isset($Player['credit']) ? $Player['credit'] : 0);
        if($PlayerCoin < $Coin){
            return Request::answerCallbackQuery([
                'text' =>  self::$Dt->L->_('PleaseChargeAccount'),
                'show_alert' => true,
                'callback_query_id' => self::$Dt->callback_id
            ]);
        }

        GR::UpdateCoin($PlayerCoin - $Coin, self::$Dt->user_id);
        if(isset($Player['set_laqab'])){
            GR::SetLaqabStatusByname(false,$Player['fullname']);
        }
        self::$Dt->collection->Players->updateOne(array("user_id"=>(float) self::$Dt->user_id), ['$set' => ['fullname' =>  $find['name'],'set_laqab' => true]]);


               $Code = time();
                $PlayerMessage = self::$Dt->L->_('LaqabByMessage',array("{0}" => "<code>$Code</code>","{1}" => $find['name'], "{3}" => jdate('Y-m-d H:i:s') ));
                Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $PlayerMessage,
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
                Request::sendMessage([
                    'chat_id' => ADMIN_ID,
                    'text' => self::$Dt->L->_('AdminMessageCheckOut',array("{0}" => self::$Dt->user_link, "{1}" => self::$Dt->user_id ,'{2}' =>"لقب ".$find['name'],"{3}" => "<code>$Code</code>","{4}" => jdate("Y-m-d H:i:s"),"{5}" =>  $PlayerCoin,'{6}' => self::$Dt->username)),
                    'parse_mode' => 'HTML'
                ]);
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }
    public static function CreateBet($type){
        switch($type){
            case 'hourse':

                $check = GR::FindGame();
                if(!$check) {
                    Request::sendMessage([
                        'chat_id' => -1001713075877,
                        'text' => '✅ یک بازی شرطبندی ایجاد شد

🕚 بقییه بازیکنان 30 ثانیه فرصت دارند تا شرط خود را ثبت کنند.

تاریخ و زمان استارت :  ' . jdate('Y-m-d H:i:s'),
                    ]);


                    GR::CreateHoursBet(0);
                }

                GR::getUserKeyBoardBet();
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

                break;
        }
    }

    public  static  function btsOnHou($in){
       $findLast = GR::GetPlayerBet();
       $coin = 0;
        $NoP = RC::NoPerfix();
        $BetCounter =  ($NoP->exists('UserBet:'.self::$Dt->user_id) ? (int) $NoP->get('UserBet:'.self::$Dt->user_id) : 10 );
       if(self::$Dt->Player['credit'] < $BetCounter){
           return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => 'میزان سکه شما کافی نیست! میتوانید با ارسال دستور /coin خریداری نمایید.','show_alert' => true]);
       }
       if($findLast){
           $coin = ((int) $findLast[$in] + $BetCounter);
           $total = ((int) $findLast['total']) + $BetCounter;
           self::ChangeTotalAndcoinBet($in,$coin,$total);
       }
        if(!$findLast){
            self::CreateUserBet($in,$BetCounter);
        }

         GR::getUserKeyBoardBet();
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }

    public static function CreateUserBet($hs_id,$counter){

        self::$Dt->collection->player_bets->insertOne([
            'user_id'=> self::$Dt->user_id,
            'hourse_1' => ($hs_id == 'hourse_1' ? $counter : 0),
            'hourse_2' => ($hs_id == 'hourse_2' ? $counter : 0),
            'hourse_3' => ($hs_id == 'hourse_3' ? $counter : 0),
            'hourse_4' => ($hs_id == 'hourse_4' ? $counter : 0),
            'hourse_5' => ($hs_id == 'hourse_5' ? $counter : 0),
            'hourse_6' => ($hs_id == 'hourse_6' ? $counter : 0),
            'hourse_7' => ($hs_id == 'hourse_7' ? $counter : 0),
            'hourse_8' => ($hs_id == 'hourse_8' ? $counter : 0),
            'total' => $counter,
            'status'=> 'wait'
        ]);

    }

    public static function btsConfirm(){
        $game=  GR::FindGame();
        if(!$game){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => 'متاسفانه بازی حالت عضو گیری موجود نیست! لطفا در کانال https://t.me/onyxwerewolfbet وضعیت بازی را چک نمایید! ','show_alert' => true]);
        }

        if($game['game_status'] !== 'join'){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => 'متاسفانه بازی حالت عضو گیری موجود نیست! لطفا در کانال https://t.me/onyxwerewolfbet وضعیت بازی را چک نمایید! ','show_alert' => true]);

        }

        $findPlayer = GR::GetPlayerBet();
        if(!$findPlayer){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => 'لطفا ابتدا شرط خود را ثبت نمایید!','show_alert' => true]);
        }

        if(self::$Dt->Player['credit'] < $findPlayer['total']){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => 'موجودی حساب شما کم میباشد! لطفا ابتدا سکه خریداری نمایید با ارسال دستور /coin','show_alert' => true]);
        }

        if($findPlayer['status'] !== 'wait'){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => 'وضعیت شرطبندی شما در حالت انتظار نمیباشد!','show_alert' => true]);
        }

        $endCoin =((int) self::$Dt->Player['credit'] -  $findPlayer['total']);

        GR::UpdateCoin(((int) self::$Dt->Player['credit'] -  $findPlayer['total']), self::$Dt->user_id);

        Request::sendMessage([
            'chat_id' => ADMIN_ID,
            'text' =>  vsprintf("کسر  %s سکه از %s بابت شرطبندی",[$findPlayer['total'],self::$Dt->fullname]),
            'parse_mode' => 'HTML',
        ]);
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => self::$Dt->L->_('BetMinPlayer',array("{0}" =>$findPlayer['total'],"{1}" => jdate("Y-m-d H:i:s") ))
        ]);

        $list = "";
        $list .= ($findPlayer['hourse_1'] > 0 ? "اسب شماره 1 ".number_format($findPlayer['hourse_1']) ."💵 ".PHP_EOL : "");
        $list .= ($findPlayer['hourse_2'] > 0 ? "اسب شماره 2 ".number_format($findPlayer['hourse_2']) ."💵 ".PHP_EOL : "");
        $list .= ($findPlayer['hourse_3'] > 0 ? "اسب شماره 3 ".number_format($findPlayer['hourse_3']) ."💵 ".PHP_EOL : "");
        $list .= ($findPlayer['hourse_4'] > 0 ? "اسب شماره 4 ".number_format($findPlayer['hourse_4']) ."💵 ".PHP_EOL : "");
        $list .= ($findPlayer['hourse_5'] > 0 ? "اسب شماره 5 ".number_format($findPlayer['hourse_5']) ."💵 ".PHP_EOL : "");
        $list .= ($findPlayer['hourse_6'] > 0 ? "اسب شماره 6 ".number_format($findPlayer['hourse_6']) ."💵 ".PHP_EOL : "");
        $list .= ($findPlayer['hourse_7'] > 0 ? "اسب شماره 7 ".number_format($findPlayer['hourse_7']) ."💵 ".PHP_EOL : "");
        $list .= ($findPlayer['hourse_8'] > 0 ? "اسب شماره 8 ".number_format($findPlayer['hourse_8']) ."💵 ".PHP_EOL : "");

        $MSgEdit = self::$Dt->L->_('MsgBetSet',array("{0}" =>$findPlayer['total'] ,"{1}" => jdate('Y-m-d H:i:s'),"{2}" => $list ));

        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => $MSgEdit
        ]);

        $channelMessage= self::$Dt->L->_('ChannelMessage',array("{0}" => self::$Dt->Player['fullname'],"{1}" => number_format($findPlayer['total'])));
        Request::sendMessage([
            'chat_id' => -1001713075877,
            'text' => $channelMessage,
        ]);
        GR::PlayerConfirm();
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }

    public static function ChangeTotalAndcoinBet($key,$coin,$total)
    {
        self::$Dt->collection->player_bets->updateOne(
            ['user_id' => self::$Dt->user_id],
            ['$set' => [$key => (int) $coin ,'total' => (int) $total ]]
        );
    }

    public static function CM_bet(){

        $inline_keyboard = new InlineKeyboard(
            [['text' => self::$Dt->L->_('bet_hourse'), 'callback_data' => "BetGame/hourse" ]],
            [['text' => self::$Dt->L->_('bet_bomb'), 'callback_data' => "BetGame/bomb" ]],

            [['text' => "بستن صفحه", 'callback_data' => "closeBanList"]]
        );


        return Request::sendMessage([
            'text' => self::$Dt->L->_('BetText',array("{0}" => self::$Dt->Player['credit'])),
            'chat_id' => self::$Dt->user_id,
            'reply_markup' => $inline_keyboard,
        ]);
    }


    public static  function btsReject(){
        GR::DelBet();
        GR::getUserKeyBoardBet();
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
    }
    public static  function generateRandom()
    {
        return rand(0, 100) / 10;
    }

    public static function CM_Game(){


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
            $speed = self::generateRandom();
            $strength = self::generateRandom();
            $HourseList[$key]['speed'] = $speed;
            $HourseList[$key]['status'] = 0;
            $HourseList[$key]['strength'] = $strength;
            $HourseList[$key]['endurance'] = self::generateRandom();
            $HourseList[$key]['bestspeed'] = ($HourseList[$key]['speed'] + self::BASE_SPEED);
            $HourseList[$key]['autonomy'] = $HourseList[$key]['endurance'] * self::ENDURANCE_FACTOR;
            $HourseList[$key]['slowdown'] = self::JOCKEY_SLOWDOWN - ($HourseList[$key]['strength'] * self::STRENGTH_FACTOR * self::JOCKEY_SLOWDOWN);
            $HourseList[$key]['timespent'] = 0;
            $HourseList[$key]['distancecovered'] = 0;

        }
        foreach($HourseList as $key => $row) {
            $List = "";
            $List .= "|" . $row['id'] . "|⇨";
            $List .= $row['text']."}".$row['speed']."|".$row['strength']."|".$row['endurance'];
            array_push($ReStart, $List);
        }
       $message =  Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
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
                    $horseRealSpeed = GR::getHorseRealSpeed($row, $currentDistance);
                    $calculatedDistance = $currentDistance + $horseRealSpeed * $progressSeconds;
                    $calculatedSeconds = $row['timespent'] + $progressSeconds;
                    if ($calculatedDistance > $horseAutonomy && $currentDistance < $horseAutonomy) {
                        $gapMeters = 0;
                        $gapSeconds = $gapMeters / $horseRealSpeed;
                        $horseRealSpeed = GR::getHorseRealSpeed($row, $calculatedDistance);
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
                'chat_id' => self::$Dt->chat_id,
                'message_id' => $message->getResult()->getMessageId(),
                'text' =>  implode(PHP_EOL,$res_in)
            ]);

        } while (!$win);

        $List = "";
        $ReNew = [];
        foreach($HourseList as $key => $row) {
            $List = "";
            $List .= "|" . $row['id'] . "|⇨";
            $List .= $row['text']."}".($firstId == $row['id'] ? 'برنده' : '').$row['timespent'];
            array_push($ReNew, $List);
        }
        Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' =>  implode(PHP_EOL,$ReNew),
            'parse_mode' => 'HTML',
        ]);


    }

    public static function ChangeBetCount(){
        $NoP = RC::NoPerfix();
        $BetCounter =  ($NoP->exists('UserBet:'.self::$Dt->user_id) ? (int) $NoP->get('UserBet:'.self::$Dt->user_id) : 10 );
        $add = 10;
        switch($BetCounter){
            case 10:
              $add = 50;
              break;
            case 50:
                $add = 250;
             break;
            case 250:
                $add = 1000;
            break;
            case 1000:
                $add = 10000;
                break;
            case 10000:
                $add = 10;
           break;
        }
        $NoP->set('UserBet:'.self::$Dt->user_id,$add);
        GR::getUserKeyBoardBet();
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }


    public static function CM_Account($edit = false){


        $TypeUser = self::$Dt->type_user;
        $expire = self::$Dt->expire;

        $NoP = RC::NoPerfix();
        $NoP->del('account_status:'.self::$Dt->user_id);
        $NoP->del('account_status_set_text:'.self::$Dt->user_id);
        if($TypeUser == 'user') {
            $Keybaord = new InlineKeyboard(
                [
                    ['text' => self::$Dt->LG->_('btnUpToSilver'), 'callback_data' => "upAcc/silver" ],

                ],
                [
                    ['text' => self::$Dt->LG->_('btnUpToVip'), 'callback_data' => "upAcc/vip" ]
                ]
            );
        }elseif($TypeUser == 'vip'){
            $Keybaord = new InlineKeyboard(
                [
                    ['text' => self::$Dt->LG->_('btnSetText'), 'callback_data' => "asdopt/settext" ],

                ],
                [
                    ['text' => self::$Dt->LG->_('btnSetGifOrPic'), 'callback_data' => "asdopt/setgif" ]
                ]
            );
        }elseif($TypeUser == 'silver'){
            $Keybaord = new InlineKeyboard(

                [
                    ['text' => self::$Dt->LG->_('btnUpToVip'), 'callback_data' => "upAcc/vip" ]
                ],
                [
                    ['text' => self::$Dt->LG->_('btnSetPic'), 'callback_data' => "upAcc/vip" ]
                ]
            );
        }


        if(!$edit) {
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => self::$Dt->L->_('Account_main',array("{0}" => self::$Dt->L->_('type_'.$TypeUser),"{1}" => $expire)),
                'parse_mode' => 'HTML',
                'reply_markup' => $Keybaord,
            ]);
        }

         Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('Account_main',array("{0}" => self::$Dt->L->_('type_'.$TypeUser),"{1}" => $expire)),
            'reply_markup' => $Keybaord,
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);


    }

    public static function upAcc($type){
        switch ($type){
            case 'silver':
                $Cr_min = 200;
            break;
            case 'vip':
                $Cr_min = 350;
            break;
            case 'back':
                return self::CM_Account(true);
            break;
        }

        $reply_markup =  new InlineKeyboard(
            [
                ['text' => self::$Dt->LG->_('CanceleBtn'), 'callback_data' => "upAcc/back" ],
                ['text' => self::$Dt->LG->_('YesBtnUp'), 'callback_data' => "ugrade/".$type ],
            ]
        );

        $re = Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('AccountToUpgrade', array("{0}" =>  self::$Dt->L->_('type_'.$type), "{1}" => $Cr_min,'{2}' => number_format(self::$Dt->Player['credit']) ,"{3}" => self::$Dt->L->_('AccountG_'.$type) )),
            'reply_markup' => $reply_markup,
            'parse_mode' => 'HTML'
        ]);

        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);


    }


    public static function ugrade($type){
        switch ($type){
            case 'silver':
                $Cr_min = 200;
                break;
            case 'vip':
                $Cr_min = 350;
                break;
            case 'back':
                return self::CM_Account(true);
                break;
        }

        if(self::$Dt->Player['credit'] < $Cr_min){
            return Request::answerCallbackQuery([
                'text' => 'موجودی حساب شما برای ارتقا حساب کافی نمیباشد!',
                'show_alert' => true,
                'callback_query_id' => self::$Dt->callback_id
            ]);
        }

        $Expire = strtotime("+1 month",time());
        $MinCrEnd = self::$Dt->Player['credit'] - $Cr_min;


        GR::MinCreditCredit($MinCrEnd);
        // Buyer Message
        GR::TransMesgsage([
            'coin' => $Cr_min
            ,'current_coin' =>  $MinCrEnd
            ,'last_coin' => self::$Dt->Player['credit']
            , 'des' => 'ارتقا حساب کاربری به '.self::$Dt->L->_('type_'.$type),
            'type' => 'min'
        ],false,self::$Dt->user_id);

        // Admin Message
        GR::TransMesgsage([
            'coin' => $Cr_min
            ,'current_coin' =>  $MinCrEnd
            ,'last_coin' => self::$Dt->Player['credit']
            , 'des' => 'ارتقا حساب کاربری به '.self::$Dt->L->_('type_'.$type),
            'type' => 'min'
        ],true,ADMIN_ID);


        GR::ChangeUserType($type,$Expire,self::$Dt->user_id);



        $reply_markup =  new InlineKeyboard(
            [
                ['text' => self::$Dt->LG->_('CanceleBtn'), 'callback_data' => "upAcc/back" ],
            ]
        );

        $re = Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('AccountComplateUpgrade', array("{0}" =>  self::$Dt->L->_('type_'.$type), "{1}" => jdate('Y-m-d H:i:s',$Expire),'{2}' => jdate('Y-m-d H:i:s')  )),
            'reply_markup' => $reply_markup,
            'parse_mode' => 'HTML'
        ]);


        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }

    public static function asdopt($type){
        $NoP = RC::NoPerfix();
        switch ($type){
            case 'settext':
                if(self::$Dt->type_user !== 'vip'){
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }
                $NoP->del('account_status_set_text:'.self::$Dt->user_id);

                $Text = self::$Dt->L->_('SetTextHelp');
                $reply_markup =  new InlineKeyboard(
                    [
                        ['text' => self::$Dt->LG->_('set_gif_kad'), 'callback_data' => "settext/kad" ],
                        ['text' => self::$Dt->LG->_('set_gif_khab'), 'callback_data' => "settext/khab" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('set_gif_ahan'), 'callback_data' => "settext/ahan" ],
                        ['text' => self::$Dt->LG->_('set_gif_dard'), 'callback_data' => "settext/dard" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('set_gif_hakem'), 'callback_data' => "settext/hakem" ],
                        ['text' => self::$Dt->LG->_('set_gif_start'), 'callback_data' => "settext/start" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('CanceleBtn'), 'callback_data' => "upAcc/back" ],
                    ]
                );

                $re = Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $Text,
                    'reply_markup' => $reply_markup,
                    'parse_mode' => 'HTML'
                ]);

            break;
            case 'setgif':
                if(self::$Dt->type_user !== 'vip'){
                    return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);
                }

                $NoP->del('account_status:'.self::$Dt->user_id);
                $Text = self::$Dt->L->_('TextHelpSetGif');
                $reply_markup =  new InlineKeyboard(
                    [
                        ['text' => self::$Dt->LG->_('set_gif_wolf'), 'callback_data' => "setGifi/wolf" ],
                        ['text' => self::$Dt->LG->_('set_gif_qatel'), 'callback_data' => "setGifi/qatel" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('set_gif_kad'), 'callback_data' => "setGifi/kad" ],
                        ['text' => self::$Dt->LG->_('set_gif_khab'), 'callback_data' => "setGifi/khab" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('set_gif_ahan'), 'callback_data' => "setGifi/ahan" ],
                        ['text' => self::$Dt->LG->_('set_gif_dard'), 'callback_data' => "setGifi/dard" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('set_gif_hakem'), 'callback_data' => "setGifi/hakem" ],
                        ['text' => self::$Dt->LG->_('set_gif_start'), 'callback_data' => "setGifi/start" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('CanceleBtn'), 'callback_data' => "upAcc/back" ],
                    ]
                );

                $re = Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => $Text,
                    'reply_markup' => $reply_markup,
                    'parse_mode' => 'HTML'
                ]);


            break;
        }

        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }



    public static function  settext($type){
        $Text =  self::$Dt->L->_('TextSetText',array("{0}" => self::$Dt->L->_('set_gif_'.$type)));

        $reply_markup =  new InlineKeyboard(
            [

                ['text' => self::$Dt->LG->_('CanceleBtn'), 'callback_data' => "asdopt/settext" ],
            ]
        );

        $NoP = RC::NoPerfix();
        $NoP->set('account_status_set_text:'.self::$Dt->user_id,'settext_'.$type);

        $re = Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => $Text,
            'reply_markup' => $reply_markup,
            'parse_mode' => 'HTML'
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);



    }

    public static function SetTextD($type){
        $GetLest = self::$Dt->text;

        if(strlen($GetLest) > 100){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => 'متن حداکثر میتواند 100 کاراکتر باشد !',
                'parse_mode' => 'HTML'
            ]);
        }
        $TypeEx = explode('_',$type);




        GR::ChangeUserOption($type,$GetLest,self::$Dt->user_id);
        $NoP = RC::NoPerfix();
        $NoP->del('account_status_set_text:'.self::$Dt->user_id);

        $reply_markup =  new InlineKeyboard(
            [
                ['text' => self::$Dt->LG->_('DeleteTextBtn'), 'callback_data' => "delTextPr/".$TypeEx[1]."/".self::$Dt->user_id ],
            ]
        );


        Request::sendMessage([
           'chat_id' => self::$Dt->user_id,
           'text' => self::$Dt->L->_('ChangeTextSuccess',array("{0}" => self::$Dt->L->_('set_gif_'.$TypeEx[1]),'{1}' => $GetLest)),
           'parse_mode' => 'HTML',
            'reply_markup' => $reply_markup,
       ]);

          Request::sendMessage([
              'chat_id' => self::$Dt->user_id,
              'text' => self::$Dt->L->_('ChangeTextSuccess',array("{0}" => self::$Dt->L->_('set_gif_'.$TypeEx[1]),'{1}' => $GetLest)).PHP_EOL.self::$Dt->L->_('UserDetials',array("{0}" => self::$Dt->user_id , "{1}" => self::$Dt->fullname,"{2}" => self::$Dt->username)),
              'parse_mode' => 'HTML',
              'reply_markup' => $reply_markup,
          ]);



    }
    public static function  setGifi($type){
        $Text =  self::$Dt->L->_('TextSetGif',array("{0}" => self::$Dt->L->_('set_gif_'.$type)));

        $reply_markup =  new InlineKeyboard(
            [
                (self::$Dt->{'setgif_'.$type} ?  ['text' => self::$Dt->L->_('current_gif'), 'callback_data' => "getMyGif/".$type ] : ['text' => self::$Dt->L->_('NotGifSet'), 'callback_data' => "NotAllowed" ]),

                ['text' => self::$Dt->LG->_('CanceleBtn'), 'callback_data' => "asdopt/setgif" ],
            ]
        );

        $NoP = RC::NoPerfix();
        $NoP->set('account_status:'.self::$Dt->user_id,'setgif_'.$type);


        $re = Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => $Text,
            'reply_markup' => $reply_markup,
            'parse_mode' => 'HTML'
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }

    public static function getMyGif($type){
        $GifType = self::$Dt->L->_('set_gif_'.$type);
        $Msg = self::$Dt->L->_('getMyGifText',array("{0}" => $GifType));
        GR::SendMs(self::$Dt->user_id,$Msg,self::$Dt->{'setgif_'.$type});
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }

    public static function  SendDoc($type){
        $Message = Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => 'درحال دریافت فایل...',
            'parse_mode' => 'HTML'
        ]);
        $NoP = RC::NoPerfix();

        $message = self::$Dt->message;
        $message_type = $message->getType();

        $doc = $message->{'get' . ucfirst($message_type)}();
        // For photos, get the best quality!
        ($message_type === 'photo') && $doc = end($doc);
        $file_id = $doc->getFileId();

        $file    = Request::getFile(['file_id' => $file_id]);
        if ($file->isOk() && $re = Request::downloadFile($file->getResult())) {
            $FileUrl = "http://bot.onyxwerewolf.ir/Download/".$file->getResult()->getFilePath();
            GR::ChangeUserOption($type,$FileUrl,self::$Dt->user_id);
            $TypeEx = explode('_',$type);
            $reply_markup =  new InlineKeyboard(
                [
                    ['text' => self::$Dt->LG->_('DeleteGifBtn'), 'callback_data' => "delGif/".$TypeEx[1]."/".self::$Dt->user_id ],
                ]
            );

            // Player Message
            Request::sendVideo([
                'chat_id' => self::$Dt->user_id,
                'video' => $FileUrl,
                'caption' => self::$Dt->L->_('SetGifResult',array("{0}" => self::$Dt->L->_('set_gif_'.$TypeEx[1]))),
                'parse_mode' => 'HTML',
                'reply_markup' => $reply_markup,
                ]);

            // Admin Message
            Request::sendVideo([
                'chat_id' => ADMIN_ID,
                'video' => $FileUrl,
                'caption' => self::$Dt->L->_('SetGifResult',array("{0}" => self::$Dt->L->_('set_gif_'.$TypeEx[1]))).PHP_EOL.self::$Dt->L->_('UserDetials',array("{0}" => self::$Dt->user_id , "{1}" => self::$Dt->fullname,"{2}" => self::$Dt->username)),
                'parse_mode' => 'HTML',
                'reply_markup' => $reply_markup,
            ]);
            if($Message->isOk()){
                Request::deleteMessage(['chat_id' => self::$Dt->user_id,'message_id' => $Message->getResult()->getMessageId()]);
            }

            $NoP->del('account_status:'.self::$Dt->user_id);

          } else {
            Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => var_export($re,true),
                'parse_mode' => 'HTML'
            ]);
        }




    }


    public static function delTextPr($type, $user_id){
        $TypeText = self::$Dt->L->_('set_gif_'.$type);
        $user_id = (float) $user_id;

        Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => self::$Dt->L->_('TextTextDelete',array("{0}" => $TypeText)),
            'reply_markup' => new InlineKeyboard([]),
        ]);
        GR::ChangeUserOption('settext_'.$type,'remove',$user_id);

        if(self::$Dt->user_id == ADMIN_ID && $user_id !== ADMIN_ID) {
            $CheckUser = GR::CheckUserById($user_id);
            if($CheckUser) {
                Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => self::$Dt->L->_('TextTextDeleteBotAdmin',array(
                        "{0}" => $CheckUser['fullname'],
                        '{1}' => $TypeText,
                    )),
                    'parse_mode' => 'HTML'
                ]);
            }
        }


        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);

    }
    public static function  DelGif($type, $user_id){
        $TypeText = self::$Dt->L->_('set_gif_'.$type);
        $user_id = (float) $user_id;

        Request::editMessageCaption([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'caption' => self::$Dt->L->_('TextGifDelete',array("{0}" => $TypeText)),
            'parse_mode' => 'HTML',
            'reply_markup' => new InlineKeyboard([]),
        ]);
        GR::ChangeUserOption('setgif_'.$type,'remove',$user_id);


        if(self::$Dt->user_id == ADMIN_ID && $user_id !== ADMIN_ID) {
            $CheckUser = GR::CheckUserById($user_id);
            if($CheckUser) {
                Request::sendMessage([
                    'chat_id' => $user_id,
                    'text' => self::$Dt->L->_('TextGifDeleteBotAdmin',array(
                        "{0}" => $CheckUser['fullname'],
                        '{1}' => $TypeText,
                    )),
                    'parse_mode' => 'HTML'
                ]);
            }
        }

        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);


    }




    public static function  CM_KillList($type = 'day',$send = true){

        $Nop = RC::Noperfix();



        if($type == 'day') {
            $date = date('Y-m-d');
            $end = date('Y-m-d',strtotime( '+1 '.$type));
        }else{
            $date = date('Y-m-d',strtotime( ' -1 '.$type));
            $end = date('Y-m-d');
        }


        if(!$Nop->exists('GetDataKills2:'.$end.":".$date)) {
            $Lists = GR::getKillTopList($date, $end);
            $MSg = GR::ConvertListData($Lists);
            $EndText = self::$Dt->L->_('GetListKills', array("{0}" => self::$Dt->L->_($type), '{1}' => jdate('Y-m-d',strtotime($date)), '{2}' => jdate('Y-m-d',strtotime($end)), '{3}' => $MSg));
            $Nop->set('GetDataKills2:'.$end.":".$date,$EndText);
            if($type == 'day') {
                $Nop->expire('GetDataKills2:'.$end.":".$date,3600);
            }
        }else{
            $EndText = $Nop->get('GetDataKills2:'.$end.":".$date);

        }


        $reply_markup =  new InlineKeyboard(
            [
                ['text' => self::$Dt->LG->_('TodayKillList')." ".($type == 'day' ? '✅' : ''), 'callback_data' => "getKilllist/day" ],
                ['text' => self::$Dt->LG->_('WeekKillList')." ".($type == 'week' ? '✅' : ''), 'callback_data' => "getKilllist/week"],
                ['text' => self::$Dt->LG->_('MonthList')." ".($type == 'month' ? '✅' : ''), 'callback_data' => "getKilllist/month" ],

            ]
        );

        if($send) {
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $EndText,
                'parse_mode' => 'HTML',
                'reply_markup' => $reply_markup,
            ]);
        }

        $re = Request::editMessageText([
            'chat_id' => self::$Dt->user_id,
            'message_id' => self::$Dt->message_id,
            'text' => $EndText,
            'reply_markup' => $reply_markup,
            'parse_mode' => 'HTML'
        ]);
        return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);


    }


    public static function CM_MyHero($send = true){
        $checkHero = GR::CheckUserHero();


        // Not Hero

        if(!$checkHero){
            $reply_markup =  new InlineKeyboard(
                [
                    ['text' => self::$Dt->LG->_('createHeroBtn'), 'callback_data' => "BfdHero/build" ],
                    ['text' => self::$Dt->LG->_('CancelHero'), 'callback_data' => "BfdHero/cancel"],

                ]
            );


            if($send){
                return Request::sendMessage([
                    'chat_id' => self::$Dt->user_id,
                    'text' => self::$Dt->L->_('MyHeroMessage'),
                    'parse_mode' => 'HTML',
                    'reply_markup' => $reply_markup,
                ]);
            }

            Request::editMessageText([
                'chat_id' => self::$Dt->user_id,
                'message_id' => self::$Dt->message_id,
                'text' => self::$Dt->L->_('MyHeroMessage'),
                'reply_markup' => $reply_markup,
                'parse_mode' => 'HTML'
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);


        }


    }


    public static function CreateHero($type){
        $Nop = RC::Noperfix();

        $CheckHero = GR::CheckUserCreateHero();

        switch ($type){
            case 'back_0':
                self::CM_MyHero(false);
            break;
            case 'notAction':
                return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id, 'text' => 'این دکمه کار خاصی انجام نمیده!', 'show_alert' => true]);
             break;
            case 'des_hero_all':
            case 'des_hero_bashkoch':
            case 'des_hero_zar':
            case 'des_hero_efrit':
            case 'des_hero_qoqnos':
            case 'des_hero_isonade':

                if($type == "des_hero_all") {
                    $Img = "https://bot.onyxwerewolf.ir/Upload/all.jpg";
                }
                $Text = self::$Dt->L->_($type);

            Request::sendPhoto([
                'chat_id' => self::$Dt->user_id,
                 'photo' =>  Request::encodeFile($Img),
                'caption' => $Text,
                'parse_mode' => 'HTML',
            ]);
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id]);


            break;
            case 'build':
                if(!$CheckHero) {
                    GR::CreateHero();
                }

                $reply_markup =  new InlineKeyboard(
                    [
                        ['text' => self::$Dt->LG->_('hero_all'), 'callback_data' => "BfdHero/des_hero_all" ],
                        ['text' => '50 سکه', 'callback_data' => "BfdHero/all" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('hero_bashkoch'), 'callback_data' => "BfdHero/des_hero_bashkoch" ],
                        ['text' => '150 سکه', 'callback_data' => "BfdHero/bashkoch" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('hero_zar'), 'callback_data' => "BfdHero/des_hero_zar" ],
                        ['text' => '300 سکه', 'callback_data' => "BfdHero/zar" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('hero_efrit'), 'callback_data' => "BfdHero/des_hero_efrit" ],
                        ['text' => '500 سکه', 'callback_data' => "BfdHero/efrit" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('hero_qoqnos'), 'callback_data' => "BfdHero/des_hero_qoqnos" ],
                        ['text' => '900 سکه', 'callback_data' => "BfdHero/qoqnos" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('hero_isonade'), 'callback_data' => "BfdHero/des_hero_isonade" ],
                        ['text' => '1500 سکه', 'callback_data' => "BfdHero/isonade" ],
                    ],
                    [
                        ['text' => self::$Dt->LG->_('CanceleBtn'), 'callback_data' => "BfdHero/back_0" ],
                    ]
                );

                return Request::editMessageText([
                    'chat_id' => self::$Dt->user_id,
                    'message_id' => self::$Dt->message_id,
                    'text' => self::$Dt->L->_('hero_step_1'),
                    'reply_markup' => $reply_markup,
                    'parse_mode' => 'HTML'
                ]);

            break;
        }

    }

    public static function CM_AddRoleToGroup(){



        if((int) self::$Dt->user_id !== (int) ADMIN_ID){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => '<b>❌ دسترسے براے شما تعریف نشده است❗️</b>',
                'parse_mode' => 'HTML',
            ]);
        }


        $HelpText = 'Help Use Command:
 <code>/addrole [group_id]|[role_name]</code>';

        $text = self::$Dt->text;
        if(!$text){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $HelpText,
                'parse_mode' => 'HTML',
            ]);
        }

        $explode = explode('|',$text);

        if(count($explode) !== 2) {
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $HelpText,
                'parse_mode' => 'HTML',
            ]);
        }
        $chat_id = $explode[0];

        $findGroup =  GR::findGroup((float) $chat_id);
        if(!$findGroup){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => '❌ گروه ('.$chat_id.") در لیست گروه‌هاے ربات شما یافت نشد!",
                'parse_mode' => 'HTML',
            ]);
        }
        $allowdRole = [
            'role_BlackKnight',
            'role_BrideTheDead',
            //'role_hipo',
            'role_dian',
            'role_Chiang',
            'role_kentvampire',
            'role_betaWolf',
            'role_iceWolf',
            'role_Lilis',
            'role_Magento',
            'role_franc',
            'role_Mummy',
            'role_Joker',
            'role_Harly',
            'role_Archer',
            'role_davina',
            'role_Phoenix',
            'role_babr',
            'role_qhost',
            //'role_javidShah',
            'role_Princess',
            'role_Mouse',
            'role_Watermelon',
            'role_Bomber',
            'role_dinamit',
           // 'role_hellboy',


        ];
        $role_id = $explode[1];
        $TextHelpRole = "<b>نقش‌هایے ڪہ میتوانید بـہ گروه اضافـہ ڪنید ⤹ <\b>".PHP_EOL;
        foreach ($allowdRole as $val){
            $TextHelpRole .= "<code>{$val}</code> : <strong>".self::$Dt->LG->_($val."_n")."</strong>".PHP_EOL;
        }
        if(!in_array($role_id,$allowdRole)){

            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $TextHelpRole,
                'parse_mode' => 'HTML',
            ]);
        }

        $findLastAdded = GR::findLastAddRole($chat_id,$role_id);
        if($findLastAdded){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "نقش ".self::$Dt->LG->_($role_id."_n")." از قبل بـہ گروه ".$findGroup['group_name'].":".$findGroup['chat_id']." اضافـہ شده است.",
                'parse_mode' => 'HTML',
            ]);
        }


        GR::addRoleToGroup($findGroup,$role_id);

        $addedText = "🎭 نقش ".self::$Dt->LG->_($role_id."_n") ." با موفقیت اضافـہ شد.".PHP_EOL;
        $addedText .= "📝 نام گروه ⫸ ".$findGroup['group_name'].PHP_EOL;
        $addedText .= "🆔 شناسـہ گروه ⫸ ".$findGroup['chat_id'];
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $addedText,
            'parse_mode' => 'HTML',
        ]);
    }

    public static function CM_RemoveRoleGroup(){


        if((int) self::$Dt->user_id !== (int) ADMIN_ID){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => '<b>❌ دسترسے براے شما تعریف نشده است❗️</b>',
                'parse_mode' => 'HTML',
            ]);
        }


        $HelpText = 'Help Use Command:
 <code>/removerole [group_id]|[role_name]</code>';

        $text = self::$Dt->text;
        if(!$text){
            return  Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $HelpText,
                'parse_mode' => 'HTML',
            ]);
        }

        $explode = explode('|',$text);

        if(count($explode) !== 2) {
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $HelpText,
                'parse_mode' => 'HTML',
            ]);
        }
        $chat_id = $explode[0];
        $findGroup =  GR::findGroup($chat_id);
        if(!$findGroup){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => '❌ گروه ('.$chat_id.") در لیست گروه‌هاے ربات شما یافت نشد !",
                'parse_mode' => 'HTML',
            ]);
        }
        $allowdRole = [
            'role_BlackKnight',
            'role_BrideTheDead',
            //'role_hipo',
            'role_dian',
            'role_Chiang',
            'role_kentvampire',
            'role_betaWolf',
            'role_iceWolf',
            'role_Lilis',
            'role_Magento',
            'role_franc',
            'role_Mummy',
            'role_Joker',
            'role_Harly',
            'role_Archer',
            'role_davina',
            'role_Phoenix',
            'role_babr',
            'role_qhost',
            //'role_javidShah',
            'role_Princess',
            'role_Mouse',
            'role_Watermelon',
            'role_Bomber',
            'role_dinamit',
            // 'role_hellboy',
        ];
        $role_id = $explode[1];
        $TextHelpRole = "<b>نقش‌هایے ڪہ میتوانید پاڪ ڪنید از گروه ⤹ <\b>".PHP_EOL;
        foreach ($allowdRole as $val){
            $TextHelpRole .= "<code>{$val}</code> : <strong>".self::$Dt->LG->_($val."_n")."</strong>".PHP_EOL;
        }
        if(!in_array($role_id,$allowdRole)){

            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => $TextHelpRole,
                'parse_mode' => 'HTML',
            ]);
        }



        $findLastAdded = GR::findLastAddRole($chat_id,$role_id);
        if(!$findLastAdded){
            return Request::sendMessage([
                'chat_id' => self::$Dt->user_id,
                'text' => "نقش ".self::$Dt->LG->_($role_id."_n")." تا ڪنون بـہ گروه ".$findGroup['group_name'].":".$findGroup['chat_id']." اضافـہ نشده است.",
                'parse_mode' => 'HTML',
            ]);
        }

        GR::RemoveGroupRole($chat_id,$role_id);

        $addedText = "🎭 نقش ".self::$Dt->LG->_($role_id."_n") ." با موفقیت حذف شد.".PHP_EOL;
        $addedText .= "📝 نام گروه ⫸ ".$findGroup['group_name'].PHP_EOL;
        $addedText .= "🆔 شناسـہ گروه ⫸ ".$findGroup['chat_id'];
        return Request::sendMessage([
            'chat_id' => self::$Dt->user_id,
            'text' => $addedText,
            'parse_mode' => 'HTML',
        ]);


    }


    public static function OfAndOnRoleGroup($role){
        $checkBuy = GR::findLastAddRole(self::$Dt->chat_id,$role);
        if(!$checkBuy){
            return Request::answerCallbackQuery(['callback_query_id' => self::$Dt->callback_id,'text' => 'شما قبلا این نقش را برای گروه خود خریداری ننموده اید!','show_alert' => true]);
        }

        $set_status = ($checkBuy['status'] == true ? false : true);
        GR::SetStatusVipGrup($role,$set_status);
        return self::GetConfigKeyboard('viprole');
    }


}
