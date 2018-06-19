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

class DropboxTool
{
    private $access_token;
    private $client_key;
    private $client_secret;

    /**
     * DropboxUpload constructor.
     * @param $access_token
     */
    function __construct($client_key, $client_secret, $access_token)
    {
        $this->client_key = $client_key;
        $this->client_secret = $client_secret;
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

        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

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

    /**
     * list items from a folder,
     * if folder_path is provided, items will be listed from the provided folder,
     * else items will be listed from root directory
     *
     * if cursor is provided, more items will be loaded from same directory
     *
     * @param string $folder_path Folder path to list items from
     * @param string $cursor Cursor of next item to list
     * @return array
     */
    public function listFolder($folder_path = '/', $cursor = null)
    {
        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            if ($cursor) {
                // get more items for cursor
                $folderContent = $dropbox->listFolderContinue($cursor);
                $items = $folderContent->getItems();
            } else {
                $folderContent = $dropbox->listFolder("/");
                $items = $folderContent->getItems();
            }
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        $cursor = $folderContent->getCursor();
        $has_more = $folderContent->hasMoreItems();

        $all_items = $items->all();

        $response_items = [];
        foreach ($all_items as $item)
            $response_items[] = $item->getData();

        $response = [
            'data' => $response_items,
            'cursor' => $cursor,
            'has_more' => $has_more
        ];

        return $response;
    }

    /**
     * get revisions of a file
     *
     * @param string $file File path to get revision for
     * @param int $limit Limit of revision to fetch
     * @return array
     * @throws Exception
     */
    public function getRevisions($file, $limit = 3)
    {
        if (empty($file)) {
            throw new Exception('File path not provided');
        }

        if ($limit < 1) {
            throw new Exception('Limit can not be less than 1');
        }

        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            $revisions = $dropbox->listRevisions($file, ["limit" => $limit]);
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        $response_data = [];
        foreach ($revisions->all() as $item)
            $response_data[] = $item->getData();

        return $response_data;
    }

    /**
     * search for query within path
     *
     * @param $query Query to search for
     * @param string $path Path of the directory to search within
     * @param int $start Start index of the search to perform
     * @param int $max_results Maximum Result to search for
     * @return mixed
     * @throws Exception
     */
    public function search($query, $path = '/', $start = 0, $max_results = 5)
    {
        if (empty($query)) {
            throw new Exception('Query not provided');
        }

        if ($start < 0) {
            throw new Exception('Start can not be less than 0');
        }

        if ($max_results < 1) {
            throw new Exception('max_results can not be less than 1');
        }

        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            $searchResults = $dropbox->search($path, $query, ['start' => $start, 'max_results' => $max_results]);
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        $items = $searchResults->getItems();
        $cursor = $searchResults->getCursor();
        $has_more = $searchResults->hasMoreItems();

        $response_items = [];
        foreach ($items->all() as $item) {
            $response_items[] = $item->getData()['metadata'];
        }

        $response['data'] = $response_items;
        $response['cursor'] = $cursor;
        $response['has_more'] = $has_more;

        return $response;
    }

    /**
     * create folder at specified path
     *
     * @param $folder_path Folder path with name to create
     * @return array
     * @throws Exception
     */
    public function createFolder($folder_path)
    {
        if (empty($folder_path)) {
            throw new Exception('folder_path is empty');
        }

        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            $folder = $dropbox->createFolder($folder_path);
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        return $folder->getData();
    }

    /**
     * delete file or folder
     * @param $path Path of file/folder to delete
     * @return array Array information of deleted file/folder
     * @throws Exception
     */
    public function delete($path)
    {
        if (empty($path))
            throw new Exception('Path is empty');


        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            $deletedFolder = $dropbox->delete($path);
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        return $deletedFolder->getData();
    }

    /**
     * move file/folder to other location
     *
     * @param $current_path Current path of file/folder
     * @param $move_to_path Path to move file/folder to this path
     * @return array
     * @throws Exception
     */
    public function move($current_path, $move_to_path)
    {
        if (empty($current_path))
            throw new Exception('current_path is empty');

        if (empty($move_to_path))
            throw new Exception('move_to_path is empty');


        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            $file = $dropbox->move($current_path, $move_to_path);
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        return $file->getData();
    }

    /**
     * copy file/folder to another location
     *
     * @param $current_path Current path fo file/folder to copy
     * @param $copy_to_path Path of file/folder to copy to
     * @return array
     * @throws Exception
     */
    public function copy($current_path, $copy_to_path)
    {
        if (empty($current_path))
            throw new Exception('current_path is emtpy');

        if (empty($copy_to_path))
            throw new Exception('copy_to_path is emtpy');


        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            $file = $dropbox->copy($current_path, $copy_to_path);
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        return $file->getData();
    }

    /**
     * get temporary link of the file
     *
     * @param $file_path File path to get temporary link for
     * @return array File information
     * @throws Exception
     */
    public function getTemporaryLink($file_path)
    {
        if (empty($file_path))
            throw new Exception('file_path is empty');


        $app = new DropboxApp($this->client_key, $this->client_secret, $this->access_token);

        $dropbox = new Dropbox($app);

        try {
            $file = $dropbox->getTemporaryLink($file_path);
        } catch (DropboxClientException $exception) {
            throw new Exception($exception->getMessage());
        }

        return $file->getData();
    }
}
