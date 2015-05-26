<?php
App::import('Core', 'Controller');
App::import('Controller', 'Notifications.Send');
class AnalyticsDayShell extends Shell
{
    public $uses = array(
        'Video',
        'Membership',
        'Analytics.SessionActivity',
        'JobQueue'
    );

    public function perform()
    {
        $this->initialize();
        $this->{array_shift($this->args)}();
    }

    public function test() {
        $result = $this->SessionActivity->find('first');
        debug($result);
    }

    /**
     * Total Videos Viewed, Customer, Most recent Visit, Video titles
     *
     * @param int $client_id
     **/

    public function DailyDigest(){
        $recent_users = array();
        $status = $conditions = $order = array();
        $client_id = $this->args[0];


        $conditions['Membership.client_id'] = $client_id;

        $conditions['Membership.last_login >='] = date("Y/m/d" ,mktime(0, 0, 0, date("m")  , date("d")-7, date("Y"))).' 00:00:00'; //week ago
        $conditions['Membership.last_login <='] = date("Y/m/d" ,mktime(0, 0, 0, date("m")  , date("d")+1, date("Y"))).' 00:00:00'; //tomorrow

        $order['User.username'] = 'ASC';

        //find memberships
        $contain = array(
            'User'=>array(
                'fields'=>array(
                    'username',
                    'first_name',
                    'last_name',
                    'company'
                )
            )

        );

        //need membership_id, Username,last_login
        $users = $this->Membership->find('all',array('conditions'=>$conditions, 'contain'=>$contain, 'order'=>$order, 'fields'=>array('Membership.id','Membership.last_login')));

        /* Analytics Conditions */

        $dayInfo = explode('.',$this->args[1]);
        // Overwrite $today and $yesterday based on 2nd argument passed using periods 
        // Example 2nd arg: 05.18.15
        $today = date("m/d/y", mktime(0, 0, 0, $dayInfo[0], $dayInfo[1], $dayInfo[2]));
        $yesterday = date("m/d/y", mktime(0, 0, 0, $dayInfo[0], $dayInfo[1]-1, $dayInfo[2]));

        //debug($yesterday);
        //debug($today);
        //die;

        $created = array(
            '$gte'=>new MongoDate(strtotime($yesterday.' 00:00:00')),
            '$lte'=>new MongoDate(strtotime($today. ' 00:00:00')),
        );

       // debug($created);

        $anayltics_fields = array(
            'video_id',
            'playlist_id',
            'playthrough_percent',
            'country_name',
            'created'
        );
        /* End Analytics Conditions */
        //debug($users);
        foreach ($users as $k=>$user)
        {
            //search session_activites for records created today for membership_id
            //return video_id,playlist_id,playthough_percent,country_name
            $recent_users[$k]['video_titles'] = '';

            $activitys = $this->SessionActivity->find('all',array('conditions'=>array('membership_id'=>(int) $user['Membership']['id'],'created'=>$created), 'fields'=>$anayltics_fields));
            //debug($activitys);
            $videos = array();

            foreach($activitys as $v => $activity){
                if($activity['SessionActivity']['video_id'] != 0){
                    $this->Video->id = $activity['SessionActivity']['video_id'];
                    $title = $this->Video->field('title');
                    $videos[$v]['video_id'] = $activity['SessionActivity']['video_id'];
                    $videos[$v]['title'] = $title;
                    $videos[$v]['playthrough'] = (isset($activity['SessionActivity']['playthrough_percent'])) ? $activity['SessionActivity']['playthrough_percent']: 0;
                    $videos[$v]['playlist_id'] = $activity['SessionActivity']['playlist_id'];
                    $videos[$v]['country'] = $activity['SessionActivity']['country_name'];
                    $recent_users[$k]['video_titles'][]=$title;
                }
            }

            if($recent_users[$k]['video_titles'] == '' ){
                unset($recent_users[$k]);
            }

            if(count($videos) > 0){
                $recent_users[$k]['total_video_viewed'] = count($videos);
                $recent_users[$k]['customer'] = $user['User']['username'];
                $recent_users[$k]['company'] = $user['User']['company'];
                $recent_users[$k]['name'] = $user['User']['first_name'].' '.$user['User']['last_name'];
                $recent_users[$k]['most_recent_visit'] = $user['Membership']['last_login'];
                $recent_users[$k]['videos'] = $videos;
            }
        }

        //date_default_timezone_set('PST');
        //$this->log($recent_users,'analytics/daily');
        //debug($recent_users);

        /* Send the Email */
        $this->Send = new SendController();
        $recipients = $this->Send->daily_digest($recent_users,$client_id);
        if(!empty($recipients)){
            $to = implode(',',array_keys($recipients));
            $status['description'] = 'Email Sent to: '.$to;
            $status['status'] = 'Finished';
            $status['finished'] = date('Y-m-d H:i:s');
        }
        else{
            $status['description'] = 'Email Did not Send';
            $status['status'] = 'Error';
        }
        //$jobId = array_pop($this->args);
        //$this->JobQueue->updateJob($jobId,$status);
    }




    /**
    * Count up sessions activites per video and update client play counts
    */
    public function play_update() {

        $videos = $this->Video->find('list',array(
            'conditions'=>array(
                'Video.active'=>true,
                'Video.deleted'=>false,
                'Video.client_id'=>1060 //1478 deadrich | 995 smallwolf // Limit to this account only
            ),
            'fields'=>array(
                'Video.id',
                'Video.title'
            )
        ));

        foreach($videos as $video_id => $video_name){
            $activities = $this->SessionActivity->find('all',array(
                'conditions'=>array(
                    'activity.name'=>"Play",
                    'video_id'=>$video_id,
                    'playthrough_percent'=>array(
                        '$gte'=> (int)0
                    )
                ),
                'fields'=>array(
                    'video_id',
                    'playthrough_percent',
                )
            ));

            $total_plays = count($activities);
            if(!empty($total_plays)){
                $total_avg_playthrough = 0;
                foreach($activities as $a){
                    $total_avg_playthrough = $total_avg_playthrough + $a['SessionActivity']['playthrough_percent'];
                }
                $stat_avg_playthrough = $total_avg_playthrough / $total_plays;
            }else{
                $total_plays = $total_avg_playthrough = $stat_avg_playthrough = 0;
            }

            $this->Video->id = $video_id;
            $this->Video->set(array(
                'total_plays'=>$total_plays,
                'total_avg_playthrough'=>$total_avg_playthrough,
                'stat_avg_playthrough'=>$stat_avg_playthrough
            ));
            $this->Video->save();

            $this->out('Video ID: '.$video_id.' - '.$video_name);
            $this->out('Total Plays: '.$total_plays);
            $this->out('Total Avg Playthrough: '.$total_avg_playthrough);
            $this->out('Statistical Avg Playthrough: '.$stat_avg_playthrough);
            $this->out('------------------------------');
            //debug($activities);
        }


    }

}
?>
