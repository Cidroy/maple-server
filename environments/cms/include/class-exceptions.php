<?php
namespace maple\cms\exceptions;
use Exception;

class FileNotFoundException extends Exception {}
class FilePermissionException extends Exception {}
class VendorMissingException extends Exception {}
class RenderEngineException extends Exception {}
class InsufficientPermissionException extends Exception {}
class SqlConnectionException extends Exception {}
class InvalidPluginException extends Exception {}

?>
