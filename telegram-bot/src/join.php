<?php

namespace phpcron\CronBot;

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;

class join
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

    public static function Handel(){
        if(R::CheckExit('GamePl:StartNewGame')){
            return false;
        }
        self::NextGameMessage();
        $timer = R::Get('timer');

        $LeftTime = $timer - time();


        self::SendStarterMessage();
        self::UpdatePlayerList();
        switch ($LeftTime){
            case 62:
            case 61:
            case 60:
            case 59:
            case 58:
                $inline_keyboard = HL::_getJoinKeyboard();
                $msg = self::$Dt->LG->_('OnlyJoinTheGameTime',array("{0}" => self::$Dt->LG->_('minuts')));
                $result = Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $msg,
                    'parse_mode'=> 'HTML',
                    'reply_markup' => $inline_keyboard,
                ]);
                if($result->isOk()) {
                    R::rpush($result->getResult()->getMessageId(), 'deleteMessage');
                }
                return true;
                break;
            case 32:
            case 31:
            case 30:
            case 29:
            case 28:

                $inline_keyboard = HL::_getJoinKeyboard();
                $msg = self::$Dt->LG->_('OnlyJoinTheGameTime',array("{0}" => self::$Dt->LG->_('Secend',array("{0}" => "<strong>30</strong>"))));
                $result = Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $msg,
                    'parse_mode'=> 'HTML',
                    'reply_markup' => $inline_keyboard,
                ]);
                if($result->isOk()) {
                    R::rpush($result->getResult()->getMessageId(), 'deleteMessage');
                }
                return true;
                break;
            case 11:
            case 10:
            case 9:
            case 8:
                $inline_keyboard = HL::_getJoinKeyboard();
                $msg = self::$Dt->LG->_('OnlyJoinTheGameTime',array("{0}" =>self::$Dt->LG->_('Secend',array("{0}" => "<strong>10</strong>"))));
                $result = Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $msg,
                    'parse_mode'=> 'HTML',
                    'reply_markup' => $inline_keyboard,
                ]);
                if($result->isOk()) {
                    R::rpush($result->getResult()->getMessageId(), 'deleteMessage');
                }
                return true;
                break;
            default:
                $countPlayer = HL::_getCountPlayer();
                $GameMode = R::Get('GamePl:gameModePlayer');
                $MinPlayers = ($GameMode == "Vampire" ? 7 : 5);

                if($LeftTime <= 0){
                    if(R::CheckExit('GamePl:StartNewGame')){
                        return false;
                    }

                    R::GetSet(time(),'GamePl:GamePl:EndJoinTimeGame');
                    R::GetSet(true,'GamePl:StartNewGame');
                    R::Del('GamePl:time_update');
                    R::Del('GamePl:UserJoin');

                    if($countPlayer < $MinPlayers) {
                        HL::GroupClosedThGame('join');
                        self::UpdatePlayerList();
                        self::DeleteMessage();
                        return Request::sendMessage([
                            'chat_id' => self::$Dt->chat_id,
                            'text' => self::$Dt->LG->_('NotStartGameForPlayer'),
                        ]);
                    }
                    self::UpdatePlayerList();
                    self::DeleteMessage();
                    return self::GameStarted();

                }
                break;
        }

    }

    public static function GetRoleMafia($count_Player){
        $roleList = [];
        $MafiaRole = SE::MafiaRole();

        $CNCountAddMafia = round((35 * $count_Player / 100));
        for ($i = 0; $i < round(min(max($CNCountAddMafia, 3), 1)); $i++) {
            array_push($roleList, $MafiaRole[$i]);
        }
        $CN_add = 0;
        if($CNCountAddMafia > 3){
            $CN_add = $CNCountAddMafia - 3;
            for ($i = 0; $i < round($CN_add); $i++) {
                array_push($roleList, "role_Mafia");
            }
        }


        $CitizenRole = SE::RoleMafiaMode();
        foreach ($MafiaRole as $key => $role){
            switch ($role){
                default:
                    array_push($roleList, $CitizenRole[$i]);
                    break;
            }
        }
        if($count_Player > 6){
            for ($i = 0; $i < ($count_Player - 6 - $CN_add); $i++){
                array_push($roleList, "role_Citizen");
            }
        }

        shuffle($roleList);
        shuffle($roleList);
        shuffle($roleList);
        shuffle($roleList);

        return $roleList;
    }
    public static function MafiaUserRole(){
        $countPlayer = HL::_getCountPlayer();
        $balance = false;
        $attemp = 0;
        do {
            $attemp++;
            if($attemp >= 550){
                HL::GroupClosedThGame('join');
                self::UpdatePlayerList();
                self::DeleteMessage();
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('ErrorStartGame_Balance'),
                ]);
                return false;
            }

            $MafiaRoles = self::GetRoleMafia($countPlayer);
            $AnArray = array_slice($MafiaRoles, 0, ($countPlayer));

            if(count($AnArray) !== $countPlayer){
                $balance = false;
            }else {
                $balance = true;
            }
        }while($balance);
    }
    public static function GameStarted(){
        // ثبت زمان شروع بازی
        HL::ChangeStartGameTime();

        R::GetSet(true,'GamePl:Kill');
        // ارسال پیام شروع بازی
        Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => self::$Dt->LG->_('GameStart'),
        ]);

        $GameMode = R::Get('GamePl:gameModePlayer');
        if($GameMode == "Mafia"){

            return  true;
        }
        // نقش دادن به کاربران
        $role =  self::UserRole();

        if($role) {
            // تغییر وضعیت بازی
            HL::ChangeGameStatus('night');

            // دریافت متن الان در چه روزی هستیم و یا شب و یا رای گیری
            $GameStatusLang = HL::GetGameStatusLang();
            // ارسال به لیست پیام های گروه
            HL::SaveMessage($GameStatusLang);
            /*
             * کلیه متون مربوت به این روز دریافت شد و الان اماده ارساله
             * بصورت ترتیبی ارسال میشن به گروه پیام ها
             */
            HL::SendGroupMessage(true);


            return true;
        }
        return false;
    }

    public static function SendStarterMessage(){
        if(R::CheckExit('GamePl:SendStarterMessage')){
            return false;
        }
        R::GetSet(true, 'GamePl:SendStarterMessage');
        $L = self::$Dt->LG->_('StarterMessage', array("{0}" => R::Get('GamePl:StarterName')));
        $result = Request::sendMessage([
            'chat_id' => self::$Dt->chat_id,
            'text' => $L,
            'parse_mode' => 'HTML',
        ]);
        if($result->isOk()) {
            R::rpush($result->getResult()->getMessageId(), 'deleteMessage');

        }
    }


    public static function GetRoleWight($Array,$CountPlayer,$CountTeam){
        $Wolf_W = 0;
        $Ferqe = 0;
        $Rosta = 0;
        $Qatel = 0;
        $Monafeq = 0;
        $Vampire = 0;
        $Blod = 0;
        $kalan = 0;
        $FireFighter = 0;
        foreach ($Array as $role){
            switch ($role){
                case 'role_WolfJadogar':
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':
                case 'role_Honey':
                case 'role_enchanter':
                case 'role_WhiteWolf':
                case 'role_forestQueen':
                    $Wolf_W = ($Wolf_W + SE::_W($role,$Array,$CountTeam));
                    break;
                case 'role_Qatel':
                case 'role_Archer':
                    $Qatel = ($Qatel + SE::_W($role,$Array,$CountTeam));
                    break;
                case 'role_monafeq':
                    $Monafeq = ($CountPlayer / 2);
                    break;
                case 'role_ferqe':
                case 'role_Royce':
                    $Ferqe = ($Ferqe + SE::_W($role,$Array,$CountTeam));
                    break;
                case 'role_Firefighter':
                case 'role_IceQueen':
                    $FireFighter =  ($FireFighter + SE::_W($role,$Array,$CountTeam));
                    break;
                case 'role_lucifer':
                    break;
                case 'role_Bloodthirsty':
                    $Blod = ($Vampire + SE::_W($role,$Array,$CountTeam));
                    break;
                case 'role_Vampire':
                    $Vampire = ($Vampire + SE::_W($role,$Array,$CountTeam));
                    break;
                default:
                    if($role == "role_kalantar"){
                        $kalan = $kalan + 1;
                    }
                    $Rosta = ($Rosta + SE::_W($role,$Array,$CountTeam));
                    break;
            }
        }

        return ['wolf' =>$Wolf_W,'blod'=> $Blod,'kalan'=>$kalan,'ferqe' => $Ferqe,'rosta' => $Rosta,'monafeq'=> $Monafeq,'qatel' => $Qatel,'Vampire' => $Vampire,'FireFighter' => $FireFighter];
    }
    public static function UserRole(){
        $countPlayer = HL::_getCountPlayer();

        $balanced = false;
        $attemp = 0;
        $nonVg = [
            'role_Khaen',
            'role_Vahshi',
            'role_Honey',
            'role_kentvampire',
            'role_monafeq',
            'role_Lucifer'
            ,'role_monafeq'
            ,'role_Qatel'
            ,'role_WolfTolle'
            ,'role_WolfGorgine',
            'role_Wolfx',
            'role_WolfAlpha',
            'role_WolfJadogar',
            'role_enchanter',
            'role_WhiteWolf',
            'role_forestQueen',
            'role_Joker',
            'role_Harly',
            'role_Firefighter',
            'role_IceQueen',
            'role_Vampire'
            ,'role_Bloodthirsty'
            ,'role_Archer'
            ,'role_franc'
            ,'role_Mummy'
            ,'role_Royce'
            ,'role_davina'
            ,'role_lucifer'
            ,'role_betaWolf'
            ,'role_kentvampire'
            ,'role_Chiang'
            ,'role_Bomber'
            ,'role_Hamzad'
            ,'role_ferqe'
            ];

        $GameMode = R::Get('GamePl:gameModePlayer');
        $WolfRole = SE::WolfRole();
        do {
            $attemp++;
            if($attemp >= 550){
                HL::GroupClosedThGame('join');
                self::UpdatePlayerList();
                self::DeleteMessage();
                Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => self::$Dt->LG->_('ErrorStartGame_Balance'),
                ]);
                return false;
            }

            $Roles = self::GetRoleRandom($countPlayer);
            $AnArray = array_slice($Roles, 0, ($countPlayer));
            $Slice = self::SliceRole($AnArray);
            $Enemy = $Slice['enemy'];



            // اگر جادوگر ، خائن،افسونگر و یا عجوزه بود ولی گرگ نبود خائن ،جادوگر و یا عجوزه رو تبدیل به گرگ کن
            if(in_array('role_WolfJadogar',$AnArray) || in_array('role_Honey',$AnArray) || in_array('role_enchanter',$AnArray)  || in_array('role_Khaen',$AnArray) and in_array('wolf',$Enemy) == false ){
                $GetKey = self::GetKeyRoleByN($AnArray,['role_WolfJadogar','role_Khaen','role_Honey','role_enchanter','role_betaWolf','role_forestQueen','role_WhiteWolf']);
                $AnArray[$GetKey] = $WolfRole[HL::R(count($WolfRole) - 1)];
            }

            // اگه کماندار بود ولی قاتل نبود کماندار رو تبدیل به قاتل کن
            if(in_array('role_Archer',$AnArray)  and !in_array('role_Qatel',$AnArray)){
                $Archer = self::GetRoleKey('role_Archer',$AnArray);
                $AnArray[$Archer] = "role_Qatel";
            }

            // اگه ملکه جنگل بود ولی آلفا نبود  ملکه جنگلو تبدیل  کن به روستایی
            if(in_array('role_forestQueen',$AnArray)  and !in_array('role_WolfAlpha',$AnArray)){
                $ForestQueen = self::GetRoleKey('role_forestQueen',$AnArray);
                $AnArray[$ForestQueen] = 'role_WolfAlpha';
            }



            // اگه اصیل نبود ولی ومپایر نبود یکی از روستایی هارو تبدیل به اصیل کن
            if(!in_array('role_Bloodthirsty',$AnArray) && in_array('role_Vampire',$AnArray)){
                $VgKey = self::GetRandomvgKey($AnArray,$nonVg);
                $AnArray[$VgKey] = "role_Bloodthirsty";
            }


            // اگه ومپایر اصیل بود ولی کلانتر نبود یکی از روستاییا رو تبدیل به  اصیل کن
            if(in_array('role_Bloodthirsty',$AnArray) && !in_array('role_kalantar',$AnArray)){
                $VgKey = self::GetRandomvgKey($AnArray,$nonVg);
                $AnArray[$VgKey] = "role_kalantar";
            }


            // اگه ومپایر بود ولی اصیل نبود یه روستایی رو تبدیل به اصیل کن
            if(in_array('role_Bloodthirsty',$AnArray) && !in_array('role_Vampire',$AnArray)){
                $VgKey = self::GetRandomvgKey($AnArray,$nonVg);
                $AnArray[$VgKey] = "role_Vampire";
            }

            // اگر فرقه گرا بود و شکارچی نبود پیدا کن یه روستایی رو و تبدیلش کن به شکارچی
            if(in_array('role_ferqe',$AnArray)  && !in_array('role_shekar',$AnArray)){
                $VgKey = self::GetRandomvgKey($AnArray,$nonVg);
                $AnArray[$VgKey] = "role_shekar";
            }

            // اگر رویس بود و شکارچی نبود پیدا کن یه روستایی رو و تبدیلش کن به شکارچی
            if(in_array('role_Royce',$AnArray)  && !in_array('role_shekar',$AnArray)){
                $VgKey = self::GetRandomvgKey($AnArray,$nonVg);
                $AnArray[$VgKey] = "role_shekar";
            }

            // اگر پیشگو رزرو بود ولی توی بازی پیشگویی وجود نداشت رزرو رو تبدیل به پیشگو کن
            if(in_array('role_PishRezerv',$AnArray) && !in_array('role_pishgo',$AnArray)){
                $RzrvKey = self::GetRoleKey('role_PishRezerv',$AnArray);
                $AnArray[$RzrvKey] = 'role_pishgo';
            }


            $NinVamRole = ['role_Vampire','role_Bloodthirsty','role_kentvampire'];
            $NinCultRole = ['role_ferqe','role_Royce','role_Mummy','role_franc'];
            $NinKiller = ['role_Qatel','role_Archer'];
            $NinWolfRole = ['role_forestQueen','role_WhiteWolf','role_WolfAlpha','role_Wolfx','role_WolfGorgine','role_WolfTolle','role_WolfTolle'];
           // werewolf



            $Slice = self::SliceRole($AnArray);
            $CountTeam = self::GetCountRole($AnArray);
            $Vg = $Slice['safe'];
            $Enemy = $Slice['enemy'];

            // در آخر چک کن ببین دو تا تیم برای مبارزه با هم توی روستا وجود دارن  یا نه
            if(count($Vg) > 0 and count($Enemy) > 0){
                $balanced = true;
            }

            $RoleWidget = self::GetRoleWight($AnArray,$countPlayer,$CountTeam);
            $Rosta = $RoleWidget['rosta'];
            $Wolf = $RoleWidget['wolf'];
            $Qatel = $RoleWidget['qatel'];
            $Ferqe = $RoleWidget['ferqe'];
            $Vampire = $RoleWidget['Vampire'];
            $blod = $RoleWidget['blod'];
            $kalan= $RoleWidget['kalan'];
            $FireFighter = $RoleWidget['FireFighter'];
            $Monafeq = floor($Rosta + $Wolf + $Qatel + $Ferqe + $Vampire + $FireFighter / $countPlayer);




            $RoleWidget = self::GetRoleWight($AnArray,$countPlayer,$CountTeam);
            $Rosta = $RoleWidget['rosta'];
            $Wolf = $RoleWidget['wolf'];
            $Qatel = $RoleWidget['qatel'];
            $Ferqe = $RoleWidget['ferqe'];
            $Vampire = $RoleWidget['Vampire'];
            $blod = $RoleWidget['blod'];
            $kalan= $RoleWidget['kalan'];
            $FireFighter = $RoleWidget['FireFighter'];
            $Monafeq = floor($Rosta + $Wolf + $Qatel + $Ferqe + $Vampire + $FireFighter / $countPlayer);



            if($GameMode !== "Foolish" && $GameMode !== "Bomber") {

                if ($GameMode !== "Vampire") {
                    // اگه تیم روستا برابر با تیم گرگ نبود و یا روستایی برابر نبود با فرقه و یا فرقه تعدادش بیشتر از روستایی بود و قاتل وزنش بیشتر از گرگ بود بالانس درست نیست
                    if ($Rosta <= $Wolf
                        || $Ferqe >= $Rosta
                        || ($blod > 0 && $Vampire == 0)
                        || ($blod > 0 && $kalan == 0)
                        || ($Vampire > 0 && $blod == 0)
                        || (
                            $countPlayer < 11
                            && in_array('role_Royce', $AnArray)
                            && R::Get("role_ferqe") == "off"
                        )
                        || (in_array('role_Royce', $AnArray)
                            && !in_array('role_ferqe', $AnArray)
                            && R::Get("role_ferqe") == "on")
                        || ($countPlayer >= 11
                            && !in_array('role_shekar', $AnArray)
                            && R::Get("role_ferqe") == "on")
                        || (in_array('role_shekar', $AnArray)
                            && !in_array('role_ferqe', $AnArray)
                            && R::Get("role_ferqe") == "on")
                        || (in_array('role_IceQueen', $AnArray)
                            && !in_array('role_Firefighter', $AnArray))
                        || (!in_array('role_IceQueen', $AnArray)
                            && in_array('role_Firefighter', $AnArray))
                        || (in_array('role_shekar', $AnArray)
                            && !in_array('role_pishgo', $AnArray))
                        || (!in_array('role_pishgo', $AnArray)
                            && in_array('role_PishRezerv', $AnArray))
                        || (in_array('role_davina', $AnArray)
                            && !in_array('role_Qatel', $AnArray))
                        || (in_array('role_forestQueen', $AnArray)
                            && !in_array('role_WolfAlpha', $AnArray))
                        || (in_array('role_BrideTheDead', $AnArray)
                            && !in_array('role_BlackKnight', $AnArray))
                        || (in_array('role_BlackKnight', $AnArray)
                            && !in_array('role_BrideTheDead', $AnArray))
                        || (in_array('role_dian', $AnArray) && !in_array('role_BlackKnight', $AnArray) && !in_array('role_BrideTheDead', $AnArray))

                    ) {
                        $balanced = false;
                    }

                }

                if (
                    ($GameMode == "Vampire"
                        && $blod == 0)
                    || ($GameMode == "Vampire"
                        && $Vampire == 0)
                    || ($GameMode == "Vampire"
                        && $Wolf > 0
                        && $countPlayer < 8)
                    || ($blod > 0
                        && $Vampire == 0)
                    || ($blod > 0
                        && $kalan == 0)
                    || ($Vampire > 0
                        && $blod == 0)
                    ||
                    (in_array('role_BlackKnight', $AnArray)
                        && !in_array('role_BrideTheDead', $AnArray))
                    || (in_array('role_IceQueen', $AnArray)
                        && !in_array('role_Firefighter', $AnArray))
                ) {

                    $balanced = false;
                }
            }

            if(in_array('role_Joker', $AnArray) && !in_array('role_Harly', $AnArray) ){
                $balanced = false;
            }
            if(!in_array('role_Joker', $AnArray) && in_array('role_Harly', $AnArray) ){
                $balanced = false;
            }
            if(!in_array('role_ferqe', $AnArray) && in_array('role_franc', $AnArray) ){
                $balanced = false;
            }
            if(!in_array('role_IceQueen', $AnArray) && in_array('role_Magento', $AnArray) ){
                $balanced = false;
            }


            if($GameMode == "Foolish"){
                if(!in_array('role_WolfGorgine', $AnArray) || !in_array('role_pishgo', $AnArray) ){
                    $balanced = false;
                }
            }
            if($GameMode == "Bomber"){
                if(!in_array('role_Bomber', $AnArray) || !in_array('role_rosta', $AnArray) ){
                    $balanced = false;
                }
            }


            if($countPlayer !== count($AnArray)){
                $balanced = false;
            }

        } while (!$balanced);

        $Players = HL::_getPlayers();

        shuffle($Players);
        shuffle($Players);
        shuffle($Players);
        shuffle($AnArray);
        shuffle($AnArray);
        shuffle($AnArray);
        if(in_array('role_dinamit',$AnArray)){
            R::GetSet(true,'GamePl:DinamitInGame');
        }





        $RoleAssinged = [];
        $Mason = [];
        $Wolf = [];
        $Cult = [];
        $Archer = [];
        $Qatel = [];
        $Bomber = [];
        $countJ = 0;
        $Joker = [];

        $Harly = [];
        $CountDozd = 0;
        for($i = 0; $i < $countPlayer; $i++){
            if(!isset($AnArray[$i])){
                continue;
            }
            $Team = SE::GetRoleTeam($AnArray[$i]);
            $RoleName = $AnArray[$i];
            if(!isset($Players[$i])){
                continue;
            }
            $user_id = $Players[$i]['user_id'];
            $fullname = $Players[$i]['fullname'];
            $link = HL::ConvertName($user_id,$fullname);
            /*
            $Check = HL::FindePlayerRoleBuy('role_dozd',$user_id);
            if($Check){
                if(!in_array($RoleName,$nonVg) && $RoleName !== "role_shekar" && $RoleName !== "role_kalantar" && $GameMode !== "Bomber" && $GameMode !== "Foolish" && $GameMode !== "WereWolf" ){
                    if(HL::R(100) < 50){
                        if($CountDozd < 3){
                            $RoleName = "role_dozd";
                            $CountDozd++;
                        }
                    }
                }
            }
            */

            switch ($RoleName){
                case 'role_pishgo':
                    R::GetSet($link,'GamePl:SearUser');
                    break;
                case 'role_feramason':
                    array_push($Mason,$link);
                    break;
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                    break;
                case 'role_Joker':
                    array_push($Joker,$link);
                    break;
                case 'role_Harly':
                    array_push($Harly,$link);
                    break;
                case 'role_tofangdar':
                    R::GetSet(2,'GamePl:GunnerBult');
                    break;
                case 'role_kalantar':
                    R::GetSet(1,'GamePl:SheriffBult');
                    R::GetSet($link,'GamePl:KalanInGame');
                    break;
                case 'role_Bloodthirsty':
                    R::GetSet($link,'GamePl:BloodthirstyInGame');
                    break;
                case 'role_ferqe':
                    array_push($Cult,$link);
                    break;
                case 'role_Bomber':
                  array_push($Bomber,$link);
                break;
                case 'role_Qatel':
                    array_push($Qatel,$link);
                    break;
                case 'role_Archer':
                    array_push($Archer,$link);
                    break;
                case 'role_IceQueen':
                    R::GetSet($link,'GamePl:role_IceQueen:InGame');
                    break;
                case 'role_davina':
                    R::GetSet($link,'GamePl:role_davina:InGame');
                    break;
                case 'role_Firefighter':
                    R::GetSet($link,'GamePl:role_Firefighter:InGame');
                    break;
                case 'role_WolfAlpha':
                    R::GetSet($link,'GamePl:role_WolfAlpha:InGame');
                    array_push($Wolf,$link);
                    break;

                case 'role_forestQueen':
                    R::GetSet($link,'GamePl:role_forestQueen:InGame');
                    break;
                case 'role_Huntsman':
                    R::GetSet(2,'GamePl:HuntsmanT');
                    break;
                    case 'role_BlackKnight':
                        R::GetSet(2,'GamePl:BlackVoteNo');
                        R::GetSet($link,'GamePl:role_BlackKnight:InGame');
                    break;
                case 'role_BrideTheDead':
                    R::GetSet($link,'GamePl:role_BrideTheDead:InGame');
                    break;
                default:
                    break;
            }



            if(!empty($RoleName)) {


                if(in_array('role_Joker', $AnArray, true) && $countJ <= 7){
                    if($RoleName !== "role_Joker" && $RoleName !== "role_Halrly"){
                        R::GetSet(true,'GamePl:BookIn:'.$user_id);
                        $countJ = $countJ+1;
                    }
                }


                array_push($RoleAssinged, ['user_id' => $user_id,'link'=>$link, 'fullname' => $fullname, 'team' => $Team, 'Role' => $RoleName]);
            }

        }



        if(count($RoleAssinged) !== $countPlayer){
            HL::GroupClosedThGame();
            return Request::sendMessage([
                'chat_id' => self::$Dt->chat_id,
                'text' => self::$Dt->LG->_('ErrorStartGame_Balance'),
            ]);
        }
        $BomberCount = count($Bomber);
        if($BomberCount){
             $BombMaxCount =   $countPlayer - ($countPlayer > 5 ? round(max(min($countPlayer / $BomberCount,1),10)) : 2);
             R::GetSet($BombMaxCount,'GamePl:BombCount');
             R::GetSet(0,'GamePl:BombPlanted');
        }
        $BombData = ['timer','Gunpowder','Chassis','Wicks'];
        shuffle($BombData);
        shuffle($RoleAssinged);
        for ($i = 0;$i < 4; $i++){
            R::GetSet($BombData[$i],'GamePl:BomberGet:'.$RoleAssinged[$i]['user_id']);
        }

        self::AssingeRoleToPlayer($RoleAssinged,['mason'=> $Mason ,'Joker' => $Joker,'Harly' => $Harly,'wolf'=>$Wolf,'ferqe'=>$Cult,'Qatel'=> $Qatel,'Archer'=> $Archer,'Bomber' => $Bomber]);
        R::GetSet(true,'GamePl:RoleAssinged');
        return true;
    }


    public static function doNotAssign($del,$data){
        if (($key = array_search($del, $data)) !== false) {
            unset($data[$key]);
        }
        return $data;
    }

    public static function AssingeRoleToPlayer($Player,$data){
        foreach ($Player as $key => $row) {
            $wolf = self::doNotAssign($row['link'],$data['wolf']);
            $fermason =  self::doNotAssign($row['link'],$data['mason']);
            $Bomber =  self::doNotAssign($row['link'],$data['Bomber']);
            $ferqe =  self::doNotAssign($row['link'],$data['ferqe']);
            $Qatel = ($data['Qatel'] ? implode(',',$data['Qatel']) : false);
            $Archer = ($data['Archer'] ? implode(',',$data['Archer']) : false);
            $Joker = ($data['Joker'] ? implode(',',$data['Joker']) : false);
            $Halry = ($data['Harly'] ? implode(',',$data['Harly']) : false);

            switch ($row['Role']){
                case 'role_Joker':
                    $msg =  self::$Dt->LG->_($row['Role'], array("{0}" => $Halry));
                    break;
                case 'role_Harly':
                    $msg =  self::$Dt->LG->_($row['Role'], array("{0}" => $Joker));
                    break;
                case 'role_Nazer':
                    $msg = (R::CheckExit('GamePl:SearUser') == true ? self::$Dt->LG->_($row['Role'],array("{0}" => self::$Dt->LG->_('pishgo_not', array("{0}" => R::Get('GamePl:SearUser'))))) : self::$Dt->LG->_($row['Role'], array("{0}" => self::$Dt->LG->_('Not_pishgo'))));
                    break;
                case 'role_Bomber':
                    $msg = self::$Dt->LG->_($row['Role'],array("{0}" =>R::Get('GamePl:BombCount'), "{1}" => (count($Bomber) > 0 ? self::$Dt->LG->_('bomberTeam', array("{0}" => implode($Bomber))) : "")));
                    break;
                case 'role_Bloodthirsty':
                    $msg = self::$Dt->LG->_($row['Role'],array("{0}" =>R::Get('GamePl:KalanInGame')));
                    break;
                case 'role_BrideTheDead':
                    $msg = self::$Dt->LG->_($row['Role']).PHP_EOL.self::$Dt->LG->_('BlackName',array("{0}" => R::Get('GamePl:role_BlackKnight:InGame')));
                break;
                case 'role_BlackKnight':
                    $msg = self::$Dt->LG->_($row['Role']).PHP_EOL.self::$Dt->LG->_('BrideName',array("{0}" => R::Get('GamePl:role_BrideTheDead:InGame')));
                 break;
                case 'role_Qatel':
                    $msg = ($Archer ? self::$Dt->LG->_($row['Role'], array("{0}" => self::$Dt->LG->_('role_QatelIfArcher', array("{0}" => $Archer)))) :  self::$Dt->LG->_($row['Role'],array("{0}" => "")) ).(R::CheckExit("GamePl:role_davina:InGame") ? PHP_EOL.R::Get("GamePl:role_davina:InGame") : "");
                    break;
                case 'role_kalantar':
                    $msg = self::$Dt->LG->_($row['Role'],array("{0}" =>  (R::CheckExit('GamePl:BloodthirstyInGame') ? self::$Dt->LG->_('role_kalantarBloodInHome') : "")));
                    break;
                case 'role_feramason':
                    $msg = (count($fermason) == 0 ? self::$Dt->LG->_($row['Role'], array("{0}" => '')) : self::$Dt->LG->_('role_feramason_team', array("{0}" => implode(',',$fermason))));
                    break;
                case 'role_ferqe':
                    $msg = (count($ferqe) == 0 ? self::$Dt->LG->_($row['Role'], array("{0}" => '')) :  self::$Dt->LG->_($row['Role'], array("{0}" => self::$Dt->LG->_('role_ferqe_team', array("{0}" => implode(',',$ferqe))))));
                    break;
                case 'role_Archer':
                    $msg =  self::$Dt->LG->_('role_Archer', array("{0}" => $Qatel));
                    break;
                case 'role_Firefighter':
                    $msg = (R::CheckExit('GamePl:role_IceQueen:InGame') ? self::$Dt->LG->_('role_Firefighter', array("{0}" =>  self::$Dt->LG->_('role_FirefighterIce', array("{0}" => R::Get('GamePl:role_IceQueen:InGame'))))) : self::$Dt->LG->_('role_Firefighter', array("{0}" => '')));
                    break;
                case 'role_IceQueen':
                    $msg = (R::CheckExit('GamePl:role_Firefighter:InGame') ? self::$Dt->LG->_('role_IceQueen', array("{0}" => self::$Dt->LG->_('role_IceQueenFire', array("{0}" => R::Get('GamePl:role_Firefighter:InGame'))))) : self::$Dt->LG->_('role_IceQueen', array("{0}" => '')));
                    break;
                case 'role_forestQueen':
                    $Alpha_name = (R::CheckExit('GamePl:role_WolfAlpha:InGame') ? PHP_EOL.self::$Dt->LG->_('role_forestQueenAlpha',array("{0}" =>R::Get('GamePl:role_WolfAlpha:InGame')) ): "");
                    $msg =  self::$Dt->LG->_('role_forestQueen').$Alpha_name;
                    break;
                case 'role_WolfAlpha':
                    $msgForce =  (R::CheckExit('GamePl:role_forestQueen:InGame') ? PHP_EOL.self::$Dt->LG->_('role_WolfAlpha_force', array("{0}" => R::Get('GamePl:role_forestQueen:InGame')) ): "");
                    $msg =  (count($wolf) == 0 ? self::$Dt->LG->_($row['Role']).$msgForce : self::$Dt->LG->_($row['Role']).$msgForce.PHP_EOL.self::$Dt->LG->_('role_wolf_team', array("{0}" => implode(',',$wolf))));
                    break;

                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                    $msg =  (count($wolf) == 0 ? self::$Dt->LG->_($row['Role']) : self::$Dt->LG->_($row['Role']).PHP_EOL.self::$Dt->LG->_('role_wolf_team',array("{0}" => implode(',',$wolf))));
                    break;
                default:
                    $msg =  self::$Dt->LG->_($row['Role']);
                    break;
            }

            if($row['user_id'] == ADMIN_ID){
                R::GetSet(true,'GamePl:AmirKarimiInGame');
            }
            if(self::$Dt->chat_id === (float) "-1001529292214"){
                $SFRole = ['role_qhost','role_dinamit','role_kentvampire','role_BrideTheDead','role_franc','role_BlackKnight','role_Princess','role_betaWolf','role_Phoenix','role_Lilis'];
                if(in_array($row['Role'],$SFRole)){
                    HL::SavePlayerAchivment($row['user_id'],'CouseLandRole');
                }
                $Nop = R::NoPerfix();
                $countPlay =  ((int) $Nop->get('GameInCrous:'.$row['user_id'])) + 1;
                $Nop->getSet('GameInCrous:'.$row['user_id'],$countPlay);
                HL::SavePlayerAchivment($row['user_id'],'CouseLandOne');
                if($countPlay == 10){
                    HL::SavePlayerAchivment($row['user_id'],'CrouseLandThen');
                }
            }

            if(self::$Dt->chat_id === (float) "-1001699233545"){
              
                $Nop = R::NoPerfix();
                $countPlay =  ((int) $Nop->get('GameInSun:'.$row['user_id'])) + 1;
                $Nop->getSet('GameInSun:'.$row['user_id'],$countPlay);
                if($countPlay == 1){
                    HL::SavePlayerAchivment($row['user_id'],'SunOne');
                }

                if($countPlay == 10){
                    HL::SavePlayerAchivment($row['user_id'],'SunOneThen');
                }


                if($countPlay == 100){
                    HL::SavePlayerAchivment($row['user_id'],'SunOne_100');
                }


                if($countPlay == 1000){
                    HL::SavePlayerAchivment($row['user_id'],'SunOne_1000');
                }
            }
            
            if(self::$Dt->chat_id === (float) "-1001156903866"){
                $SFRole = ['role_BlackKnight','role_Princess','role_betaWolf','role_BrideTheDead','role_Phoenix','role_kentvampire','role_franc','role_Lilis'];
                if(in_array($row['Role'],$SFRole)){
                    HL::SavePlayerAchivment($row['user_id'],'OrgRole');
                }
                $Nop = R::NoPerfix();
                $countPlay =  ((int) $Nop->get('GameInOrg:'.$row['user_id'])) + 1;
                $Nop->getSet('GameInOrg:'.$row['user_id'],$countPlay);
                HL::SavePlayerAchivment($row['user_id'],'OrgOne');
                if($countPlay == 10){
                    HL::SavePlayerAchivment($row['user_id'],'OrgThen');
                }

            }




            $GameMode = R::Get('GamePl:gameModePlayer');
            if($GameMode == "Romantic" && !R::CheckExit('GamePl:love:'.$row['user_id'])){

                $userKey = $key + 1;

                if(!isset($Player[$userKey]['user_id'])) {
                    $userKey = $key - 1;
                }
                if(!R::CheckExit('GamePl:love:'.$row['user_id'])) {
                    $player = $Player[$userKey];
                    R::GetSet((float)$row['user_id'], 'GamePl:love:' . $player['user_id']);
                    R::GetSet($row['link'], 'GamePl:name:love:' . $player['user_id']);

                    R::GetSet((float)$player['user_id'], 'GamePl:love:' . $row['user_id']);
                    R::GetSet($player['link'], 'GamePl:name:love:' . $row['user_id']);
                }

            }

            Request::sendMessage([
                'chat_id' => $row['user_id'],
                'text' => $msg,
                'parse_mode'=> 'HTML'
            ]);



            $GetLastSear = (self::$Dt->redis->exists('MajikSearPlayer:'.$row['user_id']) ? (int) self::$Dt->redis->get('MajikSearPlayer:'.$row['user_id']) : 0);
            $GetLastkhabar = (self::$Dt->redis->exists('MajiKhabarPlayer:'.$row['user_id']) ? (int) self::$Dt->redis->get('MajiKhabarPlayer:'.$row['user_id']) : 0);
            $GetLastGhost = (self::$Dt->redis->exists('GhostPlayer:'.$row['user_id']) ? (int) self::$Dt->redis->get('GhostPlayer:'.$row['user_id']) : 0);
            $GetLastHiller = (self::$Dt->redis->exists('MajiKHilPlayer:'.$row['user_id']) ? (int) self::$Dt->redis->get('MajiKHilPlayer:'.$row['user_id']) : 0);
            $total = $GetLastSear+ $GetLastkhabar+$GetLastGhost+$GetLastHiller; 
            if($total > 0){
                $keyBoard = new InlineKeyboard(
                    [
                        ['text' => "🤪 خبر چینی ({$GetLastkhabar})", 'callback_data' => "slectMajik/". self::$Dt->chat_id."/MajiKhabar"],['text' => "🔮 اعلام نقش ({$GetLastSear})", 'callback_data' => "slectMajik/". self::$Dt->chat_id."/MajikSear"]
                     
                    ],
                    [
                        ['text' => "😇 محافظ ({$GetLastHiller})", 'callback_data' => "slectMajik/". self::$Dt->chat_id."/MajiKHil"],['text' => "👻 روح ({$GetLastGhost})", 'callback_data' => "slectMajik/". self::$Dt->chat_id."/MajiKGhost"]

                    ],
                );
                $result = Request::sendMessage([
                    'chat_id' => $row['user_id'],
                    'text' => "چنانچه قصد استفاده از جادو را دارید این پنل تا اتمام بازی برای شما باز است",
                    'reply_markup' => $keyBoard,
                ]);
                if($result->isOk()) {
                    R::rpush($result->getResult()->getMessageId()."_".$row['user_id'],'GamePl:EditMarkupEnd');
                }
            }
            
            
            if($row['Role'] === "role_Cow"){
                $NoP = R::NoPerfix();
                if(!$NoP->exists('PlayerCow:'.$row['user_id'])){
                 $NoP->set('PlayerCow:'.$row['user_id'],true);
                }
            }
            if($row['Role'] == "role_Watermelon"){
                $Watermelon = false;
                $NoP = R::NoPerfix();
                if($NoP->exists('Watermelon:'.$row['user_id']) == false){
                    $NoP->getset('Watermelon:'.$row['user_id'],true);
                    $NoP->expire('Watermelon:'.$row['user_id'],259200);
                    $Watermelon = true;
                }

                if($Watermelon){
                    HL::SavePlayerAchivment($row['user_id'],"YouWatermelon");
                    Request::sendMessage([
                        'chat_id' => $row['user_id'],
                        'text' => self::$Dt->LG->_('YoWatermelon'),
                        'parse_mode'=> 'HTML'
                    ]);
                }
            }


            R::GetSet( $row['team'],"GamePl:user:{$row['user_id']}:team");
            R::GetSet( $row['Role'],"GamePl:user:{$row['user_id']}:role");
            self::$Dt->collection->games_players->updateOne(
                ['user_id' => (int) $row['user_id'],'group_id'=> self::$Dt->chat_id,'game_id'=> self::$Dt->game_id],
                ['$set' => ['user_role' => $row['Role'],'team'=> $row['team']]]
            );

        }
    }

    public static function check($number){
        if($number % 2 == 0){
            return 2;
        }
        else{
            return 1;
        }
    }
    public static function GetRandomvgKey($role,$NoVgArray){
        $key = 0;
        foreach ($role as $key => $row){
            if($key == 0) {
                if (!in_array($row, $NoVgArray)) {
                    $key = $key;
                }
            }
        }

        return $key;
    }
    public static function GetKeyRoleByN($array,$for){
        $key = 0;
        foreach ($for as $row){
            if($key == 0) {
                if (in_array($row, $array)) {
                    $key = array_search($row, $array);
                }
            }
        }

        return $key;
    }
    public static function GetRoleKey($need,$array){
        return array_search($need,$array);
    }

    public static function GetCountRole(array $Roles){
        $TeamCount = ['wolf' => 0,'feramason'=> 0];
        $safeRole = [];
        foreach ($Roles as $row) {
            switch ($row){
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':
                    $TeamCount['wolf'] = (isset($TeamCount['wolf']) ? $TeamCount['wolf'] + 1 : 1);
                    break;
                case 'role_feramason':
                    $TeamCount['feramason'] = (isset($TeamCount['feramason']) ? $TeamCount['feramason'] + 1 : 1);
                    break;
                default:
                    array_push($safeRole,$row);
                    break;
            }
        }
        return $TeamCount;
    }
    public static function SliceRole(array $Roles){

        $enemy = [];
        $safeRole = [];
        foreach ($Roles as $row) {
            switch ($row){
                case 'role_WolfTolle':
                case 'role_WolfGorgine':
                case 'role_Wolfx':
                case 'role_WolfAlpha':
                    array_push($enemy,'wolf');
                    break;
                case 'role_Firefighter':
                case 'role_IceQueen':
                    array_push($enemy,'wolf');
                    break;
                case 'role_Qatel':
                case 'role_Archer':
                    array_push($enemy,'qatel');
                    break;
                case 'role_Bomber':
                    array_push($enemy,'bomber');
                    break;
                case 'role_Vampire':
                case 'role_Bloodthirsty':
                    array_push($enemy,'vampire');
                    break;
                case 'role_ferqe':
                case 'role_Royce':
                    array_push($enemy,'ferqe');
                    break;
                case 'role_dinamit':
                    array_push($enemy,'dinamit');
                break;
                case 'role_davina':
                case 'role_Mummy':
                case 'role_Chiang':
                case 'role_kentvampire':
                case 'role_WolfJadogar':
                case 'role_forestQueen':
                case 'role_monafeq':
                    break;
                default:
                    array_push($safeRole,$row);
                    break;
            }
        }

        return ['enemy' =>  $enemy ,'safe' => $safeRole];
    }


    public static function GetRoleRandom($countPlayer){
        $GameMode = R::Get('GamePl:gameModePlayer');
        $roleList = [];

        $SG = (int) ($countPlayer < 10 ? 5 : ($countPlayer < 20 ? 5 : ($countPlayer < 35 ? 6 : 6 )));
        if($GameMode == "Bomber"){
            for ($is = 0; $is < round(min(max($countPlayer / $SG,1),5)); $is++) {
                array_push($roleList, "role_Bomber");
            }

            $RostaRole = $countPlayer - count($roleList);
            for ($i = 0; $i < $RostaRole; $i++) {
                array_push($roleList, "role_rosta");
            }

            return $roleList;
        }
        if($GameMode == "Foolish"){
            //WolfRolle
            for ($i = 0; $i < round(min(max($countPlayer / 5, 3), 1)); $i++) {
                array_push($roleList, "role_WolfGorgine");
            }

            if($countPlayer >= 11){
                array_push($roleList, "role_WolfJadogar");
                array_push($roleList, "role_ngativ");
                array_push($roleList, "role_PishRezerv");
            }
            // SearRole
            array_push($roleList, "role_pishgo");

            $countFoolish = $countPlayer - count($roleList);
            // FollishRole
            for ($i = 0; $i < round($countFoolish); $i++) {
                array_push($roleList, "role_ahmaq");
            }


            return $roleList;
        }
        if($GameMode !=="Vampire" || ($GameMode == "Vampire" && $countPlayer > 7)) {
            $WolfRole = SE::WolfRole();

            shuffle($WolfRole);
            shuffle($WolfRole);
            shuffle($WolfRole);

            for ($i = 0; $i < round(min(max($countPlayer / 5, 1), 3)); $i++) {
                if(R::Get($WolfRole[$i]) == "on") {
                    array_push($roleList, $WolfRole[$i]);
                }
            }
        }
        if(($GameMode == "Vampire" && R::Get("role_Vampire") == "on") || ($GameMode == "Mighty" && $countPlayer >= 25 && R::Get("role_Vampire") == "on")){
            // به ازای هر 5 نفر 1 ومپایر اضافه شه
            for($i = 0;$i < round($countPlayer / 5); $i++){
                array_push($roleList,'role_Vampire');
            }
        }



        if($GameMode == "Normal"){
            $roles = SE::GetRole();
        }elseif($GameMode == "Mighty"){
            $roles = SE::mightyRole();
        }elseif($GameMode == "Easy"){
            $roles = SE::EasyRole();
        }elseif($GameMode == "Vampire"){
            $roles = SE::VampireRole();
        }elseif($GameMode == "Romantic"){
            $roles = SE::RomanticRole();
        }elseif($GameMode == "WereWolf"){
            $roles = SE::GetWereWolfRole();
        }else{
            $roles = SE::GetRole();
        }

        shuffle($roles);
        shuffle($roles);
        shuffle($roles);
        shuffle($roles);
        shuffle($roles);



        for($i = 0, $iMax = count($roles); $i < $iMax; $i++){
            switch ($roles[$i]){
                case 'role_shekar':
                case 'role_ferqe':
                case 'role_Royce':
                    if(R::Get($roles[$i]) == "on" and $countPlayer >= 11){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Mouse':
                    $checkAllow = HL::CheckAllowGroup('role_Mouse');
                    if(R::Get($roles[$i]) == "on" and $countPlayer >= 11 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_BlackKnight':
                    $checkAllow = HL::CheckAllowGroup('role_BlackKnight');
                    if($countPlayer >= 30 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_dian':
                    $checkAllow = HL::CheckAllowGroup('role_dian');
                    if($countPlayer >= 25 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Magento':
                    $checkAllow = HL::CheckAllowGroup('role_Magento');
                    if($countPlayer >= 20 && $checkAllow ){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_kentvampire':
                    $checkAllow = HL::CheckAllowGroup('role_kentvampire');

                    if($countPlayer >= 25 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Phoenix':
                    $checkAllow = HL::CheckAllowGroup('role_Phoenix');

                    if($countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_betaWolf':
                    $checkAllow = HL::CheckAllowGroup('role_betaWolf');

                    if($countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_hipo':
                    $checkAllow = HL::CheckAllowGroup('role_hipo');

                    if($countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Lilis':
                    $checkAllow = HL::CheckAllowGroup('role_Lilis');

                    if($countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_franc':
                    $checkAllow = HL::CheckAllowGroup('role_franc');

                    if($countPlayer >= 11 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Harly':
                    $checkAllow = HL::CheckAllowGroup('role_Harly');
                    if($countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Joker':
                    $checkAllow = HL::CheckAllowGroup('role_Joker');
                    if($countPlayer >= 20  && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_BrideTheDead':
                    $checkAllow = HL::CheckAllowGroup('role_BrideTheDead');
                    if($countPlayer >= 30 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_javidShah':
                    $checkAllow = HL::CheckAllowGroup('role_javidShah');
                    if($countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_babr':

                    $checkAllow = HL::CheckAllowGroup('role_babr');
                    if($countPlayer >= 7 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_davina':
                    $checkAllow = HL::CheckAllowGroup('role_davina');
                    if( $countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Huntsman':
                    if(R::Get($roles[$i]) == "on" and $countPlayer >= 20){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_isra':
                    if($countPlayer >= 25){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_monafeq':
                    if(R::Get($roles[$i]) == "on"){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_lucifer':
                    if(R::Get($roles[$i]) == "on" && $countPlayer >= 15){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Vampire':
                case 'role_Bloodthirsty':
                    if( (R::Get($roles[$i]) == "on" && $GameMode == "Vampire") || (R::Get($roles[$i]) == "on" && $GameMode == "Mighty" && $countPlayer >= 25)){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case "role_Spy":
                    if(R::Get($roles[$i]) == "on" && $countPlayer >= 11){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Firefighter':
                case 'role_IceQueen':
                    if( R::Get($roles[$i]) == "on" && $countPlayer >= 18){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_enchanter':
                case 'role_forestQueen':
                case 'role_Honey':
                case 'role_WhiteWolf':
                    if(R::Get($roles[$i]) == "on" && $countPlayer >= 20){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_iceWolf':
                    $checkAllow = HL::CheckAllowGroup('role_iceWolf');
                    if( $countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Archer':

                    $checkAllow = HL::CheckAllowGroup('role_Archer');
                    if( $countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;
                case 'role_Knight':
                    if(R::Get($roles[$i]) == "on" && $countPlayer >= 13){
                        array_push($roleList,$roles[$i]);
                    }
                    break;

                case 'role_Cow':

                    $checkAllow = HL::CheckAllowGroup('role_Cow');
                    if( $countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_qhost':

                    $checkAllow = HL::CheckAllowGroup('role_qhost');
                    if( $countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_Princess':

                    $checkAllow = HL::CheckAllowGroup('role_Princess');
                    if( $countPlayer >= 25 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_Chiang':

                    $checkAllow = HL::CheckAllowGroup('role_Chiang');
                    if( $countPlayer >= 25 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_Botanist':

                    $checkAllow = HL::CheckAllowGroup('role_Botanist');
                    if( $countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_Watermelon':

                    $checkAllow = HL::CheckAllowGroup('role_Watermelon');
                    if( $countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_Bomber':

                    $checkAllow = HL::CheckAllowGroup('role_Bomber');
                    if( $countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_dinamit':

                    $checkAllow = HL::CheckAllowGroup('role_dinamit');
                    if( $countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_Mummy':

                    $checkAllow = HL::CheckAllowGroup('role_Mummy');
                    if( $countPlayer >= 15 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                case 'role_hellboy':

                    $checkAllow = HL::CheckAllowGroup('role_hellboy');
                    if( $countPlayer >= 20 && $checkAllow){
                        array_push($roleList,$roles[$i]);
                    }
                    break;


                default:

                    if(R::Get($roles[$i]) == "on" || !R::CheckExit($roles[$i])) {
                        array_push($roleList, $roles[$i]);
                    }
                    break;
            }
        }

        if($GameMode !== "Mighty") {
            if(R::Get("role_feramason") == "on" || !R::CheckExit("role_feramason")) {
                array_push($roleList, 'role_feramason');
                array_push($roleList, 'role_feramason');
            }
        }
        if(in_array('role_shekar',$roleList)){
            array_push($roleList,'role_ferqe');
            array_push($roleList,'role_ferqe');

        }


        if($countPlayer > 11  && R::Get("role_ferqe") == "on")  {
            for ($i = 0; $i < round($countPlayer / $SG); $i++) {
                array_push($roleList, 'role_ferqe');
            }
        }


        if($GameMode !== "Mighty" && R::Get("role_rosta") == "on" ) {
            for ($i = 0; $i < round($countPlayer / $SG); $i++) {
                array_push($roleList, 'role_rosta');
            }
        }

        return $roleList;
    }

    public static function NextGameMessage(){
        $NextList = HL::GetNextGame();
        if($NextList) {
            foreach ($NextList as $row) {

                Request::sendMessage([
                    'chat_id' => $row,
                    'text' => self::$Dt->LG->_('NotifyNewGame', array("{0}" => self::$Dt->group_name)),
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => 'true',
                ]);

            }
        }

        HL::DeleteNextList();
    }

    public static function DeleteMessage(){
        $data =  R::LRange(0,-1,'deleteMessage');
        foreach ($data as $datum) {
            Request::deleteMessage([
                'chat_id' => self::$Dt->chat_id,
                'message_id' => $datum,
            ]);
        }
        R::Del('deleteMessage');
        $dataEditMarkup =  R::LRange(0,-1,'EditMarkup');
        foreach ($dataEditMarkup as $datum) {
            Request::editMessageReplyMarkup([
                'chat_id' => self::$Dt->chat_id,
                'message_id' => $datum,
                'reply_markup' =>  new InlineKeyboard([]),
            ]);
        }
        R::Del('EditMarkup');
    }
    public static function UpdatePlayerList(){

        $checkUpdate = R::CheckExit('GamePl:time_update');
        if($checkUpdate == false){

            if(R::CheckExit('GamePl:Player_list') == false){
                return false;
            }
            $countPlayer = HL::_getCountPlayer();
            if($countPlayer >= R::Get('max_player')){
                R::GetSet(time() - 5 ,'timer');
            }
            Request::editMessageText([
                'chat_id' => self::$Dt->chat_id,
                'message_id' => R::Get('Player_ListMessage_ID'),
                'text' => R::Get('GamePl:Player_list'),
                'parse_mode' => 'HTML'
            ]);
            R::Del('GamePl:Player_list');
        };

        if(R::CheckExit('GamePl:NewUserJoin') == true and R::CheckExit('GamePl:UserJoin') == false){

            $timer = HL::_getGameTimer();
            $LeftTime = $timer - time();


            if($LeftTime > 240){
                $TTime = self::$Dt->LG->_('minut',array("{0}" => "<strong>5</strong>"));
            }elseif($LeftTime > 180){
                $TTime = self::$Dt->LG->_('minut',array("{0}" => "<strong>4</strong>"));
            }elseif($LeftTime > 120){
                $TTime = self::$Dt->LG->_('minut',array("{0}" => "<strong>3</strong>"));
            }elseif($LeftTime > 60){
                $TTime = self::$Dt->LG->_('minut',array("{0}" => "<strong>2</strong>"));
            }elseif($LeftTime > 30){
                $TTime = self::$Dt->LG->_('minuts');
            }elseif($LeftTime > 10){
                $TTime = self::$Dt->LG->_('Secend',array("{0}" => "<strong>30</strong>"));
            }elseif($LeftTime <= 10){
                $TTime = self::$Dt->LG->_('Secend',array("{0}" => "<strong>10</strong>"));
            }
            $Tx =  self::$Dt->LG->_('Join_Message',array("{0}"=> $TTime));
            $re = [];
            $data = R::LRange(0,-1,'GamePl:NewUserJoin');
            R::Del('GamePl:NewUserJoin');
            foreach ($data as $datum) {
                array_push($re,$datum);
            }
            if(count($re)) {
                $REs = implode(PHP_EOL,$re);
                $re = Request::sendMessage([
                    'chat_id' => self::$Dt->chat_id,
                    'text' => $REs.PHP_EOL.$Tx,
                    'parse_mode' => 'HTML'
                ]);
                if($re->isOk()) {
                    R::rpush($re->getResult()->getMessageId(), 'deleteMessage');
                }
            }


        }


    }

}