#!/usr/bin/env bash
set -Eeuo pipefail

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source ./functions.sh

# output variables
output_var 'AWS_ACCESS_KEY_ID' $(terraform_output 'aws_access_key_id')
output_var 'AWS_SECRET_ACCESS_KEY' $(terraform_output 'aws_secret_access_key')
output_var 'AWS_DEFAULT_REGION' $(terraform_output 'aws_default_region')
output_var 'AWS_S3_LOGS_BUCKET' $(terraform_output 'aws_s3_logs_bucket')
output_var 'AZURE_ABS_CONNECTION_STRING' $(terraform_output 'azure_abs_connection_string')
output_var 'AZURE_ABS_CONTAINER' $(terraform_output 'azure_abs_container')
echo ""
