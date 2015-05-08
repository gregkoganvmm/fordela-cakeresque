<?php

class AssetImage extends AppModel {

    /**
     * Create an image record
     *
     * @param $image_path
     * @param $model
     * * @param $model
     * @param $foreign_id
     * @param $client_id
     * @return bool
     *
     */
    public function addImage($image_path,$model,$foreign_id) {
        $img = pathinfo($image_path);
        $image = $img['filename'].'.'.$img['extension'];
        $filesize = filesize($image_path);
        list($width, $height) = getimagesize($image_path);
        $model_path = $this->model_path($model);

        $this->create();
        $this->set(array(
            'foreign_id' => $foreign_id,
            'model' => $model,
            'filename' => $image,
            'ext' => $img['extension'],
            //'dir' => '/'.$model_path.'/'.$foreign_id.'/',
            'key' => $model_path.'/'.$foreign_id.'/'.$image,
            'filesize' => $filesize,
            'height' => $height,
            'width' => $width
        ));
        if($this->save()) {
            return true;
        } else {
            return false;
        }
    }

    protected function model_path($model) {
        switch($model) {
            case 'Audio': $model_path = 'audio'; break;
            default: $model_path = strtolower($model).'s';
        }
        return $model_path;
    }

    public function copyRecordsToId($OldVideoId, $NewVideoId, $model = 'Video') {
        // Get old records
        $oldRecords = $this->find('all',array(
            'conditions' => array('foreign_id' => $OldVideoId,'model' => $model)
        ));

        // Save new records
        foreach($oldRecords as $record) {
            unset($record['AssetImage']['id']);
            unset($record['AssetImage']['created']);
            $record['AssetImage']['foreign_id'] = $NewVideoId;
            $record['AssetImage']['key'] = 'videos/'.$NewVideoId.'/'.$record['AssetImage']['filename'];
            $this->create();
            $this->save($record);
        }

        return;
    }
}
?>
