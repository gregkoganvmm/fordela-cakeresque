<?php
App::import('Core', 'Controller');
App::import('Controller', 'Notifications.Send');
App::uses('CakeTime', 'Utility');

class StorageShell extends Shell {
    
    public $uses = array('Client','Audio','Video','VideoVersion','StorageStat','EncodingJob','BandwidthStat');

    public $tasks = array('BandwidthStats');

    /**
    *   Fixes the encode count for all StorageStat records. How? Gets list of clients 
    *   then updates every transcode count for every StorageStat record for each client.  
    */
    public function fixCounts() {
        $this->out('Starting script to update all transcode counts in table storage_stats');

        $clients = $this->Client->find('list',array('order' => array('Client.id' => 'ASC')));

        foreach($clients as $client_id => $client_name) {
            $conditions = array('StorageStat.client_id' => $client_id);
            $fields = array('StorageStat.id','StorageStat.created');
            
            $client_records = $this->StorageStat->find('list',array('conditions' => $conditions,'fields' => $fields));

            foreach($client_records as $record_id => $created) {
                // Get the day's encode count and save it
                $date = CakeTime::format('Y-m-d', $created);
                $encode_count = $this->_getNumberTranscoded($client_id,$created);
                if($encode_count > 0) {
                    $this->out($record_id.' - '.$date.' Encodes: '.$encode_count);
                    $this->StorageStat->id = $record_id;
                    $this->StorageStat->savefield('daily_transcode_count',$encode_count);
                }
            }
        }

        
    }

    /**
    *   Main storege script run daily at 6am
    */
    public function getClientTotals() {

        $clients = $this->Client->find('all',array(
                'conditions' => array('Client.id <>' => 1), // exclude VMS-ADMIN
                'fields' => array('Client.id','Client.name','Client.video_count','Client.audio_count','Client.max_storage'),
                'order' => 'Client.id DESC',
                'contain' => array()
            ));

        foreach($clients as $k => $client) {
            $client_id = $client['Client']['id'];
            
            // Get Storage Totals

            // Source Total from Video records - obsolete now that Source can be deleted
            //$video_sum = $this->Video->query("SELECT SUM(filesize) FROM videos WHERE client_id = {$client_id} AND active = 1 AND deleted = 0");
            //$this->out($video_sum[0][0]['SUM(filesize)']." video_sum no credit");

            // Total Source from version Source record
            $video_sum = $this->VideoVersion->query("SELECT SUM(versions.filesize) FROM video_versions versions INNER JOIN videos video ON versions.video_id = video.id WHERE video.client_id = {$client_id} AND video.active = 1 AND versions.filesize != 0 AND versions.name = 'Source' AND video.deleted = 0");
            if(!empty($video_sum) && $video_sum[0][0]['SUM(versions.filesize)'] >= 105801316){ // 105801316 
                $video_sum[0][0]['SUM(versions.filesize)'] = (int)$video_sum[0][0]['SUM(versions.filesize)'] - (int)105801316; // subtract 101 MB for demo videos
            }

            $video_bytes =  (!empty($video_sum) && $video_sum[0][0]['SUM(versions.filesize)'] <> null) ? $video_sum[0][0]['SUM(versions.filesize)'] : 0;
            $video = $this->formatRawSize($video_bytes);

            $versions_sum = $this->VideoVersion->query("SELECT SUM(versions.filesize) FROM video_versions versions INNER JOIN videos video ON versions.video_id = video.id WHERE video.client_id = {$client_id} AND video.active = 1 AND versions.filesize != 0 AND versions.name != 'Source Version' AND versions.name != 'Source' AND video.deleted = 0");
            if(!empty($versions_sum) && $versions_sum[0][0]['SUM(versions.filesize)'] >= 105801316){
                $versions_sum[0][0]['SUM(versions.filesize)'] = (int)$versions_sum[0][0]['SUM(versions.filesize)'] - (int)105801316; // subtract 101 MB for demo videos
            }
            
            $versions_bytes = (!empty($versions_sum) && $versions_sum[0][0]['SUM(versions.filesize)'] <> null) ? $versions_sum[0][0]['SUM(versions.filesize)'] : 0;
            $versions = $this->formatRawSize($versions_bytes);

            //SELECT * FROM videos where client_id = 995 AND created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);
            $video_sum_30days = $this->Video->query("SELECT SUM(filesize) FROM videos where client_id = {$client_id} AND active = 1 AND created >= DATE_SUB(CURDATE(), INTERVAL 30 DAY);");
            $video_30days_bytes = (!empty($video_sum_30days)) ? $video_sum_30days[0][0]['SUM(filesize)'] : 0;
            $video_last_30days =  $this->formatRawSize($video_30days_bytes);
            $video_archive_bytes = ($video_bytes - $video_30days_bytes >= 0) ? $video_bytes - $video_30days_bytes : 0;

            $audio_sum = $this->Audio->query("SELECT SUM(filesize) FROM audio WHERE client_id = {$client_id} AND deleted = 0");
            $audio_bytes = (!empty($audio_sum) && $audio_sum[0][0]['SUM(filesize)'] <> null) ? $audio_sum[0][0]['SUM(filesize)'] : 0;
            $audio = (!empty($audio_bytes)) ? $this->formatRawSize($audio_bytes) : 0;

            $total_bytes = $video_bytes + $versions_bytes + $audio_bytes;
            $total = $this->formatRawSize($total_bytes);

            $this->out("\r\n\r\n".$client['Client']['name']);
            $this->out($video_sum[0][0]['SUM(versions.filesize)']." video_sum with credit");
            $this->out($video." video source total");

            $this->out($video_30days_bytes." video last 30 days bytes");
            $this->out($video_archive_bytes." archived bytes");

            $this->out($versions." versions");
            $this->out($audio." audio");
            $this->out($total." total");

            // Could save to Client record
            $this->Client->id = $client_id;
            $this->Client->set(array(
                'video_storage' => $video_bytes,
                // Adding last month and archived
                'video_storage_last_month' => $video_30days_bytes,
                'video_storage_archived' => $video_archive_bytes,
                'versions_storage' => $versions_bytes,
                'audio_storage' => $audio_bytes,
                'total_storage' => $total_bytes
            ));
            $this->Client->save();

            // Determine if a daily record already exists or not
            $dailyStorage = $this->StorageStat->find('first',array('conditions'=>array(
                                'client_id' => $client_id,
                                'created >' => date('Y-m-d'),
                            )));
            // If exists "update" else "create"
            if(!empty($dailyStorage['StorageStat']['client_id'])){
                $this->StorageStat->id = $dailyStorage['StorageStat']['id'];
            }else{
                $this->StorageStat->create();
            }
            $total_daily_video = $video_bytes + $versions_bytes;
            $bursting_check = $total_bytes - $client['Client']['max_storage'];
            $bursting = ($bursting_check > 0) ? $bursting_check : 0;
            $transcode_count_today = $this->_getNumberTranscoded($client_id, 'today');
            
            $this->StorageStat->set(array(
                'client_id' => $client_id,
                'total_daily_storage' => $total_bytes, // Both Audio and Video
                'total_daily_video' => $total_daily_video, // Source and Versions
                'total_daily_video_source' => $video_bytes,
                'total_daily_video_versions' => $versions_bytes,
                'daily_transcode_count' => $transcode_count_today,
                'daily_bursting' => $bursting, // Over max storage that day
                'total_daily_audio' => $audio_bytes,
                'video_count' => $client['Client']['video_count'],
                'audio_count' => $client['Client']['audio_count'],
            ));
            $this->StorageStat->save();

            // Get and save yesterdays transcode count
            $transcode_count_yesterday = $this->_getNumberTranscoded($client_id, 'yesterday');
            $yesterdaysId = $this->_getYesterdaysId($client_id);

            $this->out('Yesterdays trancode count: '.$transcode_count_yesterday);
            $this->out('Yesterdays StorageStat id: '.$yesterdaysId);

            if(!empty($transcode_count_yesterday) && !empty($yesterdaysId)){
                $this->StorageStat->id = $yesterdaysId;
                $this->StorageStat->savefield('daily_transcode_count',$transcode_count_yesterday);
            }
        }

        // Send Email Report
        //$this->getStorageReport();

        $this->BandwidthStats->execute();
    }

