<?php

namespace App\Repository\Shared;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

/**
 * Devuelve los registros de las tablas generales fijas
 */
class TablasAFIPRepository
{
	private Connection $connection;

	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getAllTasasIva(): array
	{
		$sql = "SELECT t.* from tasa_iva t order by t.nombre";
		return $this->connection->fetchAllAssociative($sql);
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getAllUnidadesMedidas(): array
	{
		$sql = "SELECT u.* from unidades_medida u order by u.nombre";
		return $this->connection->fetchAllAssociative($sql);
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getAllConceptosIncluidos(): array
	{
		$sql = "SELECT ci.* from conceptos_incluidos ci order by ci.id";
		return $this->connection->fetchAllAssociative($sql);
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getAllCategoriasIVA(): array
	{
		$sql = "SELECT c.* from categorias_iva c order by c.nombre";
		return $this->connection->fetchAllAssociative($sql);
	}

	/**
	 * Devuelve la categoría del IVA de acuerdo al nombre de ella
	 * @param string $nombre
	 * @return array|bool
	 * @throws Exception
	 */
	public function getAllCategoriasIVAByNombre(string $nombre): array|bool
	{
		$sql = "SELECT c.* from categorias_iva c where c.nombre = ?";
		return $this->connection->fetchAssociative($sql, [$nombre]);
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	public function getProvincias(): array
	{
		$sql = "SELECT p.* from provincia p order by p.nombre";
		return $this->connection->fetchAllAssociative($sql);
	}

	/**
	 * Trae la categoría de iva de acuerdo al nombre - usada en consulta padrón A5
	 * @param string $nombre
	 * @return array|bool
	 * @throws Exception
	 */
	public function getCategoriasIVAByNombre(string $nombre): array|bool
	{
		$sql = 'select * from  categorias_iva c  where c.nombre = ?;';
		return $this->connection->fetchAssociative($sql, [$nombre]);
	}


    /**
     * Obtiene las categorías de IVA por el ID
     * @param int $id
     * @return array|bool
     * @throws Exception
     */
    public function getCategoriasIVAById(int $id): array|bool
    {
        $sql = 'select * from  categorias_iva c  where c.id = ?;';
        return $this->connection->fetchAssociative($sql, [$id]);
    }

	/**
	 * Trae una provincia de acuerdo al código interno del AFIP
	 * @param int $codAfip
	 * @return array|bool
	 * @throws Exception
	 */
	public function getProvinciaByCodAfip(int $codAfip): array|bool
	{
		$sql = 'select * from  provincia p  where p.codigo_afip = ?;';
		return $this->connection->fetchAssociative($sql, [$codAfip]);
	}

	/**
	 * Trae el tipo de comprobante del AFIP de acuerdo a lo que eligió en el IDE el usuario
	 * @param int $cateIvaEmpresa
	 * @param int $cateIvaCliente
	 * @return array
	 * @throws Exception
	 */
	public function getTipoComprobantes(int $cateIvaEmpresa, int $cateIvaCliente): array
	{
		$sql = "SELECT c.* from tipo_comprobante c 
           where c.categoria_iva_empresa_id =  ? and c.categoria_iva_cliente_id = ? order by c.nombre";
		return $this->connection->fetchAllAssociative($sql, array($cateIvaEmpresa, $cateIvaCliente));
	}

	/**
	 * Obtiene el registro del tipo de comprobante por su Id
	 *
	 * @param int $idTipoComprobante
	 * @return array
	 * @throws Exception
	 */
	public function getTipoComprobanteById(int $idTipoComprobante): array
	{
		$sql = "SELECT c.* from tipo_comprobante c where c.id =  ? ";
		return $this->connection->fetchAssociative($sql, [$idTipoComprobante]);
	}

	/**
	 * Trae todas las condiciones de ventas
	 * @return array
	 * @throws Exception
	 */
	public function getCondicionesVentas(): array
	{
		$sql = 'select * from  condicion_de_venta';
		return $this->connection->fetchAllAssociative($sql);
	}

	/**
	 * @param int $idCondVenta
	 * @return false|array
	 * @throws Exception
	 */
	public function getCondicionesVentasByID(int $idCondVenta): array|bool
	{
		$sql = 'select * from  condicion_de_venta cv  where cv.id = ?';
		return $this->connection->fetchAssociative($sql, [$idCondVenta]);
	}

	/**
	 * Busca la tasa de iva por el codigo de AFIP
	 * @param int $codAfip
	 * @return array|bool
	 * @throws Exception
	 */
	public function getTasaIvaByCodAfip(int $codAfip): array|bool
	{
		$sql = 'select * from  tasa_iva ta  where ta.codigo_afip = ?';
		return $this->connection->fetchAssociative($sql, [$codAfip]);
	}

    /**
     * @throws Exception
     */
    public function getTasaIvaById(int $id): array|bool
    {
        $sql = 'select ta.* from  tasa_iva ta  where ta.id = ?';
        return $this->connection->fetchAssociative($sql, [$id]);
    }

    /**
     * @throws Exception
     */
    public function getUnidadMedidaById(int $id): array|bool
    {
        $sql = "SELECT u.* from unidades_medida u where u.id = ?";
        return $this->connection->fetchAssociative($sql, [$id]);
    }
}
