<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;



class HL
{
    /**
     * Cron object
     *
     * @var \phpcron\CronBot\cron
     */
    private static $Dt;

    public static function initialize(cron $H)
    {

        if (!($H instanceof cron)) {
            throw new Exception\CronException('Invalid Hook Pointer!');
        }

        self::$Dt = $H;

    }
    public static function _getGameState(){
        return R::Get('game_state') ?? "end";
    }
    public static function _getGameTimer(){
        return R::Get('timer') ?? 0;
    }

    public static function R($max){
        return random_int(0,$max);
    }
    public static function _getJoinKeyboard(){
        $join =  new InlineKeyboard(
            [
                ['text' => self::$Dt->LG->_('joinToGame'), 'url' => self::$Dt->JoinLink]
            ]

        );

        return $join;
    }

    public static function _getCountPlayers(){

        $result = self::$Dt->collection->games_players->countDocuments(['group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id]);
        return $result;
    }
    public static function _getCountONPlayers(){

        $result = self::$Dt->collection->games_players->countDocuments(['group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'user_state' => 1]);
        return $result;
    }

    public static function FindUserId($id){
        $result = self::$Dt->collection->Players->findOne(['user_id' => (float) $id]);
        if ($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }

    public static function GroupClosedThGame($type =false){
        if($type == 'join'){
            $GameMode = R::Get('GamePl:gameModePlayer');
            if($GameMode == 'coin') {
                $players = self::_getPlayers();
                foreach ($players as $row) {
                    $Player = self::FindUserId($row['user_id']);
                    if ($row['user_state'] == 1) {
                        self::UpdateCoins(((int)$Player['credit'] + 10), $row['user_id']);
                        Request::sendMessage([
                            'chat_id' => $row['user_id'],
                            'text' => self::$Dt->L->_('BackSendCoinEndGame'),
                            'disable_web_page_preview' => 'true',
                            'parse_mode' => 'HTML'
                        ]);
                    }
                }
            }
        }

        R::GetSet(true,'GamePl:GameIsEnd');
        R::Del('game_state');
        self::$Dt->collection->games_players->deleteMany(['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id]);
        self::$Dt->collection->games->deleteOne(['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id]);
        self::$Dt->collection->join_user->deleteOne(['chat_id' => self::$Dt->chat_id]);
    }


    public static function _getOnPlayers(){
        $result = self::$Dt->collection->games_players->find(
            ['group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'user_state' => 1,'user_status' => 'on']);

        if($result) {
            return iterator_to_array($result);
        }

        return false;
    }

    public static function _getPlayers(){
        $result = self::$Dt->collection->games_players->find(
            ['group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id],
            ['sort' => ['user_state'=> 1,'dead_time' => 1],
            ]);
        return iterator_to_array($result);
    }


    public static function VisitHome($visit,$Visitor){

    }
    public static function _getName($fullname,$id){
        return self::ConvertName($id,$fullname);
    }

    public static function _getPlayerList(){
        $d = self::_getPlayers();
        $re = [];

        foreach ($d as $row){
            if($row['user_role'] == "role_qhost" && !R::CheckExit('GamePl:FindGhost')){
                continue;
            }
            $name  = ($row['user_state'] == 0 || $row['user_state'] == 2 ) ? "*".self::ClearName($row['fullname_game'])."*: " : self::ConvertName($row['user_id'],$row['fullname_game'],true)." :";
            $UserRole = ($row['user_state'] == 0 || $row['user_state'] == 2 and R::Get('expose_role_after_dead') == "onr") ?  "*".self::$Dt->LG->_($row['user_role']."_n")."*" ."-" : '';
            $state = ($row['user_state'] == 0 ? self::$Dt->LG->_('is_dead') : ($row['user_state'] == 2 ?  self::$Dt->LG->_('is_smited') :  self::$Dt->LG->_('is_on')));
            $love  = ($row['user_state'] !== 1 ? R::CheckExit('GamePl:love:'.$row['user_id']) ? "- ❤️" : "" : "");


            $re[] = $name . $UserRole . $state . $love;
        }
        $list = implode(PHP_EOL,$re);

        $AllPlayer = self::_getCountPlayers();
        $PlayerOn = self::_getCountPlayer();

        $PlayerList = self::$Dt->LG->_('playerlistOn',array("{0}" => "{$PlayerOn}/{$AllPlayer}", "{1}" => $list));
        return $PlayerList;
    }


    public static function ClearName($name){
        $name = str_replace(['[',']','(',')','*','”','˜','_','/',"!","#","+",'`','`','.',"-","=",'|',':','?','`',':','~','{','}',"'","~~~~"],'',$name);
        return $name;
    }
    public static function ConvertName($user_id,$name,$markDown = false){

        if(is_array($user_id) || is_array($name)){
            return "[Error](tg://user?id=630127836)";
        }
        if($markDown){
            $name = str_replace(['[',']','(',')','*','”','˜','_','/',"!","#","+",'`','`','.',"-","=",'|',':','?','`',':','~','{','}',"'","~~~~"],'',$name);
            return "[$name](tg://user?id={$user_id})";
        }
        return '<a href="tg://user?id='.$user_id.'">'.$name.'</a>';

    }


    public static function SendPlayerList(){
        $list = self::_getPlayerList(true);
        $re = Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $list,
            'parse_mode'=> 'Markdown'
        ]);
        if($re->isOk()) {
            R::GetSet($re->getResult()->getMessageId(), 'Player_ListMessage_ID');
        }
    }

    public static function GetGameStatusLang(){
        $status = R::Get('game_state');
        switch ($status){
            case 'night':
                if(R::CheckExit('GamePl:KhabgozarOk')){
                    return self::$Dt->LG->_('SandmanNight');
                }
                return self::$Dt->LG->_('MassgeFortypeSummery_night', array("{0}" => "<strong>".R::Get('night_timer')."</strong>"));
            case 'day':
                if(R::CheckExit('GamePl:DavinaOk')){
                    return self::$Dt->LG->_('MessageDayWhenDavina');
                }
                $mSG = (R::CheckExit('GamePl:Kill') == false && R::CheckExit('GamePl:KhabgozarOk') == false ? self::$Dt->LG->_('NoAttakInDay') : false);
                if($mSG){self::SaveMessage($mSG);}
                $Day_no = R::Get('GamePl:Day_no') ??  1;
                return self::$Dt->LG->_('MassgeFortypeSummery_day', array("{0}" =>  "<strong>".R::Get('day_timer')."</strong>")).PHP_EOL.self::$Dt->LG->_('Day_nos',  array("{0}" => "<strong>{$Day_no}</strong>"));

            case 'vote':
                if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
                    return false;
                }

                if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                    return self::$Dt->LG->_('RulerMessageVoteNow',array("{0}" =>  SE::_s('RulerSecendVote')));
                }

                return (R::Get('secret_vote') == "offr" ? self::$Dt->LG->_('MassgeFortypeSummery_vote',array("{0}" =>  "<strong>".R::Get('vote_timer')."</strong>")) :  self::$Dt->LG->_('MassgeFortypeSummery_Secretvote', array("{0}" => "<strong>".R::Get('secret_timer')."</strong>")));
        }
        return "Empty";
    }

    public static function ChangeStartGameTime(){
        R::GetSet(time(),'GamePl:StartedTime');
    }
    public static function ChangeGameStatus(string $to){

        R::GetSet($to,'game_state');
        R::GetSet(true,'GamePl:SetTimer');

        switch ($to){
            case 'night':
                $timer = (int) (R::Get('night_timer') ?? 90);

                if(R::CheckExit('GamePl:KhabgozarOk')){
                    $timer = 0;
                }
                break;
            case 'day':
                $timer =  (R::Get('day_timer') ?: 90);
                if(R::CheckExit('GamePl:DavinaOk')){
                    $timer = 30;
                    self::LockPlayer();
                }
                break;
            case 'vote':
                $timer = (int) (R::Get('secret_vote') == "offr" ?  R::Get('vote_timer') ?? 90 :  R::Get('secret_timer') ?? 90);

                if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                    $timer = SE::_s('RulerSecendVote');
                }

                if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
                    $timer = 0;
                }
                break;
        }

        $Times = (time() + $timer);
        R::GetSet( $Times,'timer');

        self::$Dt->collection->games->updateOne(
            ['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id ],
            ['$set' => ['game_status' => $to, 'timer']]
        );
        R::Del('GamePl:SetTimer');
        return false;

    }
    public static function SaveMessage($msg){
        R::rpush($msg,'GamePl:group_message');
    }
    public static function LockPlayer(){
        $PlayerList = self::_getOnPlayers();
        foreach ($PlayerList as $row){
            Request::restrictChatMember([
                'chat_id' => self::$Dt->chat_id,
                'user_id' => $row['user_id'],
                'permissions' => ['can_send_messages' => false,'can_send_media_messages' => false,'can_send_polls' => false,'can_send_other_messages' => false,'can_add_web_page_previews' => false,'can_change_info'=>false,'can_invite_users' => false ,'can_pin_messages' => false],
                'until_date' => strtotime( '+30 second' )
              ]);
        }
    }
    public static function GetLenMessage(array $Messages){
        $len = 0;
        foreach ($Messages as $message) {
            $len = $len + strlen($message);
        }

        return $len;

    }

    public static function GetSliceMessage($Messages , $AllowLen = 300){
        // تغییر ترتیب از اخر به اول

        $implo = []; // آرایه داده های ترتیب داده شده
        $SingleSend = []; // آرایه داده های تکی
        $implo_len = 0; // تعداد کاراکتر های ارایه ترتیبی
        foreach ( $Messages as $val) {
            if($implo_len <= $AllowLen) {
                $implo[] = $val;
                $implo_len = $implo_len + strlen($val);
            }else{
                $SingleSend[] = $val;
            }

        }

        return ['single'=> $SingleSend ,'Implode'=> $implo];
    }
    public static function SendGroupMessage($sendList = false,$Allowlen = 300){
        $Messages = R::LRange(0,-1,'GamePl:group_message');
        $reversed = array_reverse($Messages);
        if($reversed){
            $Get = self::GetSliceMessage($reversed,$Allowlen);

            $SingleSend = $Get['single'];
            $implo = $Get['Implode'];

            if(count($SingleSend) > 0){
                $reversedSingleSend = array_reverse($SingleSend);
                foreach ($reversedSingleSend as $val){
                    Request::sendMessage([
                        'chat_id' => self::$Dt->chat_id,
                        'text' => $val,
                        'parse_mode' =>'HTML'
                    ]);
                }
            }



            if(count($implo) > 0){
                $reversedimplo = array_reverse($implo);
                $re = implode(PHP_EOL.PHP_EOL,$reversedimplo);
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $re,
                    'parse_mode' => 'HTML'
                ]);
            }

            R::Del('GamePl:group_message');
        }

        if($sendList == true && R::CheckExit('GamePl:Kill') == true) {
            $list = self::_getPlayerList(true);
            $re = Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => $list,
                'parse_mode' => 'Markdown'
            ]);
            if ($re->isOk()) {
                R::GetSet($re->getResult()->getMessageId(), 'Player_ListMessage_ID');
            }
            (R::CheckExit('GamePl:Kill') == true && R::CheckExit('GamePl:HunterKill') == false && R::CheckExit('GamePl:StopBlack') == false && R::CheckExit('GamePl:WolfCubeDead') == false && R::CheckExit('GamePl:RoyceDead') == false ? R::Del('GamePl:Kill') : "");
        }

        //sleep(1);
    }

    public static function checkUserINPrisoner($Detial){
        if(!R::CheckExit('GamePl:PrincessPrisoner:'.$Detial['user_id'])) return false;
        return  true;
    }

    public static function SendPrincessMessage($user,$Princess){
        $U_name = self::ConvertName($user['user_id'],$user['fullname_game']);

        switch ($user['user_role']){
            case 'role_Vampire':
                $VampireMessage = self::$Dt->LG->_("PrincessPrisonerVampireTeam",array("{0}" => $U_name));
                self::SendForVampireTeam($VampireMessage,$user['user_id'],'prince_zd');
                break;
            case 'role_ferqe':
            case 'role_Royce':
                $CultMessage = self::$Dt->LG->_("PrincessPrisonerCultTeam",array("{0}" => $U_name));
                self::SendForCultTeam($CultMessage,$user['user_id'],'prince_zd');
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $WolfMessage = self::$Dt->LG->_("PrincessPrisonerWolfTeam",array("{0}" => $U_name));
                self::SendForWolfTeam($WolfMessage,$user['user_id'],'prince_zd');
                break;
            case 'role_Qatel':
                $Hilda = self::_getPlayerByRole('role_hilda');
                if($Hilda) {
                    $HildaMessage = self::$Dt->LG->_("PrincessPrisonerHilda", array("{0}" => $U_name));
                    self::SendMessage($HildaMessage,$Hilda['user_id'],'prince_zd');
                }
                $Archer = self::_getPlayerByRole('role_Archer');
                if($Archer) {
                    $ArcherMessage =  self::$Dt->LG->_("PrincessPrisonerKillerArcher", array("{0}" => $U_name));
                    self::SendMessage($ArcherMessage,$Archer['user_id'],'prince_zd');
                }
                break;
        }

        $PlayerMessage = self::$Dt->LG->_('PrincessPrisoner');
        self::SendMessage($PlayerMessage,$user['user_id'],'prince_zd');
        R::GetSet(true,'GamePl:PrincessPrisoner:'.$user['user_id']);
        $PrincessMessage = self::$Dt->LG->_('PrincessPrisonerSuccess',array("{0}" => $U_name));
        self::SendMessage($PrincessMessage,$Princess['user_id']);
        return true;
    }

    

    public static function PlayerByTeam($Player = false){
        if($Player == false){
            $Player = self::_getOnPlayers();
        }

        $WolfTeam = [];
        $FerqeTeam = [];
        $Fermason = [];
        $vampire = [];
        $Qatel = [];
        $Bomber = [];
        $Rosta = [];
        $Magento = [];
        $Black = [];
        foreach ($Player as $row){
            switch ($row['user_role']){
                case 'role_forestQueen':
                    if(R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false){
                        continue 2;
                    }
                    $WolfTeam[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':
                    $WolfTeam[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                break;
                case 'role_BlackKnight':
                 case 'role_BrideTheDead':
                  case 'role_dian':
                  $Black[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                break;
                case 'role_kentvampire':
                    if(!R::CheckExit('GamePl:KentVampireConvert')){
                        continue 2;
                    }
                    $vampire[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                break;
                case 'role_Royce':
                case 'role_ferqe':
                case 'role_Mummy':
                    $FerqeTeam[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_feramason':
                    $Fermason[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_Magento':
                    $Magento[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_Bloodthirsty':
                    if(R::CheckExit('GamePl:Bloodthirsty')){
                        $vampire[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    }
                    break;
                case 'role_Chiang':
                    if(R::CheckExit('GamePl:DeadBloodthirsty')){
                        $vampire[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    }
                 break;
                case 'role_Vampire':
                    $vampire[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_Bomber':
                    $Bomber[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_Qatel':
                case 'role_Archer':
                case 'role_davina':
                    $Qatel[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
                case 'role_rosta':
                    $Rosta[] = ['user_id' => $row['user_id'], 'Link' => self::_getName($row['fullname_game'], $row['user_id']), 'role' => $row['user_role']];
                    break;
            }
        }

        return ['wolf'=> $WolfTeam,'black' => $Black,'magento' => $Magento , 'ferqe'=> $FerqeTeam,'Fermason'=> $Fermason,'vampire' => $vampire,'Qatel'=>$Qatel,'Bomber' => $Bomber,'Rosta' => $Rosta];

    }

    public static function GetPlayerNonKeyboard($d,$callBack,$in_list = false){
        $NightNo = R::Get('game_state');

        $player = self::_getOnPlayers();
        $re = [];
        $game_state = R::Get('game_state');
        $playerCount = count($player);
        foreach($player as $key => $row){
            $UserRole = $row['user_role'];
            if($UserRole == "role_BrideTheDead"){
                continue;
            }
            if(!$in_list){
                if($game_state == "night") {
                    if (R::CheckExit('GamePl:GhostPlayer_Night:' . $row['user_id'])) {
                        $GetNight = R::Get('GamePl:GhostPlayer_Night:' . $row['user_id']);
                        if ($GetNight == R::Get('GamePl:Night_no')) {
                            continue;
                        }
                    }
                }elseif($game_state == "day"){
                    if (R::CheckExit('GamePl:GhostPlayer_Day:' . $row['user_id'])) {
                        $GetNight = R::Get('GamePl:GhostPlayer_Day:' . $row['user_id']);
                        if ($GetNight == R::Get('GamePl:Day_no')) {
                            continue;
                        }
                    }
                }
            }
            if(!$in_list) {
                if ($UserRole == "role_qhost" && !R::CheckExit('GamePl:FindGhost') && $NightNo == "night") {

                    $GetKey = (isset($player[random_int(0, ($playerCount - 1))]) ? random_int(0, ($playerCount - 1)) : 0);
                    $re[] = [
                        ['text' => $player[$GetKey]['fullname'], 'callback_data' => "{$callBack}/" . self::$Dt->chat_id . "/{$row['user_id']}"]
                    ];
                    continue;
                } elseif ($UserRole == "role_qhost" && !R::CheckExit('GamePl:FindGhost')) {
                    continue;
                }

            }
            if($in_list == false) {
                if (!in_array($row['user_id'], $d)) {
                    $re[] = [
                        ['text' => $row['fullname'], 'callback_data' => "{$callBack}/" . self::$Dt->chat_id . "/{$row['user_id']}"]
                    ];
                }
            }else{
                if (in_array($row['user_id'], $d)) {
                    $re[] = [
                        ['text' => $row['fullname'], 'callback_data' => "{$callBack}/" . self::$Dt->chat_id . "/{$row['user_id']}"]
                    ];
                }
            }
        }

        switch ($callBack){
            case 'VoteSelect':
            case 'NightSelect_Hamzad':
            case 'NightSelect_Vahshi':
            case 'NightSelect_Cupe':
                break;
            case 'NightSelect_Firefighter':
                if(R::Get('GamePl:Day_no') > 1 && R::CheckExit('GamePl:FirefighterList')) {
                    $re[] = [
                        ['text' => self::$Dt->LG->_('ButtenFireFighter'), 'callback_data' => "RoleFireFighterFight" . "/" . self::$Dt->chat_id]
                    ];
                }
                break;
            default:
                $re[] = [
                    ['text' => "skip", 'callback_data' => "skip" . "/" . self::$Dt->chat_id]
                ];
                break;
        }


        return $re;
    }


    public static function ChangeLuciferTeam($to,$user_id){
        R::GetSet($to,"GamePl:user:{$user_id}:team");

       self::$Dt->collection->games_players->updateOne(
            ['user_id' => (float) $user_id,'game_id'=> self::$Dt->game_id,'group_id'=> self::$Dt->chat_id],
            ['$set' => ['team' => $to ,'change_time' => time() ]]
        );
    }


    public static function CheckBlack(){

    }
    public static function CheckKalantar(){
        $KillFor = R::Get('GamePl:KillFor');
        switch ($KillFor){
            case 'vote':
                R::GetSet(true,'GamePl:HunterKillVote');
            case 'kill':
            case 'shot':
                if(R::CheckExit('GamePl:kalantar_userid')){
                    $selected = R::Get('GamePl:Selected:'.R::Get('GamePl:kalantar_userid'));
                    $KalantarName = R::Get('GamePl:kalantar_fullname');
                    $Detial = self::_getPlayer($selected);
                    $U_name = self::ConvertName($Detial['user_id'],$Detial['fullname_game']);
                    $MessageKey = ($KillFor == "kill" || $KillFor == "shot"  ? 'HunterKilledFinalShot' : 'HunterKilledFinalLynched');

                    $GroupMessage = self::$Dt->LG->_($MessageKey,array("{0}" => $KalantarName, "{1}" => $U_name, "{2}" => self::$Dt->LG->_('user_role', array("{0}"=> self::$Dt->LG->_($Detial['user_role']."_n")))));
                    self::SaveMessage($GroupMessage);

                    // در نقش کلانتر بعد از اعدام شدن یک گرگ یا یک قاتل را بزنید
                    if($Detial['user_role'] == "role_WolfTolle" || $Detial['user_role'] == "role_WolfGorgine" || $Detial['user_role'] == "role_Wolfx"  || $Detial['user_role'] == "role_WolfAlpha" || $Detial['user_role'] == "role_WhiteWolf" || $Detial['user_role'] == "role_Qatel"){
                        self::SavePlayerAchivment(R::Get('GamePl:kalantar_userid'),'Hey_Man_Nice_Shot');
                    }

                    //» کلانتر باشیو تیر قبل مرگت رو برنی به ریش سفید و روستایی ساده بمیری
                    if($Detial['user_role'] == "role_rishSefid"){
                        self::ConvertPlayer(R::Get('GamePl:kalantar_userid'),'role_rosta');
                        self::SavePlayerAchivment(R::Get('GamePl:kalantar_userid'),'Demoted_by_the_Death');
                    }

                    self::UserDead($Detial,'shot_kalantar');
                    self::SaveGameActivity($Detial,'shot',['user_id'=> (int) R::Get('GamePl:kalantar_userid') ,'fullname' => 's' ]);
                    R::Del('GamePl:HunterKill');
                    R::DelKey('GamePl:kalantar_*');
                    return true;
                }
                if(R::CheckExit('GamePl:kalantar_Skip')){
                    $KalantarName = R::Get('GamePl:kalantar_fullname');
                    $MessageKey = ($KillFor == "kill" || $KillFor == "shot" ? 'HunterSkipChoiceShot' : 'HunterSkipChoiceLynched');
                    $GroupMessage = self::$Dt->LG->_($MessageKey,array("{0}"=> $KalantarName));
                    self::SaveMessage($GroupMessage);
                    R::Del('GamePl:HunterKill');
                    R::DelKey('GamePl:kalantar_*');
                    return true;
                }
                $Kalantar = self::_getPlayerByRole('role_kalantar',true);
                if($Kalantar) {
                    $KalantarName = self::ConvertName($Kalantar['user_id'], $Kalantar['fullname_game']);
                    $MessageKey = ($KillFor == "kill" || $KillFor == "shot" ? 'HunterNoChoiceShot' : 'HunterNoChoiceLynched');
                    $GroupMessage = self::$Dt->LG->_($MessageKey, array("{0}" => $KalantarName));
                    self::SaveMessage($GroupMessage);

                }

            R::Del('GamePl:HunterKill');
            R::DelKey('GamePl:kalantar_*');
                return true;
                break;
        }
    }

    public static function CheckDontSelectRole(){
        //اگر نقشای الهه ، همزاد،وحشی  انتخابی نکردم اتوماتیک واس انتخاب میکنیم واسشون
        $Key = R::LRange(0,-1,'GamePl:MessageNightSend');
        if($Key) {
            foreach ($Key as $key) {
                $Explod = explode('_',$key);
                $user_id = $Explod['1'];
                $Message_id = $Explod['0'];
                $Detial = self::_getPlayer($user_id);
                switch ($Detial['user_role']) {
                    case 'role_Hamzad':
                        $RandomUser = self::GetUserRandom([$user_id]);
                        R::GetSet($RandomUser['user_id'], 'GamePl:Hamzad');
                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('select_not', array("{0}" => $RandomUser['fullname_game'])),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    case 'role_lucifer':
                        self::ChangeLuciferTeam("rosta", $user_id);
                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('select_not',array("{0}" => self::$Dt->LG->_('RostaTeam'))),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    case 'role_Vahshi':
                        $RandomUser = self::GetUserRandom([$user_id]);
                        $Name = self::ConvertName($RandomUser['user_id'], $RandomUser['fullname_game']);
                        R::GetSet($RandomUser['user_id'], 'GamePl:Olgo');
                        R::GetSet($Name, 'GamePl:OlgoName');
                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('select_not', array("{0}" => $RandomUser['fullname_game'])),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    case 'role_elahe':
                        $RandomUser = self::GetUserRandom([]);
                        $Name = self::ConvertName($RandomUser['user_id'], $RandomUser['fullname_game']);

                        $RandomUser1 = self::GetUserRandom([$RandomUser['user_id']]);
                        $Name1 = self::ConvertName($RandomUser1['user_id'], $RandomUser1['fullname_game']);

                        R::GetSet($RandomUser1['user_id'], 'GamePl:love:' . $RandomUser['user_id']);
                        R::GetSet($Name1, 'GamePl:name:love:' . $RandomUser['user_id']);


                        R::GetSet($RandomUser['user_id'], 'GamePl:love:' . $RandomUser1['user_id']);
                        R::GetSet($Name, 'GamePl:name:love:' . $RandomUser1['user_id']);

                        Request::editMessageText([
                            'chat_id' => $user_id,
                            'text' => self::$Dt->LG->_('endTime'),
                            'message_id' => $Message_id,
                            'parse_mode' => 'HTML',
                            'reply_markup' => new InlineKeyboard([]),
                        ]);
                        R::LRem($key,1,'GamePl:MessageNightSend');
                        break;
                    default:
                        continue 2;
                        break;
                }
            }
        }
    }
    public static function CheckTimer(){

        $timer = self::_getGameTimer();
        $LeftTime = $timer - time();
        $Vote = false;
        if($LeftTime <= 0){
            $game_state = R::Get('game_state');
            if(R::CheckExit('GamePl:StopBlack')){
                self::CheckBlack();
            }
            if(R::CheckExit('GamePl:HunterKill')){
                self::CheckKalantar();
            }

            if(R::CheckExit('GamePl:SendWolfCubeDead')){
                R::Del('GamePl:WolfCubeDead');
                R::Del('GamePl:SendWolfCubeDead');

            }

            switch ($game_state){
                case 'night':

                    if(R::CheckExit('GamePl:CheckNight')){
                        return false;
                    }

                    NG::CheckNight();
                    if(R::CheckExit('GamePl:HunterKill') || R::CheckExit('GamePl:StopBlack')  || R::CheckExit('GamePl:SendWolfCubeDead') || R::CheckExit('GamePl:RoyceSelectd2')){
                        return false;
                    }
                    if(R::Get('GamePl:Day_no') == 1){
                        // همزاد،الهه و .. اگر انتخبا نکردن
                        self::CheckDontSelectRole();
                    }
                    self::ChangeGameStatus('day');
                    R::Del('GamePl:SendVote');
                    if(R::CheckExit('GamePl:role_Solh:GroupInSolh')){
                        R::Del('GamePl:role_Solh:GroupInSolh');
                    }

                    if(R::CheckExit('GamePl:role_Ruler:RulerOk')){
                        R::Del('GamePl:role_Ruler:RulerOk');
                    }
                    R::Del('GamePl:SendNightAll');
                    R::Del('GamePl:CheckNight');
                    R::Del('playerDeadName');
                    break;
                case 'day':

                    DY::CheckDay();
                    if(R::CheckExit('GamePl:HunterKill')){
                        return false;
                    }
                    self::ChangeGameStatus('vote');


                    // اگه خوابگذار خواب زده بود بازش میکنیم
                    if(R::CheckExit('GamePl:KhabgozarOk')){
                        if(R::Get('GamePl:KhabgozarOk') <= R::Get('GamePl:Night_no')) {
                            R::Del('GamePl:KhabgozarOk');
                        }
                    }
                    // اگه خوابگذار خواب زده بود بازش میکنیم
                    if(R::CheckExit('GamePl:DavinaOk')){
                        if(R::Get('GamePl:DavinaOk') <= R::Get('GamePl:Day_no')) {
                            R::Del('GamePl:DavinaOk');
                        }
                    }

                    R::Del('GamePl:CheckDay');
                    R::Del('GamePl:SendNight');

                    $Day_no = (R::Get('GamePl:Day_no') ? (int) R::Get('GamePl:Day_no') :  1);
                    R::GetSet( ($Day_no + 1) ,'GamePl:Day_no');

                    if(R::CheckExit('GamePl:DianSelectedPlayerDayNo')){
                        $EdDay = (int) R::Get("GamePl:DianSelectedPlayerDayNo");
                        $Dayno = $Day_no + 1;
                        if($Dayno == $EdDay){
                            $PlayrID = (float) R::Get("GamePl:DianSelectedPlayer");
                            $PlDetial = HL::_getPlayerById($PlayrID);
                            if($PlDetial) {
                                if($PlDetial['user_state'] == 1) {
                                    $U_name = self::ConvertName($PlDetial['user_id'],$PlDetial['fullname_game']);
                                    $GroupMessage = self::$Dt->LG->_('DianAfterFourDay');
                                    self::SaveMessage($GroupMessage,array("{0}" => $U_name));
                                    self::GamedEnd('black');
                                }else{
                                    R::Del("GamePl:DianSelectedPlayerDayNo");
                                    R::Del("GamePl:DianSelectedPlayer");
                                }
                            }else{
                                R::Del("GamePl:DianSelectedPlayerDayNo");
                                R::Del("GamePl:DianSelectedPlayer");
                            }

                        }
                    }

                    break;
                case 'vote':

                    if(R::CheckExit('GamePl:Update_vote') == false){
                        VT::CheckVoteMessage();
                    }

                    VT::CheckVoteMessage();
                    VT::CheckVote();
                    if(R::CheckExit('GamePl:StopBlack')){
                        return false;
                    }
                    if(R::CheckExit('GamePl:HunterKill')){
                        return false;
                    }
                    $Vote = true;
                    if(R::CheckExit('GamePl:trouble')){
                        VT::TroubleVote();
                        R::GetSet(true,'GamePl:trouble:ok');
                        return false;
                    }



                    // خب اگه گرگا مست خورده باشن بهتره بازش کنیم الیته برای شب بعد
                    if(R::CheckExit('GamePl:MastEat')){
                        if(R::Get('GamePl:MastEat') <= R::Get('GamePl:Night_no')){
                            R::Del('GamePl:MastEat');
                        }
                    }


                    // خب اگه آهنگ آهن زده بود اینجا بازش میکنیم برای فرداشب
                    if(R::CheckExit('GamePl:AhangarOk')){
                        if(R::Get('GamePl:AhangarOk') <= R::Get('GamePl:Night_no')){
                            R::Del('GamePl:AhangarOk');
                        }
                    }

                    if(R::CheckExit('GamePl:HunterKillVote')){
                        R::Del('GamePl:HunterKillVote');
                    }

                    self::BittanCheck();
                    self::ChangeGameStatus('night');
                    R::Del('GamePl:SendDayRole'); // باز کردن نقش روز


                    R::GetSet(R::Get('GamePl:Night_no') + 1,'GamePl:Night_no');


                    R::DelKey('GamePl:Selected:Vote:*'); // پاک کردن انتخاب ها

                    R::DelKey('GamePl:HoneyUser:*');
                    R::Del('GamePl:CheckVote');
                    R::Del('GamePl:CheckVoteSend');
                    break;
            }

            self::EditMarkupKeyboard($Vote);
            if(R::Get('game_state') == "night") {
                if(!R::CheckExit('GamePl:role_Solh:GroupInSolh')) {
                    self::DeleteDontVote(); // حذف افرادی که 2 بار رای ندادن
                }
            }

            R::DelKey('GamePl:Selected:*'); // پاک کردن انتخاب ها

            if(self::CheckEndGame()){
                return false;
            }

            $msg = self::GetGameStatusLang();
            self::SaveMessage($msg);
            self::SendGroupMessage(true);
            //sleep(1);
        }
    }

    public static function UnlockForTeam($setTime = false){
        $P_Team = self::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        if($Wolf){
            $wolfUserId = ($Wolf ? array_column($Wolf,'user_id') : false);
            if(count($P_Team['wolf']) > 1) {
                R::DelKey('GamePl:Selected:Wolf:*');
            }
            foreach ($wolfUserId as $User_id){
                R::LRem($User_id,1,'GamePl:SendNight');
                R::Del('GamePl:Selected:'.$User_id.":user");
            }
            return true;
        }

        if($setTime){
            R::GetSet((time() - 5),'timer');
            return true;
        }
        return false;
    }

    public static function BittanCheck(){

        if(R::CheckExit('GamePl:EnchanterBittanPlayer')){
            $User_id = R::Get('GamePl:EnchanterBittanPlayer');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:EnchanterBittanPlayer');
                } else {
                    $UserMessage = self::$Dt->LG->_('BittenTurned');
                    self::SendMessage($UserMessage, $User_id);
                    self::ConvertPlayer($User_id, 'role_WolfGorgine');
                    self::CheckPlayerEnchanter($User_id);
                    R::Del('GamePl:EnchanterBittanPlayer');
                }
            }
        }

        if(R::CheckExit('GamePl:BittanPlayer')){
            $User_id = R::Get('GamePl:BittanPlayer');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:BittanPlayer');
                } else {
                    $UserMessage = self::$Dt->LG->_('BittenTurned');
                    self::SendMessage($UserMessage, $User_id);
                    self::ConvertPlayer($User_id, 'role_WolfGorgine');
                    R::Del('GamePl:BittanPlayer');
                }
            }
        }

        if(R::CheckExit('GamePl:VampireBitten')){
            $User_id = R::Get('GamePl:VampireBitten');
            $Detial = self::_getPlayer($User_id);
            if($Detial) {
                if ($Detial['user_state'] !== 1) {
                    R::Del('GamePl:VampireBitten');
                } else {
                    $UserMessage = self::$Dt->LG->_('BittenTurnedVampire');
                    self::SendMessage($UserMessage, $User_id);
                    self::ConvertPlayer($User_id, 'role_Vampire');
                    R::Del('GamePl:VampireBitten');
                }
            }
        }
    }



    public static function DeleteDontVote(){
        $Key = R::keys('GamePl:DontVote:*');
        foreach ($Key as $key) {
            $Ex = explode(':', $key);
            $user_id = $Ex['3'];
            $keys = "{$Ex['1']}:{$Ex['2']}:{$Ex['3']}";
            $counter = R::Get($keys);
            if($counter >= 2){
                if($user_id) {
                    $data = self::_getPlayer($user_id);
                    if($data) {
                        if($data['user_state'] !== 1){
                            R::Del($keys);
                            continue;
                        }

                    R::GetSet((R::CheckExit('GamePl:FkPlayer') ? (int) R::Get('GamePl:FkPlayer') : 0) + 1, 'GamePl:FkPlayer');
                    R::GetSet((R::CheckExit('AfkedPlayer:' . $user_id) ? (R::Get('AfkedPlayer:' . $user_id) + 1) : 1), 'AfkedPlayer:' . $user_id);
                    if (R::CheckExit('AfkedPlayer:' . $user_id) == false) {
                        R::Ex(86400, 'AfkedPlayer:' . $user_id);
                    }


                        $name = self::ConvertName($user_id, $data['fullname_game']);
                        $Msg = self::$Dt->LG->_('afkedPlayerMessage',array("{0}" => $name, "{1}" =>  self::$Dt->LG->_($data['user_role'] . "_n"), "{2}" => self::$Dt->LG->_('AfkedTotal', array("{0}" => $name,"{1}"=> R::Get('AfkedPlayer:' . $user_id)))));
                        self::SaveMessage($Msg);
                        self::UserDead($data, 'afked');
                    }

                }
                R::Del($keys);
            }

        }
    }
    public static function _getPlayer($user_id){
        $result = self::$Dt->collection->games_players->findOne(['group_id'=>  self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'user_id' => (float) $user_id]);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }
    public static function EditMarkupKeyboard($vote = true){
        $Key = R::LRange(0,-1,'GamePl:MessageNightSend');
        if($Key) {
            foreach ($Key as $key) {
                $Ex = explode('_', $key);
                $user_id = $Ex['1'];
                if($vote) {
                    if (R::Get('game_state') == "night" && !R::CheckExit('GamePl:role_Ruler:RulerOk')) {
                        $CountDontVote = (R::CheckExit('GamePl:DontVote:' . $user_id) ? 2 : 1);
                        R::GetSet($CountDontVote, 'GamePl:DontVote:' . $user_id);
                    }
                }
                Request::editMessageText([
                    'chat_id' => $user_id,
                    'text' => self::$Dt->LG->_('endTime'),
                    'message_id' => $Ex['0'],
                    'parse_mode' => 'HTML',
                    'reply_markup' => new InlineKeyboard([]),
                ]);
            }

        }
        R::Del('GamePl:MessageNightSend');
        $Key = R::keys('GamePl:MessageNightSendDodgeVote:*');
        foreach ($Key as $key){
            $Ex = explode(':',$key);
            $user_id = $Ex['3'];
            $keys = "{$Ex['1']}:{$Ex['2']}:{$Ex['3']}";
            $Message_id = R::Get($keys);
            Request::editMessageText([
                'chat_id' => $user_id,
                'text' => self::$Dt->LG->_('endTime'),
                'message_id' => $Message_id,
                'parse_mode' => 'HTML',
                'reply_markup' => new InlineKeyboard([]),
            ]);
            R::Del($keys);
        }


        // ویرایش markUp
        $Key = R::LRange(0,-1,'GamePl:EditMarkup');
        if($Key) {
            foreach ($Key as $key) {
                $Ex = explode('_', $key);
                $user_id = $Ex['1'];
                $Message_id = $Ex['0'];
                Request::editMessageReplyMarkup([
                    'chat_id' => $user_id,
                    'message_id' => $Message_id,
                    'reply_markup' => new InlineKeyboard([]),
                ]);
            }
            R::Del('GamePl:EditMarkup');
        }


    }

    public static function EditMarkupEnd(){
        $Key = R::LRange(0,-1,'GamePl:EditMarkupEnd');
        if($Key) {
            foreach ($Key as $key) {
                $Ex = explode('_', $key);
                $user_id = $Ex['1'];
                $Message_id = $Ex['0'];
                Request::editMessageReplyMarkup([
                    'chat_id' => $user_id,
                    'message_id' => $Message_id,
                    'reply_markup' => new InlineKeyboard([]),
                ]);
            }
            R::Del('GamePl:EditMarkupEnd');
        }

    }


    public static function GetLove(){
        if(R::CheckExit('GamePl:love')){
            return R::Get('GamePl:love');
        }

        return false;
    }

    public static function CountRole($Role){
        $result = self::$Dt->collection->games_players->count(['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_state' => 1 ,'user_status' => 'on','user_role' => $Role]);
        return $result;
    }


    public static function ConvertHamzad($Detial,$U_name){
        $Hamzad = self::_getPlayerByRole('role_Hamzad');
        if($Hamzad == false){
            R::Del('GamePl:Hamzad');
            return false;
        }

        if($Hamzad['user_state'] !== 1){
            return false;
        }
        $RoleUser = self::$Dt->LG->_($Detial['user_role']."_n");
        $HamzadName = self::ConvertName($Hamzad['user_id'],$Hamzad['fullname_game']);


        switch ($Detial['user_role']){
            case 'role_Vahshi':
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name, "{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);

                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role']),$Hamzad['user_id']);
                // بهش میگیم اولگو طرف کیه
                $OlgoUser = self::$Dt->LG->_('NewWCRoleModel',array("{0}"=> R::Get('GamePl:OlgoName')));
                self::SendMessage($OlgoUser,$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Firefighter':
            case 'role_IceQueen':
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name, "{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>'')),$Hamzad['user_id']);

                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);

                if(R::Get('game_state') == "night"){

                   R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }

                return true;
                break;
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
                $P_Team = self::PlayerByTeam();
                $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
                $WolfName = ($Wolf ? implode(',',array_column($Wolf,'Link')) : false);

                // اگه گرگ تنها بود بهش میگیم تبدیل به گرگ شده،اگه تنها نبود و 2 تا گرگ بودن بهش هم تیمی هاشو میگیم
                $HamzadMessage = ($WolfName == "" ? self::$Dt->LG->_('DGTransToWolf', array("{0}" => $U_name)) : self::$Dt->LG->_('DGTransformToWolf',array("{0}" => $U_name,"{1}" => $WolfName)) );
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);

                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role']),$Hamzad['user_id']);

                // اگه گرگ دیگه ای بود بهشون اطلاع میدیم که طرف گرگ شده
                if($Wolf) {
                    $WolfTeamMessage = self::$Dt->LG->_('DGToWolf', array("{0}" => $HamzadName));
                    self::SendForWolfTeam($WolfTeamMessage);
                }

                if(R::Get('game_state') == "night"){
                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_feramason':
                $P_Team = self::PlayerByTeam();
                $Fermason =  (count($P_Team['Fermason']) > 0 ? $P_Team['Fermason'] : false);
                $FermasonName = ($Fermason ? implode(',',array_column($Fermason,'Link')) : false);

                // اگه هم تیمی داشت بهش میگیم تو پیام
                $HamzadMessage = ($Fermason ? self::$Dt->LG->_('HamzadToFeramasonTeam',array("{0}" => $U_name, "{1}" => $FermasonName)) : self::$Dt->LG->_('HamzadToFeramason',array("{0}" => $U_name)));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);

                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>'')),$Hamzad['user_id']);
                // اگه فراماسون دیگه ای بود بود بهش اطلاع میدیم که همزاد تبدیل شده به فراماسون

                if($Fermason){
                    $MasonMessage = self::$Dt->LG->_('HamzadMeFeramason',array("{0}" =>$HamzadName));
                    self::SendMessageMson($MasonMessage);
                }
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Royce':
            case 'role_ferqe':
                $P_Team = self::PlayerByTeam();
                $ferqe =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);
                $ferqeName = ($ferqe ? implode(',',array_column($ferqe,'Link')) : false);
                // اگه هم تیمی داشت بهش میگیم تو پیام
                $HamzadMessage = ($ferqe ? self::$Dt->LG->_('HamzadToFerqeTeam',array("{0}" => $U_name, "{1}" => $ferqeName)) : self::$Dt->LG->_('HamzadToFerqe',array("{0}" => $U_name)));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);


                if($Detial['user_role'] == "role_ferqe") {
                    // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                    self::SendMessage(self::$Dt->LG->_($Detial['user_role'], array("{0}" =>'')), $Hamzad['user_id']);
                }else{
                    // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                    self::SendMessage(self::$Dt->LG->_($Detial['user_role']), $Hamzad['user_id']);
                }
                // اگه فرقه گرای دیگه ای بود بهش میگیم
                if($ferqe){
                    $CultMessage = self::$Dt->LG->_('HamzadMeFerqe',array("{0}" =>$HamzadName));
                    self::SendForCultTeam($CultMessage);
                }

                if(R::Get('game_state') == "night"){

                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Nazer':
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name,"{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                // اگه پیشگو داشتیم بهش میگیم که کی پیشگوئه
                $HamzadMessage = (R::CheckExit('GamePl:SearUser') == true ? self::$Dt->LG->_($Detial['user_role'],array("{0}" => self::$Dt->LG->_('pishgo_not',array("{0}" => R::CheckExit('GamePl:SearUser'))))) : self::$Dt->LG->_($Detial['user_role'],array("{0}" => self::$Dt->LG->_('Not_pishgo'))));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
            case 'role_Qatel':
                $QatelMsg = self::$Dt->LG->_('HamzadMeKiller',array("{0}" =>  $U_name));
                $Archer = self::_getPlayerByRole('role_Archer');
                if($Archer){
                    $ArcherName = self::ConvertName($Archer['user_id'],$Archer['fullname_game']);
                    $QatelMsg .= PHP_EOL.self::$Dt->LG->_('role_QatelIfArcher',array("{0}" =>  $ArcherName));
                    $MsgArcher = self::$Dt->LG->_('HamzadMeKillerArcher',array("{0}" => $U_name, "{1}" => $HamzadName));
                    self::SendMessage($MsgArcher,$Archer['user_id']);
                }
                self::SendMessage($QatelMsg,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>  '')),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);

                if(R::Get('game_state') == "night"){

                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }

                $Hilda = self::_getPlayerByRole('role_hilda');
                if($Hilda){
                    if($Hilda['user_state'] !== 0) {
                        $HildaMsg = self::$Dt->LG->_('KillerKillHamzadHilda', array("{0}" => $HamzadName));
                        self::SendMessage($HildaMsg, $Hilda['user_id']);
                    }
                }

                return true;
                break;
            case 'role_Archer':
                $ArcherMsg = self::$Dt->LG->_('HamzadMeArcher',array("{0}" => $U_name));
                $Killer = self::_getPlayerByRole('role_Qatel');
                if($Killer){
                    $MsgArcher = self::$Dt->LG->_('HamzadMeKillerArcher',array("{0}" =>  $U_name,"{1}" => $HamzadName));
                    self::SendMessage($MsgArcher,$Killer['user_id']);
                }
                self::SendMessage($ArcherMsg,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" => ($Killer ? $HamzadName : self::$Dt->LG->_('DeadKiller')) )),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);

                if(R::Get('game_state') == "night"){

                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }

                return true;

                break;
            case 'role_kalantar':
                $HunterMessage = self::$Dt->LG->_('HamzadMeHunter',array("{0}" => $U_name));
                if(R::CheckExit('GamePl:Bloodthirsty')) {
                    $Blood = self::_getPlayerByRole('role_Bloodthirsty');
                    if ($Blood) {
                        $BloodName = self::ConvertName($Blood['user_id'],$Blood['fullname']);
                        $HunterMessage .= self::$Dt->LG->_('HamzadMeHunterBooldIn',array("{0}" => $BloodName));

                        $MsgBlood = self::$Dt->LG->_('HamzadMeHunterBlood',array("{0}" => $U_name,"{1}"=> $HamzadName));
                        self::SendMessage($MsgBlood, $Blood['user_id']);
                    }
                }
                self::SendMessage($HunterMessage,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>'')),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role'],true);
                return true;
                break;
            case 'role_Bloodthirsty':
                $HunterMessage = self::$Dt->LG->_('HamzadMeBlood',array("{0}" =>$U_name));
                $kalantarName = "";
                if(R::CheckExit('GamePl:Bloodthirsty')) {
                    $kalantar = self::_getPlayerByRole('role_kalantar');
                    if ($kalantar) {
                        $kalantarName = self::ConvertName($kalantar['user_id'],$kalantar['fullname']);
                        $HunterMessage .= self::$Dt->LG->_('HamzaBloodInKalantar',array("{0}" =>$kalantarName));

                        $MsgKalanBlood = self::$Dt->LG->_('HamzadMeBloodHunterMasg', array("{0}" => $U_name,"{1}" => $HamzadName));
                        self::SendMessage($MsgKalanBlood, $kalantar['user_id']);
                    }
                }
                self::SendMessage($HunterMessage,$Hamzad['user_id']);
                self::SendMessage(self::$Dt->LG->_($Detial['user_role'],array("{0}" =>$kalantarName)),$Hamzad['user_id']);
                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role'],true);
                if(R::Get('game_state') == "night"){

                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }

                break;

            default:
                // اول بهش میگیم که همزادش مرده
                $HamzadMessage = self::$Dt->LG->_('HamzadTabdilshode',array("{0}" => $U_name, "{1}" => $RoleUser));
                self::SendMessage($HamzadMessage,$Hamzad['user_id']);
                // درباره نقشش بهش میگیم که چیکار میتونه بکنه
                self::SendMessage(self::$Dt->LG->_($Detial['user_role']),$Hamzad['user_id']);

                if(R::Get('game_state') == "night"){
                    R::rpush($Hamzad['user_id'],'GamePl:SendNight');
                }

                self::ConvertPlayer($Hamzad['user_id'],$Detial['user_role']);
                return true;
                break;
        }
    }


    public static function ConvertOlgo($Detial,$U_name){
        $Vahshi = self::_getPlayerByRole('role_Vahshi',false);
        if($Vahshi == false){
            R::Del('GamePl:Olgo');
            R::Del('GamePl:OlgoName');
            return false;
        }

        if($Vahshi['user_state'] !== 1){
            return false;
        }
        $VahshiName = self::ConvertName($Vahshi['user_id'],$Vahshi['fullname_game']);

        $P_Team = self::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        $WolfName = ($Wolf ? implode(',',array_column($Wolf,'Link')) : false);

        $OlgoMessage = ($Wolf ? self::$Dt->LG->_('OlgoChangedToTeam',array("{0}" =>  $U_name, "{1}" => $WolfName)) : self::$Dt->LG->_('OlgoChangedToTone',array("{0}" =>  $U_name)));
        self::SendMessage($OlgoMessage,$Vahshi['user_id']);
        if($Wolf){
            $wolfMessage = self::$Dt->LG->_('OlgoChangedTo',array("{0}" =>  $U_name, "{1}" => $VahshiName));
            self::SendForWolfTeam($wolfMessage);
        }
        if(R::Get('game_state') == "night"){
            R::rpush($Vahshi['user_id'],'GamePl:SendNight');
        }
        self::ConvertPlayer($Vahshi['user_id'],'role_WolfGorgine');

        return true;
    }

    public static function CheckRezrv($U_name){
        $Rezrv = self::_getPlayerByRole('role_PishRezerv');
        if($Rezrv == false){
            return false;
        }
        if($Rezrv['user_state'] !== 1){
            return false;
        }

        $RezrvName =  self::ConvertName($Rezrv['user_id'],$Rezrv['fullname_game']);
        $RzrvMessage = self::$Dt->LG->_('ApprenticeNowSeer',array("{0}" =>   $U_name));
        self::SendMessage($RzrvMessage,$Rezrv['user_id']);
        R::rpush($Rezrv['user_id'],'GamePl:SendNight');
        R::GetSet($RezrvName,'GamePl:SearUser');
        self::ConvertPlayer($Rezrv['user_id'],'role_pishgo');
        $Nazer = self::_getPlayerByRole('role_Nazer');
        if($Nazer){
            $NazerMessage = self::$Dt->LG->_('BeholderNewSeer',array("{0}" =>   $RezrvName, "{1}" => $U_name));
            self::SendMessage($NazerMessage,$Nazer['user_id']);
        }

        return true;
    }

    public static function CheckKhaen(){
        $Khaen = self::_getPlayerByRole('role_Khaen');
        if($Khaen == false){
            return false;
        }
        if($Khaen['user_state'] !== 1){
            return false;
        }

        $P_Team = self::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);

        if($Wolf == false){
            R::GetSet('GamePl:NotSend:'.$Khaen['user_id'],R::Get('GamePl:Night_no'));
            self::ConvertPlayer($Khaen['user_id'],'role_WolfGorgine');
            $KhaenMessage = self::$Dt->LG->_('TraitorTurnWolf');
            self::SendMessage($KhaenMessage,$Khaen['user_id']);
            return true;
        }

        return false;
    }

    public static function CheckPlayerEnchanter($Del){
        $data = R::Sort('GamePl:Enchanter','desc');
        if (($key = array_search($Del, $data)) !== false) {
            unset($data[$key]);
        }
        return $data;

    }
    public static function ClearEnchanter(){
        if(R::CheckExit('GamePl:Enchanter')){
            $Data = R::LRange(0,-1,'GamePl:Enchanter');
            foreach ($Data as $user_id) {
                $player = self::_getPlayer($user_id);
                if($player) {
                    if($player['user_state'] == 1) {
                        $Message = self::$Dt->LG->_('ClearEnchanter');
                        self::SendMessage($Message, $user_id);
                    }
                }
            }

            R::Del('GamePl:Enchanter');
        }
    }



    public static function CheckSendNight($user_id){
        $data =  R::LRange(0,-1,'GamePl:SendNight');
        if($data){

            if(in_array($user_id,$data)){
                return true;
            }
        }

        return false;
    }

    public static function ConvertforestQueen($Name){
        $forestQueen = self::_getPlayerByRole('role_forestQueen');
        if($forestQueen == false){
            return false;
        }
        if($forestQueen['user_state'] !== 1){
            return false;
        }

        $forestQueenName = self::ConvertName($forestQueen['user_id'],$forestQueen['fullname_game']);

        $MessageforestQueenConvert = self::$Dt->LG->_('forestQueenConvert',array("{0}" =>  $Name));
        self::SendMessage($MessageforestQueenConvert,$forestQueen['user_id']);

        $WolfMessage = self::$Dt->LG->_('forestQueenConvertForTeamWolf',array("{0}" => $Name, "{1}"=> $forestQueenName));
        self::SendForWolfTeam($WolfMessage);
        R::GetSet(true,'GamePl:role_forestQueen:forestQueenBitten');
        R::GetSet(true, 'GamePl:role_forestQueen:AlphaDead');

    }

    public static function _getCountPlayer(){

        $result = self::$Dt->collection->join_user->findOne(['chat_id' => self::$Dt->chat_id]);

        if($result) {
            $array = iterator_to_array($result);
            return count($array['users']);
        }
        return 0;
    }

    public static function RemoveUser($user_id){
        self::$Dt->collection->join_user->updateOne(array("chat_id"=>self::$Dt->chat_id),array('$pull' => array("users" => ['user_id' => $user_id])));
    }

    public static function RandomBookChange($UserId){
        if(R::CheckExit('GamePl:BookIn:'.$UserId)){
            R::Del('GamePl:BookIn:'.$UserId);
            $NotUserId = [$UserId];
            $GetKeys = R::Keys('GamePl:BookIn:*');
            if($GetKeys){
                foreach ($GetKeys as $key) {
                    $explode = explode(":",$key);
                    array_push($NotUserId,$explode['3']);
                }
            }

            $result = self::$Dt->collection->games_players->findOne([
                'game_id' => self::$Dt->game_id
                , 'group_id' => self::$Dt->chat_id
                , 'user_state' => 1
                , 'user_status' => 'on'
                , 'user_role' => ['$nin' => ['role_Harly','role_Joker']]
                , 'user_id' => ['$nin' => $NotUserId]
            ]);
            if ($result) {
                $array = iterator_to_array($result);
                R::GetSet(true,'GamePl:BookIn:'.$array['user_id']);
            }
        }
    }

    public static function UserDead($Detial,$for){
        if(!is_array($Detial)){
            $Detial = self::_getPlayer($Detial);
        }

        if(!$Detial) return false;


        if(R::CheckExit('GamePl:DianSelectedPlayer')){
            $GetId = (float) R::Get('GamePl:DianSelectedPlayer');
            $KillUserId = (float) $Detial['user_id'];
            $KillName = self::ConvertName($Detial['user_id'], $Detial['fullname_game']);

            if($GetId == $KillUserId){
                $groupMessage = self::$Dt->LG->_('DianAfterKillPlayer',array("{0}" => $KillName));
                self::SaveMessage($groupMessage);
                R::Del('GamePl:DianSelectedPlayer');
                R::Del('GamePl:DianSelectedPlayerDayNo');
            }elseif($Detial['user_role'] == 'role_dian'){
                R::Del('GamePl:DianSelectedPlayer');
                R::Del('GamePl:DianSelectedPlayerDayNo');
                $groupMessage = self::$Dt->LG->_('dianKillBeforVoteSelect',array("{0}" => $KillName));
                self::SaveMessage($groupMessage);
            }
        }

        $GameMode = R::Get('GamePl:gameModePlayer');

        if($for == "afked") {
            self::SaveGameActivity($Detial, 'afk', ['user_id' => 0, 'fullname' => 0]);
        }

        $RNo = R::NoPerfix();
        $TimeGame = time() -  R::Get('GamePl:StartedTime');
        $left_times = ($RNo->exists('userGameTime:'.$Detial['user_id']) ? $RNo->get('userGameTime:'.$Detial['user_id']) : 0);
        $RNo->set('userGameTime:'.$Detial['user_id'], $left_times +  $TimeGame);

        $user_id = $Detial['user_id'];
        $Name = self::ConvertName($user_id,$Detial['fullname_game']);


        if(self::$Dt->mute_die){
            Request::restrictChatMember([
                'chat_id' => self::$Dt->chat_id,
                'user_id' => $Detial['user_id'],
                'permissions' => ['can_send_messages' => false,'can_send_media_messages' => false,'can_send_polls' => false,'can_send_other_messages' => false,'can_add_web_page_previews' => false,'can_change_info'=>false,'can_invite_users' => false ,'can_pin_messages' => false],
                'until_date' => time() - 20
            ]);

            R::rpush(['user_id' => $Detial['user_id'] ,'fullname' => $Name],'GamePl:MutedPlayer','json');
            $Msg = self::$Dt->L->_('mutedPlayer',array("{0}" => $Name ));
            self::SendMessage($Msg,self::$Dt->chat_id);
         }

        R::rpush($Name,'playerDeadName');
        $status = 0;
        if($for == "smite"){
            $status = 2;
        }
          self::$Dt->collection->games_players->updateOne(
            ['user_id' => (float) $user_id,'game_id'=> self::$Dt->game_id,'group_id'=> self::$Dt->chat_id],
            ['$set' => ['user_state' => $status, 'user_status' => $for ,'dead_time' => time() ]]
        );


        if(R::Get('GamePl:Night_no') == 0) {
            R::rpush($Detial['user_id'], 'GamePl:NightKill');
        }


        if(R::CheckExit('GamePl:love:'.$user_id)  && R::CheckExit('GamePl:CheckLover:'.$user_id) == false){
            if($for == "afked"){
                if($GameMode == "Romantic"){
                    R::GetSet(true,'GamePl:LoNW:'.$user_id);
                }
                R::GetSet(true,'GamePl:CheckLover:'.$user_id);
            }else {
                $LoverId = R::Get('GamePl:love:'.$user_id);
                $LoverDetial = self::_getPlayer($LoverId);
                if($LoverDetial) {
                    if($LoverDetial['user_state'] == 1) {
                        if($GameMode == "Romantic"){
                            R::GetSet(true,'GamePl:LoNW:'.$user_id);
                            R::GetSet(true,'GamePl:LoNW:'.$LoverDetial['user_id']);
                        }
                        $LoverName = self::ConvertName($LoverDetial['user_id'], $LoverDetial['fullname_game']);
                        $GroupMessage = self::$Dt->LG->_('LoverDied', array("{0}" => $Name, "{1}"=>  $LoverName,"{2}"=> self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($LoverDetial['user_role'] . "_n")))));
                        if (R::CheckExit('GamePl:HunterKill')) {
                            self::SendMessage($GroupMessage);
                        } else {
                            self::SaveMessage($GroupMessage);
                        }
                        R::GetSet(true, 'GamePl:CheckLover:' . $LoverId);
                        R::GetSet(true, 'GamePl:CheckLover:' . $user_id);
                        self::SaveGameActivity($LoverDetial, 'love_dead', $Detial);

                        if ($LoverDetial['user_role'] == "role_kalantar") {
                            self::HunterKill($GroupMessage, $LoverDetial['user_id'], 'kill');
                        }
                        self::UserDead($LoverDetial, 'love');
                    }
                }
            }

        }




        if($Detial['user_role'] == "role_ferqe"){
            self::CheckMummy($Detial,'kill');
        }
        if($Detial['user_role'] == "role_Royce"){
            self::CheckMummy($Detial,'royce');
        }

        if($Detial['user_role'] == "role_Sweetheart"){
            if(R::CheckExit('GamePl:SweetheartLove')){
                R::DelKey('GamePl:SweetheartLove*');
            }
        }

        // چک میکنیم اگه کاربر تو لیست طلسم ها بود پاک شه
        if(R::CheckExit('GamePl:Enchanter')){
            self::CheckPlayerEnchanter($Detial['user_id']);
        }



        // اگه طرف  افسونگر بود تمام طلسم ها پاک شه
        if($Detial['user_role'] == "role_enchanter"){
            self::ClearEnchanter();
        }


        if(R::CheckExit('GamePl:BomberGet:'.$Detial['user_id'])){

                $GetKeys = R::Keys('GamePl:BomberGet:*');
                $DinamitUer = [];
                foreach ($GetKeys as $row){
                    $Exp = explode(':',$row);
                    $DinamitUer[] = $Exp[3];
                }

                /*
                $RandomPlayer = self::GetUserRandom($DinamitUer);
                if($RandomPlayer) {
                    R::GetSet(R::Get('GamePl:BomberGet:'.$Detial['user_id']),'GamePl:BomberGet:'.$RandomPlayer['user_id']);
                }
                */
                   

                R::Del('GamePl:BomberGet:'.$Detial['user_id']);

        }

        // اگه طرف وحشی بود پاک میکنیم الگوش کی بوده
        if($Detial['user_role'] == "role_Vahshi"){
            R::Del('GamePl:Olgo');
        }

        // چک میکنیم طرف الگو کسی بوده یا نه
        if(R::CheckExit('GamePl:Olgo')){
            $OlgoId = R::Get('GamePl:Olgo');
            if($OlgoId == $user_id){
                self::ConvertOlgo($Detial,$Name);
                R::Del('GamePl:Olgo');
                R::Del('GamePl:OlgoName');
            }
        }


        if($Detial['user_role'] == "role_Ruler"){
            if(R::CheckExit('GamePl:role_Ruler:RulerOk') && R::CheckExit('GamePl:RulerOkSend') == false){
                $groupMessage = self::$Dt->LG->_('RulerIsDead');
                self::SaveMessage($groupMessage);
                R::Del('GamePl:role_Ruler:RulerOk');
            }
        }

        if($Detial['user_role'] == "role_Fereshte"){
            R::DelKey('GamePl:role_angel:*');
        }

        // اگر همزاد بود نقش نگرفت پاک کن همزاد کی بوده
        if($Detial['user_role'] == "role_Hamzad"){
            R::Del('GamePl:Hamzad');
        }
        // چک میکنیم طرف  همزاد داشت یا نه
        if(R::CheckExit('GamePl:Hamzad')){
            $HamzadId = R::Get('GamePl:Hamzad');
            if($HamzadId == $user_id){
                self::ConvertHamzad($Detial,$Name);
                R::Del('GamePl:Hamzad');
                R::DelKey("GamePl:UserInHome:{$Detial['user_role']}*");
                R::DelKey("GamePl:Selected:{$Detial['user_role']}*");
                R::GetSet(true,'GamePl:Kill');
                R::DelKey("GamePl:{$Detial['user_role']}:*");
                self::RemoveUser($user_id);
                return true;
            }
        }

        if($Detial['user_role'] == "role_BlackKnight"){
            $Birde = self::_getPlayerByRole('role_BrideTheDead');
            if($Birde) {
                if ($Birde['user_state'] == 1) {
                    $Name = self::ConvertName($Birde['user_id'],$Birde['fullname_game']);
                    $GroupMsg = self::$Dt->LG->_('BrideTheDeadBlackDie',array("{0}" => $Name));
                    self::SaveMessage($GroupMsg);
                    self::UserDead($Birde,'black');
                }
            }
        }

        if($Detial['user_role'] == "role_Phoenix"){
            $GetPlayer = R::Keys('GamePl:PhoenixHealer:*');
            if($GetPlayer) {
                foreach ($GetPlayer as $row){
                    $explode = explode(':',$row);
                    if(isset($explode['3'])) {
                        $UserId = $explode['3'];
                        $Player =  self::_getPlayer($UserId);
                        if($Player['user_state'] !== 1){
                            continue;
                        }
                        $PlayerMessage = self::$Dt->LG->_('MessagePlayerPhoenixDead');
                        self::SendMessage($PlayerMessage,$Player['user_id']);
                    }

                }
                R::DelKey('GamePl:PhoenixHealer:*');
            }
        }


        if(R::CheckExit('GamePl:BookIn:'.$Detial['user_id'])){
            self::RandomBookChange($Detial['user_id']);
        }

        if($Detial['user_role'] == "role_Joker"){
            $Harly = self::_getPlayerByRole('role_Harly');
            if($Harly){
                $HarlyMessage = self::$Dt->LG->_('HarlyWhenDiedJoker');
                self::SendMessage($HarlyMessage,$Harly['user_id']);
                R::GetSet(true,'GamePl:DiedJoker');
            }
        }

        if($Detial['user_role'] == "role_Princess"){
            $GetPlayer = R::Keys('GamePl:PrincessPrisoner:*');
            if($GetPlayer) {
                foreach ($GetPlayer as $row){
                    $explode = explode(':',$row);
                    if(isset($explode['3'])) {
                        $UserId = $explode['3'];
                        $Player =  self::_getPlayer($UserId);
                        if($Player['user_state'] !== 1){
                            continue;
                        }
                        $PlayerMessage = self::$Dt->LG->_('PrincessDead');
                        self::SendMessage($PlayerMessage,$Player['user_id']);
                    }

                }
                R::DelKey('GamePl:PrincessPrisoner:*');
            }
        }


        if($Detial['user_role'] == "role_Harly"){
            $Joker = HL::_getPlayerByRole('role_Joker');
            if($Joker){
                $JokerMessage = self::$Dt->LG->_('JokerMessageWhenHalryDied');
                self::SendMessage($JokerMessage,$Joker['user_id']);
                R::GetSet(true,'GamePl:DiedHarly');
            }
        }

        if($Detial['user_role'] == "role_Qatel") {
            self::CheckHilda();
        }


        if($Detial['user_role'] == "role_shekar"){
            $Huntsman = self::_getPlayerByRole('role_Huntsman');
            if($Huntsman) {
                if ($Huntsman['user_state'] == 1) {
                    R::GetSet(R::Get('GamePl:Night_no'),'GamePl:NotSend:'.$Huntsman['user_id']);
                    $HuntsmanMessage = self::$Dt->LG->_('HuntsmanDeadCultHulter',array("{0}" => $Name));
                    self::SendMessage($HuntsmanMessage,$Huntsman['user_id']);
                    self::ConvertPlayer($Huntsman['user_id'],'role_shekar');

                }
            }
        }

        if($Detial['user_role'] == "role_IceQueen" || $Detial['user_role'] == "role_Firefighter" ){
            $Fire = self::_getPlayerByRole('role_Firefighter',false);
            $Ice = self::_getPlayerByRole('role_IceQueen',false);
            if(!$Fire && !$Ice){
                $LILis = self::_getPlayerByRole('role_Lilis',false);
                if($LILis && !R::CheckExit('GamePl:DieFireAndIc')) {
                    $LilisMessage = self::$Dt->LG->_('KillAllTeamLilis');
                    self::SendMessage($LilisMessage,$LILis['user_id']);
                    R::GetSet(true,'GamePl:DieFireAndIc');
                }
            }
        }
        // اگر توله مرده بود  2 بار بتونن بخورن
        if($Detial['user_role'] == "role_WolfTolle" && $for !== "afked"){
            R::GetSet((R::Get('GamePl:Night_no') + 1),'GamePl:WolfCubeDead');
        }


        // اگه رئیس فرقه مورده بود به فرقه گرا ها پیام بده بگو که شعد بعد میتونن 2 نفرو دعوت بدن
        if($Detial['user_role'] == "role_Royce" and $for !== "afked"){
            $CultMessage = self::$Dt->LG->_('RoyceDead',array("{0}" =>$Name));
            R::GetSet((R::Get('GamePl:Night_no') + 1), 'GamePl:RoyceDead');
            self::SendForCultTeam($CultMessage,$Detial['user_id']);
        }


        if($Detial['user_role'] == "role_Bloodthirsty"){
            $VampireMessage = self::$Dt->LG->_('DeadBldBeforeFinde',array("{0}" =>$Name));
            self::SendForVampireTeam($VampireMessage);
            self::CheckChiang($Name);
            R::GetSet(20,'GamePl:VampireConvert');
            R::GetSet(true,'GamePl:DeadBloodthirsty');
            R::Del('GamePl:Bloodthirsty');
            R::Del('GamePl:VampireFinded');

        }
        if($Detial['user_role'] == "role_kalantar"){
            if(R::CheckExit('GamePl:BloodthirstyInGame') && R::CheckExit('GamePl:VampireFinded') == false){
                $Bloodthirsty = self::_getPlayerByRole('role_Bloodthirsty',false);
                if($Bloodthirsty) {
                    if ($Bloodthirsty['user_state'] == 1) {
                        $P_Team = self::PlayerByTeam();
                        $Vampire = (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);
                        $VampireName = ($Vampire ? implode(',', array_column($Vampire, 'Link')) : false);
                        if ($Vampire) {
                            // ارسال پیام برای تیم ومپایر
                            $VampireMessage = self::$Dt->LG->_('VampireDeadHunterBeforeFinde', array("{0}" => $Name, "{1}" => R::Get('GamePl:BloodthirstyInGame')));
                            self::SendForVampireTeam($VampireMessage, $Bloodthirsty['user_id']);
                        }
                        // ارسال پیام برای اصیل
                        $BlooadMessage = self::$Dt->LG->_('VampireDeadHunterBeforeFindeBlodMessage', array("{0}" => $Name, "{1}" => $Vampire ? $VampireName : self::$Dt->LG->_('VampireNoTeam')));
                        self::SendMessage($BlooadMessage, $Bloodthirsty['user_id']);

                        R::GetSet('GamePl:NotSend:' . $Bloodthirsty['user_id'], R::Get('GamePl:Night_no'));
                        R::GetSet(true, 'GamePl:VampireFinded');
                        R::GetSet(SE::_s('BVampireChangeConvet'), 'GamePl:VampireConvert');
                        R::GetSet(true, 'GamePl:Bloodthirsty');
                    }

                }
            }
        }
        // چک میکنیم اگه طرف آلفا بود ملکه جنگل نقش بگیره
        if($Detial['user_role'] == "role_WolfAlpha"){
            self::ConvertforestQueen($Name);
        }




        // چک میکنیم اگه طرف آلفا بود ملکه جنگل نقش بگیره
        if($Detial['user_role'] == "role_forestQueen" && $for !== "afked"){
            if(R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false) {
                R::GetSet((R::Get('GamePl:Night_no') + 1), 'GamePl:DeadforestQueen');
            }
        }


        // اگه طرف پیشگو بوده چک میکنیم پیش رزرو هست یا نه
        if($Detial['user_role'] == "role_pishgo"){
            self::CheckRezrv($Name);
        }


        if($Detial['user_role'] == "role_IceQueen"){
            if(R::CheckExit('GamePl:IceQueenIced')){
                R::DelKey('GamePl:IceQueenIced*');
            }
        }


        R::DelKey("GamePl:UserInHome:{$Detial['user_role']}*");
        R::DelKey("GamePl:Selected:{$Detial['user_role']}*");
        R::GetSet(true,'GamePl:Kill');
        R::DelKey("GamePl:{$Detial['user_role']}:*");
        self::RemoveUser($user_id);

        if($Detial['team'] == "wolf") {
            // چک میکنیم خائن هست تو بازی یا نه
            self::CheckKhaen();
            // چک کردن برای تبدیل به گرگ اگه همه گرگا مونده بودن
            self::CheckWhiteWolf();
        }
        
        if($Detial['team'] == "ferqeTeem") {
            // چک میکنیم فرقه ای تو بازی هست یا نه
            self::CheckFranc();
        }

        if($Detial['team'] == "vampire" && !R::CheckExit('GamePl:VampireBitten')) {
            self::CheckKentVampire();
        }

        return true;
    }

    public static function CheckFranc(){
        $Franc = self::_getPlayerByRole('role_franc');
        if($Franc == false){
            return false;
        }
        if($Franc['user_state'] !== 1){
            return false;
        }

        $P_Team = self::PlayerByTeam();
        $FrancD =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);

        if(!$FrancD){
            R::GetSet('GamePl:NotSend:'.$Franc['user_id'],R::Get('GamePl:Night_no'));
            $FrancMessage = self::$Dt->LG->_('FrancDeadCult');
            self::SendMessage($FrancMessage,$Franc['user_id']);
            R::GetSet(true,'GamePl:FrancNightOk');
            R::DelKey('GamePl:role_franc:*');
            return true;
        }

        return false;
    }

