<?php

namespace App\Repository\Empresa\Clientes;

use App\Form\Empresa\Clientes\ClienteType;
use App\Repository\Paginador;
use App\Repository\Shared\LocalidadRepository;
use App\Repository\TablasSimplesAbstract;
use App\Utils\Utils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class ClienteRepository extends TablasSimplesAbstract
{

    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'cliente', true);
    }

    /**
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();

        $sql = "SELECT c.id, c.nombre, c.tipo_documento, c.numero_documento, c.codigo, c.domicilio, c.localidad_id,  
            c.categoria_iva_id, c.observaciones, c.saldo, c.activo,c.telefono, c.email,  cat.nombre as categoria_iva, 
            l.nombre as localidad, p.nombre as provincia_nombre, l.codigo_postal, l.provincia_afip 
            FROM cliente c inner join localidad l on c.localidad_id = l.id 
            inner join provincia p on l.provincia_afip = p.codigo_afip 
            inner join categorias_iva cat on c.categoria_iva_id = cat.id
            where c.empresa_id = $this->empresaId ";

        $arrParam = [ 'c.codigo','c.nombre', 'l.nombre', 'p.nombre', 'c.domicilio', 'c.telefono', 'c.email', 'c.saldo'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setCampoActivo('c.activo')
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }


	/**
	 * Trae el registro del cliente completo (localidad,etc); si se le pasa empresa la toma como parámetro
	 * @param int $id
	 * @param int|null $empresaId
	 * @return array|bool
	 * @throws Exception
	 */
    public function getByidCompleto(int $id, int $empresaId = null): array|bool
    {
		$empresaIde = ($empresaId)? : $this->empresaId;
        $sql = "SELECT c.id, c.nombre, c.tipo_documento, c.numero_documento, c.codigo, c.domicilio, c.localidad_id, 
       			c.telefono, c.email, c.categoria_iva_id, c.observaciones, c.saldo, c.activo, l.nombre as localidad, 
       			p.nombre as provincia_nombre, l.codigo_postal, l.provincia_afip, cat.nombre as categoria_iva,
       			c.condicion_venta_id
            FROM cliente c inner join localidad l on c.localidad_id = l.id 
            inner join provincia p on l.provincia_afip = p.codigo_afip 
            inner join categorias_iva cat on c.categoria_iva_id = cat.id
            where c.id = ? 
            and c.empresa_id = $empresaIde";
        return $this->connection->fetchAssociative($sql, [$id]);
    }

    /**
     * Autocompletar de clientes para una empresa o para varias pasándole el id de empresa (usada en el administrador)
     * @param string $texto
     * @param int|null $empresaId
     * @return array
     * @throws Exception
     */
    public function getAutocompletar(string $texto, ?int $empresaId): array
    {
        $idEmpresa = ($empresaId) ? : $this->empresaId;
        $sql = "select cli.id, cli.codigo, cli.nombre, cli.categoria_iva_id, cat.nombre as categoria_iva
				from cliente cli inner join categorias_iva cat on cli.categoria_iva_id = cat.id 
                where cli.empresa_id = $idEmpresa 
                and cli.activo = 1  
                and (cli.codigo like ? or cli.nombre like ?) 
                order by cli.codigo limit 20";
        return $this->connection->fetchAllAssociative($sql, ["%$texto%", "%$texto%"]);
    }

    /**
     * función que trae el cliente consumidor final predeterminado por cada empresa (venta rápida)
     * @throws Exception
     */
    public function getClienteConsumidorFinal(): array|bool
    {
        $sql = "SELECT c.id FROM cliente c 
            where  c.tipo_documento = 9 
            and c.numero_documento = 11111111
            and c.empresa_id = $this->empresaId
            order by c.id 
            limit 1";
        return $this->connection->fetchAssociative($sql);
    }

    /**
     * Función que busca si existe un cliente con el cuit para no duplicarlo
     * @throws Exception
     */
    public function getByNroCuit(string $cuit, bool $controlaEmpresa = true): array|bool
    {
        $sql = "select * 
                from cliente 
                where numero_documento = ?";
		if ($controlaEmpresa){
			$sql .= " and empresa_id = $this->empresaId";
		}
        return $this->connection->fetchAssociative($sql, [$cuit]);
    }

    /**
     * Busca todas las nc y nd de un cliente
     * @param int $clienteId
     * @param int $empresaId
     * @return array
     * @throws Exception
     */
    public function getNcNd (int $clienteId, int $empresaId): array
    {
        $sql = "SELECT f.id, f.fecha, f.punto_venta, f.numero, f.total_neto, f.total_iva, f.total_final, tip.nombre 
				FROM factura f 
				inner join tipo_comprobante tip on f.tipo_comprobante_id = tip.id
				WHERE f.cliente_id = ? AND f.fecha > '2019-04-13' and  f.empresa_id = ? 
				and f.tipo_comprobante_id in (2,3,7,8, 340, 341, 342, 343, 344, 345, 346, 347, 348, 349, 351,352, 354,355,
				                             357,358,363, 364, 365, 366, 371,372, 372, 374)
				ORDER BY id";
        return $this->connection->fetchAllAssociative($sql, [$clienteId, $empresaId]);
    }

	/**
	 * @param array $registroValores
	 * @return int
	 * @throws Exception
	 */
    public function createRegistroCliente(array $registroValores): int
    {
        // controlo si existe la localidad - sino la guardo
        $localidadId = (new LocalidadRepository($this->connection, $this->security))->guardoLocalidad($registroValores);

        $registroValores['localidad_id'] = $localidadId;
        $registroValores['empresa_id'] = $this->empresaId;
        $registroValores['saldo'] = 0;
        unset($registroValores['codigo_postal'],$registroValores['localidad'],$registroValores['provincia_nombre'],$registroValores['provincia_afip']);
        return parent::createRegistro($registroValores);
    }

	/**
	 * @param array $registro
	 * @param int $recordId
	 * @return void
	 * @throws Exception
	 */
    public function updateRegistroCliente(array $registro, int $recordId): void
    {
        // controlo si existe la localidad - sino la guardo
        $localidadId = (new LocalidadRepository($this->connection, $this->security))->guardoLocalidad($registro);

        $registro['localidad_id'] = $localidadId;
        $registro['empresa_id']   = $this->empresaId;
        unset($registro['codigo_postal'],$registro['localidad'],$registro['provincia_nombre'],$registro['provincia_afip']);
        parent::updateRegistro($registro, $recordId);
    }

    /**
     * @param array $registro
     * @param int $recordId
     * @return void
     * @throws Exception
     */
    public function updateCampoRegistro(array $registro, int $recordId): void
    {
        parent::updateRegistro($registro, $recordId);
    }


    /**
     * @throws Exception
     */
    public function update(array $registro, int $recordId): void
    {
        parent::updateRegistro($registro, $recordId);
    }


	/**
	 * Dependiendo de lo que venga del frontend, se busca un cliente por su id o sino por su CUIT
	 * o bien; si se lo trajo desde AFIP se lo da de alta (si no existe)
	 *
	 * @param array $postValues
	 * @param ClienteType $type
	 * @return array
	 * @throws Exception
	 */
	public function procesoClientesNuevos(array $postValues, ClienteType $type): array {
		$idCliente = (int)$postValues['id'];
		if ($idCliente > 0) {
			$cliente = $this->checkIdExiste($idCliente);
			$clienteId = $cliente['id'];
		}else{
			$cuitCliente = (double)$postValues['numero_documento'];
			$cliente = $this->getByNroCuit($cuitCliente);
			if ($cliente) {
				$clienteId = $cliente['id'];
			} else {
                $postValues['activo'] = ($postValues['activo'])?1:0;
                $postValues['condicion_venta_id'] = 1;
                $postValues['tipo_documento'] = null;
                unset($postValues['categoria_iva']);
				$type->controloRegistro($postValues, 0);
				$clienteId = $this->createRegistroCliente($postValues);
			}
		}
		return $this->getById($clienteId);
	}
    // ------------------------ <Rest Api> -------------------------------------

    /**
     * Devuelve a todos los usuarios que tengan como roles ROLE_USER
     * @return array
     * @throws Exception
     */
    public function getAllRestApi(): array
    {
        $sql = "SELECT empresa_id, nombre FROM usuarios where roles = 'ROLE_USER' order by nombre";

        return $this->connection->fetchAllAssociative($sql);
    }

}
