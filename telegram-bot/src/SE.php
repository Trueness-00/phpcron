<?php
namespace phpcron\CronBot;


class SE
{
    /**
     * Cron object
     *
     * @var \phpcron\CronBot\cron
     */
    private static $CN;



    public static function GetModeRole($Mode){
        if($Mode == "Normal"){
            return self::GetRole();
        }elseif($Mode == "Mighty"){
            return self::mightyRole();
        }elseif($Mode == "Easy"){
            return self::EasyRole();
        }elseif($Mode == "Vampire"){
            return self::VampireRole();
        }elseif($Mode == "Romantic"){
            return self::RomanticRole();
        }elseif($Mode == "WereWolf"){
            return self::GetWereWolfRole();
        }else{
            return self::GetRole();
        }

    }


    public static function GetWereWolfRole(){
        $roles = [
            'role_rosta',
            'role_feramason',
            'role_pishgo',
            'role_karagah',
            'role_tofangdar',
            'role_babr',
            'role_rishSefid',
            'role_Augur',
            'role_Gorgname',
            'role_Nazer',
            'role_Hamzad',
            'role_kalantar',
            'role_Fereshte',
            'role_Ahangar',
            'role_ferqe',
            'role_KhabGozar',
            'role_Khaen',
            'role_Kadkhoda',
            'role_Mast',
            'role_Vahshi',
            'role_Chemist',
            'role_qhost',
            'role_dinamit',
            'role_Princess',
            'role_Shahzade',
            'role_Qatel',
            'role_PishRezerv',
            'role_PesarGij',
            'role_NefrinShode',
            'role_Solh',
            'role_ferqe',
            'role_shekar',
            'role_monafeq',
            'role_ahmaq',
            'role_ferqe',
            'role_faheshe',
            'role_ngativ',
            'role_WolfJadogar',
            'role_trouble',
            'role_Firefighter',
            'role_IceQueen',
            'role_iceWolf',
        ];

        return $roles;
    }

