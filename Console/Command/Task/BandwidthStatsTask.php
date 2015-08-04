<?php
//ARG 1: YYYY-M-D (month is not 0 based)
//ARG 2: CID || CID,CID,CID
App::uses('HttpSocket', 'Network/Http');
class BandwidthStatsTask extends Shell {
    public $uses = array('BandwidthStat','Clients');
    function execute(){
        $this->out('Running');
        $this->out(' ');


        if(!isset($this->args[0])){
            $today = date('Y-m-d', strtotime('-1 day', strtotime("now")));
        }else{
            $today = $this->args[0];
        }

        if(!isset($this->args[1])){
            $clients = $this->Clients->find('list', array(
                'conditions'=>array('Clients.active'=>1),
                'fields'=>array(
                    'Clients.id',
                    'Clients.max_monthly_bandwidth'
                )
            ));
        }else{
            $this->out($this->args[1]);
            $cid_arr = explode(',', $this->args[1]);
            $this->out($cid_arr);
            $clients = $this->Clients->find('list', array(
                'conditions'=>array(
                    'Clients.active'=>1,
                    'Clients.id'=>$cid_arr
                ),
                'fields'=>array(
                    'Clients.id',
                    'Clients.max_monthly_bandwidth'
                )
            ));
        }

        $http = new HttpSocket();
        $http->configAuth('Digest', 'MDP7E8VDYN99H7E3BW4ECA9HBD2R1P', 'w0eyh72s');

        $servers = array(
            '8e4203d8-2ae8-4f88-a342-8e254bc815a3', //S3 ARCHIVE
            '5ee8c510-7074-4176-9d72-7b7a82f25beb', //S3 MEDIA
            '12e38f77-2f92-4a7c-8bc9-93743f99451a', //CF scdn.fordela.com
            'e58126c9-1018-4e15-bfde-9388ca4b5ef3', //CF a.fordela.com d19vifrtijsvqo
            '9118f9e1-d37b-4aeb-ba81-5c1893079afb', //CF m.fordela.com d1zyjm1lvh3ioe
            'bd30d939-4635-4ca1-9bad-e680f2168131', //CF h.fordela.com d2bdodfxgs2eb9
            '62474798-3db3-48f3-886c-9bdeffad3d8d', //CF cdn.fordela.com d2j0r9v6dd0xi5
            'a5ef298a-d21b-4677-8629-a3e2f87b7c1c', //CF media.fordela.com.s3.amazonaws.com d2l059irv4lhhc
            '5ce24fa0-53ab-4950-8371-785d7a5936bd', //CF archive.fordela.com.s3.amazonaws.com E3UKUCDEFFYJLZ
            '1c158cee-bd73-40ce-ad92-bd531265c0ad', //CF cdnmedia.fordela.com E1STJ5QMC4RO4W
            'adf1cab3-013f-4ebc-86e0-b65372ec5af4'  //CF media-hls.fordela.com.s3.amazonaws.com E36U5TVQAE14P5
        );

        $allBandwidth = array();
        $allBandwidthData = array();

        foreach($servers as $server){
            $response = json_decode($http->get('https://api.qloudstat.com/v13/'.$server.'/uri/bandwidth_outbound?graph=values&from='.$today.'&to='.$today)->body);
            $datas = $response->table->rows;
            foreach($datas as $data){
                if($data->c[0]->v !== 'unknown'){
                    $urlArray = explode('/', $data->c[0]->v);
                    if($urlArray[0] == ''){
                        $i = 1;
                    }else{
                        $i = 0;
                    }

                    if(isset($allBandwidth[$urlArray[$i]]) && is_numeric($urlArray[$i])){
                        if($urlArray[$i] == '2543'){$this->out(json_encode(array($urlArray[$i], intval($data->c[1]->v))));}
                        array_push($allBandwidthData[$urlArray[$i]], array($data->c[0]->v, intval($data->c[1]->v)));
                        $allBandwidth[$urlArray[$i]] = intval($allBandwidth[$urlArray[$i]]) + intval($data->c[1]->v);
                    }else if(is_numeric($urlArray[$i])){
                        if($urlArray[$i] == '2543'){$this->out(json_encode(array($urlArray[$i], intval($data->c[1]->v))));}
                        $allBandwidthData[$urlArray[$i]] = array(array($data->c[0]->v, intval($data->c[1]->v)));
                        $allBandwidth[$urlArray[$i]] = intval($data->c[1]->v);
                    }
                }
            }
        }

        foreach($clients as $client_id => $client_bandwidth){
            $this->BandwidthStat->create();
            $thisbandwidth = 0;
            $thisbandwidthData = '';
            if(isset($allBandwidth[$client_id])){
                $thisbandwidth = $allBandwidth[$client_id];
                $thisbandwidthData = $allBandwidthData[$client_id];
            }

            $this->BandwidthStat->set(array(
                'client_id'=>$client_id,
                'date'=>$today,
                'max_monthly_bandwidth'=>$client_bandwidth,
                'bandwidth_data'=>json_encode($thisbandwidthData),
                'bandwidth'=>$thisbandwidth
            ));

            $this->BandwidthStat->save();
            $this->out('Client: '.$client_id.' => '.$thisbandwidth);
            $this->out(' ');
            $this->out(json_encode($thisbandwidthData));
            $this->out(' ');
            $this->out(' ');
        }
        $this->out(' ');
        $this->out('Complete');
    }
}
