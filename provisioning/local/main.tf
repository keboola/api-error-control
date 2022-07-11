terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 4.20"
    }
    azurerm = {
      source  = "hashicorp/azurerm"
      version = "~> 3.11"
    }
  }

  backend "s3" {
    profile        = "Keboola-Dev-Platform-Services-AWSAdministratorAccess"
    role_arn       = "arn:aws:iam::681277395786:role/kbc-local-dev-terraform"
    region         = "eu-central-1"
    bucket         = "local-dev-terraform-bucket"
    dynamodb_table = "local-dev-terraform-table"
  }
}

variable "name_prefix" {
  type = string
  validation {
    condition     = length(var.name_prefix) > 0
    error_message = "The \"name_prefix\" must be non-empty string."
  }
}