    public static function CheckKentVampire(){

        $kent = self::_getPlayerByRole('role_kentvampire');
        if(!$kent){
            return false;
        }

        if($kent['user_state'] !== 1){
            return false;
        }


        $P_Team = self::PlayerByTeam();
        $Vampire =  (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);

        if(!$Vampire){
            R::GetSet(true,'GamePl:KentVampireConvert');
            $kentMessage = self::$Dt->LG->_('KentVampireKillAllVampire');
            self::SendMessage($kentMessage,$kent['user_id']);
        }

        return true;
    }

    public static function doNotAssign($del,$data){
        if (($key = array_search($del, $data)) !== false) {
            unset($data[$key]);
        }
        return $data;
    }

    public static function CheckMummy($detial,$type){
        if(R::CheckExit('GamePl:DieCult') && $type == 'kill'){
            return false;
        }
        if(R::CheckExit('GamePl:ConvertCult') && $type == 'royce'){
            return  false;
        }
        $Mummy = self::_getPlayerByRole('role_Mummy');
        if(!$Mummy){
            return false;
        }
        if($Mummy['user_state'] == 0)  return false;
        $CultName = self::ConvertName($detial['user_id'], $detial['fullname_game']);

        if($type == 'kill') {
            $MummyMessage = self::$Dt->LG->_('MummyMessageWhenKillCult', array("{0}" => $CultName));
            self::SendMessage($MummyMessage, $Mummy['user_id']);
            R::GetSet(true, 'GamePl:DieCult');
        }

        if($type == 'royce'){
            $MsgCultTeam =  self::$Dt->LG->_('AfterDieRoyce',array("{0}" => $CultName));
            self::SendForCultTeam($MsgCultTeam,$Mummy['user_id']);
            R::GetSet(20,'GamePl:ConvertCult');
        }
        return  true;
    }
    public static function CheckChiang($name){
        $Chiang = self::_getPlayerByRole('role_Chiang');
        if(!$Chiang){
            return false;
        }
        if($Chiang['user_state'] == 0)  return false;
        $GetPlayerByTeam = self::PlayerByTeam();
        $Name = self::ConvertName($Chiang['user_id'],$Chiang['fullname_game']);

        $Vampire =  (count($GetPlayerByTeam['vampire']) > 0 ? $GetPlayerByTeam['vampire'] : false);
        $TeamName = ($Vampire ? implode(',',self::doNotAssign($Name,array_column($Vampire,'Link'))) : false);

        $ChiangMessage = self::$Dt->LG->_('ChiangDeadBlod',array("{1}" => $TeamName ,"{0}" => $name));
        self::SendMessage($ChiangMessage,$Chiang['user_id']);
        $TeamMessage = self::$Dt->LG->_('ChiangDeadBlodVampireGroup',array("{0}" => $name,"{1}" => $Name));
        self::SendForVampireTeam($TeamMessage);

        return true;
    }
    public static function CheckHilda(){
        $Hilda = self::_getPlayerByRole('role_hilda');
        if(!$Hilda) return false;
        if($Hilda['user_state'] == 0)  return false;
        $HildaMessage = self::$Dt->LG->_('KillerKillHilda');
        self::SendMessage($HildaMessage,$Hilda['user_id']);
        R::GetSet(true,'GamePl:KillerIsKillHildaInGame');
        return true;

    }
    public static function GetUserRandomNonWolf($not_in = []){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'team' => ['$nin' => ['wolf']]
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_id' => ['$nin' => $not_in]
        ],[ 'limit' => -1 ,'skip' => mt_rand( 0, (self::_getCountPlayers()) )]);

        if($result) {
            $array = iterator_to_array($result);
            if(!isset($array['0'])){
                return self::GetUserRandom($not_in);
            }
            if(in_array($array['0']['user_id'],$not_in)){
                return self::GetUserRandom($not_in);
            }
            return $array['0'];
        }


        return false;
    }

    public static function CheckWhiteWolf(){
        $WhiteWolf = self::_getPlayerByRole('role_WhiteWolf');
        if($WhiteWolf == false){
            return false;
        }
        if($WhiteWolf['user_state'] !== 1){
            return false;
        }

        $P_Team = self::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);

        if(!is_array($Wolf)){
            R::GetSet('GamePl:NotSend:'.$WhiteWolf['user_id'],R::Get('GamePl:Night_no'));
            $WhiteWolfMessage = self::$Dt->LG->_('WhiteWolfDeadAllWolf');
            self::SendMessage($WhiteWolfMessage,$WhiteWolf['user_id']);
            R::GetSet(true,'GamePl:WhiteWolfToWolf');
            self::ConvertPlayer($WhiteWolf['user_id'],'role_WolfGorgine',false);
            return true;
        }

        return false;
    }
    public static function _getPlayerByRole($role,$dead = false){
        if($dead == false){
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 1];
        }else{
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 0];
        }
        $result = self::$Dt->collection->games_players->findOne($query);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }
    public static function _getPlayerByRoleGroup($role,$dead = false){
        if($dead == false){
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 1];
        }else{
            $query = ['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_role' => $role,'user_state' => 0];
        }
        $result = self::$Dt->collection->games_players->find($query);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }

    public static function _getPlayerById($user_id){
        $result = self::$Dt->collection->games_players->findOne(['game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_id' => $user_id]);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }
        return false;
    }
    public static function SendMessage($msg,$chat_id = false,$Gif = false,$markdown = false){
        if($chat_id == false){
            $chat_id = self::$Dt->chat_id;
        }

        if($Gif){
            $GifKey = SE::GetGif($Gif);
            if($GifKey) {
                return Request::sendVideo([
                    'chat_id' => $chat_id,
                    'video' => $GifKey,
                    'caption' => $msg,
                    'parse_mode' => (!$markdown ? 'HTML' : 'Markdown'),
                ]);
            }
        }

        Request::sendMessage([
            'chat_id' => $chat_id,
            'text' => $msg,
            'parse_mode' =>  (!$markdown ? 'HTML' : 'Markdown'),
        ]);
    }


    public static function _GetByTeam($Team){
        $result = self::$Dt->collection->games_players->find(['team' => $Team,'game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_state' => 1 ,'user_status' => 'on']);
        if($result) {
            $re = [];
            $array = iterator_to_array($result);
            foreach ($array as $Key =>  $row){
                if($row['user_role'] == 'role_forestQueen') {
                    if (R::CheckExit('GamePl:role_forestQueen:AlphaDead') == false) {
                        continue;
                    }
                    $re[] = $array[$Key];
                }

                if($row['user_role'] == "role_Bloodthirsty"){
                    if(R::CheckExit('GamePl:Bloodthirsty') == false){
                        continue;
                    }
                    $re[] = $array[$Key];
                    continue;
                }
                if($row['user_role'] == "role_Chiang"){
                    if(!R::CheckExit('GamePl:DeadBloodthirsty')){
                        continue;
                    }
                    $re[] = $array[$Key];
                    continue;
                }
                switch ($Team){
                    case 'wolf':
                        $wolfRole = SE::WolfRole();
                        if(in_array($row['user_role'],$wolfRole)){
                            $re[] = $array[$Key];
                        }
                        break;
                    case 'Firefighter':
                        $wolfRole = ['role_Magento'];
                        if(in_array($row['user_role'],$wolfRole)){
                            $re[] = $array[$Key];
                        }
                        break;
                    default:
                        $re[] = $array[$Key];
                        break;
                }
            }

            return $re;
        }
        return false;
    }

    public static function SendForVampireTeam($msg,$sendMe = false,$gif = false){
        $no_in = ($sendMe ? [$sendMe] : []);
        $user = self::_GetByTeam('vampire');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }
    public static function SendForQatelTeam($msg,$sendMe = false){
        $no_in = ($sendMe ? [$sendMe] : []);
        $user = self::_GetByTeam('qatel');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }


    public static function SendForMagentoTeam($msg,$sendMe = false){
        $no_in = ($sendMe == true ? [$sendMe] : []);
        $user = self::_GetByTeam('Firefighter');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }

    public static function SendForWolfTeam($msg,$sendMe = false){
        $no_in = ($sendMe == true ? [$sendMe] : []);
        $user = self::_GetByTeam('wolf');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }

    public static function SendForCultTeam($msg,$sendMe = false){
        $no_in = ($sendMe ? [$sendMe] : []);
        $user = self::_GetByTeam('ferqeTeem');
        if($user){
            foreach ($user as $row){
                if(!in_array($row['user_id'],$no_in)) {
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => $msg,
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }
    }

    public static function ConvertPlayer($user_id,$to,$hamzad = false){
        $Detial = self::_getPlayer($user_id);

        if($Detial['user_role'] == "role_enchanter"){
            self::ClearEnchanter();
        }
        $PlayerName = self::ConvertName($Detial['user_id'],$Detial['fullname_game']);

        if($Detial['user_role'] == "role_pishgo"){
            $Rezrv = self::_getPlayerByRole('role_PishRezerv');
            if($Rezrv){
                if($Rezrv['user_state'] == 1){
                    $RezrvName =  self::ConvertName($Rezrv['user_id'],$Rezrv['fullname_game']);
                    $RzrvMessage = self::$Dt->LG->_('ChangeRolePishgo');
                    self::SendMessage($RzrvMessage,$Rezrv['user_id']);
                    R::rpush($Rezrv['user_id'],'GamePl:SendNight');
                    R::GetSet($RezrvName,'GamePl:SearUser');
                    self::ConvertPlayer($Rezrv['user_id'],'role_pishgo');
                    $Nazer = self::_getPlayerByRole('role_Nazer');
                    if($Nazer){
                        $NazerMessage = self::$Dt->LG->_('MessageForNazer',array("{0}" => $PlayerName,"{1}"=> $RezrvName));
                        self::SendMessage($NazerMessage,$Nazer['user_id']);
                    }
                }
            }
        }
        if($Detial['user_role'] == "role_kalantar" && $hamzad == false){

            if(R::CheckExit('GamePl:BloodthirstyInGame') && R::CheckExit('GamePl:VampireFinded') == false){
                $Bloodthirsty = self::_getPlayerByRole('role_Bloodthirsty');
                if($Bloodthirsty) {
                    if ($Bloodthirsty['user_state'] == 1) {
                        $P_Team = self::PlayerByTeam();
                        $Vampire = (count($P_Team['vampire']) > 0 ? $P_Team['vampire'] : false);
                        $VampireName = ($Vampire ? implode(',', array_column($Vampire, 'Link')) : false);
                        if ($Vampire) {
                            // ارسال پیام برای تیم ومپایر
                            $VampireMessage = self::$Dt->LG->_('VampireChangeRoleBeforeFinde', array("{0}" => R::Get('GamePl:BloodthirstyInGame')));
                            self::SendForVampireTeam($VampireMessage, $Bloodthirsty['user_id']);
                        }
                        // ارسال پیام برای اصیل
                        $BlooadMessage = self::$Dt->LG->_('VampireChangeHunterBeforeFindeBlodMessage', array("{0}" => $Vampire ? $VampireName : self::$Dt->LG->_('VampireNoTeam')));
                        self::SendMessage($BlooadMessage, $Bloodthirsty['user_id']);

                        R::GetSet('GamePl:NotSend:' . $Bloodthirsty['user_id'], R::Get('GamePl:Night_no'));
                        R::GetSet(true, 'GamePl:VampireFinded');
                        R::GetSet(SE::_s('BVampireChangeConvet'), 'GamePl:VampireConvert');
                        R::GetSet(true, 'GamePl:Bloodthirsty');
                    }
                }
            }
        }

        switch ($to){
            case 'role_rishSefid':
                R::Del('GamePl:Eatelder');
                break;
            case 'role_tofangdar':

               // R::GetSet(R::Get('GamePl:GunnerBult'),'GamePl:GunnerBult');
                break;
            case 'role_Kadkhoda':
                R::Del('GamePl:NotSend_role_Kadkhoda');
                break;
            case 'role_Solh':
                R::Del('GamePl:NotSend_role_Solh');
            break;
        }

        $GetTeam = (SE::GetRoleTeam($to) ? SE::GetRoleTeam($to)  : $to) ;

        R::GetSet($GetTeam,"GamePl:user:{$user_id}:team");
        R::GetSet($to,"GamePl:user:{$user_id}:role");
        R::GetSet(true,'GamePl:ChangedUserRole:'.$user_id);
        // » در یک بازی نقشتون تغییر پیدا کنه
        self::SavePlayerAchivment($user_id,'Change_Sides_Works');
        if($to !== "role_ferqe"){
            R::GetSet((R::Get("GamePl:UserXchangeRole:{$user_id}") ?? 0 ) + 1,"GamePl:UserXchangeRole:{$user_id}");
        }
         self::$Dt->collection->games_players->updateOne(
            ['user_id' => (float) $user_id,'game_id'=> self::$Dt->game_id,'group_id'=> self::$Dt->chat_id],
            ['$set' => ['user_role' => $to, 'team' => SE::GetRoleTeam($to) ,'change_time' => time() ]]
        );

    }

    public static function CheckAlphaInGame(){
        $result = self::$Dt->collection->games_players->count(['team' => 'wolf','game_id' => self::$Dt->game_id,'group_id' => self::$Dt->chat_id,'user_state' => 1 ,'user_status' => 'on','user_role' => 'role_WolfAlpha']);
        return $result;
    }


    public static function _getLastVampire(){

        $RoleVampire = ['role_Vampire'];

        $result = self::$Dt->collection->games_players->findOne([
            'team' => 'vampire'
            ,'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$in' => $RoleVampire]
        ],[
            'sort' => ['change_time' => -1],
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }



    public static function _getPlayerINRole($role){


        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$in' => $role]
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }
    public static function _getLastMagento(){
        $MagentoRole = ['role_Magento'];


        $result = self::$Dt->collection->games_players->findOne([
            'team' => 'Firefighter'
            ,'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$in' => $MagentoRole]
        ],[
            'sort' => ['change_time' => -1],
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }
    public static function _getLastWolf(){
        $wolfRole = SE::WolfRole();
        if (R::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
            array_push($wolfRole,'role_forestQueen');
        }

        $result = self::$Dt->collection->games_players->findOne([
            'team' => 'wolf'
            ,'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$in' => $wolfRole]
        ],[
            'sort' => ['change_time' => -1],
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function BittanPlayer($user_id){
        R::GetSet($user_id,'GamePl:BittanPlayer');
    }


    public static function BittanPlayerEnchanter($user_id){
        R::GetSet((float) $user_id,'GamePl:EnchanterBittanPlayer');
    }


    public static function _getDeadMesssage($role,$name){

        switch ($role){
            case 'role_pishgo':
                return self::$Dt->LG->_('RolePishgo_eat',array("{0}" =>$name));
                break;
            case 'role_Mast':
                return self::$Dt->LG->_('RoleMast_eat',array("{0}" =>$name));
                break;
            case 'role_tofangdar':
                return self::$Dt->LG->_('RoleTofangdar_eat',array("{0}" =>$name));
                break;
            case 'role_ahmaq':
                return self::$Dt->LG->_('RoleAhmag_eat',array("{0}" =>$name));
                break;
            case 'role_karagah':
                return self::$Dt->LG->_('roleKaragh_eat',array("{0}" =>$name));
                break;
            case 'role_WolfJadogar':
                return self::$Dt->LG->_('SorcererEaten',array("{0}" =>$name));
                break;
            case 'role_enchanter':
                return self::$Dt->LG->_('EatenEnchanter',array("{0}" =>$name));
                break;
            case 'role_PishRezerv':
                return self::$Dt->LG->_('ApprenticeSeerEaten',array("{0}" =>$name));
                break;
        }

        return false;
    }

    public static function MesssageQatel($user_role,$name){

        switch ($user_role){
            case 'role_ferqe':
                return self::$Dt->LG->_('CultistKilled',array("{0}" =>$name));
                break;
            case 'role_Mast':
                return self::$Dt->LG->_('DrunkKilled',array("{0}" =>$name));
                break;
            case 'role_elahe':
                return self::$Dt->LG->_('CupidKilled',array("{0}" =>$name));
                break;
            case 'role_Ahangar':
                return self::$Dt->LG->_('BlacksmithKilled',array("{0}" =>$name));
                break;
            case 'role_Fereshte':
                return self::$Dt->LG->_('GuardianAngelKilled',array("{0}" =>$name));
                break;
            case 'role_tofangdar':
                return self::$Dt->LG->_('GunnerKilled',array("{0}" =>$name));
                break;
            case 'role_Kadkhoda':
                return self::$Dt->LG->_('MayorKilled',array("{0}" =>$name));
                break;
            case 'role_Shahzade':
                return self::$Dt->LG->_('PrinceKilled',array("{0}" =>$name));
                break;
            case 'role_pishgo':
                return self::$Dt->LG->_('SeerKilled',array("{0}" =>$name));
                break;
            default:
                return self::$Dt->LG->_('DefaultKilled',array("{0}" => $name, "{1}" => self::$Dt->LG->_('user_role',array("{0}" =>self::$Dt->LG->_($user_role."_n")))));
                break;
        }

    }


    public static function PlusTime($Second){
        $Time = R::Get('timer');
        R::GetSet(($Time + $Second),'timer');
    }

    public static function WolfCubeDead(){
        $P_Team = self::PlayerByTeam();
        $Wolf =  (count($P_Team['wolf']) > 0 ? $P_Team['wolf'] : false);
        if($Wolf){
            $WolfUserId = ($Wolf ? array_column($Wolf,'user_id') : false);
            self::EditMarkupKeyboard(false);
            self::PlusTime(30);
            R::GetSet(true, 'GamePl:WolfCubeSelect2');
            self::SendGroupMessage(false);
            R::Del('GamePl:SendNightAll');
            R::Del('GamePl:CheckNight');
            (count($P_Team['wolf']) > 1 ? R::DelKey('GamePl:Selected:Wolf:*') : R::Del('GamePl:Selected:'.$WolfUserId['0']));
            foreach ($WolfUserId as $user_id){
                R::LRem($user_id,1,'GamePl:SendNight');
            }
        }

    }

    public static function RoyceDeadSelect(){
        $P_Team = self::PlayerByTeam();
        $Cult =  (count($P_Team['ferqe']) > 0 ? $P_Team['ferqe'] : false);
        if($Cult) {
            $FerqeUserId = ($Cult ? array_column($Cult,'user_id') : false);
            self::EditMarkupKeyboard(false);
            R::GetSet(true, 'GamePl:RoyceSelectd2');
            R::DelKey('GamePl:Selected:*'); // پاک کردن انتخاب ها
            self::PlusTime(30);
            R::Del('GamePl:CheckNight');
            R::Del('GamePl:SendNightAll');
            foreach ($FerqeUserId as $user_id){
                R::LRem($user_id,1,'GamePl:SendNight');
            }
        }
    }

    public static function HunterKill($Gp_message,$User_id = false,$For = 'kill'){
        self::EditMarkupKeyboard(false);
        R::GetSet(true,'GamePl:HunterKill');
        R::GetSet($For,'GamePl:KillFor');
        // به زمان بازی اضافه میکنیم
        self::PlusTime(45);
        self::SendMessage($Gp_message);
        if($User_id == false){
            return false;
        }
        switch ($For){
            case 'kill':
            case 'shot':
                $KalanMessage = self::$Dt->LG->_('HunterShotChoice');
                $rows = self::GetPlayerNonKeyboard([$User_id], 'Kalantar_shot');
                $inline_keyboard = new InlineKeyboard(...$rows);
                $result =  Request::sendMessage([
                    'chat_id' => $User_id,
                    'text' => $KalanMessage,
                    'reply_markup' => $inline_keyboard,
                    'parse_mode' => 'HTML',
                ]);
                if($result->isOk()){
                    R::rpush($result->getResult()->getMessageId()."_".$User_id,'GamePl:MessageNightSend');
                }
                return true;
                break;
            case 'vote':

                $KalanMessage = self::$Dt->LG->_('HunterLynchedChoice');
                $rows = self::GetPlayerNonKeyboard([$User_id], 'Kalantar_shot');
                $inline_keyboard = new InlineKeyboard(...$rows);
                $result =  Request::sendMessage([
                    'chat_id' => $User_id,
                    'text' => $KalanMessage,
                    'reply_markup' => $inline_keyboard,
                    'parse_mode' => 'HTML',
                ]);
                if($result->isOk()){
                    R::rpush($result->getResult()->getMessageId()."_".$User_id,'GamePl:MessageNightSend');
                }
                return true;
                break;
        }
    }

    public static function _getLastCult(){
        $result = self::$Dt->collection->games_players->findOne([
            'team' => 'ferqeTeem'
            ,'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => 'role_ferqe'
        ],[
            'sort' => ['change_time' => -1],
        ]);

        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }

    public static function SendMessageMson($Msg){
        $result = self::$Dt->collection->games_players->find(['user_role' => 'role_feramason','user_state' => 1,'user_status'=> 'on']);
        if($result){
            $array = iterator_to_array($result);
            foreach ($array as $row){
                self::SendMessage($Msg,$row['user_id']);
            }
        }
    }
    public static function SendMasonAfterChangeRole($Name){
        $result = self::$Dt->collection->games_players->find(['user_role' => 'role_feramason','user_state' => 1,'user_status'=> 'on']);

        if($result){
            $array = iterator_to_array($result);
            $Msg = self::$Dt->LG->_('MasonConverted',array("{0}" => $Name));
            foreach ($array as $row){
                self::SendMessage($Msg,$row['user_id']);
            }
        }
    }

    public static function GetRoleRandom($not_in = []){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_role' => ['$nin' => $not_in]
        ],[ 'limit' => -1 ,'skip' => random_int( 0, (self::_getCountPlayers()) )]);

        if($result) {
            $array = iterator_to_array($result);
            if(!isset($array['0'])){
                return self::GetRoleRandom($not_in);
            }
            return $array['0'];
        }


        return false;
    }

    public static function GetUserRandom($not_in = []){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'user_id' => ['$nin' => $not_in]
        ],[ 'limit' => -1 ,'skip' => random_int( 0, (self::_getCountPlayers()) )]);

        if($result) {
            $array = iterator_to_array($result);
            if(!isset($array['0'])){
                return self::GetUserRandom($not_in);
            }
            if(in_array($array['0']['user_id'], $not_in)){
                return self::GetUserRandom($not_in);
            }
            return $array['0'];
        }


        return false;
    }


    public static function checkTime(){

        if(R::CheckExit('GamePl:SetTimer')){
            return false;
        }

        $time = R::Get('timer');

        $l = $time -  time();


        $GameStatus = R::Get('game_state');
        switch ($GameStatus) {
            case 'night':


                if(R::CheckExit('GamePl:role_lucifer:checkLucifer')){
                    return false;
                }
                $NightTime  = R::Get('night_timer');
                $S_Time  = $NightTime / 2;
                if($l > $S_Time){
                    return false;
                }

                $SendNight = R::LRange(0,-1,'GamePl:SendNight');
                if(count($SendNight) > 0){
                    $Keys = R::LRange(0,-1,'GamePl:MessageNightSend');
                    if(count($Keys) <= 0){
                        R::GetSet( time() - 5,'timer');
                    }
                }
                break;
            case 'vote':

                $CountPlayer = R::LRange(0,-1,'GamePl:SendVote');
                $CountSends = count($CountPlayer);
                if($CountSends > 1) {
                    $Keys = R::LRange(0,-1,'GamePl:MessageNightSend');
                    if (count($Keys) <= 0 ) {
                        R::GetSet(time() - 2, 'timer');
                    }
                }
                break;
        }
    }




    public static function GetAlivePlayer(){
        $Players = self::_getOnPlayers();

        $OnTeam = [];
        $OnRole = [];

        $Count['rosta'] = 0;
        $Count['wolf'] = 0;
        $Count['ferqeTeem'] = 0;
        $Count['qatel'] = 0;
        $Count['lucifer'] = 0;
        $Count['monafeq'] = 0;
        $Count['Firefighter'] = 0;
        $Count['vampire'] = 0;
        $Count['black'] = 0;
        $Count['joker'] = 0;

        foreach ($Players as $row){


            switch ($row['user_role']){
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':
                    $Count['wolf']++;
                    break;
                case 'role_BlackKnight':   
                case 'role_BrideTheDead':
                    $Count['black']++;
               break;
                case 'role_forestQueen':
                    if (R::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        $Count['wolf']++;
                    }
                    break;
                case 'role_Vampire':
                case 'role_Bloodthirsty':
                case 'role_kentvampire':
                case 'role_Chiang':
                    $Count['vampire']++;
                    break;
                default:
                    if($row['user_role'] == "role_WolfJadogar" || $row['user_role'] == "role_Honey" || $row['user_role'] == "role_enchanter"){
                        $Count['rosta']++;
                    }else{
                        if(isset($Count[$row['team']])) {
                            $Count[$row['team']]++;
                        }
                    }
                    break;
            }
            $OnRole[] = ['user_id' => $row['user_id'], 'link' => self::ConvertName($row['user_id'], $row['fullname_game']), 'user_role' => $row['user_role'], 'team' => $row['team']];
            if(!in_array($row['team'],$OnTeam)){
                if($row['team'] == "wolf"){
                    $Wolf = SE::WolfRole();
                    if (R::CheckExit('GamePl:role_forestQueen:AlphaDead')) {
                        $Wolf[] = 'role_forestQueen';
                    }

                    if(in_array($row['user_role'],$Wolf)){
                        $OnTeam[] = $row['team'];
                    }
                }elseif($row['team'] == "rosta" || $row['team'] == "ferqeTeem" || $row['team'] == "qatel" || $row['team'] == "vampire" || $row['team'] == "monafeq" || $row['team'] == "lucifer" || $row['team'] == "Firefighter" || $row['team'] == "black"){
                    $OnTeam[] = $row['team'];
                }
            }
        }

        return ['on_role' => $OnRole,'on_team' => $OnTeam,'Count' => $Count];
    }

    public static function GetRoleNotIn($Team){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'team' => ['$nin' => [$Team]]
        ]);

        if($result){
            $array = iterator_to_array($result);
            if(!$array){
                return false;
            }
            return $array['0'];
        }
    }
    public static function GetRoleNotInRandom($Team){
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            ,'group_id' => self::$Dt->chat_id
            ,'user_state' => 1
            ,'user_status' => 'on'
            ,'team' => ['$nin' => [$Team]]
        ],[ 'limit' => -1 ,'skip' => min(random_int( 0, (self::_getCountPlayers()) ),1)]);

        if($result){
            $array = iterator_to_array($result);
            if(!$array){
                return false;
            }
            return $array['0'];
        }
    }


    public static function CheckingLove(){
        $data = R::Keys('GamePl:love:*');

        $checkWolfTeam = false;
        $loverHel = [];
        $Lover =[];
        foreach ($data as $key){
            $ex =  explode(":",$key);
            $keyFull = "{$ex['1']}:{$ex['2']}:{$ex['3']}";
            $GetLover = R::Get($keyFull);

            if(R::Get("GamePl:user:{$GetLover}:team") == R::Get("GamePl:user:{$ex['3']}:team")){
                $checkWolfTeam = true;
            }
            if(!R::CheckExit('GamePl:CheckLover:'.$ex['3'])){
                $loverHel[] = $ex['3'];
                $Lover[] = $ex['3'];
            }

        }


        return ['count' => count($Lover),'wolfTeam'=> $checkWolfTeam,'heals'=>count($loverHel)];
    }

    public static function KillAllByTeam($Team = "rosta"){
        self::$Dt->collection->games->updateMany(
            ['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'team' => $Team ],
            ['$set' => ['user_state' => 0,'user_status' => 'bomber']]
        );
    }

    public static function CheckEndGame(){




        // چک میکنیم کسی هست واسه تبدیل یا نه
        if(R::CheckExit('GamePl:EnchanterBittanPlayer')){
            return false;
        }
        // چک میکنیم کسی هست واسه تبدیل یا نه
        if(R::CheckExit('GamePl:VampireBitten')){
            return false;
        }

        // چک میکنیم کسی هست واسه تبدیل یا نه
        if(R::CheckExit('GamePl:BittanPlayer')){
            return false;
        }



        $CountPlayer = self::_getCountPlayer();
        if(R::CheckExit('GamePl:DinamitInGame')){
                if(R::CheckExit('GamePl:FindBombCount')){
                    if(R::Get('GamePl:FindBombCount') == 3){
                        return 'dinamit';
                    }
                }
        }

        // اگه کسی زنده نبود بازی تموم میشه
        if($CountPlayer == 0){
            return 'nothing';
        }
        $GameMode = R::Get('GamePl:gameModePlayer');
        if($GameMode == "Bomber"){
            $ALLBomb = (int) R::Get('GamePl:BombCount');
            $PlantedBomb = (int) R::Get('GamePl:BombPlanted');
            if($PlantedBomb >= $ALLBomb ){
                return  'Bomber';
            }

            $P_Team = self::PlayerByTeam();
            $Rosta = count($P_Team['Rosta']);
            $Bomber = count($P_Team['Bomber']);
            if ($Bomber >= $Rosta) {
                self::KillAllByTeam();
                return  'Bomber';
            }

            if ($Rosta > 0 && $Bomber == 0) {
                return 'rosta';
            }

            if($Rosta == 0 && $Bomber == 0) {
                return 'nothing';
            }

            return  false;
        }


        $AliveTeam = self::GetAlivePlayer();

        $RoleOn = array_column($AliveTeam['on_role'], 'user_role');
        $Team = $AliveTeam['on_team'];
        $CountTeam = $AliveTeam['Count'];
        switch ($CountPlayer){
            case 0:
                return 'nothing';
                break;
            case 1:
                // چک میکنیم اگه ، جادوگر ،منافق، عجوزه و یا شیطان فقط زنده بود کسی برنده نشه
                if(in_array('role_WolfJadogar',$RoleOn) || in_array('role_monafeq',$RoleOn)  || in_array('role_Honey',$RoleOn)  || in_array('role_enchanter',$RoleOn) || in_array('role_Chiang',$RoleOn) || in_array('role_Mummy',$RoleOn) || in_array('role_dinamit',$RoleOn) ){

                    // » جاوگر باشیو تنها بازمانده روستا
                    if(in_array('role_WolfJadogar',$RoleOn)){
                        self::SavePlayerAchivment($AliveTeam['on_role'][0]['user_id'],'Time_to_retire');
                    }

                    if(in_array('role_monafeq',$RoleOn)) {
                        $MonafKey = array_keys($RoleOn,"role_monafeq"); $Monaf_key = $MonafKey['0'];$Monaf_name = $AliveTeam['on_role'][$Monaf_key]['link'];

                        $GroupMessage = self::$Dt->LG->_('TannerEnd', array("{0}" => $Monaf_name));
                        self::SaveMessage($GroupMessage);
                    }

                    return 'nothing';
                }

                $TeamOn = (isset($AliveTeam['on_role']['0']['team']) ? $AliveTeam['on_role']['0']['team'] : 'nothing') ;
                return $TeamOn;

                break;
            case 2:
                if(R::CheckExit('GamePl:SweetheartLove') && in_array('role_Sweetheart',$RoleOn)){
                    return 'lover';
                }

                $checkLove = self::CheckingLove();
                if($checkLove['count'] == 2 && $checkLove['heals'] > 1){
                    return 'lover';
                }

                if(in_array('role_BlackKnight',$RoleOn) || in_array('role_BrideTheDead',$RoleOn) || in_array('role_dian',$RoleOn) ){
                    $GeN = self::GetRoleNotIn('black');
                    if($GeN){
                        self::UserDead($GeN['user_id'],'black');
                    }
                    return 'black';
                }


                if(in_array('role_davina',$RoleOn) && !in_array('role_Qatel',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_davina");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    self::UserDead($AliveTeam['on_role'][$Qatel_key]['user_id'],'kill');

                    return $AliveTeam['on_role'][$AnyKey]['team'];
                }

                // چک میکنیم اگه ، منافق با جادو ... زنده بود کسی برنده نشه
                if(in_array('role_monafeq',$RoleOn) and in_array('role_WolfJadogar',$RoleOn)   || in_array('role_Honey',$RoleOn)  || in_array('role_enchanter',$RoleOn)|| in_array('role_davina',$RoleOn) || in_array('role_Joker',$RoleOn) || in_array('role_Harly',$RoleOn)){
                    return 'nothing';
                }
                // اگه آتیش با هرکی ب زنده موند
                if(in_array('role_Firefighter',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_Firefighter");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'Firefighter');
                    $Anyname = $AliveTeam['on_role'][$AnyKey]['link'];
                    $GroupMessage = self::$Dt->LG->_('FirefighterEnd',array("{0}" => $Anyname));
                    self::SaveMessage($GroupMessage);
                    return 'Firefighter';
                }
                // اگه ملکه بخ از تیم، آتیش با هرکی ب زنده موند
                if(in_array('role_IceQueen',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_IceQueen");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'Firefighter');
                    $Anyname = $AliveTeam['on_role'][$AnyKey]['link'];
                    $GroupMessage = self::$Dt->LG->_('IceFirefighterEnd',array("{0}" => $Anyname));
                    self::SaveMessage($GroupMessage);
                    return 'Firefighter';
                }


                // اگه ومپ اصیل آزاد بود و با کلانتر زنده مونده بود
                if(R::CheckExit('GamePl:Bloodthirsty')){
                    if(in_array('role_kalantar',$RoleOn) && in_array('role_Bloodthirsty',$RoleOn)){
                        $WolfData = self::GetRoleNotIn('vampire');$WolfName = self::ConvertName($WolfData['user_id'],$WolfData['fullname_game']);
                        $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];
                        // کلانتر شانسشو امتحان میکنه اگه تیر بزنه روستا میبره وگرنه برنده بازی گرگه
                        if(self::R(100) < SE::_s('HunterKillVampireChanceBase')){
                            $GroupMessage = self::$Dt->LG->_('HunterKillsVampireEnd',array("{0}" =>  $Kalan_name,"{1}" => $WolfName));
                            self::SaveMessage($GroupMessage);
                            self::UserDead($WolfData['user_id'],'shot');

                            return 'rosta';
                        }
                        $GroupMessage = self::$Dt->LG->_('VampireKillsHunterEnd',array("{0}" => $Kalan_name,"{1}" => $WolfName));
                        self::SaveMessage($GroupMessage);
                        self::UserDead($Kalan_id,'vampire');
                        return 'vampire';
                    }
                }
                // اگه ومپایر با کلانتر زنده موند
                if((in_array('role_kalantar', $RoleOn) && in_array('role_Vampire', $RoleOn)) || (in_array('role_Bloodthirsty', $RoleOn) && in_array('role_kalantar', $RoleOn))){
                    $WolfData = self::GetRoleNotIn('vampire');$WolfName = self::ConvertName($WolfData['user_id'],$WolfData['fullname_game']);
                    $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];
                    // کلانتر شانسشو امتحان میکنه اگه تیر بزنه روستا میبره وگرنه برنده بازی گرگه
                    if(self::R(100) < SE::_s('HunterKillVampireChanceBase')){
                        $GroupMessage = self::$Dt->LG->_('HunterKillsVampireEnd',array("{0}" => $Kalan_name,"{1}" => $WolfName));
                        self::SaveMessage($GroupMessage);
                        self::UserDead($WolfData['user_id'],'shot');
                        return 'rosta';
                    }
                    $GroupMessage = self::$Dt->LG->_('VampireKillsHunterEnd',array("{0}" =>  $Kalan_name,"{1}" => $WolfName));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($Kalan_id,'vampire');
                    return 'vampire';
                }


                if(in_array('role_kalantar',$RoleOn) && in_array('wolf',$Team)){
                    $WolfData = self::GetRoleNotIn('rosta');$WolfName = self::ConvertName($WolfData['user_id'],$WolfData['fullname_game']);
                    $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];
                    // کلانتر شانسشو امتحان میکنه اگه تیر بزنه روستا میبره وگرنه برنده بازی گرگه
                    if(self::R(100) < SE::_s('HunterKillWolfChanceBase')){
                        $GroupMessage = self::$Dt->LG->_('HunterKillsWolfEnd',array("{0}" =>  $Kalan_name, "{1}" => $WolfName));
                        self::SaveMessage($GroupMessage);
                        self::UserDead($WolfData['user_id'],'shot');
                        return 'rosta';
                    }
                    $GroupMessage = self::$Dt->LG->_('WolfKillsHunterEnd',array("{0}" => $Kalan_name,"{1}"=> $WolfName));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($Kalan_id,'eat');
                    return 'wolf';
                }
                // اگه کلانتر با قاتل زنده بود ، کلانتر تیر بزنه به قاتل، کسی برنده نمیشه
                if(in_array('role_kalantar',$RoleOn) && in_array('role_Qatel',$RoleOn)){
                    $QatelKey = array_keys($RoleOn,"role_Qatel"); $Qatel_key = $QatelKey['0'];$Qatel_name = $AliveTeam['on_role'][$Qatel_key]['link'];$Qatel_id = $AliveTeam['on_role'][$Qatel_key]['user_id'];
                    $KalanKey = array_keys($RoleOn,"role_kalantar"); $Kalan_key = $KalanKey['0'];$Kalan_name = $AliveTeam['on_role'][$Kalan_key]['link'];$Kalan_id = $AliveTeam['on_role'][$Kalan_key]['user_id'];

                    $GroupMessage = self::$Dt->LG->_('SKHunterEnd',array("{0}" => $Qatel_name,"{1}" => $Kalan_name));
                    self::SaveMessage($GroupMessage);
                    self::SavePlayerAchivment($Qatel_id,'Double_Kill');
                    self::SavePlayerAchivment($Kalan_id,'Double_Kill');
                    self::UserDead($Qatel_id,'shot');
                    self::UserDead($Kalan_id,'kill');
                    return 'nothing';
                }
                // اگه شکارچی با یه فرقه گرا زنده بمونه شکارچی فرقه رو میکشه و میبره
                if(in_array('role_shekar',$RoleOn) && in_array('role_ferqe',$RoleOn)){
                    $shekarKey = array_keys($RoleOn,"role_shekar"); $shekar_key = $shekarKey['0'];$shekar_name = $AliveTeam['on_role'][$shekar_key]['link'];$shekar_id = $AliveTeam['on_role'][$shekar_key]['user_id'];
                    $ferqeKey = array_keys($RoleOn,"role_ferqe"); $ferqe_key = $ferqeKey['0'];$ferqe_name = $AliveTeam['on_role'][$ferqe_key]['link'];$ferqe_id = $AliveTeam['on_role'][$ferqe_key]['user_id'];
                    $GroupMessage = self::$Dt->LG->_('CHKillsCultistEnd',array("{0}" => $ferqe_name,"{1}" => $shekar_name));
                    self::SaveMessage($GroupMessage);
                    self::UserDead($ferqe_id,'CultHuner');
                    return 'rosta';
                }

                if(in_array('role_Qatel',$RoleOn) && !in_array('role_Archer',$RoleOn)){
                    $SearchShkey = array_search("role_Qatel",$RoleOn );
                    $Qatel_key = $SearchShkey;
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);
                    $Killer_name = $AliveTeam['on_role'][$Qatel_key]['link'];
                    if(isset($AliveTeam['on_role'][$AnyKey])) {
                      $GroupMessage = self::$Dt->LG->_('SerialKillerWinsOverpower', array("{0}" => $Killer_name, "{1}" => $AliveTeam['on_role'][$AnyKey]['link']));
                     self::SaveMessage($GroupMessage);
                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'kill');
                    }
                    return 'qatel';
                }

                if(in_array('role_Archer',$RoleOn) && !in_array('role_Qatel',$RoleOn)){
                    $SearchShkey = array_keys($RoleOn, "role_Archer");
                    $Qatel_key = $SearchShkey['0'];
                    $AnyKey = ($Qatel_key == 0 ? 1 : 0);

                    self::UserDead($AliveTeam['on_role'][$AnyKey]['user_id'],'kill');
                    return 'qatel';
                }
                if(in_array('wolf',$Team)){
                    $GeN = self::GetRoleNotIn('wolf');
                    if($GeN){
                    self::UserDead($GeN['user_id'],'eat');
                    }
                    return 'wolf';
                }

                if(in_array('ferqeTeem',$Team)){
                    $CountTeams = self::CountRole('role_ferqe');
                    if($CountTeams == 2){
                        return 'ferqeTeem';
                    }
                    $SearchShkey = array_keys($RoleOn, "role_ferqe");
                    if(isset($SearchShkey['0'])) {
                        $Ferqe_key = $SearchShkey['0'];
                        $AnyKey = ($Ferqe_key == 0 ? 1 : 0);
                        self::ConvertPlayer($AliveTeam['on_role'][$AnyKey]['user_id'], 'role_ferqe');
                        return 'ferqeTeem';
                    }else{
                        return 'ferqeTeem';
                    }
                }
                break;
            default:
                break;
        }

        if(in_array('role_Joker',$RoleOn) || in_array('role_Harly',$RoleOn)){
            if(R::CheckExit('GamePl:FindedBook')){
                if((int) R::Get('GamePl:FindedBook') >= 3){
                    return 'joker';
                }
            }elseif($CountPlayer <= 3){
                return 'joker';
            }
        }

        // اگه قاتل بود بازی تموم نمیشه مسلما
        if(in_array('qatel',$Team)){
            // اگر کماندار با قاتل بود
            if(in_array('role_Qatel',$RoleOn) && in_array('role_Archer',$RoleOn)){
                // اگر تعداد تیم قاتل برابر یا مساوی با بقییه تیم ها بود
                if($CountTeam['qatel'] >= $CountTeam['wolf']+$CountTeam['rosta']+$CountTeam['ferqeTeem']+$CountTeam['Firefighter']+$CountTeam['vampire']+$CountTeam['monafeq']){
                    // چک میکنیم اگه شب بعد کماندار تیر داره و تیم قاتل برابر با بقییه تیم هاست تیم قاتل میبره
                    $ArcherSend = R::Get('GamePl:ArcherSendFor');
                    $Night_now = R::Get('GamePl:Night_no') + 1;
                    if($ArcherSend == $Night_now){

                        return 'qatel';
                    }
                }
            }
            return false;
        }

        if($CountTeam['black'] > ($CountTeam['rosta']+$CountTeam['Firefighter']+$CountTeam['black']+$CountTeam['monafeq']) && $CountTeam['wolf'] == 0 && $CountTeam['vampire'] == 0 && $CountTeam['ferqeTeem'] == 0 ){
            return 'black';
        }



        if($CountTeam['Firefighter'] > ($CountTeam['rosta']+$CountTeam['black']+$CountTeam['monafeq']) && $CountTeam['wolf'] == 0 && $CountTeam['vampire'] == 0 && $CountTeam['ferqeTeem'] == 0 ){
            return 'Firefighter';
        }


        // اگه پادشاه آتش بود بازی تموم نمیشه مسلما
        if(in_array('role_Firefighter',$RoleOn)){
            return false;
        }
        // اگه پادشاه آتش بود بازی تموم نمیشه مسلما
        if(in_array('role_IceQueen',$RoleOn)){
            return false;
        }

        if($CountTeam['ferqeTeem'] > 0 && $CountTeam['Firefighter'] == 0 && $CountTeam['black'] == 0 && $CountTeam['monafeq'] == 0 && $CountTeam['rosta'] == 0 && $CountTeam['wolf'] == 0 &&  $CountTeam['vampire'] == 0){
            return 'ferqeTeem';
        }

        // اگه فقط یه تیم موند اون تیم برندست
        if(count($Team) == 1){
            return $Team['0'];
        }


        if($CountTeam['wolf'] >= ($CountTeam['rosta']+$CountTeam['black']+$CountTeam['ferqeTeem']+$CountTeam['Firefighter']+$CountTeam['vampire']+$CountTeam['monafeq'])){
            $checkLove = self::CheckingLove();
            if($checkLove['count'] == 2 and $checkLove['heals'] > 1) {
                $TeamLove = $checkLove['wolfTeam'];
                $TotalOnTeam = $CountTeam['rosta']+$CountTeam['ferqeTeem']+$CountTeam['Firefighter']+$CountTeam['vampire']+$CountTeam['monafeq'];
                if (in_array('role_tofangdar', $RoleOn) && R::CheckExit('GamePl:GunnerBult')) {
                    if($TotalOnTeam == $CountTeam['wolf'] || (($TotalOnTeam + 1) == $CountTeam['wolf'] && $TeamLove)){
                        return false;
                    }
                }
            }

            return 'wolf';
        }

        if($CountTeam['vampire'] >= ($CountTeam['rosta']+$CountTeam['black']+$CountTeam['Firefighter']+$CountTeam['monafeq']) && $CountTeam['wolf'] == 0 && $CountTeam['ferqeTeem'] == 0){
            return 'vampire';
        }

        if($CountTeam['wolf'] == 0 && $CountTeam['Firefighter'] == 0 && $CountTeam['black'] == 0 && $CountTeam['ferqeTeem'] == 0  && $CountTeam['vampire'] == 0 && $CountTeam['Firefighter'] == 0  && $CountTeam['rosta'] > 0){
            return 'rosta';
        }

        return false;
    }


    public static function GameEndMessage($Winner){

        $WinnerTeam = $Winner;

        switch ($WinnerTeam){
            case 'rosta':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_rosta'),
                    'caption' => self::$Dt->LG->_('winner_rosta'),
                    'parse_mode' => 'HTML',
                ]);
                break;

                case 'black':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('winner_black'),
                    'caption' => self::$Dt->LG->_('win_black'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'joker':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_joker'),
                    'caption' => self::$Dt->LG->_('winner_joker'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'ferqeTeem':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_ferqe'),
                    'caption' => self::$Dt->LG->_('winner_ferqeTeem'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'wolf':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_wolf'),
                    'caption' => self::$Dt->LG->_('winner_wolf'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'nothing':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('nothing'),
                    'caption' => self::$Dt->LG->_('winner_nothing'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'qatel':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_qatel'),
                    'caption' => self::$Dt->LG->_('winner_qatel'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'lover':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_lover'),
                    'caption' => self::$Dt->LG->_('winner_lover'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'monafeq':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_trap'),
                    'caption' => self::$Dt->LG->_('winner_monafeq'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'Firefighter':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_firefighter'),
                    'caption' => self::$Dt->LG->_('win_Firefighter'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'vampire':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_vampire'),
                    'caption' => self::$Dt->LG->_('win_vampire'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'SweetheartLove':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_lover'),
                    'caption' => self::$Dt->LG->_('winner_lover'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'Bomber':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('winner_Bomber'),
                    'caption' => self::$Dt->LG->_('winner_Bomber'),
                    'parse_mode' => 'HTML',
                ]);
                break;
            case 'dinamit':
                return Request::sendVideo([
                    'chat_id' => self::$Dt->chat_id,
                    'video' => R::RandomGif('win_dinamit'),
                    'caption' => self::$Dt->LG->_('win_dinamit'),
                    'parse_mode' => 'HTML',
                ]);
                break;
        }

        return false;
    }

    public static function CheckLover($user_id,$Team){
        if(R::CheckExit('GamePl:love:'.$user_id)){

            $love= R::Get('GamePl:love:'.$user_id);
            $Lover = R::Get('GamePl:love:'.$love);


            if(R::Get('GamePl:gameModePlayer') == "Romantic"){
                if((R::CheckExit('GamePl:LoNW:'.$user_id) && $Team == "lover" )|| (R::CheckExit('GamePl:LoNW:'.$Lover)  && $Team == "lover") ){
                    return false;
               }
            }

            $loveTeam =  R::Get('GamePl:user:'.$Lover.":team");
            $loverTeam = R::Get('GamePl:user:'.$love.":team");

                if ($loveTeam == $Team || $loverTeam == $Team || $Team == "lover") {
                    return true;
                 }


            return false;
        }
        return false;
    }


    public static function TopPlayer($UserRole,$TopPlayers,$isOn,$Win){
        $AllPlayer = self::_getCountPlayers();
        $PlayerOn = self::_getCountPlayer();
        $TopRole = SE::_GetTop($UserRole);

        $TopPlayer = ($AllPlayer / $PlayerOn) * $Win + $TopRole + $isOn;
        $Top = (is_nan(floor($TopPlayer)) ? : floor($TopPlayer));
        $TopEnd = ($Win == 1 ? $TopPlayers + $Top : $TopPlayers - $Top);
        $TopEnd = (is_nan($TopEnd) ? 0 : $TopEnd);

        return ['TopUser' => ($Win == 1 ?  "+$Top" :  "-$Top"),'TotalTop' => $TopEnd];
    }

    public static function AddPlayerAchio($user_id,$AchioKey){

    }

    public static function CheckPlayerAchio($user_id,$AchioKey){
        //achievement
    }

    public static function GetLevelUPUser($xp){
        $FXp = $xp;

        $Level = 1;
        if($FXp > 1000 && $FXp < 2000){
            $Level = 2;
        }elseif($FXp > 2000 && $FXp < 4000){
            $Level = 3;
        }elseif($FXp > 4000 && $FXp < 7000){
            $Level = 4;
        }elseif($FXp > 7000 && $FXp < 11000){
            $Level = 5;
        }elseif($FXp > 11000 && $FXp < 16000){
            $Level = 6;
        }elseif($FXp > 16000 && $FXp < 22000){
            $Level = 7;
        }elseif($FXp > 22000 && $FXp < 29000){
            $Level = 8;
        }elseif($FXp > 29000 && $FXp < 37000){
            $Level = 9;
        }elseif($FXp > 37000 && $FXp < 46000){
            $Level = 10;
        }elseif($FXp > 46000 && $FXp < 51000){
            $Level = 11;
        }elseif($FXp > 51000 && $FXp < 57000){
            $Level = 12;
        }elseif($FXp > 57000 && $FXp < 64000){
            $Level = 13;
        }elseif($FXp > 64000 && $FXp < 72000){
            $Level = 14;
        }elseif($FXp > 72000 && $FXp < 77000){
            $Level = 15;
        }elseif($FXp > 77000 && $FXp < 83000){
            $Level = 16;
        }elseif($FXp > 83000 && $FXp < 90000){
            $Level = 17;
        }elseif($FXp > 90000 && $FXp < 98000){
            $Level = 18;
        }elseif($FXp > 98000 && $FXp < 107000){
            $Level = 19;
        }elseif($FXp > 107000 && $FXp < 112000){
            $Level = 20;
        }elseif($FXp > 112000 && $FXp < 118000){
            $Level = 21;
        }elseif($FXp > 118000 && $FXp < 125000){
            $Level = 22;
        }elseif($FXp > 125000 && $FXp < 132000){
            $Level = 23;
        }elseif($FXp > 132000 && $FXp < 140000){
            $Level = 24;
        }elseif($FXp > 140000 && $FXp < 148000){
            $Level = 25;
        }elseif($FXp > 148000 && $FXp < 157000){
            $Level = 26;
        }elseif($FXp > 157000 && $FXp < 162000){
            $Level = 27;
        }elseif($FXp > 162000 && $FXp < 168000){
            $Level = 28;
        }elseif($FXp > 168000 && $FXp < 176000){
            $Level = 29;
        }elseif($FXp > 176000 && $FXp < 230000){
            $Level = 30;
        }elseif($FXp > 230000 && $FXp < 340000){
            $Level = 31;
        }elseif($FXp > 340000 && $FXp < 440000){
            $Level = 32;
        }elseif($FXp > 440000 && $FXp < 560000){
            $Level = 33;
        }elseif($FXp > 560000 && $FXp < 670000){
            $Level = 34;
        }elseif($FXp > 670000 && $FXp < 790000){
            $Level = 35;
        }elseif($FXp > 790000 && $FXp < 880000){
            $Level = 36;
        }elseif($FXp > 880000 && $FXp < 990000){
            $Level = 37;
        }elseif($FXp > 990000 && $FXp < 1010000){
            $Level = 38;
        }elseif($FXp > 1010000 && $FXp < 1025000){
            $Level = 39;
        }elseif($FXp > 1025000 && $FXp < 1125000){
            $Level = 40;
        }elseif($FXp > 1125000 && $FXp < 1225000){
            $Level = 41;
        }elseif($FXp > 1225000 && $FXp < 1325000){
            $Level = 42;
        }elseif($FXp > 1325000 && $FXp < 1425000){
            $Level = 43;
        }elseif($FXp > 1425000 && $FXp < 1525000){
            $Level = 44;
        }elseif($FXp > 1525000 && $FXp < 1625000){
            $Level = 45;
        }elseif($FXp > 1625000 && $FXp < 1725000){
            $Level = 46;
        }elseif($FXp > 1725000 && $FXp < 1825000){
            $Level = 47;
        }elseif($FXp > 1825000 && $FXp < 1925000){
            $Level = 48;
        }elseif($FXp > 1925000 && $FXp < 2125000){
            $Level = 49;
        }elseif($FXp > 2125000){
            $Level = 50;
        }

        return $Level;
    }
    public static function SaveUserState($Detial,$Win,$AllPl){
        $Win = ($Win == "win" ? 1 : 0);
        $Los = ($Win == "lost" ? 1 : 0);
        $IsOn = ($Detial['user_state'] == 1 ? 1 : 0);

        $result = self::$Dt->collection->Players->findOne(['user_id' => (float) $Detial['user_id']]);
        if($result) {
            $array = iterator_to_array($result);

            $Legue = 0;
            if($Win  == 1){
                $Legue = 5;
            }
            if($IsOn == 1 && $Win == 1){
                $Legue = 7;
            }
            if($Legue > 0){
                self::SaveLeagueData($Detial,$Legue);
            }

            $UserLevel = (!is_numeric($array['Site_Username']) ? 1 : $array['Site_Username']) ;
            $UserXp = (!is_numeric($array['Site_Password']) ? 0 : $array['Site_Password']) ;
            $Xp = 50;
            $plus = 0;
            $LevelPlus = 1.2;


            if($Win){

                switch ($Detial['team']){
                    case 'rosta':
                        $plus = 25;
                        break;
                    case 'ferqeTeem':
                        $plus = 35;
                        break;
                    case 'qatel':
                        $plus = 40;
                        break;
                    case 'wolf':
                        $plus = 30;
                        break;
                    case 'Firefighter':
                        $plus = 50;
                        break;
                    case 'vampire':
                        $plus = 45;
                        break;
                }

            }


            if($UserLevel  < 10 and  $UserLevel > 5){
                $LevelPlus = 2.1;
            }elseif($UserLevel  < 15 and  $UserLevel > 10){
                $LevelPlus = 3.3;
            }elseif($UserLevel  < 20 and  $UserLevel > 15){
                $LevelPlus = 4.2;
            }elseif($UserLevel  < 25 and  $UserLevel > 20){
                $LevelPlus = 5.1;
            }elseif($UserLevel  < 30 and  $UserLevel > 25){
                $LevelPlus = 6;
            }elseif($UserLevel  > 30 and  $UserLevel < 40){
                $LevelPlus = 6.80;
            }elseif($UserLevel  > 40 and  $UserLevel <= 50){
                $LevelPlus = 8;
            }

            $finalXp = (round($Xp) + round($plus)) * $LevelPlus;

            $FXp = round($UserXp + $finalXp);
            $UserLevelN = self::GetLevelUPUser($FXp);

            if($UserLevelN > $UserLevel){
                $LAng = self::$Dt->L->_('NewLevel',array("{0}" => self::$Dt->L->_('level_'.$UserLevel),"{1}" => self::$Dt->L->_('level_'.$UserLevelN),"{2}"=> self::ConvertName(1609838807,'Eвяαнιм👑')  ));
                self::SendMessage($LAng,$Detial['user_id']);
            }

            //$TopPlayer = self::TopPlayer($Detial['user_role'],$array['top'],$IsOn,$Win);

            //  $UserIdles = $TopPlayer['TopUser'];

            //  $T_Top=  round(($TopPlayer['TotalTop'] < 0 ? 0 : $TopPlayer['TotalTop']));
            //   if($T_Top > 0) {
            //  $Lang = ($UserIdles < 0 ? "امتیاز کم شد  {$UserIdles}  امتیاز کلی کنونی {$T_Top}" : "امتیاز اضافه شد  {$UserIdles}  امتیاز کلی کنونی {$T_Top}");
            //    R::rpush($Lang, 'UserIdles:' . $Detial['user_id']);
            // }

            // در یک بازی 10 نفره یا بیشتر با نقش مست زنده بماند.
            if($AllPl >= 10 && $Detial['user_role'] == "role_Mast" && $Detial['user_state'] == 1){
                self::SavePlayerAchivment($Detial['user_id'],'Wobble_Wobble');
            }
            // در یک بازی 20 نفره یا بیشتر از 20نفره زنده بمانید و یک رای هم نگیرید
            if($AllPl >= 20 && R::CheckExit('GamePl:VoteList:'.$Detial['user_id']) == false && $Detial['user_state'] == 1){
                self::SavePlayerAchivment($Detial['user_id'],'Inconspicuous');
            }


            $GameMode = R::Get('GamePl:gameModePlayer');
            if($GameMode == "Mighty"){
                self::SavePlayerAchivment($Detial['user_id'],'Welcome_to_the_Asylum');
            }
            if($AllPl == 5){
                self::SavePlayerAchivment($Detial['user_id'],'Introvert');
            }
            if($AllPl == 35){
                self::SavePlayerAchivment($Detial['user_id'],'Enochlophobia');
            }
            if($array['total_game'] + 1 > 0){
                self::SavePlayerAchivment($Detial['user_id'],'Welcome_to_Hell');
            }
            if($array['total_game'] + 1 == 100){
                self::SavePlayerAchivment($Detial['user_id'],'Dedicated');
            }
            if($array['total_game'] + 1 == 1000){
                self::SavePlayerAchivment($Detial['user_id'],'Obsessed');
            }
            if(R::CheckExit('GamePl:AmirKarimiInGame')){
                self::SavePlayerAchivment($Detial['user_id'],'Just_a_Beareen_Teams');
            }
            // توی 100 بازی زنده بمونید
            if($array['SurviveTheGame'] + $IsOn == 100){
                self::SavePlayerAchivment($Detial['user_id'],'Survivalist');
            }

            // در نقش ناتاشا 5 شب به خونه 5 بازیکن متفاوت و سیف بروید و خونه نمانید (اسکیپ نزنید)
            if(R::CheckExit('GamePl:VisitSafeCountFaheshe') && $Detial['user_role'] == "role_faheshe"){
                if(R::Get('GamePl:VisitSafeCountFaheshe') >= 5 ){
                    self::SavePlayerAchivment($Detial['user_id'],'Promiscuous');
                }
            }

            // یکی از حداقل دو ماسون باقی مانده در یک بازی باشید
            if($Detial['user_role'] == "role_feramason" && $Detial['user_state'] == 1){
                $GetTeam = self::PlayerByTeam();
                if(count($GetTeam['Fermason']) >= 2){
                    self::SavePlayerAchivment($Detial['user_id'],'Mason_Brother');
                }
            }

            // تغییر نقش دو بار در یک بازی- تبدیل فرقه شمارش نمی شود
            if(R::CheckExit("GamePl:UserXchangeRole:{$Detial['user_id']}")){
                if(R::Get("GamePl:UserXchangeRole:{$Detial['user_id']}") >= 2){
                    self::SavePlayerAchivment($Detial['user_id'],'Double_Shifter');
                }
            }


            self::$Dt->collection->Players->updateOne(
                ['user_id' => (float) $Detial['user_id']],
                ['$set' => [
                    'total_game' => $array['total_game'] + 1
                    ,'LoserGames'=> $array['LoserGames'] + $Los
                    ,'SlaveGames' => $array['SlaveGames'] + $Win
                    , 'SurviveTheGame' => $array['SurviveTheGame'] + $IsOn
                    //, 'top' => ($TopPlayer['TotalTop'] < 0 ? 0 : $TopPlayer['TotalTop'])
                    , 'Site_Username' => $UserLevelN
                    , 'Site_Password' => $FXp
                ]]
            );

            return $array['total_game'];
        }
    }

    public static function  SaveLeagueData($player, $score = 0){
        $cn = self::$Dt->collection->leagueData;
     
        $Date =  new \MongoDB\BSON\UTCDateTime();


        $result = $cn->findOne(['user_id' => $player['user_id']]);
        if($result){
            $array = iterator_to_array($result);
            $cn->updateOne(array("user_id"=> $player['user_id']),   ['$set' => ['score' => ($array['score']+$score ) ,'updated_time' => $Date,   'update_jdate' => jdate('Y-m-d H:i:s')]]);
            return true;
        }

        $cn->insertOne([
            'user_id' => $player['user_id'],
            'score' => $score,
            'date_save' => $Date,
            'updated_time' => $Date,
            'update_jdate' => jdate('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public static function SendListEndGame($Winner){
        $Players = self::_getPlayers();
        $Re = [];
        $NobesCount = 0;
        $AllPlayer = self::_getCountPlayers();
        $PlayerOn = self::_getCountPlayer();
        $winners = [];

        foreach ($Players as $row){
            //شرکت در یک بازی که هیچ برنده ای ندارد
            if($Winner == "nothing"){
                self::SavePlayerAchivment($row['user_id'],'Death_Village');
            }


            if($row['user_state'] == 1) {
                // ثبت زمان بازی کردن کاربر
                $RNo = R::NoPerfix();
                $TimeGame = time() - R::Get('GamePl:StartedTime');
                $left_times = ($RNo->exists('userGameTime:'.$row['user_id']) ? $RNo->get('userGameTime:'.$row['user_id']) : 0);
                $RNo->set('userGameTime:' . $row['user_id'], $left_times + $TimeGame);
            }

            $CheckWin =  (self::CheckLover($row['user_id'],$Winner) == true ? "win" : ($row['user_role'] == "role_Hamzad" ? "lost" : ($row['user_state'] == 2 ? "smite" :  ($row['team'] == $Winner ? "win" : "lost"))));

            if($row['user_role'] == "role_Watermelon"){
                $CheckWin = "win";
            }
            if($row['user_role'] == "role_dozd"){
                $CheckWin = "win";
            }

            // از اول بازی فرقه گرا باشی و زنده بمونی و ببری
            if(($row['user_role'] == "role_ferqe" && !R::CheckExit('GamePl:ChangedUserRole:' . $row['user_id']) && $row['user_state'] == 1 && $CheckWin == "win") || ($row['user_role'] == "role_Royce" && !R::CheckExit('GamePl:ChangedUserRole:' . $row['user_id']) && $row['user_state'] == 1 && $CheckWin == "win")){
                self::SavePlayerAchivment($row['user_id'],'Cult_Leader');
            }

            $WinOrLost = ($CheckWin == "win" ? self::$Dt->LG->_('winner') : ($CheckWin == "lost" ? self::$Dt->LG->_('loset') : self::$Dt->LG->_('is_smited')));
            if($CheckWin == 'win'){
                array_push($winners,$row['user_id']);
            }
            $OnOrDead = ($row['user_state'] == 1 ? self::$Dt->LG->_('is_on')."-" : self::$Dt->LG->_('is_dead')."-");
            $Lover = (R::CheckExit('GamePl:love:'.$row['user_id'])  ? "❤️" : "");


            $UserRole = (R::Get('expose_role') == "all" ? "*".($row['user_role'] !== "" ? self::$Dt->LG->_($row['user_role']."_n") : "Error")."*" : "");
            $Name =  self::ConvertName($row['user_id'],$row['fullname_game'],true);
            $End = $Name.": ".$Lover." ".$OnOrDead.$UserRole." ".$WinOrLost;
            $CountGame = self::SaveUserState($row,$CheckWin,$AllPlayer);
            if($CountGame < 50){
                $NobesCount++;
            }

            // » بازی 35نفر رو با نقش قاتل زنجیره ای پیروز بشید
            if($Winner == "qatel" && $row['user_role'] == "role_Qatel" && $row['user_state'] == 1 && $AllPlayer >= 35 ){
                self::SavePlayerAchivment($row['user_id'],'Psychopath_Killer');
            }
            array_push($Re,$End);
        }


        $GameMode = R::Get('GamePl:gameModePlayer');
        if($GameMode == 'coin'){
            $AllCoin = ((int) $AllPlayer  * 10) - 5;
            $countWinner = count($winners);
            $FonPlayerOnCoin = round($AllCoin / $countWinner);
            foreach($winners as $user_id){
                $result = self::$Dt->collection->Players->findOne(['user_id' => (float)$user_id]);
                if($result) {
                    $array = iterator_to_array($result);
                    self::UpdateCoins((((int) $array['credit']) + ((int) $FonPlayerOnCoin)), $user_id);
                    Request::sendMessage([
                        'chat_id' => $user_id,
                        'text' => self::$Dt->L->_('WinCoinEndGame', array("{0}" => $FonPlayerOnCoin)),
                        'disable_web_page_preview' => 'true',
                        'parse_mode' => 'HTML'
                    ]);
                }
            }

        }
        $TimeToLeft = (time() - R::Get('GamePl:StartedTime'));

        $GroupMessage = self::$Dt->LG->_('endGame',array("{0}" =>"{$PlayerOn}/$AllPlayer","{1}" => implode(PHP_EOL,$Re),"{2}" => gmdate("G:i:s", $TimeToLeft)));

        self::SendMessage($GroupMessage,false,false,true);


        // ذخیره اطلاعات بازی گروه
        self::GroupStats(['game_time' => $TimeToLeft,'player_count' => $AllPlayer ,'nobes_player' => $NobesCount]);

        self::SaveGameEndData($Players,$AllPlayer);


        return $Re;
    }

    public static function UpdateCoins($coin,$user_id){
        self::$Dt->collection->Players->updateOne(
            ['user_id' => (float)$user_id],
            ['$set' => ['credit' => $coin]]
        );
    }

    public static function GroupStats($Data){
        $afked = (int) (R::CheckExit('GamePl:FkPlayer') ? R::Get('GamePl:FkPlayer') : 0);
        $TotalJoinTime = ((int) R::Get('GamePl:GamePl:EndJoinTimeGame') - (int) R::Get('GamePl:StartGameAt') );
        self::$Dt->collection->group_stats->insertOne([
            'group_id'       =>    self::$Dt->chat_id,
            'group_name'     =>    R::Get('group_name'),
            'game_mode'      =>    self::$Dt->game_mode,
            'group_lang'     =>    self::$Dt->def_lang,
            'game_id'        =>    self::$Dt->game_id,
            'joinTime'       =>      floor($TotalJoinTime / 60),
            'player_count'   =>    $Data['player_count'],
            'game_time'      =>     floor($Data['game_time'] / 60),
            'nobes_Player'   =>    $Data['nobes_player'],
            'afked_player'   =>    ($afked > 0 ? $afked : 1),
            'm_date'         =>    date('Y-m-d H:i:s'),
            'k_date'         =>    jdate('Y-m-d'),
            'k_time'         =>    jdate('H:i:s'),
            'time'           =>    time()
        ]);
    }

    public static function GamedEnd($Winner){

        R::GetSet(true,'GamePl:GameIsEnd');
        // اگه پیامی بود ویرایش میکنیم
        self::EditMarkupKeyboard(false);
        // اگه پیامی بود بفرست
        self::SendGroupMessage(false);
        // پیام بردن رو بفرست تو گروه
        self::GameEndMessage($Winner);
        // لیست برد و باخت رو هم میفرستیم تو بازی
        self::SendListEndGame($Winner);
        // بازی گروه رو ببند
        self::UnlockAllPlayerMute();
        self::EditMarkupEnd();
        self::GroupClosedThGame();

        sleep(2);
    }

    public static function SaveKillWolf($wolfTeam,$Detial){

        foreach ($wolfTeam as $row){
            if(R::CheckExit('GamePl:Selected:'.$Detial['user_id'])){
                self::SaveGameActivity($Detial,'eat',['user_id'=> $row['user_id'],'fullname'=> $row['Link']]);
            }
        }
    }
    public static function SaveKillVampire($vampireTeam,$Detial){

        foreach ($vampireTeam as $row){
            if(R::CheckExit('GamePl:Selected:'.$Detial['user_id'])){
                self::SaveGameActivity($Detial,'vampire',['user_id'=> $row['user_id'],'fullname'=> $row['Link']]);
            }
        }
    }
    public static function SaveVoteKillVote($Array,$Detial){
        if(is_array($Array)){
            foreach ($Array as $row){
                if(!empty($row['name']) && $row['user_id']) {
                    self::SaveGameActivity($Detial, 'vote_kill', ['user_id' => $row['user_id'], 'fullname' => $row['name']]);
                }
            }
        }
    }


    public static function SaveKill($killer,$kill,$for){
        $result = self::$Dt->collection->kills->count(['killer' => (float) $killer , 'game_id' => self::$Dt->game_id]);
        if($result == 0) {
            self::$Dt->collection->kills->insertOne([
                'killer'          =>    $killer,
                'kill'            =>    $kill,
                'for'             =>    $for,
                'game_id'         =>    self::$Dt->game_id,
                'time'            =>    time(),
                'group_id'        =>    self::$Dt->chat_id
            ]);
        }
    }

    public static function GetRoleUserId($role){
        $result = self::$Dt->collection->games_players->findOne(['user_role' => $role,'group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id,'user_state'=> 1,'user_status' => "on"]);
        if($result) {
            $array = iterator_to_array($result);
            return $array['user_id'];
        }
        false;
    }

    public static function LoverBYSweetheart($User_id,$team){
        $Detial = self::_getPlayer($User_id);
        $U_name = self::ConvertName($Detial['user_id'],$Detial['fullname_game']);
        $Sweetheart = self::_getPlayerByRole('role_Sweetheart');


        if(R::CheckExit('GamePl:love:'.$Detial['user_id'])){
            $LoverId = R::Get('GamePl:love:'.$Detial['user_id']);
            $LoverDetial = self::_getPlayer($LoverId);
            if($LoverDetial['user_state'] == 1) {
                R::GetSet(true,'GamePl:CheckLover:'.$LoverId);
                $LoverName = self::ConvertName($LoverDetial['user_id'], $LoverDetial['fullname_game']);
                $GroupMessage = self::$Dt->LG->_('MsgGroupDeadLastLove', array("{0}" => $LoverName, "{1}" => self::$Dt->LG->_('user_role', array("{0}" => self::$Dt->LG->_($LoverDetial['user_role'] . "_n")))));
                self::SaveMessage($GroupMessage);
                self::UserDead($LoverId, 'Sweetheart');
                R::Del('GamePl:love:'.$LoverId);
                self::SaveGameActivity($LoverDetial,'love_dead',$Detial);
                $MessagePlayer = self::$Dt->LG->_('MsgPlayerDeadLastLove');
                self::SendMessage($MessagePlayer, $LoverId);
                $SweetheartMessage = self::$Dt->LG->_('MsgSweetHeartLastLoveDead', array("{0}" => $LoverName));
                self::SendMessage($SweetheartMessage, $Detial['user_id']);
            }
        }

        if(R::CheckExit('GamePl:love:'.$Sweetheart['user_id'])){
            $LoverId = R::Get('GamePl:love:'.$Sweetheart['user_id']);
            $LoverDetial = self::_getPlayer($LoverId);
            if($LoverDetial['user_state'] == 1) {
                R::GetSet(true,'GamePl:CheckLover:'.$LoverId);
                $LoverName = self::ConvertName($LoverDetial['user_id'], $LoverDetial['fullname_game']);
                $GroupMessage = self::$Dt->LG->_('MsgGroupDeadLastLove',  array("{0}" => $LoverName,"{1}" => self::$Dt->LG->_('user_role',  array("{0}" => self::$Dt->LG->_($LoverDetial['user_role'] . "_n")))));
                self::SaveMessage($GroupMessage);
                self::UserDead($LoverId, 'Sweetheart');
                R::Del('GamePl:love:'.$LoverId);
                self::SaveGameActivity($LoverDetial,'love_dead',$Sweetheart);
                $MessagePlayer = self::$Dt->LG->_('MsgPlayerDeadLastLove');
                self::SendMessage($MessagePlayer, $LoverId);
                $SweetheartMessage = self::$Dt->LG->_('MsgSweetHeartLastLoveDead', array("{0}" => $LoverName));
                self::SendMessage($SweetheartMessage, $Sweetheart['user_id']);
            }
        }


        self::SaveGameActivity(['user_id' => $Sweetheart['user_id'],'fullname'=>$Sweetheart['fullname'] ],'love',$Detial);
        self::SaveGameActivity($Detial,'love',['user_id' => $Sweetheart['user_id'],'fullname'=>$Sweetheart['fullname'] ]);


        R::GetSet($User_id,'GamePl:love:'.$Sweetheart['user_id']);
        R::GetSet($Sweetheart['user_id'],'GamePl:love:'.$User_id);

        R::GetSet($User_id,'GamePl:SweetheartLove');
        R::GetSet($team,'GamePl:SweetheartLove:team');
        R::GetSet($U_name,'GamePl:SweetheartLove:name');
        R::GetSet(true,'GamePl:SweetheartLove:'.$User_id);
        R::GetSet(self::$Dt->LG->_('user_role',self::$Dt->LG->_($Detial['user_role']."_n")),'GamePl:SweetheartLove:role');
        $SweetheartMessage = self::$Dt->LG->_('MsgLoveSweetHeart',array("{0}" => $U_name));
        self::SendMessage($SweetheartMessage,$Sweetheart['user_id']);

        return true;
    }


    public static function CheckSmite(){
        $data = R::LRange(0,-1,'GamePl:SmitePlayer');
        if($data){
            foreach ($data as $user_id){
                $Player = self::_getPlayer($user_id);
                if($Player){
                    $name = self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $role = self::$Dt->LG->_('user_role',array("{0}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    $Lang = self::$Dt->LG->_('PlayerFlee',array("{0}" => $name,"{1}" => $role));
                    self::UserDead($Player,'smite');
                    self::SendMessage($Lang);
                }
            }
            R::Del('GamePl:SmitePlayer');
        }
    }


    public static function VampireConvert($user_id){
        R::GetSet($user_id,'GamePl:VampireBitten');
    }


    public static function SavePlayerAchivment($user_id,$achive_code){
        $result = self::$Dt->collection->achievement_player->findOne(['user_id' => $user_id]);
        if(!$result) {
            self::$Dt->collection->achievement_player->insertOne([
                'user_id' => $user_id,
                'achievements' => [$achive_code],
            ]);
            $AchMessage = self::$Dt->L->_('AchioUnlock').PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code).PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code."_dic");
            self::SendMessage($AchMessage, $user_id);
            return true;
        }

        $updateResult = self::$Dt->collection->achievement_player->updateOne(array("user_id" => $user_id, 'achievements' => ['$nin' => [$achive_code]]), array('$push' => array("achievements" => $achive_code)));
        if($updateResult->getMatchedCount() > 0){
            $AchMessage = self::$Dt->L->_('AchioUnlock').PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code).PHP_EOL;
            $AchMessage .= self::$Dt->L->_($achive_code."_dic");
            self::SendMessage($AchMessage, $user_id);
        }

        return false;
    }


    public static function InsertMedal($user_id,$MedalIcon,$info){
        self::SendMessage($info, $user_id);
        self::$Dt->collection-> PlayerStateMedal ->insertOne([
            'user_id'       =>    $user_id,
            'medal'     =>    $MedalIcon,
            'medal_info'      =>    $info,
        ]);
    }
    public static function SaveGameActivity($d,$actvity,$to){
        self::$Dt->collection->   game_activity ->insertOne([
            'chat_id'       =>    self::$Dt->chat_id,
            'game_id'     =>     self::$Dt->game_id,
            'player_id'      =>   $d['user_id'],
            'player_name' =>      $d['fullname'],
            'actvity'   => $actvity,
            'to'   => $to['user_id'],
            'to_name' => $to['fullname'],
            'm_date'         =>    date('Y-m-d H:i:s'),
            'jdate'          => jdate('Y-m-d H:i:s')
        ]);
    }

    public static function SaveGameEndData($players,$count_player){
        self::$Dt->collection-> group_states ->insertOne([
            'chat_id'       =>    self::$Dt->chat_id,
            'game_id'     =>     self::$Dt->game_id,
            'player_id'      =>   $players,
            'count_player'   => $count_player,
            'm_date'         =>    date('Y-m-d H:i:s'),
            'jdate'          => jdate('Y-m-d H:i:s')
        ]);
    }

    public static function GetUserForLove($not){
        $player = self::GetUserRandom([$not]);

    }


    public static function GetNextGame(){
        $result = self::$Dt->collection->next_game->findOne(['chat_id' => self::$Dt->chat_id]);

        if($result){
            $array = iterator_to_array($result);
            if($array['users']){
                return $array['users'];
            }

            return false;
        }

        return false;
    }

    public static function GetRoleEnemyVampire(){

        $Roles = ['role_shekar','role_Qatel','role_Archer','role_WolfTolle','role_WolfGorgine','role_Wolfx','role_WolfAlpha','role_Firefighter','role_IceQueen','role_Knight','role_shekar'];
        $NinRole = ['role_Vampire','role_Bloodthirsty'];
        $result = self::$Dt->collection->games_players->find([
            'game_id' => self::$Dt->game_id
            , 'group_id' => self::$Dt->chat_id
            , 'user_state' => 1
            , 'user_status' => 'on'
            , 'user_role' => ['$in' => $Roles]
        ], ['limit' => -1, 'skip' => mt_rand(0, (self::_getCountPlayer()))]);

        if ($result) {
            $array = iterator_to_array($result);
            if (!isset($array['0'])) {
                return self::GetRoleEnemyVampire();
            }
            return $array['0'];
        }


        return false;

    }
    public static function DeleteNextList(){
        self::$Dt->collection->next_game->deleteOne(['chat_id' => self::$Dt->chat_id]);
    }

    public static function GostFinded($Detial){
        if(R::CheckExit('GamePl:FindGhost')){
            return false;
        }
        $GhostMessage = self::$Dt->LG->_('GhostFinde');
        self::SendMessage($GhostMessage,$Detial['user_id']);
        R::GetSet(true,'GamePl:FindGhost');
        return true;
    }

    public static function  UnlockAllPlayerMute(){
        $Data = R::LRange(0,-1,'GamePl:MutedPlayer');
        $RecedPlayer = [];
        if($Data){
            foreach ($Data as $row){
                $UserData = json_decode($row,true);
                Request::restrictChatMember([
                    'chat_id' => self::$Dt->chat_id,
                    'user_id' => $UserData['user_id'],
                    'permissions' => ['can_send_messages' => true,'can_send_media_messages' => true,'can_send_polls' => true,'can_send_other_messages' => true,'can_add_web_page_previews' => true,'can_change_info'=>true,'can_invite_users' => true ,'can_pin_messages' => true]
                ]);
                array_push($RecedPlayer,$UserData['fullname']);
            }
        }
        
        if($RecedPlayer){
            $Message = self::$Dt->L->_('UnMutedPlayers',array("{0}" =>implode(PHP_EOL,$RecedPlayer) ));
            self::SendMessage($Message,self::$Dt->chat_id);
        }
    }
    public static function SetUpdate(){
        self::$Dt->collection->games->updateOne(
            ['group_id' => self::$Dt->chat_id,'game_id'=> self::$Dt->game_id ],
            ['$set' => ['update' => false]]
        );
    }

    public static function HandelMajik($data){
        $Nop = R::NoPerfix();
        foreach ($data as $row){
            $Ex = explode(':',$row);
            $FullKey = "$Ex[1]:$Ex[2]:$Ex[3]";
            $GetData = R::Get($FullKey);
            $userId = $Ex[3];
            $GetPlayer = self::_getPlayer($userId);

            if(!$GetPlayer){
                return false;
            }
            if($GetPlayer['user_state'] !== 1 && $GetData !== true ){
                /*
                $PlayerMSg = self::$Dt->L->_('PlayerDie');
                self::SendMessage($PlayerMSg,$userId);
                $GetLast = $Nop->get('GhostPlayer:'.$userId);
                $Nop->set('GhostPlayer:'.$userId,$GetLast + 1);
                $Nop->set($row,true);
                */
               continue;

            }
            switch ($GetData){
                case 'MajiKhabarPlayer':
                    if(!R::CheckExit('GamePl:StartNewGame')){
                        return false;
                    }
                    self::SendMajikKhbarr($GetPlayer);
                    $Nop->set($row,true);
                break;
                case 'GhostPlayer':
                    if(!R::CheckExit('GamePl:StartNewGame')){
                        return false;
                    }

                    $ForNight = R::Get('GamePl:Night_no') + 1;
                    $ForDay = R::Get('GamePl:Day_no') + 1;
                    R::GetSet($ForNight,'GamePl:GhostPlayer_Night:'.$userId);
                    R::GetSet($ForDay,'GamePl:GhostPlayer_Day:'.$userId);
                    $PlayerMsg = self::$Dt->L->_('GhostActive',array("{0}" => $ForNight ,"{1}" => $ForDay ));
                    self::SendMessage($PlayerMsg,$userId);
                    R::GetSet(true,$FullKey);
                    $Nop->set($row,true);
                break;
                case 'MajiKHilPlayer':
                    if(!R::CheckExit('GamePl:StartNewGame')){
                        return false;
                    }

                    $ForNight = R::Get('GamePl:Night_no') + 1;
                    R::GetSet($ForNight,'GamePl:Heal_Night:'.$userId);
                    $PlayerMsg = self::$Dt->L->_('ActiveHealMajik',array("{0}" => $ForNight));
                    self::SendMessage($PlayerMsg,$userId);
                    $Nop->set($row,true);

                    break;
            }
        }
    }


    public static function CheckMajikHealPlayer($user_id){
        if(R::CheckExit('GamePl:Heal_Night:'.$user_id)){
            if(R::Get('GamePl:Night_no') == R::Get('GamePl:Heal_Night:'.$user_id)){
                return true;
            }
        }
        return false;
    }
    public static function SendMajikKhbarr($Detial){


        switch ($Detial['team']){
            case 'rosta':
                $Player =  self::GetRoleNotInRandom('rosta');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
            break;
            case 'monafeq':
            case 'hamzad':
            case 'lucifer':
                $Player =  self::GetRoleNotInRandom('rosta');
            if($Player){
                $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                self::SendMessage($Msg,$Detial['user_id']);
            }
                break;
            case 'dinamit':
                $Player =  self::GetRoleNotInRandom('rosta');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
                break;
            case 'Bomber':
                $Player =  self::GetRoleNotInRandom('Bomber');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
                break;
            case 'ferqeTeem':
                $Player =  self::GetRoleNotInRandom('ferqeTeem');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
                break;
            case 'qatel':
                $Player =  self::GetRoleNotInRandom('qatel');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
                break;
            case 'wolf':
                $Player =  self::GetRoleNotInRandom('wolf');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
                break;
            case 'Firefighter':
                $Player =  self::GetRoleNotInRandom('Firefighter');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
                break;
            case 'vampire':
               $Player =  self::GetRoleNotInRandom('vampire');
                if($Player){
                    $Name =  self::ConvertName($Player['user_id'],$Player['fullname_game']);
                    $Msg = self::$Dt->L->_('MajikKhabarChinSee',array("{0}" => $Name,"{1}" => self::$Dt->LG->_($Player['user_role']."_n")));
                    self::SendMessage($Msg,$Detial['user_id']);
                }
            break;
        }
    }
    public static function CheckPhoenixHeal($Detial){
        if(!R::CheckExit('GamePl:PhoenixHealer:'.$Detial['user_id'])) return false;
        return  true;
    }
    public static function GetPlayer($user_id){
        $user_id = (float) $user_id;
        $result = self::$Dt->collection->Players->findOne(['user_id'=>  $user_id]);
        if($result) {
            $array = iterator_to_array($result);
            return $array;
        }

        return false;
    }
    
    public static function UpdateCoin($user_id,$Coin){
        self::$Dt->collection->Players->updateOne(array("user_id"=>(float) $user_id),  ['$set' => ['credit' => (float) $Coin]] );

        return true;
    }

    public static function KaragahS(){
        $Karagah = self::_getPlayerByRole('role_karagah',false);
        if($Karagah){
            if(R::CheckExit('GamePl:SendMsgWolfKara')) {
                return false;
            }
            if(self::R(100) < 40){
                $Name = self::ConvertName($Karagah['user_id'],$Karagah['fullname_game']);
                $Msg = self::$Dt->LG->_('KaragahSForWolf',array("{0}" => $Name));
                self::SendForWolfTeam($Msg);
                R::GetSet(true,'GamePl:SendMsgWolfKara');
            }
        }
    }
    public static function FindePlayerRoleBuy($role,$user_id){
        $Data = self::GetRoleBuy();
        if(!$Data){
            return false;
        }
        $Find = false;
        foreach ($Data as $row){
            if($row['_id'] == $role){

                if(in_array($user_id,$row['users'])){
                    $Find = true;
                    break;
                }
            }
        }

        return $Find;

    }

    public static function GetRoleBuy(){
        if(R::CheckExit('GamePl:BuyRole')){
            $Data = json_decode(R::Get('GamePl:BuyRole'),true);
            return $Data;
        }
        $ops = [
            ['$match' => ['active' => true]],
            ['$group' => [
                '_id' => '$role'
                ,'count' => ['$sum' => 1]
                ,"users" => ['$addToSet' => '$user_id']
            ]],

        ];
        $result = self::$Dt->collection->buy_role->aggregate($ops);
        if ($result) {
            $array = iterator_to_array($result);
            R::GetSet($array,'GamePl:BuyRole','json');
            return json_decode(json_encode($array),true);
        }

        return false;
    }

    public static function CheckAllowGroup($role_id){
        $chat_id = (float) self::$Dt->chat_id;
        $result = self::$Dt->collection->group_roles->findOne(['chat_id'=>  (float) self::$Dt->chat_id,'role_id'=> $role_id,'status' => true]);
        if($result) {
            $array = iterator_to_array($result);
            return true;
        }

        return false;
    }



}
