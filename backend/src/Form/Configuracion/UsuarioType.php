<?php


namespace App\Form\Configuracion;

use App\Form\AbstractTypes;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\Constraints as Assert;

class UsuarioType extends AbstractTypes
{


	public function constrains(int $id): Assert\Collection
	{
		$arrConst =[
			'id' => new Assert\Range(['min' => $id]),
			'nombre' => [new Assert\NotBlank(), new Assert\Length(['min' => 3])],
			'email' =>[new Assert\NotBlank(), new Assert\Email()],
			'roles' => [new Assert\NotBlank(), new Assert\Choice(['ROLE_ADMIN', 'ROLE_USER'])],
			'activo' => [new Assert\NotBlank(), new Assert\Choice([0,1,"0","1"])],
		];
		if ($id === 0){
			$arrConst['password'] = [new Assert\NotBlank(), new Assert\Length(['min' => 6])];
		}else{
			$arrConst['password'] = new Assert\Optional();
		}
		return new Assert\Collection($arrConst);
	}

    /**
     * Método que controla el registro enviado
     * @param array $postValues
     * @param int $id
     */
	public function controloRegistro(array $postValues, int $id): void
	{
		$this->controloRol($postValues['roles']);
		$constCompr = $this->constrains($id);
		$errors = $this->validation->validate($postValues, $constCompr);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}
	}

	/**
	 * Controlo que un supervisor NO permita cargar un Administrador
	 */
	private function controloRol(string $rol): void
	{
		if ($rol === 'ROLE_ADMIN' && $this->security->getUser()->getRoles()[0] === 'ROLE_USER') {
			throw new HttpException(400, 'No se permite crear un Administrador');
		}
	}


	/**
	 * Constrains para el cambio de clave
	 */
	private function constrainsCambioClave(): Assert\Collection
	{
		return new Assert\Collection([
			'primeraClave' => [
				new Assert\NotBlank(['normalizer'=>'trim']),
				new Assert\Length(['min' => 8]),
				new Assert\Regex('/^(?=[^A-Z]*[A-Z])(?=[^a-z]*[a-z])(?=[^0-9]*[0-9]).{8,}$/')],
			'segundaClave' => [
				new Assert\NotBlank(['normalizer'=>'trim']),
				new Assert\Length(['min' => 8]),
				new Assert\Regex('/^(?=[^A-Z]*[A-Z])(?=[^a-z]*[a-z])(?=[^0-9]*[0-9]).{8,}$/')]
		]);
	}

	/**
	 * @param array $postValues
	 * @return void
	 */
	public function controloCambioClaves(array $postValues): void
	{
		$constUsuario = $this->constrainsCambioClave();
		$errors = $this->validation->validate($postValues, $constUsuario);
		if (0 !== count($errors)) {
			$mensaje = $this->traduccionError($errors[0]);
			throw new HttpException(400, $mensaje);
		}
		if ($postValues['primeraClave'] !== $postValues['segundaClave']) {
			throw new HttpException(400, 'Las claves NO coinciden');
		}
	}
}
