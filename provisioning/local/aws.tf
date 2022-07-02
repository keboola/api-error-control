provider "aws" {
  region  = local.region
  profile = var.aws_profile
}

locals {
  region = "eu-central-1"
}

variable "aws_profile" {
  type = string
  validation {
    condition     = length(var.aws_profile) > 0
    error_message = "The \"aws_profile\" must be non-empty string."
  }
}

resource "aws_iam_user" "api_error_control_iam_user" {
  name          = "${var.name_prefix}-api-error-control-User"
  force_destroy = true
}

resource "aws_iam_access_key" "api_error_control_credentials" {
  user = aws_iam_user.api_error_control_iam_user.id
}

resource "aws_s3_bucket" "api_error_control_s3_bucket" {
  force_destroy = true
  bucket        = "${var.name_prefix}-api-error-control-logs"
}

resource "aws_iam_user_policy" "api_error_control_iam_user_policy" {
  name = "S3Access"
  user = aws_iam_user.api_error_control_iam_user.id

  policy = <<EOT
{
    "Statement": [
      {
        "Action": [
          "s3:PutObject",
          "s3:GetObject",
          "s3:ListBucket",
          "s3:DeleteObject"
        ],
        "Effect": "Allow",
        "Resource": [
          "${aws_s3_bucket.api_error_control_s3_bucket.arn}/*"
        ]
      }
    ]
}
EOT
}

output "api_error_control_aws_region" {
  value = local.region
}

output "api_error_control_aws_key" {
  value = aws_iam_access_key.api_error_control_credentials.id
}

output "api_error_control_aws_secret" {
  value     = aws_iam_access_key.api_error_control_credentials.secret
  sensitive = true
}

output "api_error_control_s3_bucket" {
  value = aws_s3_bucket.api_error_control_s3_bucket.bucket
}
