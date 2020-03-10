<?php
class Loader {
  public function view($view, $data = []) {
    foreach ($data as $key=>$value) {
      $$key = $value;
    }
    include "views/${view}.php";
  }

  public function load($view = null, $data = []) {
    $this->view('header', $data);
    if ($view!==null) {
        $this->view($view, $data);
    }
    $this->view('footer', $data);
  }
}