    public static function RomanticRole(){
        $roles =[
            'role_rosta',
            'role_feramason',
            'role_pishgo',
            'role_karagah',
            'role_Joker',
            'role_Phoenix',
            'role_Harly',
            'role_babr',
            'role_Lilis',
            'role_BlackKnight',
            'role_BrideTheDead',
            'role_dian',
            'role_tofangdar',
            'role_rishSefid',
            'role_iceWolf',
            'role_Augur',
           'role_davina',
            'role_Gorgname',
          //  'role_Cow',
            'role_Nazer',
            'role_Hamzad',
            'role_Huntsman',
            'role_kalantar',
            'role_Fereshte',
            'role_Ahangar',
            'role_KhabGozar',
            'role_Khaen',
            'role_Kadkhoda',
            'role_Mast',
            'role_Vahshi',
            'role_Chemist',
            'role_qhost',
            'role_dinamit',
            'role_Princess',
            'role_Shahzade',
            'role_Qatel',
            'role_PishRezerv',
            'role_PesarGij',
            'role_NefrinShode',
            'role_Solh',
            'role_lucifer',
            'role_shekar',
            'role_monafeq',
            'role_ahmaq',
            'role_ferqe',
            'role_franc',
            'role_Mummy',
            'role_Royce',
            'role_faheshe',
            'role_ngativ',
            'role_WolfJadogar',
            'role_trouble',
            'role_Firefighter',
            'role_IceQueen',
            'role_Spy',
            'role_Ruler',
            'role_Honey',
            'role_Knight',
            'role_forestQueen',
            'role_enchanter',
            'role_Archer',
            'role_Vampire',
            'role_Bloodthirsty'

            //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ];

        return $roles;
    }
    public static function GetRole(){
        $roles =[
            'role_rosta',
            'role_feramason',
            'role_davina',
            'role_iceWolf',
            'role_Phoenix',
            'role_Joker',
            'role_Harly',
            'role_pishgo',
            'role_BlackKnight',
            'role_BrideTheDead',
            'role_babr',
            'role_dian',
            'role_karagah',
            'role_elahe',
            'role_Augur',
            'role_tofangdar',
            'role_rishSefid',
            'role_Huntsman',
            'role_Chemist',
            'role_qhost',
            'role_dinamit',
            'role_Princess',
            'role_Gorgname',
            'role_Nazer',
            'role_Hamzad',
            'role_kalantar',
            'role_Fereshte',
            //'role_Cow',
            'role_Sweetheart',
            'role_Ahangar',
            'role_KhabGozar',
            'role_Khaen',
            'role_Kadkhoda',
            'role_Mast',
            'role_Vahshi',
            'role_Shahzade',
            'role_Qatel',
            'role_PishRezerv',
            'role_PesarGij',
            'role_NefrinShode',
            'role_Solh',
            'role_lucifer',
            'role_shekar',
            'role_trouble',
            'role_monafeq',
            //'role_Botanist',
            'role_ahmaq',
            'role_ferqe',
            'role_franc',
            'role_faheshe',
            'role_ngativ',
            'role_WolfJadogar',
            'role_Firefighter',
            'role_IceQueen',
            'role_Spy',
            'role_Ruler',
          // 'role_Sweetheart',
            'role_Honey',
            'role_Knight',
            //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ];

        return $roles;
    }
    public static function EasyRole(){
        $roles =[
            'role_rosta',
            'role_feramason',
            'role_pishgo',
            'role_karagah',
            'role_elahe',
            'role_tofangdar',
            'role_Huntsman',
            'role_rishSefid',
            'role_feramason',
            'role_Gorgname',
            'role_babr',
            'role_lucifer',
            'role_Nazer',
            'role_Hamzad',
            'role_kalantar',
            //'role_Cow',
            'role_Fereshte',
            'role_Ahangar',
            'role_KhabGozar',
            'role_Khaen',
            'role_Kadkhoda',
            'role_Mast',
            'role_Vahshi',
            'role_Shahzade',
            'role_Qatel',
            'role_PishRezerv',
            'role_PesarGij',
            'role_NefrinShode',
            'role_Solh',
            'role_shekar',
            'role_monafeq',
            'role_ahmaq',
            'role_ferqe',
            'role_trouble',
            'role_faheshe',
            'role_ngativ',
            'role_WolfJadogar',
            'role_Firefighter',
            'role_Ruler',
            'role_WhiteWolf',
            'role_iceWolf',
          //  'role_Sweetheart',
            //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ];

        return $roles;
    }


    public static function VampireRole(){
        $roles =[
            'role_rosta',
            'role_feramason',
            'role_pishgo',
            'role_karagah',
            //'role_khenyager',

            'role_davina',
            'role_elahe',
            'role_tofangdar',
            'role_BlackKnight',
            'role_dian',
            'role_BrideTheDead',
            'role_Augur',
            'role_babr',
            'role_Lilis',
            'role_rishSefid',
            'role_feramason',
            'role_Gorgname',
            'role_Sweetheart',
             'role_lucifer',
          //  'role_Cow',
            'role_Nazer',
            'role_Hamzad',
            'role_kalantar',
            'role_Huntsman',
            'role_Fereshte',
            'role_Ahangar',
            'role_KhabGozar',
            'role_Khaen',
            'role_Kadkhoda',
            'role_Chemist',
            'role_qhost',
            'role_dinamit',
            'role_Princess',
            'role_Mast',
            'role_Vahshi',
            'role_Shahzade',
            'role_Qatel',
            'role_PishRezerv',
            'role_PesarGij',
            'role_NefrinShode',
            'role_Solh',
            'role_shekar',
            'role_monafeq',
            'role_ahmaq',
            'role_ferqe',
            'role_franc',
            'role_Mummy',
            'role_faheshe',
            'role_ngativ',
            'role_WolfJadogar',
            'role_Firefighter',
            'role_trouble',
            'role_Ruler',
            'role_Vampire',
            'role_kentvampire',
            'role_Bloodthirsty'
            //  'role_Sweetheart',
            //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ];

        return $roles;
    }


    public static function WolfRole(){
        $roles = [
            'role_WolfTolle',
            'role_WolfGorgine',
            'role_Wolfx',
            'role_WolfAlpha',
        ];

        return $roles;
    }

    // start mighty Mode
    public static function mightyRole(){
        $roles =[
            'role_pishgo',
            'role_karagah',
           // 'role_khenyager',
            'role_Magento',
            'role_Joker',
            'role_Harly',
            'role_elahe',
            'role_tofangdar',
            'role_Sweetheart',
            'role_babr',
            'role_BlackKnight',
            'role_BrideTheDead',
            'role_dian',
            'role_Augur',
            'role_rishSefid',
            'role_Phoenix',
            'role_Nazer',
            'role_Hamzad',
            'role_kalantar',
          //  'role_Cow',
            'role_Fereshte',
            'role_Lilis',
            'role_Huntsman',
            'role_Ahangar',
            'role_KhabGozar',
            'role_Kadkhoda',
            'role_Mast',
            'role_Vahshi',
            'role_Shahzade',
            'role_Qatel',
            'role_PishRezerv',
            'role_Solh',
            'role_shekar',
            'role_monafeq',
            'role_lucifer',
           // 'role_Botanist',
            'role_ferqe',
            'role_ferqe',
            'role_ferqe',
            'role_franc',
            'role_Mummy',
            'role_Royce',
            'role_WhiteWolf',    
            'role_iceWolf',
            'role_faheshe',
            'role_WolfJadogar',
            'role_forestQueen',
            'role_davina',
            'role_Firefighter',
            'role_IceQueen',
            'role_Spy',
            'role_Ruler',
            'role_Chemist',
            'role_dinamit',
            'role_qhost',
            'role_dinamit',
            'role_Princess',
            'role_enchanter',
           // 'role_Sweetheart',
            'role_Archer',
            'role_Honey',
            'role_Knight',
            'role_trouble',
            'role_Vampire',
            'role_kentvampire',
            'role_Bloodthirsty'
            //
////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        ];

        return $roles;
    }

    public static function GetRoleTeam($role){
        $team = false;
        switch ($role){
            case 'role_rosta':
            case 'role_feramason':
            case 'role_pishgo':
            case 'role_karagah':
            case 'role_elahe':
            case 'role_tofangdar':
            case 'role_rishSefid':
            case 'role_Gorgname':
            case 'role_Nazer':
            case 'role_kalantar':
            case 'role_babr':
            case 'role_Cow':
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
            case 'role_trouble':
            case 'role_Huntsman':
            case 'role_Mouse':
            case 'role_Chemist':
            case 'role_Augur':
            case 'role_Princess':
            case 'role_qhost':
            case 'role_Phoenix':
                $team = "rosta";
                break;
            case 'role_monafeq':
                $team = "monafeq";
                break;
            case 'role_khenyager':
                $team = "khenyager";
             break;
             case 'role_dozd':
                $team = "dozd";
             break;
             case 'role_dinamit':
                $team = "dinamit";
             break;
            case 'role_Joker':
            case 'role_Harly':
                $team = "joker";
            break;
            case 'role_Bomber':
                $team = "Bomber";
             break;
            case 'role_Hamzad':
                $team = "hamzad";
              break;
            case 'role_ferqe':
            case 'role_Royce':
            case 'role_Mummy':
           case 'role_franc':

                $team = "ferqeTeem";
                break;
            case 'role_Qatel':
            case 'role_Archer':
            case 'role_davina':
                $team = "qatel";
                break;
            case 'role_lucifer':
                $team = "lucifer";
                break;
            case 'role_WolfJadogar':
            case 'role_WolfTolle':
            case 'role_WolfGorgine':
            case 'role_iceWolf':
            case 'role_Wolfx':
            case 'role_WolfAlpha':
            case 'role_Honey':
            case 'role_enchanter':
            case 'role_WhiteWolf':
            case 'role_forestQueen':
            case 'role_betaWolf':
                $team = "wolf";
                break;
            case 'role_Firefighter':
            case 'role_IceQueen':
            case 'role_Lilis':
            case 'role_Magento':
                 $team = "Firefighter";
            break;
            case 'role_Vampire':
            case 'role_Bloodthirsty':
            case 'role_Chiang':
            case 'role_kentvampire':
                $team = "vampire";
            break;
            case 'role_BlackKnight':
            case 'role_BrideTheDead':
            case 'role_dian':
                $team = "black";
            break;
        }

        return $team;
    }

    public static function _W($role,$role_list,$teamCount){
        switch ($role){
            case 'role_rosta':
                return 1;
             break;
             case 'role_Watermelon':
                return 0;
             break;
            case 'role_feramason':
                return 1 * $teamCount['feramason'];
                break;
            case 'role_lucifer':
                return 17;
                break;
            case 'role_Chemist':
                return 10;
                break;
            case 'role_Bloodthirsty':
                return 10;
                break;
            case 'role_Vampire':
                return 8;
                break;
            case 'role_pishgo':
                return 7;
                break;
            case 'role_Knight':
                return 8;
            break;
            case 'role_Ruler':
                return 4;
                break;
                case 'role_Botanist':
                return 6;
                break;
            case 'role_karagah':
                return 6;
                break;
            case 'role_elahe':
                return 2;
                break;
            case 'role_tofangdar':
                return 6;
                break;
            case 'role_rishSefid':
                return 5;
                break;
            case 'role_Gorgname':
                return -1;
                break;
            case 'role_Nazer':
                if(in_array('role_pishgo',$role_list)){
                    return 6;
                }
                return 2;
                break;
            case 'role_Hamzad':
                return 2;
                break;
            case 'role_kalantar':
                return 6;
                break;
            case 'role_Fereshte':
                return 7;
                break;
            case 'role_Ahangar':
                return 2;
                break;
            case 'role_KhabGozar':
                return 3;
                break;
            case 'role_Khaen':
                return 0;
                break;
            case 'role_Kadkhoda':
                return 4;
                break;
            case 'role_Mast':
                return 3;
                break;
            case 'role_Vahshi':
                return 1;
                break;
            case 'role_Shahzade':
                return 3;
                break;
            case 'role_faheshe':
                return 6;
                break;
            case 'role_ngativ':
                return 4;
                break;
            case 'role_ahmaq':
                return 3;
                break;
            case 'role_PishRezerv':
                return 6;
                break;
            case 'role_PesarGij':
                return -1;
                break;
            case 'role_NefrinShode':
                return 1 - $teamCount['wolf'];
                break;
            case 'role_Solh':
                return 6;
                break;
            case 'role_shekar':
                return 7;
                break;
            case 'role_Spy':
                return 5;
            break;
            case 'role_Sweetheart':
                return 4;
            break;
            case 'role_ferqe':
                return 10;
                break;
            case 'role_WolfJadogar':
                return 2;
                break;
             case 'role_WhiteWolf':
                return 12 + $teamCount['wolf'];
                break;
            case 'role_WolfTolle':
                return 12;
                break;
            case 'role_WolfGorgine':
                return 10;
                break;
            case 'role_Wolfx':
                return 11;
                break;
            case 'role_WolfAlpha':
                return 12;
                break;
            case 'role_Honey':
                return 9;
                break;
            case 'role_enchanter':
                return 8;
                break;
            case 'role_forestQueen':
                return 6;
                break;
            case 'role_Qatel':
                return 15;
                break;
            case 'role_Archer':
                return 14;
                break;
            case 'role_monafeq':
                return 1;
                break;
            case 'role_Firefighter':
                return 15;
                break;
            case 'role_IceQueen':
                return 15;
                break;
            case 'role_Royce':
                return 10;
                break;
            case 'role_trouble':
                return 8;
                break;
                case 'role_Huntsman':
                return 8;
                break;
            default:
                return 0;
            break;
        }
    }

    public static function _s($key){
        switch ($key){
            case 'alpha_convert': // درصد تبدیل آلفا
                return 20;
            break;
            case 'Enchanter_Conver': // درصد تبدیل افسونگر
                return 30;
            break;
            case 'forestQueen_Convert': // درصد تبدیل افسونگر
                return 10;
            break;
            case 'HunterKillWolfChanceBase':
                return 30;
            break;
            case 'HunterKillVampireChanceBase':
                return 30;
            break;
            case 'RulerSecendVote':
                return 40;
            break;
            case 'VampireChangeWolfD':
                return 40;
            break;
            case 'VampireChangeWolfDU':
                return 50;
             break;
            case 'VampireChangeWolfC':
                return 10;
                break;
            case 'WolfDeadChnageInVampie':
                return 40;
                break;
            case 'CultConvertVampie':
                return 50;
                break;
            case 'KalanVampireDead':
                return 30;
                break;
            case 'BVampireChangeConvet':
                return 40;
                break;
            case 'VampireChangeConvet':
                return 20;
                break;
            case 'VampireChangeNotKill':
                return 50;
                break;
            case 'DodgeQatelDead':
                return 35;
                break;
            case 'DodgeWolfDead':
                return 35;
                break;
             case 'DodgeBloodDead':
                return 50;
                break;
            case 'ChemistSuccessChance':
                return 50;
                break;
        }
    }

    public static function GetGif($id){

        switch ($id){
            case 'eat_wolf':
                $key = 'Qu7oZZlivswWxP3lFz';
             break;
            case 'joker_kill_you':
                $key = '7OnmxnCDG2uZy4QcO7'; //ok
                break;
            case 'kill_killer':
                $key = 'hqUur3CbdAVXRTtxSC';
            break;
            case 'eat_vampire':
                $key = 'hR6EaKMgC0vw3L3Y5m';
                break;
                case 'bomber':
                $key = 'Tc21Z2hhS1nOZaib6Y';
                break;
            default:
                $key = false;
            break;
        }

        if($key){
            return "https://media.giphy.com/media/{$key}/giphy.gif";
        }

        return false;
    }

    public static function _GetTop($Role){

        switch ($Role) {
            case 'role_rosta':
                $top = 7;
                break;
              case 'role_Watermelon':
                $top = 30;
                break;
            case 'role_feramason':
                $top = 6;
                break;
            case 'role_pishgo':
                $top = 11;
                break;
            case 'role_karagah':
                $top = 12;
                break;
            case 'role_elahe':
                $top = 5;
                break;
            case 'role_tofangdar':
                $top = 11;
                break;
            case 'role_rishSefid':
                $top = 4;
                break;
            case 'role_Gorgname':
                $top = 4;
                break;
            case 'role_Nazer':
                $top = 11;
                break;
            case 'role_kalantar':
                $top = 7;
                break;
            case 'role_Fereshte':
                $top = 10;
                break;
            case 'role_Ahangar':
                $top = 7;
                break;

            case 'role_KhabGozar':
                $top = 7;
                break;
            case 'role_Khaen':
                $top = 2;
                break;
            case 'role_Kadkhoda':
                $top = 10;
                break;
            case 'role_Mast':
                $top = 4;
                break;
            case 'role_Vahshi':
                $top = 6;
                break;
            case 'role_Shahzade':
                $top = 8;
                break;
            case 'role_Qatel':
                $top = 12;
                break;
            case 'role_PishRezerv':
                $top = 6;
                break;
            case 'role_PesarGij':
                $top = 4;
                break;

            case 'role_NefrinShode':
                $top = 3;
                break;
            case 'role_Solh':
                $top = 5;
                break;
            case 'role_WolfJadogar':
                $top = 9;
                break;
            case 'role_WolfTolle':
                $top = 12;
                break;
            case 'role_WolfGorgine':
                $top = 9;
                break;
            case 'role_Wolfx':
                $top = 10;
                break;

            case 'role_WolfAlpha':
                $top = 13;
                break;

            case 'role_shekar':
                $top = 12;
                break;
            case 'role_monafeq':
                $top = 10;
                break;
            case 'role_ahmaq':
                $top = 6;
                break;
            case 'role_ferqe':
                $top = 5;
                break;
            case 'role_faheshe':
                $top = 9;
                break;
            case 'role_ngativ':
                $top = 6;
                break;
            case 'role_lucifer':
                $top = 6;
                break;
            case 'role_clown':
                $top = 8;
                break;
            case 'role_Honey':
                $top = 10;
                break;
            case 'role_Judge':
                $top = 6;
                break;
            case 'role_Spy':
                $top = 8;
                break;
            case 'role_Sweetheart':
                $top = 10;
                break;
            case 'role_Knight':
                $top = 10;
                break;
            case 'role_Botanist':
                $top = 8;
                break;
            case 'role_Royce':
                $top = 10;
                break;
            case 'role_Archer':
                $top = 8;
                break;
            case 'role_enchanter':
                $top = 10;
                break;
            case 'role_WhiteWolf':
                $top = 10;
                break;
            case 'role_forestQueen':
                $top = 9;
                break;
            case 'role_IceQueen':
                $top = 10;
                break;
            case 'role_Firefighter':
                $top = 10;
                break;
            case 'role_Vampire':
                $top = 9;
                break;
            case 'role_Bloodthirsty':
                $top = 10;
                break;
            case 'role_trouble':
                $top = 10;
            break;
            case 'role_Huntsman':
                $top = 10;
            break;
            default:
                $top = 0;
            break;
        }



        return $top;

    }



}
