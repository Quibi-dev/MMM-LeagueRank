<?php

define("API_KEY", "API_KEY_HERE");
define("API_KEY_TFT", "TFT_API_KEY_HERE");
define("API_URL", "https://euw1.api.riotgames.com/lol/");
define("API_TFT_URL", "https://euw1.api.riotgames.com/tft/");

header('Content-type:application/json;charset=utf-8');

if(isset($_GET["name"])){
    $playerName = $_GET["name"];
}else{
    //$playerName = "Quibi";

}

$result     = array();

$summoner = getSummoner($playerName);                           //Get summoner to figure out what the account ID is.

if(!isset($summoner["accountId"])){
    header('Content-type:application/json;charset=utf-8');      //Could not retrieve account ID, return server response.
    echo json_encode($summoner);
    die;
}

$summonerRank = getHighestRank($playerName);

$result["inGame"]   = getActiveGame($summoner);                 //Checks if summoner is currently in active game
$result["nickname"] = $playerName;
$result["level"]    = $summoner["summonerLevel"];

$result["type"]     = $summonerRank["queueType"];
$result["tier"]     = $summonerRank["tier"];
$result["rank"]     = $summonerRank["rank"];
$result["wins"]     = $summonerRank["wins"];
$result["losses"]   = $summonerRank["losses"];
$result["LP"]       = $summonerRank["leaguePoints"];

echo json_encode($result);
die;


function getHighestRank($summonerName){
    $summoner           = getSummoner($summonerName);                           //Get summoner to figure out what the account ID is.
    $summonerTFT        = getSummonerTFT($summonerName);                        //Get summoner to figure out what the account ID is.
    $summonerRanks      = getSummonerRank($summoner);
    $summonerTFTRank    = getSummonerTFTRank($summonerTFT);   
    
    if(isset($summonerRanks[0]) || isset($summonerRanks[1])){           //If either soloq/flex is set.    
        if(!isset($summonerRanks[0])){
            $highestSRRank = $summonerRanks[1];                       
        }
        if(!isset($summonerRanks[1])){
            $highestSRRank = $summonerRanks[0];
        }
        if(isset($summonerRanks[0]) && isset($summonerRanks[1])){       //Check for highest elo if both are set.
            $highestSRRank  = compareRanks($summonerRanks[0], $summonerRanks[1]);
        }
        
    }else{                                                              //If neither soloq/flex is set, return tft rank.
        if(isset($summonerTFTRank[0])){
            return $summonerTFTRank[0];
        }else{
            return NULL;
        }
        
    }
    if(isset($summonerTFTRank[0])){
        $rank           = compareRanks($highestSRRank, $summonerTFTRank[0]);
    }else{
        return $highestSRRank;
    }
    return $rank;
}

function compareRanks($rank1, $rank2){
    $tiers              = array("IRON", "BRONZE", "SILVER", "GOLD", "PLATINUM", "DIAMOND", "MASTER", "GRANDMASTER", "CHALLENGER");
    $ranks              = array("IV","III", "II", "I");
    if(array_search($rank1["tier"], $tiers) > array_search($rank2["tier"], $tiers)){
        $highestRank = $rank1;
    }else if(array_search($rank2["tier"], $tiers) > array_search($rank1["tier"], $tiers)){
        $highestRank = $rank2;
    }else if(array_search($rank2["tier"], $tiers) == array_search($rank1["tier"], $tiers)){
        if(array_search($rank1["rank"], $ranks) > array_search($rank2["rank"], $ranks)){
            $highestRank = $rank1;
        }else {
            $highestRank = $rank2;
        }
    }
    return $highestRank;
}

function getSummoner($name){
    $summonerIDResponse = callAPI('GET', API_URL.'summoner/v4/summoners/by-name/'.rawurlencode($name).'', false, false);
    return(json_decode($summonerIDResponse, true));
}

function getSummonerRank($summoner){
    $summonerRankResponse = callAPI('GET', API_URL.'league/v4/entries/by-summoner/'.$summoner["id"].'', false, false);
    return(json_decode($summonerRankResponse, true));
}

function getSummonerTFT($name){
    $summonerIDResponse = callAPI('GET', API_TFT_URL.'summoner/v1/summoners/by-name/'.rawurlencode($name).'', false, true);
    return(json_decode($summonerIDResponse, true));
}

function getSummonerTFTRank($summoner){
    $summonerTFTRankResponse = callAPI('GET', API_TFT_URL.'league/v1/entries/by-summoner/'.$summoner["id"].'', false, true);
    return(json_decode($summonerTFTRankResponse, true));
}

function getActiveGame($summoner){
    $liveGameResponse   = callAPI('GET', API_URL.'spectator/v4/active-games/by-summoner/'.$summoner["id"].'', false, false);
    $liveGame           = json_decode($liveGameResponse, true);
    if(isset($liveGame["gameId"])){
        return true;
    }else{
        return false;
    }
}

function callAPI($method, $url, $data, $tft){
   $curl = curl_init();
   if($tft){
       $api_key = API_KEY_TFT;
   }else{
       $api_key = API_KEY;
   }
   switch ($method){
      case "POST":
         curl_setopt($curl, CURLOPT_POST, 1);
         if ($data){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         }
         break;
      case "PUT":
         curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
         if ($data){
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);	
         }            
         break;
      default:
         if ($data){
            $url = sprintf("%s?%s", $url, http_build_query($data));
         }
   }

   // OPTIONS:
   curl_setopt($curl, CURLOPT_URL, $url);
   curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Origin: https://developer.riotgames.com",
        "Accept-Charset: application/x-www-form-urlencoded; charset=UTF-8",
        "X-Riot-Token: ".$api_key."",
        "Accept-Language: nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7"
   ));
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

   // EXECUTE:
   $result = curl_exec($curl);
   if(!$result){die("Connection Failure");}
   curl_close($curl);
   return $result;
}
?>