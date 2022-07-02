#!/usr/bin/env bash
set -Eeuo pipefail

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source ./functions.sh

# output variables
output_var 'AWS_ACCESS_KEY_ID' $(terraform_output 'api_error_control_aws_key')
output_var 'AWS_SECRET_ACCESS_KEY' $(terraform_output 'api_error_control_aws_secret')
output_var 'AWS_DEFAULT_REGION' $(terraform_output 'api_error_control_aws_region')
output_var 'S3_LOGS_BUCKET' $(terraform_output 'api_error_control_s3_bucket')
output_var 'ABS_CONNECTION_STRING' $(terraform_output 'api_error_control_abs_connection_string')
output_var 'ABS_CONTAINER' $(terraform_output 'api_error_control_abs_container')
echo ""
