<?php

global $TMXV;

Aseco::registerEvent("onStartup", "tmxv_onStartup");
Aseco::registerEvent('onNewChallenge','tmxv_onNewTrack');

Aseco::addChatCommand('videos','Sets up the tmx videos command environment');

function tmxv_onStartup($aseco) {
	global $TMXV;
	$TMXV = new TMXV();
	$TMXV->onStartup();
}

function tmxv_onNewTrack($aseco, $challenge) {
	// Get the link if available
	global $TMXV;
	$TMXV->onNewTrack($challenge);
}

function chat_videos($aseco, $command) {
	global $TMXV;
	$TMXV->onCommand($command);
}

class TMXV {
    private $config = array();
    private $videos = array();

	public function onStartup() {
		$this->msgConsole('Plugin TMX Video by malun22 initialized.');
	}

    public function onCommand($command) {        
        $player = $command['author'];
        $login = $player->login;

        // split params into arrays & insure optional parameters exist cloned from chat.admin.php by Xymph
        $arglist = explode(' ', $command['params'], 2);
        if (!isset($arglist[1])) $arglist[1] = '';
        $command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
        if (!isset($command['params'][1])) $command['params'][1] = '';

        if ($command['params'][0] == '') {
            // Show videos manialink
            $this->showVideosManialink($player);
        } elseif ($command['params'][0] == 'help') {
            // Show help manialink
        } else {
            $this->msgPlayer($login, 'Unknown command, use /videos help for more information.');
        }
    }

    private function showVideosManialink($player) {
        global $aseco;
        
        if ($aseco->server->getGame() == 'TMF') {
            $AmountPlayerStats = count($this->videos)+16;
            
            $header = 'Videos for ' . $aseco->challenge->name;
            $player->msgs = array();
            $player->msgs[0] = array(1, $header, array(1), array('Icons64x64_1', 'TrackInfo'));
    
            for ($i = 1; $i <= $AmountPlayerStats; $i++) {
                if (empty($this->videos[$i-1])) {
                    $data[] = array('');
                } else {
                    $data[] = array('$l[http://youtu.be/' . $this->videos[$i-1]['LinkId'] . "]" . $this->videos[$i-1]['Title']);
                }
                if ($i % 16 == 0) {
                        $player->msgs[] = $data;
                        $data = array();
                }
            }
            display_manialink_multi($player);
        }
    }

    public function onNewTrack($challenge) {
		if (!isset($this->config['TMXID']) || $this->config['TMXID'] != $challenge->uid) {
            unset($this->videos);
            unset($this->config['TMXID']);

            if (isset($challenge->tmx->id)) {
                $this->config['TMXID'] = $challenge->tmx->id;
                $this->loadVideos($this->config['TMXID']);
            }
		}
	}

    private function loadVideos($tmxid) {
        $this->msgConsole('Requesting videos for track with TMX ID ' . $tmxid);

        $url = 'https://tmnf.exchange/api/videos?fields=LinkId%2CTitle%2CThumbnail&trackid=' . $tmxid;

        $this->msgConsole('Requesting ' . $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CAINFO, "cacert.pem");
        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($output, true);

        if (isset($result) && isset($result['Results']) && count($result['Results']) > 0)
            $this->videos = $result['Results'];
        else
            $this->videos = array();        

        $this->msgConsole('Found ' . count($this->videos) . ' videos for track with TMX ID ' . $tmxid);
    }

    private function msgPlayer($login,$msg) {
		global $aseco;

		$aseco->client->query('ChatSendServerMessageToLogin', $aseco->formatColors('{#server}> ' . $msg), $login);
	}

	private function msgAll($msg) {
		global $aseco;

		$aseco->client->query('ChatSendServerMessage', $aseco->formatColors('{#server}> ' . $msg));
	}

	private function msgConsole($msg) {
		global $aseco;

		$aseco->console('[chat.tmxvideo.php] ' . $msg);
	}
}