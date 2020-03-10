<?php
class Gallery extends Controller {
  public function index() {
    $this->albums();
  }
  
  public function albums() {
      var_dump($this->gallery->albums());
      $this->loader->load('albums', ['title'=>'Albums', 'albums'=>$this->gallery->albums()]);
  }

  public function albums_new() {
    $this->loader->load('albums_new', ['title'=>'Création d\'un album']);
  }

  public function albums_create() {
    try {
      $album_name = filter_input(INPUT_POST, 'album_name');
      $this->gallery->create_album($album_name);
      header('Location: /index.php/gallery/albums'); /* redirection du client vers la liste des albums. */
    } catch (Exception $e) {
      $this->loader->load('albums_new',
                      ['title'=>'Création d\'un album',
                       'error_message' => $e->getMessage()]);
    }
  }
  
  public function albums_delete($album_name) {
    try {
      $name = filter_var($album_name);
      $this->gallery->delete_album($album_name);
    } catch (Exception $e) { }
    header('Location: /index.php/gallery/albums');
  }

  public function albums_show($album_name) {
    try {
        //var_dump($this->gallery->photos($album_name));
        $this->gallery->photos($album_name);
        $this->loader->load('albums_show',
                          ['title'=>$album_name,
                           'album'=>$album_name,
                           'photos'=>$this->gallery->photos($album_name)]);
    } catch (Exception $e) {
      header("Location: /index.php");
    }
  }
  
  public function photos_new($album_name) {
      $title="Création d'une photo";
      $this->loader->load('photos_new', ['title'=>$title,'album_name'=>$album_name]);
  }


  public function photos_add($album_name) {
    try {
          $album_name = filter_var($album_name);
          $this->gallery->check_if_album_exists($album_name);
       } catch (Exception $e) { header("Location: /index.php"); }
    try {
       $photo_name = filter_input(INPUT_POST,'photo_name');
       if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
           throw new Exception('Vous devez choisir une photo.');
       }
       $this->gallery->add_photo($album_name,$photo_name,$_FILES['photo']['tmp_name']);
       header("Location: /index.php/gallery/albums_show/$album_name");
    } catch (Exception $e) {
          $this->loader->load('photos_new', ['album_name'=>$album_name,
                             'title'=>"Ajout d'une photo dans l'album $album_name",
                             'error_message' => $e->getMessage()]);
    }
  }
  
  public function photos_delete($album_name, $photo_name) {
    
    $album_name = filter_var($album_name);
    $this->gallery->delete_photo($album_name, $photo_name);
    $this->loader->load('albums_show', ['title'=>$album_name,
                                        'album'=>$album_name,
                                        'photos'=>$this->gallery->photos($album_name)
                                        ]);
    
    try {
      $this->gallery->check_if_album_exists($album_name);
      header("Location: /index.php/gallery/albums_show/$album_name"); 
    } catch (Exception $e) {
      header('Location: /index.php/gallery');  
    }
  }
  
  public function photos_show($album_name,$photo_name) {
    try {
      $album_name = filter_var($album_name);
      $photo_name = filter_var($photo_name);
      $title = "$album_name/$photo_name";
      $this->loader->load('photos_show', ['title'=>$title,
                                          'album'=>$album_name,
                                          'photo'=>$this->gallery->photo($album_name,$photo_name)
                                          ]);
      } catch (Exception $e) {
    var_dump($e->getMessage());
    header("Location: /index.php");
    }
  }
}