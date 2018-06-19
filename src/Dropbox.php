<?php
/**
 * Created by PhpStorm.
 * User: anuj
 * Date: 25/1/18
 * Time: 1:39 PM
 */

namespace TBETool;


use Exception;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;
use Kunnu\Dropbox\DropboxFile;
use Kunnu\Dropbox\Exceptions\DropboxClientException;

class Dropbox
{
    private $access_token;

    /**
     * DropboxUpload constructor.
     * @param $access_token
     */
    function __construct($access_token)
    {
        $this->access_token = $access_token;
    }

    /**
     * @param $file
     * @param $title
     * @param $description
     * @return bool
     * @throws Exception
     */
    public function upload($file, $title, $description)
    {
        $video_file = $file;

        $file_name_explode = explode('/', $file);
        $file_name = $title.'_'.end($file_name_explode);

        if (empty($video_file)) {
            throw new Exception('File does not exists');
        }

        $app = new DropboxApp(DROPBOX_APP_KEY, DROPBOX_APP_SECRET, $this->access_token);

        $dropbox = new Dropbox($app);

        $dropboxFile = new DropboxFile($video_file);

        try {
            $file = $dropbox->upload($dropboxFile, '/' . $file_name, ['autorename' => true]);
        } catch (DropboxClientException $e) {
            throw new Exception($e->getMessage());
        }

        if ($file) {
            $response['id'] = $file->getId();
            $response['file_name'] = $file->getName();

            return $response;
        }

        return false;
    }
}