    public function _getYesterdaysId($client_id){
        $conditions = array(
            'client_id'=>$client_id,
            'created >'=>date('Y-m-d', time() - 60 * 60 * 24).' 00:00:00',
            'created <'=>date('Y-m-d').' 23:59:59'
        );
        $yesterday = $this->StorageStat->find('first',array('conditions'=>$conditions,'fields'=>array('id')));
        $yesterdaysId = (!empty($yesterday['StorageStat']['id'])) ? $yesterday['StorageStat']['id'] : null;
        
        return $yesterdaysId;
    }

    public function _getNumberTranscoded($client_id,$day = null){
        $conditions = array(
            'client_id'=>$client_id,
            'status'=>'Finished'
        );
        if($day == 'today'){
            $conditions['created >'] = date('Y-m-d').' 00:00:00';
        }elseif($day == 'yesterday'){
            $conditions['created >'] = date('Y-m-d', time() - 60 * 60 * 24).' 00:00:00';
            $conditions['created <'] = date('Y-m-d').' 23:59:59';
        }else{
            // specific day passed
            $conditions['created >='] = date('Y-m-d', strtotime($day)).' 00:00:00';
            $conditions['created <='] = date('Y-m-d', strtotime($day)).' 23:59:59';
        }
        
        return $this->EncodingJob->find('count',array('conditions'=>$conditions));
    }

    public function formatRawSize($bytes) {
 
        if(!empty($bytes)) {
            $s = array('bytes', 'kb', 'MB', 'GB', 'TB', 'PB');
            $e = floor(log($bytes)/log(1024));
            $output = sprintf('%.2f '.$s[$e], ($bytes/pow(1024, floor($e))));
 
            return $output;
        }
    }

    public function getStorageReport() {
        // Send email report
        $this->Send = new SendController();
        $this->Send->storage_report();
    } 
    
}
?>