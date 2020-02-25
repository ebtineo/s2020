<?php
// src/Controller/AlimentosController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Model\Model;
use App\Config\Config;
// use Dompdf\Dompdf;
// use Dompdf\Options;
use Mpdf\Mpdf;

class AlimentosController extends AbstractController{
    private $session;
	private $params;
    
    public function __construct(SessionInterface $session){
        $this->session = $session;
        $this->params = array();
        
        if ($this->session->get('usuario') == null) {
            unset($this->params['usuario']);
		} else {
			$this->params['usuario'] = true;
		}
	}

	public function inicio(){
        $this->params['mensaje'] = 'Bienvenido al proyecto con Symfony v5.0.3 de Eduardo Bracho Tineo';
		$this->params['fecha'] = date('d-m-y');

		return $this->render('alimentos/inicio.html.twig', $this->params);
	}

	public function listar(){
		$m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        $this->params['alimentos'] = $m->dameAlimentos();

		return $this->render('alimentos/listar.html.twig', $this->params);
	}

	public function insertar() {
        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        if ($this->session->get('usuario') == null) {
            $response = new RedirectResponse("/inicio");
        } else {
            $this->params['nombre'] = "";
			$this->params['energia'] = "";
			$this->params['proteina'] = "";
			$this->params['hc'] = "";
			$this->params['fibra'] = "";
			$this->params['grasa'] = "";

            if ($_SERVER['REQUEST_METHOD'] == 'POST') {

                // comprobar campos formulario
                if ($m->validarDatos($_POST['nombre'], $_POST['energia'], $_POST['proteina'], $_POST['hc'], $_POST['fibra'], $_POST['grasa'])) {
                    $m->insertarAlimento($_POST['nombre'], $_POST['energia'], $_POST['proteina'], $_POST['hc'], $_POST['fibra'], $_POST['grasa']);

                    $response2 = new RedirectResponse("/listar");
                } else {
                    $this->params['nombre'] = $_POST['nombre'];
					$this->params['energia'] = $_POST['energia'];
					$this->params['proteina'] = $_POST['proteina'];
					$this->params['hc'] = $_POST['hc'];
					$this->params['fibra'] = $_POST['fibra'];
					$this->params['grasa'] = $_POST['grasa'];
					$this->params['mensaje'] = 'No se ha podido insertar el alimento. Revisa el formulario';
                }
            }

            $response = $this->render('alimentos/insertar.html.twig', $this->params);
        }

        if (isset($response2)) {
            return $response2;
        } else {
            return $response;
        }
    }

    public function buscarPorNombre() {

        $this->params['nombre'] = '';
		$this->params['resultado'] = array();

        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->params['nombre']    = $_POST['nombre'];
            $this->params['resultado'] = $m->buscarAlimentosPorNombre($_POST['nombre']);
        }

