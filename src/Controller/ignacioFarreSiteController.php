<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\ignacio_farre_site\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\taxonomy\Entity\Term;

class IgnacioFarreSiteController extends ControllerBase {

  private $baseURL = 'http://drupal.ignaciofarre.com'; 

  /**
   * Función que devuelve el id de la entidad desde un pathAlias
   * @param string $tutorialPath
   * @return string
   */
  private function getIdFromPath($tutorialPath) {
    $path = Drupal::service('path.alias_manager')->getPathByAlias('/' . $tutorialPath);
    $partes = explode("/", $path);
    return array_pop($partes);
  }

  /**
   * Funcióon que devuelve un array de una taxonomía "Categoría" preparado para enviar al frontend a partir de su tid
   * @param Int $termId
   * @return array
   */
  private function cargarCategoria($termId) {
    $categoria = [];
    $term = Term::load($termId);
    $imagen = File::load($term->get('field_categoria_imagen')->target_id);
    $categoria['nombre'] = $term->get('name')->value;
    $categoria['description'] = $term->get('field_categoria_descrip_corta')->value;
    $categoria['titulo_desc'] = $term->get('field_categoria_titulo_desc')->value;
    $categoria['imagen'] = $this->baseURL . file_url_transform_relative(file_create_url($imagen->get('uri')->value));
    $categoria['url'] = Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/' . $termId);
    //Ponemos por defecto la sombra false para qeu cuando se ponga el cursor encima se vuelva true.
    $categoria['sombrear'] = ['sombra' => true];
    return $categoria;
  }
  
  /**
   * Función que devulve un nodo tutorial como array
   * @param Int $nid
   * @return array
   */
  private function cargarNodoTutorial($nid) {
    $tutorial = [];
    $tutorialN = Node::load($nid);

    //Cargamos las imágenes.
    $fileImagen = File::load($tutorialN->get('field_image')->target_id);
    //Creamos el tutorial
    $tutorial['titulo'] = $tutorialN->get('title')->value;
    $tutorial['body'] = $tutorialN->get('body')->value;
    $tutorial['subtitulo'] = $tutorialN->get('field_tutorial_subtitulo')->value;
    $tutorial['imagen'] = $this->baseURL . file_url_transform_relative(file_create_url($fileImagen->get('uri')->value));
    $tutorial['url'] = Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
    $tutorial['fecha_creacion'] = date("d-m-Y", $tutorialN->get('created')->value);
    $tutorial['autor'] = 'Ignacio Farre';
    foreach ($tutorialN->get('field_tutorial_categoria') as $key => $categoria) {
      $term = Term::load($categoria->target_id);
      $tutorial['categorias'][$key]['nombre'] = $term->get('name')->value;
      $tutorial['categorias'][$key]['url'] = Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/' . $categoria->target_id);
    }
    return $tutorial;
  }

  /**
   * Función que devuelve los id de las taxonomias de un vocabulario.
   * @param string $vocabulario
   * @return boolean
   */
  private function getTaxoDeUnVocabulario($vocabulario) {
    $query = Drupal::entityQuery('taxonomy_term')
            ->condition('vid', $vocabulario)
            ->sort('weight')
            ->execute();
    if (!empty($query)) {
      return $query;
    }
    return false;
  }

  /*
   ******************************************************************************
   * Public functions
   ******************************************************************************
  */
  
  public function getPrueba2() {
    header('Access-Control-Allow-Origin: *');
    $tipos = ['bien', 'funciona', 'muy bien2'];
    
    return new JsonResponse($tipos);
  }
  
  public function getPrueba() {
    header('Access-Control-Allow-Origin: *');
    $tipos = ['bien', 'funciona', 'muy bien'];
    
    return new JsonResponse($tipos);
  }
  /**
   * Función que devuelve un json para llenar el slider
   * @return JsonResponse
   */
  public function getArticulosSlider() {
    header('Access-Control-Allow-Origin: *');
    $tipos = [];
    $query = Drupal::entityQuery('node')
            ->condition('type', 'articulo_ignacio_farre')
            ->condition('status', 1)
            ->condition('field_tags.entity.name', 'slider')
            ->execute();

    if (!empty($query)) {
      $contador = 0;
      foreach ($query as $tipoId) {
        $tipoN = Node::load($tipoId);
        //Hay que crear la entidad imagen y sacarle el path...
        $file1000 = File::load($tipoN->get('field_image')->target_id);
        $file640 = File::load($tipoN->get('field_imagen_640')->target_id);
        //La transformamos en absolta nosotros ya que si no me mete el public://
        $img_absoluta1000 = $this->baseURL . file_url_transform_relative(file_create_url($file1000->get('uri')->value));
        $img_absoluta640 = $this->baseURL . file_url_transform_relative(file_create_url($file640->get('uri')->value));
        //Creamos el array para enviar
        $tipos[$tipoId] = [
            'titulo' => $tipoN->get('title')->value,
            'resumen' => $tipoN->get('field_resumen')->value,
            'imagen1000' => $img_absoluta1000,
            'imagen640' => $img_absoluta640,
            'nid' => $tipoId,
            'id' => $contador
        ];
        $contador++;
      }
    }
    return new JsonResponse($tipos);
  }

