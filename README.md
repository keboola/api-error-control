# API Error Control Library 
[![Build Status](https://travis-ci.com/keboola/api-error-control.svg?branch=master)](https://travis-ci.com/keboola/api-error-control)
[![Maintainability](https://api.codeclimate.com/v1/badges/8209d9ce388376d24cf8/maintainability)](https://codeclimate.com/github/keboola/api-error-control/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/8209d9ce388376d24cf8/test_coverage)](https://codeclimate.com/github/keboola/api-error-control/test_coverage)

The library provides utility classes for catching, formatting and logging errors for KBC API backend.
The provided classes are:

- `UserException` - The API code should throw this exception in case the exception should be forwarded to the end user. 
- `ApplicationException` - The API code should throw this exception in case the exception should be concealed. 
- `ExceptionListener` - Symfony Kernel exception listener which ensures the above described behavior, to use it put 
the following in `services.yaml`:
```yaml
services:
    Keboola\ErrorControl\EventListener\ExceptionListener:
        tags:
            - { name: kernel.event_listener, event: kernel.exception }
    
```
- `LogProcessor` - Log processor which adds useful fields into every log message and optionally uploads full 
exception traces to S3. To configure, add the following to `services.yaml`:
```yaml
services:
    Keboola\ErrorControl\Monolog\LogProcessor:
        public: true
        arguments:
            $appName: "%app_name%"
        tags:
            - { name: monolog.processor, method: processRecord }
```
_Note:_ You need to have `symfony/monolog-bundle` installed for the tag `monolog.processor` to work.  
- `UploaderFactory` - Used by LogProcessor to upload full exception traces into S3 or ABS. To configure, add the following 
to `services.yaml`:
```yaml
services:
    Keboola\ErrorControl\Uploader\UploaderFactory:
        arguments:
            $storageApiUrl: "%storage_api_url%"
            $s3Bucket: "%logs_s3_bucket%"
            $s3Region: "%logs_s3_bucket_region%"
            $absConnectionString: "%log_abs_connection_string%"
            $absContainer: "%log_abs_container%"
        
```
At least one combination of ($s3Bucket and $s3Region) or ($absConnectionString and $absContainer) must be provided. 

- `LogInfo` - A record class used to pass additional information to the log processor. Use it in application code as:
```php
/** @var LogProcessor $logProcessor */
$logProcessor = $this->container->get('Keboola\\ErrorControl\\Monolog\\LogProcessor');
$logProcessor->setLogInfo(new LogInfo(...));
```

## Development
### AWS resources
Use the provided `test-cf-stack.json` to create a CloudFormation stack. Use the outputs to set environment variables
`AWS_DEFAULT_REGION`, `S3_LOGS_BUCKET`. Create an access key for the generated user. Set it to the environment 
variables `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`.

### Azure resources
Use the provided `test-arm-template.json` to create ARM stack:

    az group create --name testing-api-error-control --location "East US"

    az deployment group create --resource-group testing-api-error-control --template-file test-arm-template.json --parameters storage_account_name=testingapierrorcontrol container_name=test-container

Go to the [Azure Portal](https://portal.azure.com/) > Storage Account > testingapierrorcontrol > Access Keys and copy connection string. 
Go to Storage Account - Lifecycle Management - and set a cleanup rule to remove files older than 1 day from the container.
Set  `ABS_CONNECTION_STRING` and `ABS_CONTAINER`. Run tests with `composer ci`. 

Use `docker-compose run dev composer ci` to run tests locally.

## Migration From 2.x to 3.x
Replace:

```yaml
services:
    Keboola\ErrorControl\Monolog\S3Uploader:
        arguments:
            $storageApiUrl: "%storage_api_url%"
            $s3bucket: "%logs_s3_bucket%"
            $region: "%logs_s3_bucket_region%"
```

with:
 
```yaml
services:
    Keboola\ErrorControl\Uploader\UploaderFactory:
        arguments:
            $storageApiUrl: "%storage_api_url%"
            $s3Bucket: "%logs_s3_bucket%"
            $s3Region: "%logs_s3_bucket_region%"
        
```

In case you were using `S3Uploader` directly somewhere, you have to replace the occurrences with `UploaderFactory` 
and call `getUploader()` method to actually get an uploader.
