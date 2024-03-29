#### 1.14.3

* Add 'screen-options' endpoint on`\Stackonet\WP\Framework\Traits\ApiCrudOperations` class.

#### 1.14.2

* Fix issue on batch update.

#### 1.14.1

* Fix issue on data read on DatabaseModal

#### 1.14.0

* Fix background process manual delete does not working when only one item.
* Update json_validate functionality.
* Add status count functionality.
* Add modal option to DatabaseModal class.
* Update UploadedFile class to read file from url and path.

#### 1.13.0

* Update `\StackonetWPFrameworkTest\Media\UploadedFileTest` class.

#### 1.12.0

* Update `\Stackonet\WP\Framework\Fields\ImageUploader` field class.

#### 1.11.0

* Add search option for `DataStoreBase::find_multiple()` method.

#### 1.10.0

* Update background processing functionality.

#### 1.9.15

* Update `Stackonet\WP\Framework\Abstracts\BackgroundProcessWithUiHelper`

#### 1.9.14

* Update `Stackonet\WP\Framework\Abstracts\BackgroundProcessWithUiHelper`

#### 1.9.13

* Update `Stackonet\WP\Framework\Supports\RestClient` class.

#### 1.9.12

* Update `Stackonet\WP\Framework\Abstracts\BackgroundProcessWithUiHelper` class to show ongoing task info.

#### 1.9.11

* Add `Stackonet\WP\Framework\Abstracts\BackgroundProcessWithUiHelper` class to show ongoing task info.

#### 1.9.10

* Update `Stackonet\WP\Framework\Supports\RestClient` class.

#### 1.9.9

* Add option to map read record(s) from database into `Stackonet\WP\Framework\Abstracts\Data` class

#### 1.9.8

* Update `Stackonet\WP\Framework\Traits\ApiCrudOperations::get_items()` class by fixing count issue

#### 1.9.7

* Fix issue `Stackonet\WP\Framework\Abstracts\Data::to_array()` method is not showing changed data unless
  class `apply_changes` method.

#### 1.9.6

* Add functionality for token based authentication.

#### 1.9.5

* Update `Stackonet\WP\Framework\Traits\ApiCrudOperations` class by adding `prepare_collection_item_for_response` to
  format collection item for response and `prepare_single_item_for_response` to format single item for response.

#### 1.9.4

* Update `Stackonet\WP\Framework\Traits\ApiPermissionChecker` class to list capabilities for operation.

#### 1.9.3

* Update `DataStoreBase` class to access `get_pagination_and_order_data` and `get_order_by` methods.

#### 1.9.2

* Add `ChunkFileUploader` class to upload files in chunks.

#### 1.9.1

* Add Date, Time, Datetime, Email, Tel, Url and Number field types.

#### 1.9.0

* Add Metabox api classes.

#### 1.8.1

* Fix issue: sort data is not sorting properly.
* Add **mailtrap.io** setting to test email functionality.
* Fix issue related to `\Stackonet\WP\Framework\Supports\Collection` class.

#### 1.8.0

* Add test for RestClient and Filesystem class.
* Update string helper test.
* Add some test for ArrayHelper and StringHelper class.
* Update method call for 'trash', 'restore' and 'delete' for DatabaseModel class.
* Add support for static call for DatabaseModel class.
* Update cache option for database count_records method.
* Add DataStoreBase class.

#### 1.7.0

* Update `Stackonet\WP\Framework\Abstracts\Data` class fixing PHP 8 compatibility.

#### 1.6.0

* Add phone number validation functionality
* Add unit testing for classes Validate, Sanitize, etc
* Update PostTypeModel class.
* Add FAQs example module.
* Add Testimonial example module.
* Update 'RestClient' class, add 'get_debug_info' method.
* Add multiselect feature for setting page option.

#### 1.5.0

* Add `FormBuilderInterface` class
* Add functionality to use custom FormBuilder to generate settings page fields

#### 1.4.1

* Fix protected method access issue for api response.

#### 1.4.0

* Add `ApiPermissionChecker` trait class to handle Api permission check.
* Add query builder support on `DatabaseModel` class.

#### 1.3.2

* Fix global parameters issue on `RestClient` class

#### 1.3.1

* Update `ApiResponse::respondWithError()` method to accept `WP_Error` class.
* Update `RestClient` class.

#### 1.3.0

* Add `ApiCrudOperations` trait to handle default crud operations.

#### 1.2.1

* Fix `Sanitize::deep()` method fail on null value.
* Add `Filesystem` class to work with file system.

#### 1.2.0

* Add class`DefaultController` for handling REST crud operation.
* Update `DataStoreInterface` interface class
* Update `PostTypeModel` class

#### 1.1.12

* Timezone has been set to UTC for creating all database record.

#### 1.1.11

* Update PostTypeModel class.
* Fix composer version check error.

#### 1.1.10

* Update DatabaseModal::create_multiple() method to return newly created records ids.

#### 1.1.9

* Update PostTypeModel class.
* Add QueryBuilder class (alpha).
* Add method to sanitize deep mixed content.
* Update Data class to set initial data.
* Add new method to get foreign key constant name.
* Add FormBuilder method to generate html field.

#### 1.1.8

* Update Data class to make compatible with array_column function.
* Add Cacheable trait for handling caching functionality.
* Add TableInfo trait for reading table metadata from database.
* Update validate time method for validating 24 hours time.
* Update term field example javaScript.
* Add PostTypeModel class for working with custom post type.
* Add TermModel class for working with custom term.
* Add default value for minimum per_page for pagination.
* Add sanitize method for REST sort parameter.

#### 1.1.7

* Fix error undefined variable panel for Setting Api.
* Fix prepare statement error for creating and updating multiple items.

#### 1.1.6

* Add new method to create multiple database records.
* Refactor code to format item for a table when create new single/multiple record.
* Add new method to DatabaseModel class to update multiple rows on a single query.
* Add default setting api handler for default WordPress setting page design.
* Add setting api example.

#### 1.1.5

* Add method to set global GET parameters for all request.
* Add example for creating meta field for term.
* Add WooCommerce My Account menu page example.
* Add example class for adding custom product data to WooCommerce order item.
* Add GitHub Updater class for updating plugin from github.
* Add support for a batch (trash, restore, delete) operation.
* Add support for multiple columns for order_by parameters.

#### 1.1.4

* Rename `EmailTemplate` class to `EmailTemplateBase`
* Add two new email template class `ActionEmailTemplate` and `BillingEmailTemplate`
* Add cache support for `DatabaseModal` class
* Add example classes to illustrate users of various utility classes including
    * Background Tasks
    * Various Email Templates
    * REST Api example for Media Uploader and Web Login
    * Rest Client example for working with third party API