  /**
   * Función que devulve un array de objetos para llenar los poderes
   * @return JsonResponse
   */
  public function getPoderes() {
    header('Access-Control-Allow-Origin: *');
    $poderes = [];
    $query = Drupal::entityQuery('node')
            ->condition('type', 'ignacio_farre_poderes')
            ->condition('status', 1)
            ->sort('field_orden', 'ASC')
            ->execute();

    if (!empty($query)) {
      foreach ($query as $poderId) {
        $poderN = Node::load($poderId);
        $poder['titulo'] = $poderN->get('title')->value;
        $poder['valor'] = $poderN->get('field_valor')->value;
        $poder['tipo'] = $poderN->get('field_tipo')->value;
        $poder['clase']['progress-striped'] = $poderN->get('field_clase_progress_striped')->value == '0' ? false : true;
        $poder['clase']['active'] = $poderN->get('field_clase_active')->value == '0' ? false : true;
        $poder['mostrarImput'] = $poderN->get('field_mostrar_imput')->value == '0' ? false : true;
        $poder['claseTitulo']['text-warning'] = $poderN->get('field_clase_del_titulo_text_warn')->value == '0' ? false : true;

        $poderes[] = $poder;
      }
    }
    return new JsonResponse($poderes);
  }

  /**
   * función que devuelve un json con todos los nodos tipo experiencia en forma de array.
   * @return JsonResponse
   */
  public function getExperiencias() {
    header('Access-Control-Allow-Origin: *');
    $experiencias = [];

    $query = Drupal::entityQuery('node')
            ->condition('type', 'ignacio_farre_experiencia')
            ->condition('status', 1)
            ->sort('field_orden', 'ASC')
            ->execute();

    if (!empty($query)) {
      foreach ($query as $experienciaId) {
        $experienciaN = Node::load($experienciaId);

        //Cargamos las imágenes.
        $fileImagen500 = File::load($experienciaN->get('field_imagen_640')->target_id);
        $fileLogo = File::load($experienciaN->get('field_logotipo')->target_id);
//        print_r($experienciaN); 
        //Creamos la experiencia
        $experiencia['titulo'] = $experienciaN->get('title')->value;
        $experiencia['textoCorto'] = $experienciaN->get('field_texto_largo')->value;
        $experiencia['imagen500'] = $this->baseURL . file_url_transform_relative(file_create_url($fileImagen500->get('uri')->value));
        $experiencia['logo'] = $this->baseURL . file_url_transform_relative(file_create_url($fileLogo->get('uri')->value));
        $experiencia['anno'] = $experienciaN->get('field_ano_de_la_experiencia')->value;
        $experiencia['mostrarTecnologia'] = false;
        

        //Cargamos las tecnologías.
        $contador = 0;
        foreach ($experienciaN->get('field_tecnologia_usada') as $tecnologia) {
          $tecnologiaN = Node::load($tecnologia->target_id);
          $experiencia['tecnologias'][$contador] = $tecnologiaN->get('title')->value;
          $contador++;
        }
        $experiencias[] = $experiencia;
      }
    }
    return new JsonResponse($experiencias);
  }

  /**
   * 
   * @param string $articuloPath
   * @return JsonResponse
   */
  public function getExperiencia($articuloPath) {
    header('Access-Control-Allow-Origin: *');
    $experiencia = $articuloPath;
    return new JsonResponse($experiencia);
  }

  /**
   * Función qeu devulve las categorias "terms"
   * @return JsonResponse
   */
  public function getTutoriales() {
    header('Access-Control-Allow-Origin: *');
    $categorias = [];
    foreach ($this->getTaxoDeUnVocabulario('categorias') as $tid) {
      $categoria = $this->cargarCategoria($tid);
      $categorias[] = $categoria;
    }
    return new JsonResponse($categorias);
  }

  /**
   * Función que devuelve los datos del term y de los nodos que tiene asociados
   * @param string $tutorialPath -> alias de la url del term
   * @return JsonResponse
   */
  public function getTutorial($tutorialPath) {
    header('Access-Control-Allow-Origin: *');
    //Hay que coger el id a través del path    
    $tid = $this->getIdFromPath($tutorialPath);
    $categoria = $this->cargarCategoria($tid);

    //Ahora hay que cargar los artículos que hay en la categoria
    $query = Drupal::entityQuery('node')
            ->condition('type', 'ignacio_farre_tutoriales')
            ->condition('status', 1)
            ->condition('field_tutorial_categoria', $tid)
            ->execute();

    if (!empty($query)) {
      foreach ($query as $entity_id) {
        $categoria['articulos'][] = $this->cargarNodoTutorial($entity_id);
      }
    }
    return new JsonResponse($categoria);
  }
  
  public function getArticuloTutorial($tutorialPath, $articuloPath) {
    header('Access-Control-Allow-Origin: *');
    //Hay que coger el id a través del path    
    $nid = $this->getIdFromPath($articuloPath);
    $articulo = $this->cargarNodoTutorial($nid);
    
    
    
    
    return new JsonResponse($articulo);
  }

}

//$query = \Drupal::entityQuery('node');
//    $group = $query->orConditionGroup();
//    foreach ($this->getTaxoDeUnVocabulario('categorias') as $tid) {
//      $group = $group->condition('field_tutorial_categoria', $tid);
//    }
//    $entity_ids = $query
//            ->condition('type', 'ignacio_farre_tutoriales')
//            ->condition('status', 1)
//            ->condition($group)
//            ->execute();



//Using entityQuery, you can get the taxonomy terms  of a particular vocabulary.
//
//    $query = \Drupal::entityQuery('taxonomy_term');
//    $query->condition('vid', "tags");
//    $tids = $query->execute();
//    $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);
//
//If you want to get several vocabulary, use OR condition like that.
//    $query_or->condition('vid', "tags");
//    $query_or->condition('vid', "test");
//    $query->condition($query_or);
//
//Get Taxonomy name
//      $name = $term->toLink()->getText();
//
//Create link to the term
//      $link = \Drupal::l($term->toLink()->getText(), $term->toUrl());

//$query->sort('weight');


//$alias = \Drupal::service('path.alias_manager')->getAliasByPath('/taxonomy/term/' . $tid);
//        $path = \Drupal::service('path.alias_manager')->getPathByAlias($alias);