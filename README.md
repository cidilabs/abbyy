
# PhpAbbyy

PhpAbbyy is an integration library for the file conversion functionality of Abbyy finereader server. 

## Setup

You will need to install ZipArchive or any dependencies. For Docker that looks like the following.

```
RUN apt-get install -y \
        libzip-dev \
        zip \
  && docker-php-ext-install zip
```

PhpAbbyy can be installed to your project via Composer by adding the following line to your composer.json file: 

```"cidilabs/phpabbyy": "dev-master"```

Once Phpabbyy library is installed, you'll need to let UDOIT know which file conversion library you'll be using.

This can be done:

- In the .env: ```###> file formats ###
AVAILABLE_FILE_FORMATS="html,pdf"```

You will also need to define the Abbyy Fineserver endpoint.
```
##ABBYY domain to point to for REST API  
ABBYY_DOMAIN="http://example:8080"
```

## Basic Usage

- **fileName**: The name of the file
- **fileUrl**: The download URL for the file
- **fileType**: The file type of the original file
- **format**: The file type that we want to convert to
```
$abbyy =  new  PhpAbbyy();

$fileUrl =  "https://cidilabs.instructure.com/files/295964/download?download_frd=1&verifier=RZwKCP3iVlNQIULZnTAXO0usUROMC9AuplKkDf2g";

$options =  array('fileUrl'  => $fileUrl,  'fileType'  =>  'pdf',  'format'  =>  'html',  'fileName'  =>  'Test1.pdf');

$abbyy->convertFile($options);
```

## Class Methods

### convertFile
#### Public
#### Parameters
- ***options***: (array) 
-- **fileName**: (string) The name of the file
-- **fileUrl**: (string) The download URL for the file
-- **fileType**: (string) The file type of the original file
-- **format**: (string) The file type that we want to convert to
#### Returns
- ***taskId***: (string) The UUID representing the file conversion task
- ***null***

### isReady
#### Parameters
- ***taskId***: (string) The UUID representing the file conversion task
#### Returns
- ***True/False*** (boolean) True if the file has been converted and is ready, false otherwise
### getFileUrl
#### Parameters
- ***taskId***: (string) The UUID representing the file conversion task
#### Returns
- ***fileUrl***: (string) The url of the converted file
- ***null***
### downloadFile
#### Parameters
- ***fileUrl***: (string) The url of the converted file
#### Returns

### deleteFile
#### Parameters
- ***fileUrl***: (string) The url of the converted file
#### Returns
- ***True/False*** (boolean) True if successfully deleted, false otherwise

### unZipFile
#### Parameters
- ***filePath***: (string) File path to Zip file to unzip
#### Returns
- ***fileNames***: (string) File names from extracted Zip

### getFileTypeEnding
#### Parameters
- ***fileString***: (string) Name of file
#### Returns
- ***fileNames***: (string) returns file ending from file name

### deleteConvertedFileFromAbby
#### Parameters
- ***jobId***: (string) Job ID of task
#### Returns
- ***True/False*** (boolean) True if the request was completed


