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
- `S3Uploader` - Used by LogProcessor to upload full exception traces into S3. To configure, add the following 
to `services.yaml`:
```yaml
services:
    Keboola\ErrorControl\Monolog\S3Uploader:
        arguments:
            $storageApiUrl: "%storage_api_url%"
            $s3bucket: "%logs_s3_bucket%"
            $region: "%logs_s3_bucket_region%"
```            
- `LogInfo` - A record class used to pass additional information to the log processor. Use it in application code as:
```php
/** @var LogProcessor $logProcessor */
$logProcessor = $this->container->get('Keboola\\ErrorControl\\Monolog\\LogProcessor');
$logProcessor->setLogInfo(new LogInfo(...));
```

## Development
Use the provided `test-cf-stack.json` to create a CloudFormation stack. Use the outputs to set environment variables
`AWS_DEFAULT_REGION`, `S3_LOGS_BUCKET`. Create an access key for the generated user. Set it to the environment 
variables `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`. Run tests with `composer ci`. 

Use `docker-compose run dev composer ci` to run tests locally.
