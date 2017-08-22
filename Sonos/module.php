<?
require_once(__DIR__ .DIRECTORY_SEPARATOR."radio_stations.php");  // radio stations
require_once(__DIR__ .DIRECTORY_SEPARATOR."sonosAccess.php");  // Sonos Access

class Sonos extends IPSModule
{
    
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        
        //These lines are parsed on Symcon Startup or Instance creation
        //You cannot use variables here. Just static values.
        $this->RegisterPropertyString("IPAddress", "");
        $this->RegisterPropertyInteger("TimeOut", 1000);
        $this->RegisterPropertyInteger("DefaultVolume", 15);
        $this->RegisterPropertyInteger("UpdateStatusFrequency", 5);
        $this->RegisterPropertyInteger("UpdateStatusFrequencyNA", 300);
        $this->RegisterPropertyInteger("UpdateGroupingFrequency", 120);
        $this->RegisterPropertyInteger("UpdateGroupingFrequencyNA", 900);
        $this->RegisterPropertyBoolean("GroupForcing", false);
        $this->RegisterPropertyBoolean("MuteControl", false);
        $this->RegisterPropertyBoolean("LoudnessControl", false);
        $this->RegisterPropertyBoolean("BassControl", false);
        $this->RegisterPropertyBoolean("TrebleControl", false);
        $this->RegisterPropertyBoolean("BalanceControl", false);
        $this->RegisterPropertyBoolean("SleeptimerControl", false);
        $this->RegisterPropertyBoolean("PlayModeControl", false);
        $this->RegisterPropertyBoolean("Position", false);
        $this->RegisterPropertyBoolean("MediaImage", false);
        $this->RegisterPropertyInteger("PlaylistImport", 0);
        $this->RegisterPropertyBoolean("DetailedInformation", false);
        $this->RegisterPropertyBoolean("ForceOrder", false);
        $this->RegisterPropertyBoolean("IncludeTunein", false);
        $this->RegisterPropertyString("FavoriteStation", "");
        $this->RegisterPropertyString("WebFrontStations", "");
        $this->RegisterPropertyString("RINCON", "");
        $this->RegisterTimer('SonosTimerUpdateStatus', 5000, 'SNS_UpdateStatus('.$this->InstanceID.');');
        $this->RegisterTimer('SonosTimerUpdateGrouping', 120000, 'SNS_UpdateGrouping('.$this->InstanceID.');');
       
    }
    
    public function ApplyChanges()
    {
        $ipAddress = $this->ReadPropertyString("IPAddress");
		$timeout   = $this->ReadPropertyInteger("TimeOut");
        if ($ipAddress){
            $curl = curl_init();
            curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
			                                CURLOPT_CONNECTTIMEOUT_MS => $timeout,
                                            CURLOPT_URL => 'http://'.$ipAddress.':1400/xml/device_description.xml' ));

            if(!curl_exec($curl))  die('Error: "' . curl_error($curl) . '" - Code: ' . curl_errno($curl));
            if(!$this->ReadPropertyString("RINCON"))
            {
                $this->UpdateRINCON();
                return true;
            }
        }

        //Never delete this line!
        parent::ApplyChanges();

                        
        
        // Start create profiles
        $this->RegisterProfileIntegerEx("Status.SONOS", "Information", "", "",   Array( Array(0, "prev",       "", -1),
                                                                                        Array(1, "play",       "", -1),
                                                                                        Array(2, "pause",      "", -1),
                                                                                        Array(3, "stop",       "", -1),
                                                                                        Array(4, "next",       "", -1),
                                                                                        Array(5, "transition", "", -1) ));
        $this->RegisterProfileIntegerEx("PlayMode.SONOS", "Information", "", "",   Array( Array(0, "Normal",             "", -1),
                                                                                          Array(1, "Repeat all",         "", -1),
                                                                                          Array(2, "Repeat one",         "", -1),
                                                                                          Array(3, "Shuffle no repeat",  "", -1),
                                                                                          Array(4, "Shuffle",            "", -1),
                                                                                          Array(5, "Shuffle repeat one", "", -1) ));
        $this->RegisterProfileInteger("Volume.SONOS",   "Intensity",   "", " %",    0, 100, 1);
        $this->RegisterProfileInteger("Tone.SONOS",     "Intensity",   "", " %",  -10,  10, 1);
        $this->RegisterProfileInteger("Balance.SONOS",  "Intensity",   "", " %", -100, 100, 1);
        $this->RegisterProfileIntegerEx("Switch.SONOS", "Information", "",   "", Array( Array(0, "Off", "", 0xFF0000),
                                                                                        Array(1, "On",  "", 0x00FF00) ));
        $this->RegisterProfileInteger("Position.SONOS",   "Intensity",   "", " %",    0, 100, 1);
        
        //Build Radio Station Associations according to user settings
        if(!IPS_VariableProfileExists("Radio.SONOS"))
            $this->UpdateRadioStations();

        // Build Group Associations according Sonos Instance settings
        if(IPS_VariableProfileExists("Groups.SONOS"))
          IPS_DeleteVariableProfile("Groups.SONOS"); 
        $allSonosInstances = IPS_GetInstanceListByModuleID("{F6F3A773-F685-4FD2-805E-83FD99407EE8}");
        $GroupAssociations = Array(Array(0, "none", "", -1));

        foreach($allSonosInstances as $key=>$SonosID) {
            if (@GetValueBoolean(IPS_GetVariableIDByName("Coordinator",$SonosID)))
              $GroupAssociations[] = Array($SonosID, IPS_GetName($SonosID), "", -1);
        }

        $this->RegisterProfileIntegerEx("Groups.SONOS", "Network", "", "", $GroupAssociations);
        // End Create Profiles     
   
        // Start Register variables and Actions
        // with the following order:
        $positions = array ( 
                             ('Coordinator')     => 10,
                             ('GroupMembers')    => 11,
                             ('MemberOfGroup')   => 12,
                             ('GroupVolume')     => 13,
                             ('Details')         => 20,
                             ('CoverURL')        => 21,
                             ('ContentStream')   => 22,
                             ('Artist')          => 23,
                             ('Title')           => 24,
                             ('Album')           => 25,
                             ('TrackDuration')   => 26,
                             ('Position')        => 27,
                             ('StationID')       => 28,
                             ('nowPlaying')      => 29,
                             ('Radio')           => 40,
                             ('Playlist')        => 41,
                             ('Status')          => 49,
                             ('Volume')          => 50,
                             ('Mute')            => 51,
                             ('Loudness')        => 52,
                             ('Bass')            => 53,
                             ('Treble')          => 54,
                             ('Balance')         => 58,
                             ('Sleeptimer')      => 60,
                             ('PlayMode')        => 61,
                             ('Crossfade')       => 62,
                             ('Position')        => 70,
                             ('MediaImage')      => 71,
                             ('_updateStatus')   => 98,
                             ('_updateGrouping') => 99
                           );
        // 1) general availabe
        IPS_SetHidden( $this->RegisterVariableBoolean("Coordinator", "Coordinator", "", $positions['Coordinator']), true);
        IPS_SetHidden( $this->RegisterVariableString("GroupMembers", "GroupMembers", "", $positions['GroupMembers']), true);
        $this->RegisterVariableInteger("MemberOfGroup", "MemberOfGroup", "Groups.SONOS", $positions['MemberOfGroup']);
        $this->RegisterVariableInteger("GroupVolume", "GroupVolume", "Volume.SONOS", $positions['GroupVolume']);
        $this->RegisterVariableString("nowPlaying", "nowPlaying", "", $positions['nowPlaying']);
        $this->RegisterVariableInteger("Radio", "Radio", "Radio.SONOS", $positions['Radio']);
        $this->RegisterVariableInteger("Status", "Status", "Status.SONOS", $positions['Status']);
        $this->RegisterVariableInteger("Volume", "Volume", "Volume.SONOS", $positions['Volume']);
        $this->EnableAction("GroupVolume");
        $this->EnableAction("MemberOfGroup");
        $this->EnableAction("Radio");
        $this->EnableAction("Status");
        $this->EnableAction("Volume");

        // 2) Add/Remove according to feature activation
        // create link list for deletion of liks if target is deleted
        $links = Array();
        foreach( IPS_GetLinkList() as $key=>$LinkID ){
            $links[] =  Array( ('LinkID') => $LinkID, ('TargetID') =>  IPS_GetLink($LinkID)['TargetID'] );
        }
          
        // 2a) Bass
        if ($this->ReadPropertyBoolean("BassControl")){
            $this->RegisterVariableInteger("Bass", "Bass", "Tone.SONOS", $positions['Bass']);
            $this->EnableAction("Bass");
        }else{
            $this->removeVariableAction("Bass", $links);
        }

        // 2b) Treble
        if ($this->ReadPropertyBoolean("TrebleControl")){
            $this->RegisterVariableInteger("Treble", "Treble", "Tone.SONOS", $positions['Treble']);
            $this->EnableAction("Treble");
        }else{
            $this->removeVariableAction("Treble", $links);
        }

        // 2c) Mute
        if ($this->ReadPropertyBoolean("MuteControl")){
            $this->RegisterVariableInteger("Mute","Mute", "Switch.SONOS", $positions['Mute']);
            $this->EnableAction("Mute");
        }else{
            $this->removeVariableAction("Mute", $links);
        }

        // 2d) Loudness
        if ($this->ReadPropertyBoolean("LoudnessControl")){
            $this->RegisterVariableInteger("Loudness", "Loudness", "Switch.SONOS", $positions['Loudness']);
            $this->EnableAction("Loudness");
        }else{
            $this->removeVariableAction("Loudness", $links);
        }

        // 2e) Balance
        if ($this->ReadPropertyBoolean("BalanceControl")){
            $this->RegisterVariableInteger("Balance", "Balance", "Balance.SONOS", $positions['Balance']);
            $this->EnableAction("Balance");
        }else{
            $this->removeVariableAction("Balance", $links);
        }
        
        // 2f Sleeptimer
        if ($this->ReadPropertyBoolean("SleeptimerControl")){
            $this->RegisterVariableInteger("Sleeptimer", "Sleeptimer", "", $positions['Sleeptimer']);
        }else{
            $this->removeVariable("Sleeptimer", $links);
        }
     
        // 2g Playlists
        if ($this->ReadPropertyInteger("PlaylistImport")){
            if(!IPS_VariableProfileExists("Playlist.SONOS"))
                $this->RegisterProfileIntegerEx("Playlist.SONOS", "Database", "", "", Array());
            $this->RegisterVariableInteger("Playlist", "Playlist", "Playlist.SONOS", $positions['Playlist']);
            $this->EnableAction("Playlist");
        }else{
            $this->removeVariable("Playlist", $links);
        }

        // 2h) PlayMode + Crossfade
        if ($this->ReadPropertyBoolean("PlayModeControl")){
            $this->RegisterVariableInteger("PlayMode",  "PlayMode",  "PlayMode.SONOS", $positions['PlayMode']);
            $this->RegisterVariableInteger("Crossfade", "Crossfade", "Switch.SONOS",   $positions['Crossfade']);
            $this->EnableAction("PlayMode");
            $this->EnableAction("Crossfade");
        }else{
            $this->removeVariableAction("PlayMode", $links);
            $this->removeVariableAction("Crossfade", $links);
        }

        //2i) Detailed Now Playing informtion
        if ($this->ReadPropertyBoolean("DetailedInformation")){
            $this->RegisterVariableString("Details", "Details", "~HTMLBox", $positions['Details']);
            IPS_SetHidden($this->RegisterVariableString("CoverURL",      "CoverURL",      "",         $positions['CoverURL']),true);
            IPS_SetHidden($this->RegisterVariableString("ContentStream", "ContentStream", "",         $positions['ContentStream']),true);
            IPS_SetHidden($this->RegisterVariableString("Artist",        "Artist",        "",         $positions['Artist']),true);
            IPS_SetHidden($this->RegisterVariableString("Title",         "Title",         "",         $positions['Title']),true);
            IPS_SetHidden($this->RegisterVariableString("Album",         "Album",         "",         $positions['Album']),true);
            IPS_SetHidden($this->RegisterVariableString("TrackDuration", "TrackDuration", "",         $positions['TrackDuration']),true);
            IPS_SetHidden($this->RegisterVariableString("Position",      "Position",      "",         $positions['Position']),true);
            if(!@IPS_GetObjectIDByIdent("StationID", $this->InstanceID)){
              $vidStationID = $this->RegisterVariableString("StationID", "StationID", "", $positions['StationID']);
              IPS_SetHidden($vidStationID,true);
              //clear it 5 past the hour 
              $eid = IPS_CreateEvent(1);
              IPS_SetParent($eid, $vidStationID);
              IPS_SetEventCyclicTimeFrom($eid,0,5,0);
              IPS_SetEventCyclic($eid,0,0,0,3,3,1);
              IPS_SetEventScript($eid, "SetValueString($vidStationID,'');" );
              IPS_SetEventActive($eid, true);
            }
        }else{
            $this->removeVariableAction("Details",       $links);
            $this->removeVariableAction("CoverURL",      $links);
            $this->removeVariableAction("ContentStream", $links);
            $this->removeVariableAction("Artist",        $links);
            $this->removeVariableAction("Title",         $links);
            $this->removeVariableAction("Album",         $links);
            $this->removeVariableAction("TrackDuration", $links);
            $this->removeVariableAction("Position",      $links);
            $this->removeVariableAction("StationID",     $links);
        }

        //2j) Position
        if ($this->ReadPropertyBoolean("Position")){
            $this->RegisterVariableInteger("PositionPercent", "Position", "Position.SONOS", $positions['Position']);
        }else{
            $this->removeVariable("PositionPercent", $links);
        }

        //2k) Media image for cover
        if ($this->ReadPropertyBoolean("MediaImage")){
            $this->CreateSonosMediaImage("SonosMediaImageCover", "Cover", $positions['MediaImage']);
            //$this->RegisterVariableInteger("SonosMediaImageCover", "Cover", "", $positions['MediaImage']);
        }else{
            $this->removeMediaImage("SonosMediaImageCover", $links);
        }


        // End Register variables and Actions

        // Set interval for timer for regular status and grouping updates
        // 1) UpdateStatus
        $UpdateStatusFrequency = ($this->ReadPropertyInteger("UpdateStatusFrequency"))*1000;
        $this->SetTimerInterval("SonosTimerUpdateStatus", $UpdateStatusFrequency);

        // 2) _updateGrouping
        $UpdateGroupingFrequency = ($this->ReadPropertyInteger("UpdateGroupingFrequency"))*1000;
        $this->SetTimerInterval("SonosTimerUpdateGrouping", $UpdateGroupingFrequency);

        // Start add scripts for regular status and grouping updates
        // 1) _updateStatus 
        /* replaced intern function
        $statusScriptID = @$this->GetIDForIdent("_updateStatus");
        if ( $statusScriptID === false ){
          $statusScriptID = $this->RegisterScript("_updateStatus", "_updateStatus", file_get_contents(__DIR__ . "/_updateStatus.php"), $positions['_updateStatus']);
        }else{
          IPS_SetScriptContent($statusScriptID, file_get_contents(__DIR__ . "/_updateStatus.php"));
        }

        IPS_SetHidden($statusScriptID,true);
        IPS_SetScriptTimer($statusScriptID, $this->ReadPropertyInteger("UpdateStatusFrequency"));

        // 2) _updateGrouping
        $groupingScriptID = @$this->GetIDForIdent("_updateGrouping");
        if ( $groupingScriptID === false ){
          $groupingScriptID = $this->RegisterScript("_updateGrouping", "_updateGrouping", file_get_contents(__DIR__ . "/_updateGrouping.php"), $positions['_updateGrouping']);
        }else{
          IPS_SetScriptContent($groupingScriptID, file_get_contents(__DIR__ . "/_updateGrouping.php"));
        }

        IPS_SetHidden($groupingScriptID,true);
        IPS_SetScriptTimer($groupingScriptID, $this->ReadPropertyInteger("UpdateGroupingFrequency"));
        */
        // End add scripts for regular status and grouping updates

        // sorting
        if ($this->ReadPropertyBoolean("ForceOrder")){
            foreach($positions as $key=>$position) {
                $id = @$this->GetIDForIdent($key);
                if($id)
                    IPS_SetPosition($id, $position);
            } 
        }
    }
    
    /**
    * Start of Module functions
    */
	
	public function alexaResponse( )
	{
      $response = [];
      
      $this->alexa_get_value('Coordinator',   'bool'           , $response);
      $this->alexa_get_value('GroupMembers',  'instance_names' , $response);
      $this->alexa_get_value('MemberOfGroup', 'fromatted'      , $response);
      $this->alexa_get_value('GroupVolume',   'fromatted'      , $response);
      $this->alexa_get_value('ContentStream', 'string'         , $response);
      $this->alexa_get_value('Artist',        'string'         , $response);
      $this->alexa_get_value('Title',         'string'         , $response);
      $this->alexa_get_value('Album',         'string'         , $response);
      $this->alexa_get_value('TrackDuration', 'string'         , $response);
      $this->alexa_get_value('Position',      'string'         , $response);
      $this->alexa_get_value('nowPlaying',    'string'         , $response);
      $this->alexa_get_value('Radio',         'fromatted'      , $response);
      $this->alexa_get_value('Status',        'fromatted'      , $response);
      $this->alexa_get_value('Volume',        'fromatted'      , $response);
      $this->alexa_get_value('Mute',          'fromatted'      , $response);
      $this->alexa_get_value('Loudness',      'fromatted'      , $response);
      $this->alexa_get_value('Bass',          'fromatted'      , $response);
      $this->alexa_get_value('Treble',        'fromatted'      , $response);
      $this->alexa_get_value('Balance',       'fromatted'      , $response);
      $this->alexa_get_value('Sleeptimer',    'string'         , $response);
      $this->alexa_get_value('PlayMode',      'fromatted'      , $response);
      $this->alexa_get_value('Crossfade',     'fromatted'      , $response);		
	  
	  return $response;
	}

	protected function UpdateStatus()
    {
        $ip = $this->ReadPropertyString("IPAddress");
		$timeout   = $this->ReadPropertyInteger("TimeOut");
        $frequencyms             = $this->ReadPropertyInteger("UpdateStatusFrequency");
        $frequencyNotAvailablems = $this->ReadPropertyInteger("UpdateStatusFrequencyNA");

        // Get all needed Variable IDs
        $vidInstance      = $this->InstanceID;
        $vidVolume        = @$this->GetIDForIdent("Volume");
        $vidMute          = @$this->GetIDForIdent("Mute");
        $vidLoudness      = @$this->GetIDForIdent("Loudness");
        $vidBass          = @$this->GetIDForIdent("Bass");
        $vidTreble        = @$this->GetIDForIdent("Treble");
        $vidBalance       = @$this->GetIDForIdent("Balance");
        $vidMemberOfGroup = @$this->GetIDForIdent("MemberOfGroup");
        $vidStatus        = @$this->GetIDForIdent("Status");
        $vidRadio         = @$this->GetIDForIdent("Radio");
        $vidSleeptimer    = @$this->GetIDForIdent("Sleeptimer");
        $vidNowPlaying    = @$this->GetIDForIdent("nowPlaying");
        $vidGroupMembers  = @$this->GetIDForIdent("GroupMembers");
        $vidDetails       = @$this->GetIDForIdent("Details");
        $vidCoverURL      = @$this->GetIDForIdent("CoverURL");
        $vidStationID     = @$this->GetIDForIdent("StationID");
        $vidContentStream = @$this->GetIDForIdent("ContentStream");
        $vidArtist        = @$this->GetIDForIdent("Artist");
        $vidTitle         = @$this->GetIDForIdent("Title");
        $vidAlbum         = @$this->GetIDForIdent("Album");
        $vidTrackDuration = @$this->GetIDForIdent("TrackDuration");
        $vidPosition      = @$this->GetIDForIdent("Position");
        $vidPositionPercent = @$this->GetIDForIdent("PositionPercent");

        // If the Sonos instance is not available update of grouping makes no sense
        if ( $timeout && Sys_Ping($ip, $timeout) == false )
        {
            $frequencyNotAvailable = $frequencyNotAvailablems*1000;
            $this->SetTimerInterval("SonosTimerUpdateStatus", $frequencyNotAvailable);
            die('Sonos instance '.$ip.' is not available');
        }
        $frequency = $frequencyms*1000;
        $this->SetTimerInterval("SonosTimerUpdateStatus", $frequency);

        $sonos = new SonosAccess($ip);

        $status = $sonos->GetTransportInfo();

        SetValueInteger($vidVolume, $sonos->GetVolume());
        if($vidMute)     SetValueInteger($vidMute,     $sonos->GetMute()     );
        if($vidLoudness) SetValueInteger($vidLoudness, $sonos->GetLoudness() );
        if($vidBass)     SetValueInteger($vidBass,     $sonos->GetBass()     );
        if($vidTreble)   SetValueInteger($vidTreble,   $sonos->GetTreble()   );
        if($vidBalance){
            $leftVolume  = $sonos->GetVolume("LF");
            $rightVolume = $sonos->GetVolume("RF");

            if ( $leftVolume == $rightVolume ){
                SetValueInteger($vidBalance, 0);
            }elseif ( $leftVolume > $rightVolume ){
                SetValueInteger($vidBalance, $rightVolume - 100 );
            }else{
                SetValueInteger($vidBalance, 100 - $leftVolume );
            }
        }

        $MemberOfGroup = 0;
        if($vidMemberOfGroup) $MemberOfGroup = GetValueInteger($vidMemberOfGroup);

        if ($MemberOfGroup){
            // If Sonos is member of a group, use values of Group Coordinator
            SetValueInteger($vidStatus, GetValueInteger(IPS_GetObjectIDByName("Status", $MemberOfGroup)));
            $actuallyPlaying = GetValueString(IPS_GetObjectIDByName("nowPlaying", $MemberOfGroup));
            SetValueInteger($vidRadio, GetValueInteger(IPS_GetObjectIDByName("Radio", $MemberOfGroup)));
            if($vidSleeptimer)    SetValueInteger($vidSleeptimer,   @GetValueInteger(IPS_GetObjectIDByName("Sleeptimer", $MemberOfGroup)));
            if($vidCoverURL)      SetValueString($vidCoverURL,      @GetValueString(IPS_GetObjectIDByName("CoverURL", $MemberOfGroup)));
            if($vidContentStream) SetValueString($vidContentStream, @GetValueString(IPS_GetObjectIDByName("ContentStream", $MemberOfGroup)));
            if($vidArtist)        SetValueString($vidArtist,        @GetValueString(IPS_GetObjectIDByName("Artist", $MemberOfGroup)));
            if($vidAlbum)         SetValueString($vidAlbum,         @GetValueString(IPS_GetObjectIDByName("Album", $MemberOfGroup)));
            if($vidTrackDuration) SetValueString($vidTrackDuration, @GetValueString(IPS_GetObjectIDByName("TrackDuration", $MemberOfGroup)));
            if($vidPosition)      SetValueString($vidPosition,      @GetValueString(IPS_GetObjectIDByName("Position", $MemberOfGroup)));
            if($vidTitle)         SetValueString($vidTitle,         @GetValueString(IPS_GetObjectIDByName("Title", $MemberOfGroup)));
            if($vidDetails)       SetValueString($vidDetails,       @GetValueString(IPS_GetObjectIDByName("Details", $MemberOfGroup)));
            if($vidPositionPercent) SetValueString($vidPositionPercent,       @GetValueInteger(IPS_GetObjectIDByName("PositionPercent", $MemberOfGroup)));
        }else{
            SetValueInteger($vidStatus, $status);
            // Titelanzeige
            $currentStation = 0;

            if ( $status <> 1 ){
                // No title if not playing
                $actuallyPlaying = "";
            }else{
                $positionInfo = $sonos->GetPositionInfo();
                $mediaInfo    = $sonos->GetMediaInfo();

                if ($positionInfo["streamContent"]){
                    $actuallyPlaying = $positionInfo["streamContent"];
                } else {
                    $actuallyPlaying = $positionInfo["title"]." | ".$positionInfo["artist"];
                }

                // start find current Radio in VariableProfile
                $ListRadiostations = new RadioStations();
                $radioStations     = $ListRadiostations->get_available_stations();
                $playingRadioStation = '';
                foreach ($radioStations as $radioStation) {
                    if($radioStation["url"] == htmlspecialchars_decode($mediaInfo["CurrentURI"])){
                        $playingRadioStation = $radioStation["name"];
                        $image               = $radioStation["logo"];
                        break;
                    }
                }

                if( $playingRadioStation == ''){
                    foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('R:0/0')['Result']))->item as $item) {
                        if ($item->res == htmlspecialchars_decode($mediaInfo["CurrentURI"])){
                            $playingRadioStation = (string)$item->xpath('dc:title')[0];
                            break;
                        }
                    }
                }

                $Associations = IPS_GetVariableProfile("Radio.SONOS")["Associations"];
                if(isset($playingRadioStation)){
                    foreach($Associations as $key=>$station) {
                        if( $station["Name"] == $playingRadioStation ){
                            $currentStation = $station["Value"];
                            break;
                        }
                    }
                }
                // end find current Radio in VariableProfile
            }
            SetValueInteger($vidRadio, $currentStation);

            // detailed Information
            if($vidContentStream)   SetValueString($vidContentStream, @$positionInfo['streamContent']);
            if($vidArtist)          SetValueString($vidArtist,        @$positionInfo['artist']);
            if($vidAlbum)           SetValueString($vidAlbum,         @$positionInfo['album']);
            if($vidTrackDuration)   SetValueString($vidTrackDuration, @$positionInfo['TrackDuration'] );
            if($vidPosition)        SetValueString($vidPosition,      @$positionInfo['RelTime']);
            if($vidPositionPercent)        SetValueString($vidPositionPercent,      $this->CalculateSongPosition($positionInfo['RelTime'], $positionInfo['TrackDuration']));
            if($vidTitle){
                if(@$mediaInfo['title']){
                    SetValueString($vidTitle, @$mediaInfo['title']);
                }else{
                    SetValueString($vidTitle, @$positionInfo['title']);
                }
            }
            if($vidDetails){
                if (!isset($stationID)) $stationID = "";
                if(isset($positionInfo)){
                    // SPDIF and analog
                    if(preg_match('/^RINCON_/', $mediaInfo['title']) ){
                        $detailHTML = "";
                        // Radio or stream(?)
                    }elseif($mediaInfo['title']){
                        // get stationID if playing via TuneIn
                        $stationID = preg_replace("#(.*)x-sonosapi-stream:(.*?)\?sid(.*)#is",'$2',$mediaInfo['CurrentURI']);
                        if (!isset($image)) $image = "";
                        if($stationID && $stationID[0]=="s"){
                            if(@GetValueString($vidStationID) == $stationID){
                                $image = GetValueString($vidCoverURL);
                            }else{
                                $serial = substr($this->ReadPropertyString("RINCON"), 7,12);
                                $image = preg_replace('#(.*)<LOGO>(.*?)\</LOGO>(.*)#is','$2',@file_get_contents("http://opml.radiotime.com/Describe.ashx?c=nowplaying&id=".$stationID."&partnerId=IAeIhU42&serial=".$serial));
                            }
                            $this->RefreshMediaImage($image);
                        }else{
                            $stationID = "";
                        }
                        $detailHTML =   "<table width=\"100%\">
                          <tr>
                            <td>
                              <div style=\"text-align: right;\">
                                <div><b>".$positionInfo['streamContent']."</b></div>
                                <div>&nbsp;</div>
                                <div>".$mediaInfo['title']."</div>
                              </div>
                            </td>";

                        if(strlen($image) > 0) {
                            $detailHTML .= "<td width=\"170px\" valign=\"top\">
                              <div style=\"width: 170px; height: 170px; perspective: 170px; right: 0px; margin-bottom: 10px;\">
                              	<img src=\"".@$image."\" style=\"max-width: 170px; max-height: 170px; -webkit-box-reflect: below 0 -webkit-gradient(linear, left top, left bottom, from(transparent), color-stop(0.88, transparent), to(rgba(255, 255, 255, 0.5))); transform: rotateY(-10deg) translateZ(-35px);\">
                              </div>
                            </td>";
                        }

                        $detailHTML .= "</tr>
                        </table>";

                        // normal files
                    }else{
                        $durationSeconds        = 0;
                        $currentPositionSeconds = 0;
                        if($positionInfo['TrackDuration'] && preg_match('/\d+:\d+:\d+/', $positionInfo['TrackDuration']) ){
                            $durationArray          = explode(":",$positionInfo['TrackDuration']);
                            $currentPositionArray   = explode(":",$positionInfo['RelTime']);
                            $durationSeconds        = $durationArray[0]*3600+$durationArray[1]*60+$durationArray[2];
                            $currentPositionSeconds = $currentPositionArray[0]*3600+$currentPositionArray[1]*60+$currentPositionArray[2];
                        }
                        $detailHTML =   "<table width=\"100%\">
                          <tr>
                            <td>
                              <div style=\"text-align: right;\">
                                <div><b>".$positionInfo['title']."</b></div>
                                <div>&nbsp;</div>
                                <div>".$positionInfo['artist']."</div>
                                <div>".$positionInfo['album']."</div>
                                <div>&nbsp;</div>
                                <div>".$positionInfo['RelTime']." / ".$positionInfo['TrackDuration']."</div>
                              </div>
                            </td>";

                        if(isset($positionInfo['albumArtURI'])) {
                            $detailHTML .= "<td width=\"170px\" valign=\"top\">
                              <div style=\"width: 170px; height: 170px; perspective: 170px; right: 0px; margin-bottom: 10px;\">
                              	<img src=\"".@$positionInfo['albumArtURI']."\" style=\"max-width: 170px; max-height: 170px; -webkit-box-reflect: below 0 -webkit-gradient(linear, left top, left bottom, from(transparent), color-stop(0.88, transparent), to(rgba(255, 255, 255, 0.5))); transform: rotateY(-10deg) translateZ(-35px);\">
                              </div>
                            </td>";
                        }

                        $detailHTML .= "</tr>
                        </table>";
                    }
                }
                @SetValueString($vidDetails, $detailHTML);
                if($vidCoverURL){
                    if((isset($image)) && (strlen($image) > 0)) {
                        SetValueString($vidCoverURL, $image);
                    }else{
                        SetValueString($vidCoverURL, @$positionInfo['albumArtURI']);
                    }
                }
                SetValueString($vidStationID,$stationID);
            }

            // Sleeptimer
            if ($vidSleeptimer){
                $sleeptimer = $sonos->GetSleeptimer();
                if($sleeptimer){
                    $SleeptimerArray = explode(":",$sonos->GetSleeptimer());

                    $SleeptimerMinutes = $SleeptimerArray[0]*60+$SleeptimerArray[1];
                    if($SleeptimerArray[2])
                        $SleeptimerMinutes = $SleeptimerMinutes + 1;
                }else{
                    $SleeptimerMinutes = 0;
                }

                SetValueInteger($vidSleeptimer, $SleeptimerMinutes);
            }
        }

        $nowPlaying   = GetValueString($vidNowPlaying);
        if ($actuallyPlaying <> $nowPlaying)
            SetValueString($vidNowPlaying, $actuallyPlaying);

        // Set Group Volume
        $groupMembers        = GetValueString($vidGroupMembers);
        $groupMembersArray   = Array();
        if($groupMembers)
            $groupMembersArray = array_map("intval", explode(",",$groupMembers));
        $groupMembersArray[] = $vidInstance;

        $GroupVolume = 0;
        foreach($groupMembersArray as $key=>$ID) {
            $GroupVolume += GetValueInteger(IPS_GetObjectIDByName("Volume", $ID));
        }

        SetValueInteger($this->GetIDForIdent("GroupVolume"), intval(round($GroupVolume / sizeof($groupMembersArray))));

    }

    protected function UpdateGrouping()
    {
        $sonosInstanceID       = $this->InstanceID;
        $memberOfGoup          = GetValueInteger($this->GetIDForIdent("MemberOfGroup"));
        $coordinatorInIPS      = GetValueBoolean($this->GetIDForIdent("Coordinator"));
        $forceGrouping         = $this->ReadPropertyBoolean("GroupForcing");
        $ipAddress = $this->ReadPropertyString("IPAddress");
        $timeout   = $this->ReadPropertyInteger("TimeOut");
        $frequencyms             = $this->ReadPropertyInteger("UpdateGroupingFrequency");
        $frequencyNotAvailablems = $this->ReadPropertyInteger("UpdateGroupingFrequencyNA");
        $rinconMapping         = Array();
        $allSonosInstances     = IPS_GetInstanceListByModuleID("{F6F3A773-F685-4FD2-805E-83FD99407EE8}");

        // If the Sonos instance is not available update of grouping makes no sense
        if ( $timeout && Sys_Ping($ipAddress, $timeout) == false ){
            // If the Box is not available, only ask every 15 Minutes...
            $frequencyNotAvailable = $frequencyNotAvailablems * 1000;
            $this->SetTimerInterval("SonosTimerUpdateGrouping", $frequencyNotAvailable);
            die('Sonos instance '.$ipAddress.' is not available');
        }

        // If box is available reset to 120 Seconds interval
        $frequency = $frequencyms*1000;
        $this->SetTimerInterval("SonosTimerUpdateGrouping", $frequency);

        $topology = new SimpleXMLElement(file_get_contents('http://'.$ipAddress.':1400/status/topology'));

        foreach($allSonosInstances as $key=>$SonosID) {
            $rincon = IPS_GetProperty($SonosID ,"RINCON");
            $coordinatorInSonos = false;
            foreach ($topology->ZonePlayers->ZonePlayer as $zonePlayer){
                if($zonePlayer->attributes()['uuid'] == $rincon){
                    $group       = (string)$zonePlayer->attributes()['group'];
                    if((string)$zonePlayer->attributes()['coordinator'] === "true"){
                        $coordinatorInSonos = true;
                    }
                    break;
                }
            }
            $instance = Array( ("ID")          => $SonosID,
                ("RINCON")      => $rincon,
                ("COORDINATOR") => $coordinatorInSonos,
                ("GROUP")       => $group  );
            $rinconMapping[] = $instance;
            if($SonosID === $sonosInstanceID){
                $mySettings       = $instance;
                if($memberOfGoup === 0) $MemberOfGroupIPS = $instance;
            }
            if($SonosID === $memberOfGoup){
                $MemberOfGroupIPS = $instance;
            }
        }

        foreach($rinconMapping as $key=>$instance){
            if( $instance['GROUP'] === $mySettings['GROUP'] && $instance['COORDINATOR'] == true){
                $MemberOfGroupSonos = $instance;
                break;
            }
        }

        if(!isset($MemberOfGroupSonos))
            die ("Coordinator Instance for Group of Sonos Instance ".$sonosInstanceID." not found");

        if($MemberOfGroupIPS['ID'] != $MemberOfGroupSonos['ID']){
            if($forceGrouping){
                $groupToSet = $MemberOfGroupIPS['ID'];
            }else{
                $groupToSet = $MemberOfGroupSonos['ID'];
            }
            SNS_SetGroup($sonosInstanceID,$groupToSet);
        }elseif($mySettings['COORDINATOR'] != $coordinatorInIPS){
            if(!$mySettings['COORDINATOR']){
                SetValueBoolean(IPS_GetObjectIDByName("Coordinator", $sonosInstanceID),false);
                @IPS_SetVariableProfileAssociation("Groups.SONOS", $sonosInstanceID, "", "", -1);
            }else{
                SetValueBoolean(IPS_GetObjectIDByName("Coordinator", $sonosInstanceID),true);
                @IPS_SetVariableProfileAssociation("Groups.SONOS", $sonosInstanceID, IPS_GetName($sonosInstanceID), "", -1);
            }
        }
    }

    protected function CalculateSongPosition($Position, $TrackDuration)
    {
        if (!$Position == "")
        {
            $Position = explode(":", $Position);
            $TrackDuration = explode(":", $TrackDuration);
            $PositionSec = ($Position[0]*3600)+($Position[1]*60)+$Position[2];
            $TrackDurationSec = ($TrackDuration[0]*3600)+($TrackDuration[1]*60)+$TrackDuration[2];
            $PositionP = round(($PositionSec/$TrackDurationSec*100), 0);
        }
        else
        {
            $PositionP = 0;
        }
        return $PositionP;
    }

    public function ChangeGroupVolume(int $increment)
    {
        if (!@GetValueBoolean($this->GetIDForIdent("Coordinator"))) die("This function is only allowed for Coordinators");

        $groupMembers        = GetValueString(IPS_GetObjectIDByName("GroupMembers",$this->InstanceID ));
        $groupMembersArray   = Array();
        if($groupMembers)
            $groupMembersArray = array_map("intval", explode(",",$groupMembers));
        $groupMembersArray[] = $this->InstanceID;
            
        foreach($groupMembersArray as $key=>$ID) {
          $newVolume = (GetValueInteger(IPS_GetObjectIDByName("Volume",$ID)) + $increment);
          if ($newVolume > 100){
              $newVolume = 100;
          }elseif($newVolume < 0){
              $newVolume = 0;
          } 
          try{
            SNS_SetVolume($ID, $newVolume );
          }catch (Exception $e){}
        }

        $GroupVolume = 0;
        foreach($groupMembersArray as $key=>$ID) {
          $GroupVolume += GetValueInteger(IPS_GetObjectIDByName("Volume", $ID));
        }

        SetValueInteger(IPS_GetObjectIDByName("GroupVolume", $this->InstanceID), intval(round($GroupVolume / sizeof($groupMembersArray))));
    }

    public function ChangeVolume(int $increment)
    {
        $newVolume = (GetValueInteger($this->GetIDForIdent("Volume")) + $increment);
        try{
          $this->SetVolume($newVolume);
        }catch (Exception $e){throw $e;}
    }

    public function DeleteSleepTimer()
    {
        $targetInstance = $this->findTarget();

        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            include_once(__DIR__ . "/sonosAccess.php");
            (new SonosAccess($ip))->SetSleeptimer(0,0,0);
        }else{
            SNS_DeleteSleepTimer($targetInstance);
        }
    }
    
    public function Next()
    {
        $targetInstance = $this->findTarget();

        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            include_once(__DIR__ . "/sonosAccess.php");
            (new SonosAccess($ip))->Next();
        }else{
            SNS_Next($targetInstance);
        }
    }
    
    public function Pause()
    {
        $targetInstance = $this->findTarget();

        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            SetValue($this->GetIDForIdent("Status"), 2);
            include_once(__DIR__ . "/sonosAccess.php");
            $sonos = new SonosAccess($ip);
            if($sonos->GetTransportInfo() == 1) $sonos->Pause();
        }else{
            SNS_Pause($targetInstance);
        }
    }

    public function Play()
    {
        $targetInstance = $this->findTarget();

        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            SetValue($this->GetIDForIdent("Status"), 1);
            include_once(__DIR__ . "/sonosAccess.php");
            (new SonosAccess($ip))->Play();
        }else{
            SNS_Play($targetInstance);
        }
    }

    public function PlayFiles(array $files, string $volumeChange)
    {
        $ip = $this->getIP();

        include_once(__DIR__ . "/sonosAccess.php");
        $sonos = new SonosAccess($ip);
    
        $positionInfo       = $sonos->GetPositionInfo();
        $mediaInfo          = $sonos->GetMediaInfo();
        $transportInfo      = $sonos->GetTransportInfo();
        $isGroupCoordinator = @GetValueBoolean($this->GetIDForIdent("Coordinator"));
        if($isGroupCoordinator){
          $volume = GetValueInteger($this->GetIDForIdent("GroupVolume")); 
        }else{
          $volume = GetValueInteger($this->GetIDForIdent("Volume")); 
        }

        //adjust volume if needed
        if($volumeChange != 0){
          // pause if playing or remove from group
          if(!$isGroupCoordinator){
            $this->SetGroup(0); 
          }elseif($transportInfo==1){
            try{
              $sonos->Pause();
            }catch (Exception $e){ 
              if ( $e->getMessage() != 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)') throw $e; 
            }
          }
          
          // volume request absolte or relative?
          if($volumeChange[0] == "+" || $volumeChange[0] == "-"){
            if($isGroupCoordinator){
              $this->changeGroupVolume($volumeChange);
            }else{
              $this->ChangeVolume($volumeChange);
            }
          }else{
            if($isGroupCoordinator){
              $this->SetGroupVolume($volumeChange);
            }else{
              $this->SetVolume($volumeChange); 
            }
          }

        }

        foreach ($files as $key => $file) {
          // only files on SMB share or http server can be used
          if (preg_match('/^\/\/[\w,.,\d,-]*\/\S*/',$file) == 1){
            $uri = "x-file-cifs:".$file;
          }elseif (preg_match('/^https{0,1}:\/\/[\w,.,\d,-,:]*\/\S*/',$file) == 1){
            $uri = $file;
          }else{
            throw new Exception("File (".$file.") has to be located on a Samba share (e.g. //ipsymcon.fritz.box/tts/text.mp3) or a HTTP server (e.g. http://ipsymcon.fritz.box/tts/text.mp3)");
          }

          $sonos->SetAVTransportURI($uri);
          $sonos->Play();
          IPS_Sleep(500);
          $fileTransportInfo = $sonos->GetTransportInfo();
          while ($fileTransportInfo==1 || $fileTransportInfo==5){ 
            IPS_Sleep(200);
            $fileTransportInfo = $sonos->GetTransportInfo();
          }
        }

        // reset to what was playing before
        $sonos->SetAVTransportURI($mediaInfo["CurrentURI"],$mediaInfo["CurrentURIMetaData"]);
        if($positionInfo["TrackDuration"] != "0:00:00" && $positionInfo["Track"] > 1)
          try {
            $sonos->Seek("TRACK_NR",$positionInfo["Track"]);
          } catch (Exception $e) { }
        if($positionInfo["TrackDuration"] != "0:00:00" && $positionInfo["RelTime"] != "NOT_IMPLEMENTED" )
          try {
            $sonos->Seek("REL_TIME",$positionInfo["RelTime"]);
          } catch (Exception $e) { }

        if($volumeChange != 0){
          // set back volume
          if($isGroupCoordinator){
            $this->SetGroupVolume($volume);
          }else{
            $this->SetVolume($volume); 
          }
        }

        if ($transportInfo==1){
          $sonos->Play();
        }
    }

    public function PlayFilesGrouping(array $instances, array $files, string $volumeChange)
    {
        $ip = $this->getIP();

        include_once(__DIR__ . "/sonosAccess.php");
        $sonos         = new SonosAccess($ip);
        $transportInfo = $sonos->GetTransportInfo();
        $volume        = GetValueInteger($this->GetIDForIdent("Volume"));

        // pause if playing
        if($transportInfo==1){
          try{
            $sonos->Pause();
          }catch (Exception $e){ 
            if ( $e->getMessage() != 'Error during Soap Call: UPnPError s:Client 701 (ERROR_AV_UPNP_AVT_INVALID_TRANSITION)') throw $e; 
          }
        }

        if($volumeChange != 0){
          // volume request absolte or relative?
          if($volumeChange[0] == "+" || $volumeChange[0] == "-"){
            $this->ChangeVolume($volumeChange);
          }else{
            $this->SetVolume($volumeChange);
          }
        }
        
    
        foreach ($instances as $instanceID => &$settings){
             $ip      = IPS_GetProperty($instanceID ,"IPAddress");
             $timeout = $this->ReadPropertyInteger("TimeOut");
             if ($timeout && Sys_Ping($ip, $timeout) != true){
                 if (Sys_Ping($ip, $timeout) != true){
                     $settings["available"] = false;
                     print $instanceID." is not available\n";
                     continue;
                 }
             }
             
             $settings["available"]     = true;
             $settings["sonos"]         = new SonosAccess($ip);
             $settings["mediaInfo"]     = $settings["sonos"]->GetMediaInfo();
             $settings["positionInfo"]  = $settings["sonos"]->GetPositionInfo();
             $settings["transportInfo"] = $settings["sonos"]->GetTransportInfo();
             $settings["group"]         = GetValueInteger(IPS_GetObjectIDByName("MemberOfGroup", $instanceID));
             $settings["volumeBefore"]  = GetValueInteger(IPS_GetObjectIDByName("Volume", $instanceID));

             if(isset($settings["volume"]) && $settings["volume"] != 0){
               // volume request absolte or relative?
               if($settings["volume"][0] == "+" || $settings["volume"][0] == "-"){
                 SNS_ChangeVolume($instanceID, $settings["volume"]);
               }else{
                 SNS_SetVolume($instanceID, $settings["volume"]);
               }
             }
             
             SNS_SetGroup($instanceID, $this->InstanceID);
        }
        unset($settings);

        $this->PlayFiles($files, 0);
 
        foreach ($instances as $instanceID => $settings){
          if($settings["available"] == false) continue;
          SNS_SetGroup($instanceID, $settings["group"]);
          $settings["sonos"]->SetAVTransportURI($settings["mediaInfo"]["CurrentURI"],$settings["mediaInfo"]["CurrentURIMetaData"]);
          if(@$settings["mediaInfo"]["Track"] > 1 )
            try {     
              $settings["sonos"]->Seek("TRACK_NR",$settings["mediaInfo"]["Track"]);
            } catch (Exception $e) { }
          if($settings["positionInfo"]["TrackDuration"] != "0:00:00" && $settings["positionInfo"]["RelTime"] != "NOT_IMPLEMENTED" )
            try {
              $settings["sonos"]->Seek("REL_TIME",$settings["positionInfo"]["RelTime"]);
            } catch (Exception $e) { }
          SNS_SetVolume($instanceID, $settings["volumeBefore"]);
          if($settings["transportInfo"]==1 && !$settings["group"]) SNS_Play($instanceID);
        }

        if($volumeChange != 0){
          // set back volume
          $this->SetVolume($volume); 
        }

        if($transportInfo==1) $sonos->Play();
    }

    public function Previous()
    {
        $targetInstance = $this->findTarget();

        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            include_once(__DIR__ . "/sonosAccess.php");
            (new SonosAccess($ip))->Previous();
        }else{
            SNS_Previous($targetInstance);
        }
    }
    
    public function RampToVolume(string $rampType,int $volume)
    {
        $ip = $this->getIP();

        SetValue($this->GetIDForIdent("Volume"), $volume);
        include_once(__DIR__ . "/sonosAccess.php");
        (new SonosAccess($ip))->RampToVolume($rampType,$volume);
    }

    public function SetAnalogInput(int $input_instance)
    {
        $ip = $this->getIP();

        if(@GetValue($this->GetIDForIdent("MemberOfGroup")))
          $this->SetGroup(0);

        include_once(__DIR__ . "/sonosAccess.php");
        $sonos = new SonosAccess($ip);
        
        $sonos->SetAVTransportURI("x-rincon-stream:".IPS_GetProperty($input_instance ,"RINCON"));
    }

    public function SetBalance(int $balance)	
    {
        $ip = $this->getIP();

        $leftVolume  = 100;
        $rightVolume = 100;     
        if ( $balance < 0 ){
          $rightVolume = 100 + $balance;
        }else{
          $leftVolume  = 100 - $balance;
        }

        include_once(__DIR__ . "/sonosAccess.php");
        $sonos = (new SonosAccess($ip));
        $sonos->SetVolume($leftVolume,'LF');
        $sonos->SetVolume($rightVolume,'RF');
        if (!$this->ReadPropertyBoolean("BalanceControl")) SetValue($this->GetIDForIdent("Balance"), $balance);
    }
    
    public function SetBass(int $bass)
    {
        $ip = $this->getIP();

        include_once(__DIR__ . "/sonosAccess.php");
        (new SonosAccess($ip))->SetBass($bass);
        if (!$this->ReadPropertyBoolean("BassControl")) SetValue($this->GetIDForIdent("Bass"), $bass);
    }

    public function SetCrossfade(bool $crossfade)
    {
        $targetInstance = $this->findTarget();
      
        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            include_once(__DIR__ . "/sonosAccess.php");
            (new SonosAccess($ip))->SetCrossfade($crossfade);
            if ($this->ReadPropertyBoolean("PlayModeControl")) SetValue($this->GetIDForIdent("Crossfade"), $crossfade);
        }else{
            SNS_SetCrossfade($targetInstance,$crossfade);
        }
    }

    public function SetDefaultGroupVolume()
    {
        if (!@GetValueBoolean($this->GetIDForIdent("Coordinator"))) die("This function is only allowed for Coordinators");

        $groupMembers        = GetValueString(IPS_GetObjectIDByName("GroupMembers",$this->InstanceID ));
        $groupMembersArray   = Array();
        if($groupMembers)
            $groupMembersArray = array_map("intval", explode(",",$groupMembers));
        $groupMembersArray[] = $this->InstanceID;

        foreach($groupMembersArray as $key=>$ID) {
          try{
            SNS_SetDefaultVolume($ID);
          }catch (Exception $e) {}
        }
        
        $GroupVolume = 0;
        foreach($groupMembersArray as $key=>$ID) {
          $GroupVolume += GetValueInteger(IPS_GetObjectIDByName("Volume", $ID));
        }

        SetValueInteger(IPS_GetObjectIDByName("GroupVolume", $this->InstanceID), intval(round($GroupVolume / sizeof($groupMembersArray))));
    }

    public function SetDefaultVolume()
    {
        try{
          $this->SetVolume($this->ReadPropertyInteger("DefaultVolume"));
        }catch(Exception $e){throw $e;}
    }
    
    public function SetGroup(int $groupCoordinator)
    {
        // Instance has Memners, do nothing
        if(@GetValueString($this->GetIDForIdent("GroupMembers"))) return;
        // Do not try to assign to itself
        if($this->InstanceID === $groupCoordinator) $groupCoordinator = 0;

        $startGroupCoordinator = GetValue($this->GetIDForIdent("MemberOfGroup"));

        $ip = $this->getIP();

        // cleanup old group
        if($startGroupCoordinator){
            $groupMembersID = @IPS_GetObjectIDByIdent("GroupMembers",$startGroupCoordinator);
            $currentMembers = explode(",",GetValueString($groupMembersID));
            $currentMembers = array_filter($currentMembers, function($v) { return $v != ""; });
            $currentMembers = array_filter($currentMembers, function($v) { return $v != $this->InstanceID ; });
            SetValueString($groupMembersID,implode(",",$currentMembers));
            if(!count($currentMembers)){
                IPS_SetHidden(IPS_GetVariableIDByName("GroupVolume",$startGroupCoordinator),true);
                IPS_SetHidden(IPS_GetVariableIDByName("MemberOfGroup",$startGroupCoordinator),false);
            }
        }

        // get variable of coordinator members to be updated
        $currentMembers = Array();
        if($groupCoordinator){
            $groupMembersID = @IPS_GetObjectIDByIdent("GroupMembers",$groupCoordinator);
            $currentMembers = explode(",",GetValueString($groupMembersID));
            $currentMembers = array_filter($currentMembers, function($v) { return $v != ""; });
            $currentMembers = array_filter($currentMembers, function($v) { return $v != $this->InstanceID ; });
            if($groupCoordinator)
                $currentMembers[] = $this->InstanceID;

            SetValueString($groupMembersID,implode(",",$currentMembers));
            $uri            = "x-rincon:".IPS_GetProperty($groupCoordinator ,"RINCON");
            SetValueBoolean($this->GetIDForIdent("Coordinator"),false);
            @IPS_SetVariableProfileAssociation("Groups.SONOS", $this->InstanceID, "", "", -1);
        }else{
            $uri            = "";
            SetValueBoolean($this->GetIDForIdent("Coordinator"),true);
            @IPS_SetVariableProfileAssociation("Groups.SONOS", $this->InstanceID, IPS_GetName($this->InstanceID), "", -1);
        }
        
        // update coordinator members
        SetValue($this->GetIDForIdent("MemberOfGroup"), $groupCoordinator);
  
        
        // Set relevant variables to hidden/unhidden
        if ($groupCoordinator){
            $hidden = true;
            IPS_SetHidden(IPS_GetVariableIDByName("GroupVolume",$groupCoordinator),false);
            IPS_SetHidden(IPS_GetVariableIDByName("MemberOfGroup",$groupCoordinator),true);
        }else{
            $hidden = false;
        }
        @IPS_SetHidden($this->GetIDForIdent("nowPlaying"),$hidden);
        @IPS_SetHidden($this->GetIDForIdent("Radio"),$hidden);
        @IPS_SetHidden($this->GetIDForIdent("Playlist"),$hidden);
        @IPS_SetHidden($this->GetIDForIdent("PlayMode"),$hidden);
        @IPS_SetHidden($this->GetIDForIdent("Crossfade"),$hidden);
        @IPS_SetHidden($this->GetIDForIdent("Status"),$hidden);
        @IPS_SetHidden($this->GetIDForIdent("Sleeptimer"),$hidden);
        @IPS_SetHidden($this->GetIDForIdent("Details"),$hidden);
        // always hide GroupVolume, unhide executed on GroupCoordinator a few lines above
        @IPS_SetHidden(IPS_GetVariableIDByName("GroupVolume",$this->InstanceID),true);
        @IPS_SetHidden(IPS_GetVariableIDByName("MemberOfGroup",$this->InstanceID),false);

        include_once(__DIR__ . "/sonosAccess.php");
        (new SonosAccess($ip))->SetAVTransportURI($uri);
    }

    public function SetGroupVolume(int $volume)
    {
        if (!@GetValueBoolean($this->GetIDForIdent("Coordinator"))) die("This function is only allowed for Coordinators");

        $this->ChangeGroupVolume($volume - GetValue($this->GetIDForIdent("GroupVolume")));
    }

    public function SetLoudness(bool $loudness)
    {
        $ip = $this->getIP();
 
        include_once(__DIR__ . "/sonosAccess.php");
        (new SonosAccess($ip))->SetLoudness($loudness);
        if ($this->ReadPropertyBoolean("LoudnessControl")) SetValue($this->GetIDForIdent("Loudness"), $loudness);
    }

    public function SetMute(bool $mute)
    {
        $ip = $this->getIP();

        include_once(__DIR__ . "/sonosAccess.php");
        (new SonosAccess($ip))->SetMute($mute);
        if ($this->ReadPropertyBoolean("MuteControl")) SetValue($this->GetIDForIdent("Mute"), $mute);
    }
    
    public function SetPlaylist(string $name)
    {
        $ip = $this->getIP();

        if(@GetValue($this->GetIDForIdent("MemberOfGroup")))
          $this->SetGroup(0);

        include_once(__DIR__ . "/sonosAccess.php");
        $sonos = new SonosAccess($ip);

        $uri = '';
        foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('SQ:','BrowseDirectChildren',999)['Result']))->container as $container) {
            if ($container->xpath('dc:title')[0] == $name){
              $uri = (string)$container->res;
              break;
            }
        }  

        if($uri === ''){
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('A:PLAYLISTS','BrowseDirectChildren',999)['Result']))->container as $container) {
                if (preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $container->xpath('dc:title')[0]) == $name){
                  $uri = (string)$container->res;
                  break;
                }
            }
        }

        if($uri === '')
            throw new Exception('Playlist \''.$name.'\' not found');

        $sonos->ClearQueue();
        $sonos->AddToQueue($uri);
        $sonos->SetAVTransportURI('x-rincon-queue:'.$this->ReadPropertyString("RINCON").'#0');

    }

    public function SetPlayMode(int $playMode)
    {
        $targetInstance = $this->findTarget();
      
        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            include_once(__DIR__ . "/sonosAccess.php");
            (new SonosAccess($ip))->SetPlayMode($playMode);
            if ($this->ReadPropertyBoolean("PlayModeControl")) SetValue($this->GetIDForIdent("PlayMode"), $playMode);
        }else{
            SNS_SetPlayMode($targetInstance,$playMode);
        }
    }

    public function SetRadioFavorite()
    {
        $this->SetRadio($this->ReadPropertyString("FavoriteStation"));
    }
    
    public function SetRadio(string $radio)
    {
        $ip = $this->getIP();

        if(@GetValue($this->GetIDForIdent("MemberOfGroup")))
          $this->SetGroup(0);

        $sonos = new SonosAccess($ip);

        // try to find Radio Station URL
        $uri = get_station_url($radio);

        if( $uri == ""){
            // check in TuneIn Favorites
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('R:0/0')['Result']))->item as $item) {
                if ($item->xpath('dc:title')[0] == $radio){
                  $uri = (string)$item->res;
                  break;
                }
            }
        }
  
        if( $uri == "")
         throw new Exception("Radio station " . $radio . " is unknown" ); 

        $sonos->SetRadio($uri, $radio);
    }
    
    public function SetSleepTimer(int $minutes)
    {
        $targetInstance = $this->findTarget();

        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            $hours = 0;

            while( $minutes > 59 ){
                $hours   = $hours + 1;
                $minutes = $minutes - 60;
            }

            include_once(__DIR__ . "/sonosAccess.php");
            (new SonosAccess($ip))->SetSleeptimer($hours,$minutes,0);
        }else{
            SNS_SetSleepTimer($targetInstance,$minutes);
        }
    }

    public function SetSpdifInput(int $input_instance)
    {
        $ip = $this->getIP();

        if(@GetValue($this->GetIDForIdent("MemberOfGroup")))
          $this->SetGroup(0);

        include_once(__DIR__ . "/sonosAccess.php");
        $sonos = new SonosAccess($ip);
        
        $sonos->SetAVTransportURI("x-sonos-htastream:".IPS_GetProperty($input_instance ,"RINCON").":spdif");
    }

    public function SetTreble(int $treble)	
    {
        $ip = $this->getIP();

        include_once(__DIR__ . "/sonosAccess.php");
        (new SonosAccess($ip))->SetTreble($treble);
        if (!$this->ReadPropertyBoolean("TrebleControl")) SetValue($this->GetIDForIdent("Treble"), $treble);
    }
    
    public function SetVolume(int $volume)
    {
        $ip = $this->getIP();

        SetValue($this->GetIDForIdent("Volume"), $volume);
        include_once(__DIR__ . "/sonosAccess.php");
        (new SonosAccess($ip))->SetVolume($volume);
    }

    public function Stop()
    {
        $targetInstance = $this->findTarget();

        if($targetInstance === $this->InstanceID){
            $ip = $this->getIP();

            SetValue($this->GetIDForIdent("Status"), 3);
            include_once(__DIR__ . "/sonosAccess.php");
            $sonos = new SonosAccess($ip);
            if($sonos->GetTransportInfo() == 1) $sonos->Stop();
        }else{
            SNS_Stop($targetInstance);
        }
    }

    public function UpdatePlaylists()
    {
        $ip = $this->getIP();

        if(IPS_VariableProfileExists("Playlist.SONOS"))
            IPS_DeleteVariableProfile("Playlist.SONOS");

        include_once(__DIR__ . "/sonosAccess.php");
        $sonos = new SonosAccess($ip);

        $Associations          = Array();
        $Value                 = 1;
        $PlaylistImport        = $this->ReadPropertyInteger("PlaylistImport");

        if( $PlaylistImport === 1 || $PlaylistImport === 3  ){
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('SQ:')['Result']))->container as $container) {
                $Associations[] = Array($Value++, (string)$container->xpath('dc:title')[0], "", -1);
                // associations only support up to 32 variables
                if( $Value === 33 ) break;
            }
        }

        if(($PlaylistImport === 2 || $PlaylistImport === 3) && $Value < 33){
            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('A:PLAYLISTS')['Result']))->container as $container) {
                $Associations[] = Array($Value++, (string)preg_replace($this->getPlaylistReplacementFrom(), $this->getPlaylistReplacementTo(), $container->xpath('dc:title')[0]), "", -1);
                // associations only support up to 32 variables
                if( $Value === 33 ) break;
            }
        }

        $this->RegisterProfileIntegerEx("Playlist.SONOS", "Database", "", "", $Associations);
    }

    public function UpdateRadioStations()
    {
        include_once(__DIR__ . "/radio_stations.php");
        $Associations          = Array();
        $radiostations = new RadioStations();
        $AvailableStations     = $radiostations->get_available_stations();
        $WebFrontStations      = $this->ReadPropertyString("WebFrontStations");
        $WebFrontStationsArray = array_map("trim", explode(",", $WebFrontStations));
        $FavoriteStation       = $this->ReadPropertyString("FavoriteStation");
        $Value                 = 1;

        foreach ( $AvailableStations as $key => $val ) {
            if (in_array( $val['name'], $WebFrontStationsArray) || $WebFrontStations === "<alle>" || $WebFrontStations === "<all>" ) {
                if  ( $val['name'] === $FavoriteStation ){
                    $Color = 0xFCEC00;
                } else {
                    $Color = -1;
                }
                $Associations[] = Array($Value++, $val['name'], "", $Color);
                // associations only support up to 32 variables
                if( $Value === 33 ) break;
            }
        }
       
        if ($this->ReadPropertyBoolean("IncludeTunein") && $Value < 33){
            $ip = $this->getIP();

            include_once(__DIR__ . "/sonosAccess.php");
            $sonos = new SonosAccess($ip);

            foreach ((new SimpleXMLElement($sonos->BrowseContentDirectory('R:0/0')['Result']))->item as $item) {
                $Associations[] = Array($Value++, (string)$item->xpath('dc:title')[0], "", 0x539DE1);
                // associations only support up to 32 variables
                if( $Value === 33 ) break;
            }
        }

        usort($Associations, function($a,$b){return strnatcmp($a[1], $b[1]);});

        $Value = 1;
        foreach($Associations as $Association) {
            $Associations[$Value-1][0] = $Value++ ;
        }

        if(IPS_VariableProfileExists("Radio.SONOS"))
            IPS_DeleteVariableProfile("Radio.SONOS");

        $this->RegisterProfileIntegerEx("Radio.SONOS", "Speaker", "", "", $Associations);
    
    }
 
    public function UpdateRINCON()
    {
        $ip = $this->getIP();

        $curl = curl_init();
        curl_setopt_array($curl, array( CURLOPT_RETURNTRANSFER => 1,
                                        CURLOPT_URL => "http://".$ip.":1400/xml/device_description.xml" ));

        $result = curl_exec($curl);

        if(!$result)
           throw new Exception("Device description could not be read from ".$ip);

        $xmlr = new SimpleXMLElement($result);
        $rincon = str_replace ( "uuid:" , "" , $xmlr->device->UDN );
        if($rincon){
            IPS_SetProperty($this->InstanceID, "RINCON", $rincon );
            IPS_ApplyChanges($this->InstanceID);
        }else{
            throw new Exception("RINCON could not be read from ".$ip);
        }
    }

    /**
    * End of Module functions
    */

    public function RequestAction($Ident, $Value)
    {
        $this->SendDebug("Sonos:", "Request action for ident ". $Ident." with value ".$Value,0);
        $this->SendDebug("Sonos:", "Sonos access in directory: ". __DIR__."/sonosAccess.php" ,0);
        switch($Ident) {
            case "Balance":
                $this->SetBalance($Value);
                break;
            case "Bass":
                $this->SetBass($Value);
                break;
            case "Crossfade":
                $this->SetCrossfade($Value);
                break;
            case "GroupVolume":
                $this->SetGroupVolume($Value);
                break;
            case "Loudness":
                $this->SetLoudness($Value);
                break;
            case "MemberOfGroup":
                $this->SetGroup($Value);
                break;
            case "Mute":
                $this->SetMute($Value);
                break;
            case "PlayMode":
                $this->SetPlayMode($Value);
                break;
            case "Playlist":
                $this->SetPlaylist(IPS_GetVariableProfile("Playlist.SONOS")['Associations'][$Value-1]['Name']);
                SetValue($this->GetIDForIdent($Ident), $Value);
                $this->Play();
                sleep(1);
                SetValue($this->GetIDForIdent($Ident), 0);
                break;
            case "Radio":
                $this->SetRadio(IPS_GetVariableProfile("Radio.SONOS")['Associations'][$Value-1]['Name']);
                SetValue($this->GetIDForIdent($Ident), $Value);
                $this->Play();
                break;
            case "Status":
                switch($Value) {
                    case 0: //Prev
                        $this->Previous();
                        break;
                    case 1: //Play
                        $this->Play();
                        break;
                    case 2: //Pause
                        $this->Pause();
                        break;
                    case 3: //Stop
                        $this->Stop();
                        break;
                    case 4: //Next
                        $this->Next();
                        break;
                }
                break;
            case "Treble":
                $this->SetTreble($Value);
                break;
            case "Volume":
                $this->SetVolume($Value);
                break;
            default:
                throw new Exception("Invalid ident");
        }
    }
    
    protected function getPlaylistReplacementFrom(){
        return  array( 
                       '/\.m3u$/',
                       '/\.M3U$/',
                       '/_/'
                     );
    }

    protected function getPlaylistReplacementTo(){
        return  array( 
                       '',
                       '',
                       ' ' 
                     );
    }

    protected function getIP(){
        $ip      = $this->ReadPropertyString("IPAddress");
        $timeout = $this->ReadPropertyInteger("TimeOut");

        if ($timeout && Sys_Ping($ip, $timeout) != true){
           if (Sys_Ping($ip, $timeout)!= true){
             throw new Exception('Sonos Box '.$ip.' is not available, TimeOut: '.$timeout.'ms.');
           }
        }
        return $ip;   
    }

    protected function findTarget(){
        // instance is a coordinator and can execute command
        if(GetValueBoolean($this->GetIDForIdent("Coordinator")) === true)
            return $this->InstanceID;

        $memberOfGroup = GetValueInteger($this->GetIDForIdent("MemberOfGroup"));
        if($memberOfGroup)
            return $memberOfGroup;
        die("Instance is not a coordinator and group coordinator could not be determined");
    }

    protected function CreateSonosMediaImage($ident, $name, $position)
    {
        $details = GetValue($this->GetIDForIdent("Details")); // Detail Variable des Sonos Players
        $picurlstart = strpos($details, '<img src="');
        $picurlend = strpos($details, '" style="max-width: 170px; max-height: 170px; -webkit-box-reflect');
        $picurllength = $picurlend - ($picurlstart+10);
        $picurl = substr($details, ($picurlstart+10), ($picurllength));
        if ($picurl == "")
        {
            $Content = "";
        }
        else
        {
            $Content = file_get_contents($picurl);
        }
        $MediaID = IPS_CreateMedia(1);                  // Image im MedienPool anlegen
        IPS_SetParent($MediaID, $this->InstanceID); // Medienobjekt einsortieren unter der Sonos Instanz
        IPS_SetIdent ($MediaID, $ident);
        IPS_SetPosition($MediaID, $position);
        IPS_SetMediaCached($MediaID, true);
        // Das Cachen für das Mediaobjekt wird aktiviert.
        // Beim ersten Zugriff wird dieses von der Festplatte ausgelesen
        // und zukünftig nur noch im Arbeitsspeicher verarbeitet.
        $ImageFile = IPS_GetKernelDir()."media".DIRECTORY_SEPARATOR."sonoscover".$name.".jpg";  // Image-Datei
        IPS_SetMediaFile($MediaID, $ImageFile, False);    // Image im MedienPool mit Image-Datei verbinden
        IPS_SetName($MediaID, $name); // Medienobjekt benennen
        IPS_SetInfo ($MediaID, $name);
        IPS_SetMediaContent($MediaID, base64_encode($Content));  //Bild Base64 codieren und ablegen
        IPS_SendMediaEvent($MediaID); //aktualisieren
    }

    protected function CreateTransparentImage()
    {

    }

    protected function CreateCoverMirrorEffect($size, $angle)
    {

    }

    protected function RefreshMediaImage($picurl)
    {
        if($picurl != "")
        {
            $Content = Sys_GetURLContent($picurl);
            $MediaID = $this->GetIDForIdent("SonosMediaImageCover");
            IPS_SetMediaContent($MediaID, base64_encode($Content));  //Bild Base64 codieren und ablegen
            IPS_SendMediaEvent($MediaID); //aktualisieren
            $this->SendDebug("Sonos:", "Refresh cover in media image ". $MediaID,0);
        }
    }

    protected function removeVariable($name, $links){
        $vid = @$this->GetIDForIdent($name);
        if ($vid){
            // delete links to Variable
            foreach( $links as $key=>$value ){
                if ( $value['TargetID'] === $vid )
                     IPS_DeleteLink($value['LinkID']);
            }
            foreach(IPS_GetChildrenIDs($vid) as $key=>$cid){
              if(IPS_EventExists($cid)) IPS_DeleteEvent($cid);
            }
            $this->UnregisterVariable($name);
            $this->SendDebug("Sonos:", "Unregister variable ". $vid,0);
        }
    }

    protected function removeVariableAction($name, $links){
        $vid = @$this->GetIDForIdent($name);
        if ($vid){
            // delete links to Variable
            foreach( $links as $key=>$value ){
                if ( $value['TargetID'] === $vid )
                     IPS_DeleteLink($value['LinkID']);
            }
            foreach(IPS_GetChildrenIDs($vid) as $key=>$cid){
              if(IPS_EventExists($cid)) IPS_DeleteEvent($cid);
            }
            $this->DisableAction($name);
            $this->UnregisterVariable($name);
            $this->SendDebug("Sonos:", "Unregister variable ". $vid,0);
        }
    }

    protected function removeMediaImage($name, $links)
    {
        $MediaID = @$this->GetIDForIdent($name);
        if ($MediaID){
            // delete links to MediaImage
            foreach( $links as $key=>$value ){
                if ( $value['TargetID'] === $MediaID )
                    IPS_DeleteLink($value['LinkID']);
            }
            foreach(IPS_GetChildrenIDs($MediaID) as $key=>$cid){
                if(IPS_EventExists($cid)) IPS_DeleteEvent($cid);
            }

            IPS_DeleteMedia($MediaID, true);
        }
    }
	
    protected function alexa_get_value($variableName, $type, &$response ){
      $vid = @$this->GetIDForIdent($variableName);
      if($vid){
        switch($type){
    		case 'string':
    		  $response[$variableName] = strval(GetValue($vid));
    		  break;
    		case 'bool':
    		  $boolean = GetValueBoolean($vid);
    		    if($boolean){
                  $response[$variableName] = "true";
                }else{
                  $response[$variableName] = "false";
                }
    		  break;
    		case 'fromatted':
    		  $response[$variableName] = GetValueFormatted($vid);
    		  break;
    		case 'instance_names':
              foreach( explode(",", GetValueString($vid) ) as $key=>$instanceID ){
                if($instanceID == 0){
    	          $name_array[] = 'none';
    	        }else{
    	          $name_array[] = IPS_GetName($instanceID);
    	        }
              }
    		
    		  $response[$variableName] = join(",", $name_array);
    		  break;
    	}
      }else{
    	$response[$variableName] = "not configured";
      }
    }	

    //Remove on next Symcon update
    protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
        
        if(!IPS_VariableProfileExists($Name)) {
            IPS_CreateVariableProfile($Name, 1);
        } else {
            $profile = IPS_GetVariableProfile($Name);
            if($profile['ProfileType'] != 1)
            throw new Exception("Variable profile type does not match for profile ".$Name);
        }
        
        IPS_SetVariableProfileIcon($Name, $Icon);
        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
        
    }
    
    protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
        if ( sizeof($Associations) === 0 ){
            $MinValue = 0;
            $MaxValue = 0;
        } else {
            $MinValue = $Associations[0][0];
            $MaxValue = $Associations[sizeof($Associations)-1][0];
        }
        
        $this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, 0);
        
        foreach($Associations as $Association) {
            IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
        }
        
    }
}
?>
