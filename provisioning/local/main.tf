terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 4.20"
    }
  }
}

variable "name_prefix" {
  type = string
  validation {
    condition     = length(var.name_prefix) > 0
    error_message = "The \"name_prefix\" must be non-empty string."
  }
}
