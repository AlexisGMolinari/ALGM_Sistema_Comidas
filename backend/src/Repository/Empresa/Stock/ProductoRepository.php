<?php

namespace App\Repository\Empresa\Stock;

use App\Repository\Paginador;
use App\Repository\TablasSimplesAbstract;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;

class ProductoRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'producto', true);
    }


    /**
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();

        $sql = "SELECT p.id, p.codigo, p.nombre, p.precio, sf.nombre as subfamilia, ti.nombre as tasaiva, um.nombre as unidad, 
            fa.nombre as familia, p.fecha_modif, p.stock_actual, p.stock_minimo 
            FROM producto p inner join subfamilia sf on p.subfamilia_id = sf.id 
            inner join familia fa on sf.familia_id = fa.id 
            inner join tasa_iva ti on p.tasa_iva_id = ti.id 
            inner join unidades_medida um on p.unidad_id = um.id 
            WHERE p.empresa_id = $this->empresaId";

        $arrParam = [ 'p.codigo','p.nombre', 'p.precio', 'sf.nombre', 'fa.nombre', 'p.fecha_modif'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setCampoActivo('p.activo')
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

    /**
     * Busca el primer producto de cada empresa
     * @throws Exception
     */
    public function getPrimerProductoEmpresa (int $empresaId): array
    {
        $sql = "SELECT p.* 
                FROM producto p
                WHERE p.empresa_id = $empresaId 
                ORDER BY id 
                LIMIT 1";
        $registros = $this->connection->fetchAllAssociative($sql);
        return $registros[0];
    }

    /**
     * @throws Exception
     */
    public function getAutocompletar(string $texto): array
    {
        $sql = "select p.id, IFNULL(p.codigo, '') as codigo, p.nombre, p.precio, ti.nombre as tasaiva 
                from producto p 
                inner join tasa_iva ti on p.tasa_iva_id = ti.id 
                where p.empresa_id = $this->empresaId
                and p.activo = 1  
                and (p.codigo like ? or p.nombre like ?) 
                order by p.codigo limit 20";
        return $this->connection->fetchAllAssociative($sql, ["%$texto%", "%$texto%"]);
    }

    /**
     * Función que trae todos los registros de productos bajo stock mínimo
     * @throws Exception
     */
    public function getProductosBajoStockMinimo(): array
    {
        $sql = "select p.id, p.codigo, p.nombre, f.nombre AS familia_nombre, sf.nombre AS subfamilia_nombre,
                p.stock_actual, p.stock_minimo, (p.stock_actual - p.stock_minimo) AS a_pedir 
                from producto p 
                INNER JOIN subfamilia sf ON p.subfamilia_id = sf.id
                INNER JOIN familia f ON sf.familia_id = f.id 
                WHERE p.empresa_id = $this->empresaId
                AND p.stock_minimo <> 0
                ORDER BY (p.stock_actual - p.stock_minimo), p.nombre";
        return $this->connection->fetchAllAssociative($sql);
    }

    /**
     * Trae la tasa de iva y el nro de tasa de AFIP para items de comprobantes y el costo
     * @throws Exception
     */
    public function getByIdItemComprob(int $id): array
    {
        $sql = "SELECT p.tasa_iva_id, ti.tasa, ti.codigo_afip, p.impuesto_interno, p.costo
                FROM producto p 
                inner join tasa_iva ti on p.tasa_iva_id = ti.id 
                where p.id = ?";
        return $this->connection->fetchAssociative($sql, [$id]);
    }

	/**
	 * Guarda un producto
	 * @param array $producto
	 * @return void
	 * @throws Exception
	 */
	public function saveProducto(array $producto): void
	{
		unset($producto['familia']);
		$producto['fecha_modif'] = (new DateTime(null, new DateTimeZone("America/Argentina/Cordoba")))->format('Y-m-d H:i:s');
		$this->createRegistro($producto);
	}

	/**
	 * @param array $producto
	 * @return void
	 * @throws Exception
	 * @throws \Exception
	 */
	public function updateProducto(array $producto): void
	{
		unset($producto['familia']);
		$producto['fecha_modif'] = (new DateTime(null, new DateTimeZone("America/Argentina/Cordoba")))->format('Y-m-d H:i:s');
		$this->updateRegistro($producto, $producto['id']);
	}

	/**
	 * Actualiza el precio de los productos, si se pasa solo la flia, busco las subflias de esa flia y actualizo
	 * retorno la cantidad de registros actualizados
	 * Para los productos que tengan costo > 0 les recalculamos la utilidad
	 * @throws \Exception
	 * @throws Exception
	 */
    public function actualizoPrecios(array $campos): int|string
    {
        $this->connection->beginTransaction();
        $idFlia     = (int)$campos['idFlia'];
        $idSubFlia  = (int)$campos['idSubFlia'];
        $porciento  = (float)$campos['porciento'];
        $coef       = 1 + ($porciento / 100);
        $fechaHoy   = (new DateTime(null, new DateTimeZone("America/Argentina/Cordoba")))->format('Y-m-d H:i:s');
        if ($idSubFlia === 0){
            // busco todas las subFlias de la flia seleccionada
            $sql        = "SELECT sf.id from subfamilia sf where sf.familia_id = ?";
            $subFlias   = $this->connection->fetchAllAssociative($sql, [$idFlia]);

            $subFtxt = '';
            foreach ($subFlias as $subF ){
                $subFtxt .= $subF['id'] . ',';
            }
            $subFtxt = substr($subFtxt, 0, -1);
            $sql = "UPDATE producto SET precio = precio * ?, costo = costo * ?, fecha_modif = ? WHERE empresa_id = ? and subfamilia_id in ($subFtxt)";
            $count  = $this->connection->executeStatement($sql, [$coef, $coef, $fechaHoy, $this->empresaId]);
            $sql = "UPDATE producto SET utilidad = TRUNCATE((((precio / costo) - 1) * 100),2) WHERE empresa_id = ? and subfamilia_id in ($subFtxt) and costo > 0";
            $this->connection->executeStatement($sql, [$this->empresaId]);
        }else{
            $sql = 'UPDATE producto SET precio = precio * ?, costo = costo * ?, fecha_modif = ? WHERE empresa_id = ? and subfamilia_id = ?';
            $count = $this->connection->executeStatement($sql, [$coef, $coef, $fechaHoy, $this->empresaId, $idSubFlia]);
            $sql = "UPDATE producto SET utilidad = TRUNCATE((((precio / costo) - 1) * 100),2) WHERE empresa_id = ? and subfamilia_id = ? and costo > 0";
            $this->connection->executeStatement($sql, [$this->empresaId, $idSubFlia]);
        }
        $this->connection->commit();
        return $count;
    }

    /**
     * Actualiza el campo stock actual del producto
     * @param int $id
     * @param int $tipoMov
     * @param float $cantidad
     * @throws Exception
     */
    public function actualizoStock (int $id, int $tipoMov, float $cantidad): void
	{
        $signo = ($tipoMov === 1)? '+': '-';
        $sql = "UPDATE producto SET stock_actual = ifnull(stock_actual,0) $signo $cantidad WHERE id = ?";
        $this->connection->executeStatement($sql, [$id]);
    }

	/**
	 * Devuelve todos los productos actualizados entre dos fechas
	 * @param string $fechaDesde
	 * @param string $fechaHasta
	 * @return array
	 * @throws Exception
	 */
	public function getProductosActualizadosPorFecha(string $fechaDesde, string $fechaHasta): array
	{
		$sql = "select pr.* from producto pr where pr.fecha_modif between ? and ? and empresa_id = ?";
		return $this->connection->fetchAllAssociative($sql, [$fechaDesde . ' 00:00', $fechaHasta . ' 23:59', $this->empresaId]);
	}
}
