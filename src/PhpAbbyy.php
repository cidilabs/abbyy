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

    // Response object
    private $responseObject = [
        'data' => [
            'taskId' => '',
            'filePath' => '',
            'relatedFiles' => [],
            'status' => ''
        ],
        'errors' => []
    ];

    private $outputDir;


    public function __construct($outputDir = 'alternates')
    {
        $this->client = HttpClient::create([
            'base_uri' => $_ENV['ABBYY_DOMAIN'],
        ]);
        $this->filetype = null;
        $this->outputDir = $outputDir;
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

        $response = $this->client->request('POST', "/FineReaderServer14/api/workflows/{$this->workflows[$this->filetype]}/input/file" , $postOptions);
        if($response->getStatusCode() === Response::HTTP_CREATED) {
            $this->responseObject['data']['taskId'] = trim($response->getContent(false),"\"");
        } else {
            $this->responseObject['errors'][] = "Abby Failed to create the file";
        }
        return $this->responseObject;

    }

    public function isReady($jobId)
    {

        $response = $this->client->request('GET', "/FineReaderServer14/api/jobs/{$jobId}");
        $contentStr = $response->getContent(false);
        $jobStatus= \json_decode($contentStr, true);
        if($jobStatus['State'] === "JS_Complete"){
            $this->responseObject['data']['status'] = true;
        }else {
            $this->responseObject['data']['status'] = false;
        }
        return $this->responseObject;

    }

    public function getFileUrl($jobId)
    {

        $response = $this->client->request('GET', "/FineReaderServer14/api/jobs/{$jobId}/result/files");

        if($response->getStatusCode() === Response::HTTP_OK)
        {
            $contentStr = $response->getContent(false);
            $path = getcwd() . '/' . $this->outputDir;
            if(!is_dir($path)){
                mkdir($path,0755);
            }
            $filePath = $path . '/' . $jobId . '.zip';
            file_put_contents($filePath, $contentStr);
            $files = $this->unZipFile($filePath);
            unlink($filePath);
            if(!empty($files)) {
                $this->deleteConvertedFileFromAbby($jobId);
                $fileLocationIndex = array_key_first(preg_grep('/.*htm/',$files));
                $filePath = array_splice($files,$fileLocationIndex,1);
                $this->responseObject['data']['filePath'] = $filePath[0];
                $this->responseObject['data']['relatedFiles'] = $files;
            }
            else {
                $this->responseObject['errors'][] = "No files found from zip from taskId: {$jobId}";
            }
        }else{
            $this->responseObject['errors'][] = "Status code was not HTTP_OK 200 : {$jobId}";
        }

        return $this->responseObject;
    }

    protected function deleteConvertedFileFromAbby($jobId)
    {
        $response = $this->client->request('DELETE', "/FineReaderServer14/api/jobs/{$jobId}");
        if($response->getStatusCode() === Response::HTTP_NO_CONTENT) {
            return true;
        } else {
            $this->responseObject['errors'][] = "Could not delete file from Abby Server TaskId: {$jobId}";
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
            $this->responseObject['errors'][] = "couldn't open file";
        }

        foreach ($fileNames as $i => $file)
        {
            if(!file_exists($path . '/' . $file)){
                echo 'file does not exist';
                unset($fileNames[$i]);
            }
        }

        return $fileNames;
    }

    public function deleteFile($fileUrl) {

        if (file_exists($fileUrl)) {
            unlink($fileUrl);
        } else {
            $this->responseObject['errors'][] = "File not found";
        }
        return $this->responseObject;
    }


    private function getFileTypeEnding($fileString)
    {
        return substr(strrchr(strtolower($fileString), '.'), 1);
    }

}