<?php

namespace App\Services;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class Abbyy
{
    private $client;
    private $filetype;
    private $workflows = array(
        'pdf' => 'html-conversion',
    );

    public function __construct()
    {
        $this->client = HttpClient::create([
            'base_uri' => $_ENV['ABBYY_DOMAIN'],
        ]);
        $this->filetype = null;
    }

    public function convertFile($options)
    {
        $this->filetype = strtolower($options['fileType']);

        $postOptions = [
            'json' => [
                'FileName' => $options['fileName'],
                'FileContents' => base64_encode(file_get_contents($options['fileUrl'])),
            ]
        ];

        $response = $this->client->request('POST', '/FineReaderServer14/api/workflows/'. $this->workflows[$this->filetype] . '/input/file' , $postOptions);
        if($response->getStatusCode() === Response::HTTP_CREATED) {
            return trim($response->getContent(false),"\"");;
        } else {
            return null;
        }

    }

    public function isReady($jobId)
    {

        $response = $this->client->request('GET', '/FineReaderServer14/api/jobs/' . $jobId);
        $contentStr = $response->getContent(false);
        $jobStatus= \json_decode($contentStr, true);
        if($jobStatus['State'] === "JS_Complete"){
            return true;
        }else {
            return false;
        }

    }

    public function getFileUrl($jobId)
    {

        $response = $this->client->request('GET', '/FineReaderServer14/api/jobs/' . $jobId . '/result/files');

        if($response->getStatusCode() === Response::HTTP_OK)
        {
            $contentStr = $response->getContent(false);
            file_put_contents($jobId . '.zip', $contentStr);
            $files = $this->unZipFile($jobId . '.zip');
            unlink($jobId . '.zip');
            if(!empty($files)) {
                $this->deleteConvertedFileFromAbby($jobId);
                return $files;
            }
            else {
                return null;
            }
        }

        return null;
    }

    protected function deleteConvertedFileFromAbby($jobId)
    {
        $response = $this->client->request('DELETE', '/FineReaderServer14/api/jobs/' . $jobId);
        if($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
            return true;
        } else {
            return false;
        }
    }

    private function unZipFile($fileUrl)
    {
        $fileNames = [];
        $path = pathinfo(realpath($fileUrl), PATHINFO_DIRNAME);

        $zip = new ZipArchive;
        $res = $zip->open($fileUrl);

        if ($res === TRUE) {
            // filter out the file that we send to be converted
            for($i = 0; $i < $zip->numFiles; $i++)
            {
                if( $this->getFileTypeEnding($zip->getNameIndex($i)) !== $this->filetype)
                {
                    $fileNames[] = $zip->getNameIndex($i);
                }
            }

            // extract it to the path we determined above
            $zip->extractTo($path,$fileNames);
            $zip->close();
            echo "files extracted to $path";
        } else {
            echo "couldn't open file";
        }

        foreach ($fileNames as $i => $file)
        {
            if(!file_exists($file)){
                echo 'file does not exist';
                unset($fileNames[$i]);
            }
        }

        return $fileNames;
    }

    public function deleteFile($fileUrl) {

        if (file_exists($fileUrl)) {
            unlink($fileUrl);
            return true;
        } else {
            print("File not found");
            return false;
        }
    }


    private function getFileTypeEnding($fileString)
    {
        return substr(strrchr(strtolower($fileString), '.'), 1);
    }

}