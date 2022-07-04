provider "azurerm" {
  tenant_id       = "9b85ee6f-4fb0-4a46-8cb7-4dcc6b262a89" // Keboola
  subscription_id = var.azure_subscription_id
  features {}
}

locals {
  location = "West Europe"
}

variable "azure_subscription_id" {
  type = string
  validation {
    condition     = length(var.azure_subscription_id) > 0
    error_message = "The \"azure_subscription_id\" must be non-empty string."
  }
}

resource "azurerm_resource_group" "api_error_control" {
  location = local.location
  name     = "${var.name_prefix}-api-error-control"
}

resource "azurerm_storage_account" "api_error_control_storage_account" {
  name                     = "${var.name_prefix}apierrorcontrol"
  resource_group_name      = azurerm_resource_group.api_error_control.name
  location                 = local.location
  access_tier              = "Cool"
  account_kind             = "StorageV2"
  account_replication_type = "LRS"
  account_tier             = "Standard"
}

resource "azurerm_storage_container" "api_error_control_logs_container" {
  name                  = "test-container"
  container_access_type = "private"
  storage_account_name  = azurerm_storage_account.api_error_control_storage_account.name
}

resource "azurerm_storage_management_policy" "api_error_control_logs_policy" {
  storage_account_id = azurerm_storage_account.api_error_control_storage_account.id
  rule {
    name    = "delete-debug-files"
    enabled = true
    filters {
      prefix_match = [azurerm_storage_container.api_error_control_logs_container.name]
      blob_types   = ["blockBlob"]
    }
    actions {
      base_blob {
        delete_after_days_since_modification_greater_than = 2
      }
    }
  }
}

output "api_error_control_abs_connection_string" {
  value     = azurerm_storage_account.api_error_control_storage_account.primary_blob_connection_string
  sensitive = true
}

output "api_error_control_abs_container" {
  value = azurerm_storage_container.api_error_control_logs_container.name
}
