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

- `LogInfo` - A record class used to pass additional information to the log processor. Use it in application code as:
```php
/** @var LogProcessor $logProcessor */
$logProcessor = $this->container->get('Keboola\\ErrorControl\\Monolog\\LogProcessor');
$logProcessor->setLogInfo(new LogInfo(...));
```

## Development

Use `docker-compose run dev composer ci` to run tests locally.

## Migration From 3.x to 4.x
Remove:
 
```yaml
services:
    Keboola\ErrorControl\Uploader\UploaderFactory:
        arguments:
            $storageApiUrl: "%storage_api_url%"
            $s3Bucket: "%logs_s3_bucket%"
            $s3Region: "%logs_s3_bucket_region%"
        
```
Also note that 4.x uses Monolog 3.x and php >= 8.1, so you will also need to support these versions

## License

MIT licensed, see [LICENSE](./LICENSE) file.
