<?
function get_available_stations(){
  //Taken URLs from  http://wiki.ubuntuusers.de/internetradio/stationen 
  $RadioStations =  Array(
            Array( ('name') => "1LIVE",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s25260.png",
                   ('url')  => "x-rincon-mp3radio://www.wdr.de/wdrlive/media/einslive.m3u" ),
            Array( ('name') => "1LIVE DIGGI", 
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s45087.png",
                   ('url')  => "x-rincon-mp3radio://www.wdr.de/wdrlive/media/mp3/1live-diggi.m3u" ),
            Array( ('name') => "917xfm",   
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s85104.png",
                   ('url')  => "x-rincon-mp3radio://live96.917xfm.de" ),
            Array( ('name') => "Antenne 1",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s25770.png",
                   ('url')  => "x-rincon-mp3radio://stream.antenne1.de/stream1/livestream.mp3" ),
            Array( ('name') => "Antenne Bayern",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s42824.png",
                   ('url')  => "x-rincon-mp3radio://mp3channels.webradio.antenne.de/antenne" ),
            Array( ('name') => "Antenne MV",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s16539.png",
                   ('url')  => "x-rincon-mp3radio://streams.antennemv.de/antennemv-live/mp3-192/amv" ),
            Array( ('name') => "Antenne Thueringen",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s25774.png",
                   ('url')  => "x-rincon-mp3radio://xapp2023227392c40000.f.l.i.lb.core-cdn.net/40000mb/live/app2023227392/w2075033608/live_de_128.mp3"), 
            Array( ('name') => "Bayern 3",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s14991.png",
                   ('url')  => "x-rincon-mp3radio://streams.br.de/bayern3_2.m3u" ),
            Array( ('name') => "bigFM",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s84203.png",
                   ('url')  => "x-rincon-mp3radio://srv05.bigstreams.de/bigfm-mp3-96.m3u" ),
            Array( ('name') => "Bremen Vier",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s25565.png",
                   ('url')  => "x-rincon-mp3radio://httpmedia.radiobremen.de/bremenvier.m3u" ),
            Array( ('name') => "Energy",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s17697.png",
                   ('url')  => "x-rincon-mp3radio://energyradio.de/nuernberg" ),
            Array( ('name') => "FFH",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s17490.png",
                   ('url')  => "x-rincon-mp3radio://streams.ffh.de/radioffh/mp3/hqlivestream.m3u" ),
            Array( ('name') => "FFN",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s8954.png",
                   ('url')  => "x-rincon-mp3radio://player.ffn.de/ffn.mp3" ),
            Array( ('name') => "Hitradio N1",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s25946.png",
                   ('url')  => "x-rincon-mp3radio://webstream.hitradion1.de/hitradion1" ),
            Array( ('name') => "HR3",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s57109.png",
                   ('url')  => "x-rincon-mp3radio://metafiles.gl-systemhaus.de/hr/hr3_2.m3u" ),
            Array( ('name') => "HR-Info",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s17489.png",
                   ('url')  => "x-rincon-mp3radio://www.metafilegenerator.de/HR/hrinfo/mp3/webm.m3u" ),
            Array( ('name') => "KiRaKa",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s55365.png",
                   ('url')  => "x-rincon-mp3radio://www.wdr.de/wdrlive/media/kiraka.m3u" ),
            Array( ('name') => "MDR1",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s1346.png",
                   ('url')  => "x-rincon-mp3radio://avw.mdr.de/livestreams/mdr1_radio_sachsen_live_128.m3u" ),
            Array( ('name') => "MDR Jump",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s6634.png",
                   ('url')  => "x-rincon-mp3radio://www.jumpradio.de/static/webchannel/jump_live_channel_high.m3u"),
            Array( ('name') => "NDR2",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s17492.png",
                   ('url')  => "x-rincon-mp3radio://www.ndr.de/resources/metadaten/audio/m3u/ndr2.m3u" ),
            Array( ('name') => "N-JOY",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s25531.png",
                   ('url')  => "x-rincon-mp3radio://www.ndr.de/resources/metadaten/audio/m3u/n-joy.m3u" ),
            Array( ('name') => "Radio 91.2",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s8923.png",
                   ('url')  => "x-rincon-mp3radio://lokalradiostream.de:8004" ),
            Array( ('name') => "Radio Duisburg",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s78341.png",
                   ('url')  => "x-rincon-mp3radio://edge.live.mp3.mdn.newmedia.nacamar.net/ps-radioduisburg/livestream.mp3.m3u" ),
            Array( ('name') => "Radio Essen",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s47789.png",
                   ('url')  => "x-rincon-mp3radio://edge.live.mp3.mdn.newmedia.nacamar.net/ps-radioessen/livestream.mp3.m3u" ),
            Array( ('name') => "Radio K.W.",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s84621.png",
                   ('url')  => "x-rincon-mp3radio://edge.live.mp3.mdn.newmedia.nacamar.net/ps-radiokw/livestream.mp3" ),
            Array( ('name') => "Radio Lippe",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s96523.png",
                   ('url')  => "x-rincon-mp3radio://edge.live.mp3.mdn.newmedia.nacamar.net/ps-radiolippe/livestream.mp3" ),
            Array( ('name') => "Radio Top40",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s18355.png",
                   ('url')  => "x-rincon-mp3radio://xapp2023227392c40000.f.l.i.lb.core-cdn.net/40000mb/live/app2023227392/w2075033610/live_de_128.mp3"),
            Array( ('name') => "RPR1",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s9014.png",
                   ('url')  => "x-rincon-mp3radio://rpr1.fmstreams.de/stream1.m3u" ),
            Array( ('name') => "SWR1 BW",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s20291.png",
                   ('url')  => "x-rincon-mp3radio://mp3-live.swr.de/swr1bw_m.m3u" ),
            Array( ('name') => "SWR1 RP",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s1561.png",
                   ('url')  => "x-rincon-mp3radio://mp3-live.swr.de/swr1rp_m.m3u" ),
            Array( ('name') => "SWR3",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s24896.png",
                   ('url')  => "x-rincon-mp3radio://mp3-live.swr3.de/swr3_m.m3u" ),
            Array( ('name') => "WDR2",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s2680.png",
                   ('url')  => "x-rincon-mp3radio://www.wdr.de/wdrlive/media/wdr2.m3u" ),
            Array( ('name') => "YouFM",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s24878.png",
                   ('url')  => "x-rincon-mp3radio://metafiles.gl-systemhaus.de/hr/youfm_2.m3u" ),
            Array( ('name') => "Deutschlandfunk",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s42828.png",
                   ('url')  => "x-rincon-mp3radio://www.dradio.de/streaming/dlf.m3u " ),
            Array( ('name') => "OE3",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s8007.png",
                   ('url')  => "x-rincon-mp3radio://mp3stream7.apasf.apa.at:8000" ),
            Array( ('name') => "Sunshine Live",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s10637.png",
                   ('url')  => "x-rincon-mp3radio://stream.hoerradar.de/sunshinelive-mp3-128" ),
            Array( ('name') => "Sunshine Live - classics",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s237965.png",
                   ('url')  => "x-rincon-mp3radio://stream.hoerradar.de/sunshine-classics-mp3-128" ),
            Array( ('name') => "Sunshine Live - trance",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s237967.png",
                   ('url')  => "x-rincon-mp3radio://stream.hoerradar.de/sunshine-trance-mp3-128" ),
            Array( ('name') => "A State Of Trance",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s252026.png",
                   ('url')  => "x-sonosapi-stream:s142421?sid=254" ),
            Array( ('name') => "RevivalKult",
                   ('logo') => "http://cdn-radiotime-logos.tunein.com/s186710.png",
                   ('url')  => "x-sonosapi-stream:s186710?sid=254" )
                         );

  return  $RadioStations ;
}

function get_station_url($name, $RadioStations = null){

  if ( $RadioStations === null ){ $RadioStations = get_available_stations(); };

  foreach ( $RadioStations as $key => $val ) {
      if ($val['name'] === $name) {
          return $RadioStations[$key]['url'];
      }
  }
}

?>
