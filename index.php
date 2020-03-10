<?php
/*
foreach($_SERVER as $par){
    echo("---    " . $par."</br>");
}
*/

$config = [
  'default_controller' => 'Gallery',
  'default_method' => 'index',
  'core_classes'=> ['Controller', 'Loader', 'Model'],
  'models'=>['Gallery']
];


foreach ($config['core_classes'] as $classname) require "core/$classname.php";

function generate_error_404($exception) {
   header("HTTP/1.0 404 Not Found");
   include 'views/header.php';
   echo $exception->getMessage();
   include 'views/footer.php';
}
function get_path_elements() {
  if (!isset($_SERVER['PATH_INFO'])) {
     return null; 
  }
  $path_info = $_SERVER['PATH_INFO'];
  $path_elements = explode('/', $path_info);
  array_shift($path_elements);
  return $path_elements; 
}

function path_contains_controller_name($path_elements) {
  return $path_elements!==null && count($path_elements)>=1; 
}

function path_contains_method_name($path_elements) {
  return $path_elements!==null && count($path_elements)>=2;
}

function get_controller_name($path_elements) {
  global $config;
  return (path_contains_controller_name($path_elements))
      ?  $path_elements[0] 
      : $config['default_controller'];
}

function get_parameters($path_elements) {
  if (empty($path_elements) || count($path_elements)<=2) {
     return []; 
  }
  return array_slice($path_elements, 2);
}

function get_method_name($path_elements) {
  global $config;
  return (path_contains_method_name($path_elements))
      ?  $path_elements[1] 
      : $config['default_method'];
}

function create_controller($controller_name) {
  $controller_classname = ucfirst(strtolower($controller_name));
  $controller_filename = 'controllers/'.$controller_classname.'.php';
  if (!file_exists($controller_filename)) {
    throw new Exception("Erreur controlleur n'existe pas!");
  }
  include $controller_filename;
  if (!class_exists($controller_classname)) {
    throw new Exception("Erreur class controlleur n'existe pas!");
  }
  return new $controller_classname();
}

function call_controller_method($controller, $method_name, $parameters) {
 $reflectionObject = new ReflectionObject($controller);
  if (! $reflectionObject->hasMethod($method_name)) {
    throw new Exception("Erreur controlleur");
  }
  $reflectionMethod = $reflectionObject->getMethod($method_name);
  if ($reflectionMethod->getNumberOfParameters()!=count($parameters)){
    throw new Exception("Erreur controlleur");
  }
  $reflectionMethod->invokeArgs($controller, $parameters);
}

try {
  $path_elements = get_path_elements();
  $controller_name = get_controller_name($path_elements);
  $method_name = get_method_name($path_elements);
  $controller = create_controller($controller_name);
  $parameters = get_parameters($path_elements);
  call_controller_method($controller, $method_name, $parameters);
} catch (Exception $exception) {
  generate_error_404($exception);
}