        return $this->render('alimentos/buscarN.html.twig', $this->params);
    }

    public function buscarPorEnergia() {
        $this->params['energia'] = '';
		$this->params['resultado'] = array();
		$this->params['mensaje'] = '';

        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->params['energia']   = $_POST['energia'];
            $this->params['resultado'] = $m->buscarAlimentosPorEnergia($_POST['energia']);
            if (count($this->params['resultado']) == 0)
                $this->params['mensaje'] = 'No se han encontrado alimentos con la energía indicada';
        }

        return $this->render('alimentos/buscarE.html.twig', $this->params);
    }

    public function buscarAlimentosCombinada() {
        $this->params['energia'] = '';
		$this->params['nombre'] = '';
		$this->params['resultado'] = array();
		$this->params['mensaje'] = '';

        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->params['energia']   = $_POST['energia'];
            $this->params['nombre']    = $_POST['nombre'];
            $this->params['resultado'] = $m->buscarAlimentosCombinada($_POST['energia'], $_POST['nombre']);
            if (count($this->params['resultado']) == 0){
                $this->params['mensaje'] = 'No se han encontrado alimentos con la energía y nombre indicados';
            }
        }

        return $this->render('alimentos/buscarC.html.twig', $this->params);
    }

    public function wiki() {
        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);
        
        $this->params['alimentos'] = $m->dameAlimentos();
        
        
        return $this->render('alimentos/wiki.html.twig', $this->params);
    }

    public function ver() {
        if (!isset($_GET['id'])) {
            throw new Exception('Página no encontrada');
        }

        $id = $_GET['id'];

        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        $this->params['alimento'] = $m->dameAlimento($id);

        return $this->render('alimentos/ver.html.twig', $this->params);
    }

    // VER XML------------------------
    public function verXML() {
        $id = $_GET['id'];

        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        $this->params['alimento'] = $m->dameAlimento($id);

        $contenido = '<?xml version="1.0"?>\n';
        $contenido .= '<alimento>\n';
        $contenido .= '<nombre>'.$this->params['alimento']['nombre'].'</nombre>\n';
        $contenido .= '<energia>'.$this->params['alimento']['energia'].'</energia>\n';
        $contenido .= '<proteina>'.$this->params['alimento']['proteina'].'</proteina>\n';
        $contenido .= '<hidratocarbono>'.$this->params['alimento']['hidratocarbono'].'</hidratocarbono>\n';
        $contenido .= '<fibra>'.$this->params['alimento']['fibra'].'</fibra>\n';
        $contenido .= '<grasatotal>'.$this->params['alimento']['grasatotal'].'</grasatotal>\n';
        $contenido .= '</alimento>\n';

        // DESCARGAR
        $alimento = fopen("xml/".$this->params['alimento']['nombre'].".xml","w");
        fwrite($alimento, $contenido);
        fclose($alimento);

        return $this->render('alimentos/verXML.html.twig', $this->params);
    }

    // DESCARGAR PDF
    public function descargarPDF() {

        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        $this->params['alimentos'] = $m->dameAlimentos();

        $html = $this->renderView('alimentos/descargarPDF.html.twig', $this->params);

        $mpdf = new \Mpdf\Mpdf();

        $mpdf->WriteHTML($html);
        $mpdf->Output('listadoAlimentos.pdf','I');
    }

    // EDITAR/ELIMINAR ALIMENTO ------------------------
    public function edit() {
        
        if ($this->session->get('usuario') == null) {
            $response = new RedirectResponse("/inicio");
        } else {
            $id = $_REQUEST['id'];

            $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

            $this->params['alimento'] = $m->dameAlimento($id);

            if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar'])) {
                if ($m->validarDatos(//uso la función creada para validar cada uno de los campos y que sean del tipo definido
                    $_POST['nombre'],
                    $_POST['energia'],
                    $_POST['proteina'],
                    $_POST['hc'],
                    $_POST['fibra'],
                    $_POST['grasa']
                )) {
                    $m->editarAlimento(//llamo a la función diseñada para editar los alimentos pasandole los nuevos valores
                        $_POST['id'],
                        $_POST['nombre'],
                        $_POST['energia'],
                        $_POST['proteina'],
                        $_POST['hc'],
                        $_POST['fibra'],
                        $_POST['grasa']
                    );
                    $response2 = new RedirectResponse("/listar");
                } else {
                    $this->params['nombre'] = $_POST['nombre'];
					$this->params['energia'] = $_POST['energia'];
					$this->params['proteina'] = $_POST['proteina'];
					$this->params['hc'] = $_POST['hc'];
					$this->params['fibra'] = $_POST['fibra'];
					$this->params['grasa'] = $_POST['grasa'];
					$this->params['mensaje'] = 'No se ha podido editar el alimento. Revisa el formulario';
                }

            }else if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['borrar'])) {
                $m->borrarAlimento($id);
                $response2 = new RedirectResponse("/listar");
            }

            $response = $this->render('alimentos/edit.html.twig', $this->params);
        }

        if (isset($response2)) {
            return $response2;
        } else {
            return $response;
        }
    }

    public function login() {
        $m = new Model(Config::$mvc_bd_nombre, Config::$mvc_bd_usuario, Config::$mvc_bd_clave, Config::$mvc_bd_hostname);

        if ($this->session->get('usuario') == null) {
            $this->params['nombre'] = '';
			$this->params['pass'] = '';

            $response = $this->render('alimentos/login.html.twig', $this->params);

            // LOCAL SOLO CON ADMIN/ADMIN
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
				if ($m->logarme($_REQUEST['usuario'], $_REQUEST['pass'], $this->session)) {
                    $response = new RedirectResponse("/inicio");
				} else {
					$this->params['mensaje'] = 'Error al iniciar sesion!';
					$response = $this->render('alimentos/login.html.twig', $this->params);
				}
            }
            
        } else {
            $response = new RedirectResponse("/inicio");
        }
        
        return $response;        
    }

    public function cerrarSesion(){
        $this->session->clear();
        $response = new RedirectResponse("/inicio");
        return $response;
	}
}

?>