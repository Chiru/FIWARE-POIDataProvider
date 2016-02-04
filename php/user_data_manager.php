<?php // user_data_manager.php v.5.2.2.1 ariokkon

// Dependency: JSON Schema validator, 
// https://github.com/justinrainbow/json-schema
require 'vendor/autoload.php';

// Read and process the schema only once to globals
$user_schema = load_user_schema();

function validate_user_data($user_data)
{
    global $user_schema;
    $result = false;
    
    
    $temp = json_encode($user_data);
    $user_data = json_decode($temp);
    
    
    // Validate
    $validator = new JsonSchema\Validator();
    $validator->check($user_data, $user_schema);

    if ($validator->isValid()) {
        $result = true;
    } else {
        echo "JSON does not validate. Violations:\n";
        foreach ($validator->getErrors() as $error) {
            echo sprintf("[%s] %s\n", $error['property'], $error['message']);
        }
    }
    
    return $result;
}

function load_user_schema($user_schema_file = 'user_schema.json')
{
    $retriever = new JsonSchema\Uri\UriRetriever;
    $user_schema = $retriever->retrieve('file://' . realpath($user_schema_file));
    $refResolver = new JsonSchema\RefResolver($retriever);
    $refResolver->resolve($user_schema, 'file://' .realpath($user_schema_file));
    
    return $user_schema;
}

?>