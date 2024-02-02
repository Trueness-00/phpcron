<?php

namespace phpcron\CronBot;
use Longman\TelegramBot\Request;
use MongoDB\Client;
use RandomLib\Factory;
use SecurityLib\Strength;

class cron
{
    /**
     * Version
     *
     * @var string
     */
    protected $version = '0.1.1';

    /**
     * Group ID
     *
     * @var string
     */
    protected $group_id = '';

    /**
     * Game ID
     *
     * @var string
     */
    public $game_id = '';

    public $mute_die = '';
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
          //  'password' => "zVp7wzN9vP",
        ));

        $this->redis = $redis;
        $this->collection  = (new Client())->wop;


        $this->chat_id = (float) $data['group_id'];
        $this->game_id = $data['game_id'];
        $this->game_mode = ($data['game_mode'] ? $data['game_mode'] : "general");
        $this->def_lang = $data['def_lang'] ?? "fa";
        $this->StartAt = $data['StartAt'];
        $this->JoinLink = JOIN_URL.$data['game_id'];
        $factory = new Factory();
        $this->R = $factory->getGenerator(new Strength(Strength::MEDIUM));
        $this->data = $data;
        $LG = new Lang(FALSE);
        $LG->load($data['game_mode']."_".$data['def_lang'], FALSE);
        $this->LG = $LG;

        $L = new Lang(FALSE);
        $L->load("main_".$data['def_lang'], FALSE);
        $this->L = $L;

        R::initialize($this);
        $DieSettingMute = (R::CheckExit('mute_die') ? (R::Get('mute_die') == "onr" ? true : false) : false);
        $this->mute_die  = $DieSettingMute;
        $this->group_name =  ( R::Get('group_link') !== "") ? '<a href="' . R::Get('group_link') . '">' . R::Get('group_name') . '</a>' : R::Get('group_name') ;

        HL::initialize($this);
        Handler::initialize($this);
        join::initialize($this);
        NG::initialize($this);


        VT::initialize($this);

        DY::initialize($this);

    }

    public function handler(){
        Handler::Handel();



        echo "Update Group :".$this->group_name;

        sleep(2);
    }
}




