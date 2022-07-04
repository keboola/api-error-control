provider "aws" {
  region  = local.region
  profile = "Keboola-Dev-Platform-Services-AWSAdministratorAccess"

  default_tags {
    tags = {
      KebolaStack = "${var.name_prefix}-api-error-control"
      KeboolaRole = "api-error-control"
    }
  }
}

data "aws_caller_identity" "current" {}

locals {
  region = "eu-central-1"
}

resource "aws_iam_user" "api_error_control" {
  name          = "${var.name_prefix}-api-error-control-user"
  force_destroy = true
}

resource "aws_iam_access_key" "api_error_control_credentials" {
  user = aws_iam_user.api_error_control.id
}

resource "aws_s3_bucket" "api_error_control_logs_bucket" {
  force_destroy = true
  bucket        = "${var.name_prefix}-api-error-control-logs"
}

resource "aws_iam_user_policy" "api_error_control_logs_policy" {
  name = "S3Access"
  user = aws_iam_user.api_error_control.id

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
          "${aws_s3_bucket.api_error_control_logs_bucket.arn}/*"
        ]
      }
    ]
}
EOT
}

resource "aws_s3_bucket_lifecycle_configuration" "api_error_control_logs_life_cycle" {
  bucket = aws_s3_bucket.api_error_control_logs_bucket.id
  rule {
    id     = "Delete debug files"
    status = "Enabled"

    filter {
      prefix = "debug-files/"
    }

    expiration {
      days = 2
    }

  }
}

output "aws_default_region" {
  value = local.region
}

output "aws_access_key_id" {
  value = aws_iam_access_key.api_error_control_credentials.id
}

output "aws_secret_access_key" {
  value     = aws_iam_access_key.api_error_control_credentials.secret
  sensitive = true
}

output "s3_logs_bucket" {
  value = aws_s3_bucket.api_error_control_logs_bucket.bucket
}
