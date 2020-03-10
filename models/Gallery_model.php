<?php
class Gallery_model extends Model {
  const album_dir = "albums";
  const thumbnails_dir = "thumbnails";
  const photo_extension = "jpg";
  const str_error_album_name_format = 'Le nom d\'un album doit commencer par une lettre et contenir uniquement des lettres, des chiffres et des espaces.';
  const str_error_photo_name_format = 'Le nom d\'une photo doit commencer par une lettre et contenir uniquement des lettres, des chiffres et des espaces.';
  const str_error_album_exists = 'L\'album existe déjà.';
  const str_error_photo_exists = 'Ce nom de photo existe déjà.';
  const str_error_album_does_not_exist = 'L\'album n\'existe pas.';
  const str_error_photo_does_not_exist = 'La photo n\'existe pas.';
  const str_error_photo_format = 'La photo n\'a pas pu être sauvegardée.';
  
  public function albums() {
    $array = scandir(self::album_dir);
    $removeDirs = array(".","..");
    return array_diff($array,$removeDirs);
  }

  public function create_album($album_name) {
    $this->check_album_name ( $album_name );
    if ($this->album_exists ( $album_name ))
      throw new Exception ( self::str_error_album_exists );
    mkdir ( $this->album_path ( $album_name ) );
    mkdir ( $this->thumbnails_path ( $album_name ) );
  }
  
  public function delete_album($album_name) {
    $this->check_if_album_exists ( $album_name );
    $this->delete_directory ( $this->album_path ( $album_name ) );
  }
  
  public function photos($album_name) {
    $this->check_if_album_exists($album_name);
    $albumsscandir=scandir($this->album_path($album_name));
    $filenames = array_diff($albumsscandir, ['.', '..',self::thumbnails_dir]);
    $description_from_filename = function ($filename) use($album_name) {
      $photo_name = substr ( $filename, 0, - strlen ( self::photo_extension ) - 1 );
      return $this->photo_description_from_names ( $album_name, $photo_name );
    };
    return array_map ($description_from_filename,$filenames );
  }

  public function photo($album_name,$photo_name) {
    $this->photo_exists($album_name,$photo_name);
    return $this->photo_description_from_names($album_name,$photo_name);
  }
  
  public function add_photo($album_name,$photo_name,$tmp_file) {
    $this->check_if_album_exists($album_name);
    $this->check_photo_name($photo_name);
    if ($this->photo_exists($album_name,$photo_name)) {
        throw new Exception (self::str_error_photo_exists);
    }
    try {
      $this-> create_images($tmp_file,$album_name,$photo_name);
    } catch ( ImagickException $e ) {
      throw new Exception ( self::str_error_photo_format );
    }
  }

  public function delete_photo($album_name,$photo_name) {
    $this->photo_exists($album_name, $photo_name);
    unlink($this->photo_path($album_name, $photo_name));
    }

  public function check_if_album_exists($album_name) {
    if (!$this->album_exists($album_name))
      throw new Exception(elf:: str_error_album_exists);
  }

  public function check_if_photo_exists($album_name,$photo_name) {
    if (!$this-> photo_exists($album_name, $photo_name));
       throw new Exception ( self::str_error_photo_exists );
  }
  
  private function album_path($album_name) {
    return self::album_dir . "/$album_name";
  }
  
  private function thumbnails_path($album_name) {
    return $this->album_path ( $album_name )."/".self::thumbnails_dir;
  }
  
  private function photo_path($album_name, $photo_name) {
    return $this->album_path ( $album_name )."/$photo_name.".self::photo_extension;
  }
  
  private function thumbnail_path($album_name, $photo_name) {
    return $this->thumbnails_path ( $album_name )."/$photo_name.".self::photo_extension;
  }
  
  private function album_exists($album_name) {
    return file_exists ( $this->album_path ( $album_name ) );
  }
  
  private function photo_exists($album_name,$photo_name) {
    if (! $this->album_exists($album_name))
      return false;
    return file_exists ($this->photo_path ($album_name,$photo_name) );
  }
  
  private function delete_directory($directory) {
    $files = scandir ( $directory );
    foreach ( $files as $file ) {
      $filename = "$directory/$file";
      if ($file === '.' || $file === '..') continue;
      if (is_dir ( $filename )) {
        $this->delete_directory ( $filename );
      } else if (is_file ( $filename )) {
        unlink ( $filename );
      }
    }
    rmdir($directory);
  }

  private function check_album_name($album_name) {
    $result = filter_var ( $album_name, FILTER_VALIDATE_REGEXP, array (
        'options' => array (
            'regexp' => '/^[a-zA-Z][0-9a-zA-Z ]*$/' 
        ) 
    ) );
    if ($result === false || $result === null) {
      throw new Exception ( self::str_error_album_name_format );
    }
  }

  private function check_photo_name($photo_name) {
    $result = filter_var ($photo_name, FILTER_VALIDATE_REGEXP, array (
        'options' => array (
            'regexp' => '/^[a-zA-Z][0-9a-zA-Z ]*$/' 
        ) 
    ) );
    if ($result === false || $result === null) {
      throw new Exception ( self::str_error_photo_name_format );
    }
  }

  private function photo_description_from_names($album_name, $photo_name) {
    $photo_path = $this->photo_path ($album_name,$photo_name);
    $thumbnail_path = $this->thumbnail_path ($album_name,$photo_name);
    return [ 
        'photo_name' => $photo_name,
        'photo_path' => $photo_path,
        'thumbnail_path' => $thumbnail_path 
    ];
  }

  private function create_images($tmp_file,$album_name,$photo_name) {
    $image = new Imagick($tmp_file);
    try {
      $image->writeImage ($this->photo_path($album_name,$photo_name));
      $this->resize_to_thumbnail ( $image );
      $image->writeImage ($this->thumbnail_path($album_name, $photo_name));
    } finally {
      $image->destroy ();
    }
  }

  private function resize_to_thumbnail($image) {
    $geometry = $image->getImageGeometry ();
    if ($geometry ['width'] > $geometry ['height']) {
      $image->thumbnailImage ( 150, 0 );
    } else {
      $image->thumbnailImage ( 0, 150 );
    }
  }
}