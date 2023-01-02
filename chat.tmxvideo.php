<?php

  /*******************************************************\
 *                  TMX Video Plugin                       *
 ***********************************************************
 *                        Features                         *
 * - Adds a chatcommand connected to the new TMX video	   *
 *   system.                                               *
 * - /video                                                *
 * - /videos                                               *
 ***********************************************************
 *                    Created by malun22                   *
 ***********************************************************
 *              Dependencies: None                         *
 ***********************************************************
 *                         License                         *
 * LICENSE: This program is free software: you can         *
 * redistribute it and/or modify it under the terms of the *
 * GNU General Public License as published by the Free     *
 * Software Foundation, either version 3 of the License,   *
 * or (at your option) any later version.                  *
 *                                                         *
 * This program is distributed in the hope that it will be *
 * useful, but WITHOUT ANY WARRANTY; without even the      *
 * implied warranty of MERCHANTABILITY or FITNESS FOR A    *
 * PARTICULAR PURPOSE.  See the GNU General Public License *
 * for more details.                                       *
 *                                                         *
 * You should have received a copy of the GNU General      *
 * Public License along with this program.  If not,        *
 * see <http://www.gnu.org/licenses/>.                     *
 ***********************************************************
 *                       Installation                      *
 * - Put this plugin in /XASECO/plugins				       *
 * - Activate the plugin in XASECO/plugins.xml             *
 * - Download cacert.pem from                              *
 *   https://curl.se/docs/caextract.html and place it in   *
 *   the XASECO root folder                                *
 \*********************************************************/

global $TMXV;

Aseco::registerEvent("onStartup", "tmxv_onStartup");
Aseco::registerEvent('onNewChallenge','tmxv_onNewTrack');

Aseco::addChatCommand('videos','Sets up the tmx videos command environment');
Aseco::addChatCommand('video','Gives latest video in chat');

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

function chat_video($aseco, $command) {
    global $TMXV;
    $TMXV->onVideoCommand($command);
}

class TMXV {
    private $videos = array();

	public function onStartup() {
		$this->msgConsole('Plugin TMX Video by malun22 initialized.');
	}

    public function onCommand($command) {  
        $player = $command['author'];
        $login = $player->login;

        // Show videos manialink
        if ($this->hasVideos()) {
            $this->showVideosManialink($player);
        } else {
            $this->msgPlayer($login, '{#warning}No videos found for this track.');
        }
    }

    private function hasVideos() {
        return isset($this->videos) && count($this->videos) > 0;
    }

    public function onVideoCommand($command) {
        $player = $command['author'];
        $login = $player->login;

        // split params into arrays & insure optional parameters exist cloned from chat.admin.php by Xymph
        $arglist = explode(' ', $command['params'], 2);
        if (!isset($arglist[1])) $arglist[1] = '';
        $command['params'] = explode(' ', preg_replace('/ +/', ' ', $command['params']));
        if (!isset($command['params'][1])) $command['params'][1] = '';

        if ($command['params'][0] == '') {
            if ($this->hasVideos()) {
                $this->sendVideoInChat($login, $this->videos[0]['LinkId'], $this->videos[0]['Title']);
            } else {
                $this->msgPlayer($login, '{#warning}No videos found for this track.');
            }
        } elseif ($command['params'][0] == 'help') {
            // Show help manialink
            $this->showHelpManialink($login);
        } elseif ($command['params'][0] == 'latest') {
            if ($this->hasVideos()) {
                $this->sendVideoInChat($login, $this->videos[0]['LinkId'], $this->videos[0]['Title']);
            } else {
                $this->msgPlayer($login, '{#warning}No videos found for this track.');
            }
        } elseif ($command['params'][0] == 'oldest') {
            if ($this->hasVideos()) {
                $this->sendVideoInChat($login, $this->videos[count($this->videos)-1]['LinkId'], $this->videos[count($this->videos)-1]['Title']);
            } else {
                $this->msgPlayer($login, '{#warning}No videos found for this track.');
            }
        } else {
            $this->msgPlayer($login, '{#warning}Unknown command, use /videos help for more information.');
        }
    }

    private function saveVideoTitle($title) {
        return str_replace('$', '$$', $title);
    }

    private function sendVideoInChat($login, $videoId, $videoTitle) {
        $this->msgPlayer($login, 'Watch $l[' . $this->buildLink($videoId) . ']' . $this->saveVideoTitle($videoTitle) . '$l{#server} on YouTube.');
    }

    private function showHelpManialink($login) {
        $header = '{#black}/video$g will give the latest video in chat:';

        $help = array();
        $help[] = array('...', '{#black}help',
                        'Shows this help window');
        $help[] = array('...', '{#black}latest',
                        'Gives the latest video in chat');
        $help[] = array('...', '{#black}oldest',
                        'Gives the oldest video in chat');
        $help[] = array();
        $help[] = array('{#black}/videos', '', 'Gives all videos in a window');
        $help[] = array();
		
        // display ManiaLink message
		display_manialink($login, $header, array('Icons64x64_1', 'TrackInfo', -0.01), $help, array(1.1, 0.05, 0.3, 0.75), 'OK');
    }

    private function buildLink($videoId) {
        return 'http://youtu.be/' . $videoId;
    }

    private function showVideosManialink($player) {
        global $aseco;
        
        if ($aseco->server->getGame() == 'TMF') {
            $AmountPlayerStats = count($this->videos)+16;
            
            $header = 'Videos for ' . $aseco->server->challenge->name;
            $player->msgs = array();
            $player->msgs[0] = array(1, $header, array(1), array('Icons64x64_1', 'TrackInfo'));
    
            for ($i = 1; $i <= $AmountPlayerStats; $i++) {
                if (empty($this->videos[$i-1])) {
                    $data[] = array('');
                } else {
                    $data[] = array('$l[' . $this->buildLink($this->videos[$i-1]['LinkId']) . "]" . $this->saveVideoTitle($this->videos[$i-1]['Title']));
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

        $url = 'https://tmnf.exchange/api/videos?fields=LinkId%2CTitle%2CPublishedAt&trackid=' . $tmxid;

        $this->msgConsole('Requesting ' . $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CAINFO, "cacert.pem");
        $output = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($output, true);

        if (isset($result) && isset($result['Results']) && count($result['Results']) > 0) {
            $this->videos = $result['Results'];
            // Sort by publishedAt
            usort($this->videos, function($a, $b) {
                return strtotime($b['PublishedAt']) - strtotime($a['PublishedAt']);
            });
        } else {
            $this->videos = array();
        }        

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