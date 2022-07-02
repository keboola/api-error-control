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

Prerequisites:
* locally installed `terraform` and `jq`
    * https://www.terraform.io
    * https://stedolan.github.io/jq
* configured `az` and `aws` CLI tools (run `az login` and `aws configure --profile YOUR_AWS_PROFILE_NAME`)

### AWS and Azure resources

```shell
export NAME_PREFIX= # your name/nickname to make your resource unique & recognizable
export AWS_PROFILE=xxxx # profile name for AWS account of your team
export AZURE_SUBSCRIPTION_ID=xxxx # subscription id of your team

cat <<EOF > ./provisioning/local/terraform.tfvars
name_prefix = "${NAME_PREFIX}"
aws_profile = "${AWS_PROFILE}"
azure_subscription_id = "${AZURE_SUBSCRIPTION_ID}"
EOF

terraform -chdir=./provisioning/local init
terraform -chdir=./provisioning/local apply

./provisioning/local/update-env.sh
```

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

## License

MIT licensed, see [LICENSE](./LICENSE) file.
