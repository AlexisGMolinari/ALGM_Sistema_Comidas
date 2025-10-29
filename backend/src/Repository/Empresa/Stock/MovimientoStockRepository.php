<?php

namespace App\Repository\Empresa\Stock;

use App\Repository\Contador\ContadorPuntoDeVentaRepository;
use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Ventas\Comprobantes\FacturaRepository;
use App\Repository\Paginador;
use App\Repository\Shared\TablasAFIPRepository;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MovimientoStockRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'movimiento_stock', true);
    }

    /**
     * Trae todos los registros de mov. stock paginados
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getAllPaginados(Request $request): array
    {
        $camposRequest = $request->query->all();
        $idProducto = (isset($camposRequest['idProducto'])) ? (int)$camposRequest['idProducto'] : 0;
        $sql = "SELECT m.*, p.codigo as producto_codigo, p.nombre AS producto_nombre, p.stock_actual, d.nombre AS deposito_nombre,  
                f.punto_venta, f.numero AS comprobante_numero, cl.nombre as cliente 
                FROM movimiento_stock m 
                INNER JOIN producto p on m.producto_id = p.id
                INNER JOIN deposito d on m.deposito_id = d.id
                LEFT JOIN factura_movimiento fm on fm.id = m.factura_movimiento_id
                LEFT JOIN factura f on f.id = fm.factura_id 
                left join cliente cl on f.cliente_id = cl.id 
                where m.empresa_id = $this->empresaId ";

        if ($idProducto > 0) {
            $sql .= " and m.producto_id = $idProducto ";
        }

        $arrParam = ['m.deposito_id', 'd.nombre', 'm.producto_id', 'p.nombre', 'f.numero', 'p.codigo', 'm.cantidad',
            'cl.nombre', 'f.numero', 'm.fecha'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam);  //pasar campos con alias de tabla

        return $paginador->getServerSideRegistros();
    }

    /**
     * Devuelve todos los productos con su stock por depósitos
     * @param Request $request
     * @return array
     * @throws Exception
     */
    public function getAllStockPorDeposito(Request $request): array
    {
        $camposRequest = $request->query->all();

        $where = "where a.empresa_id = " . $this->empresaId . " ";
        if (isset($camposRequest['depositoId']) && (int)$camposRequest['depositoId'] > 0) {
            $where .= " and a.deposito_id = " . $camposRequest['depositoId'] . " ";
        }
        $sql = "SELECT b.codigo,b.nombre,e.nombre as 'familia',d.nombre as 'subfamilia',b.precio,b.fecha_modif,
				c.nombre as 'deposito',a.empresa_id,a.deposito_id,
				ROUND(sum(case a.tipo_movimiento when 1 then a.cantidad else (cantidad * -1) end),2) as 'stock_actual'
				FROM movimiento_stock  a
				inner join producto b on a.producto_id = b.id
				inner join deposito c on a.deposito_id = c.id
				inner join subfamilia d on b.subfamilia_id = d.id
				inner join familia e on e.id =  d.familia_id 
				$where";

        $arrParam = [ 'b.codigo','b.nombre', 'e.nombre', 'd.nombre', 'c.nombre', 'b.fecha_modif'];

        $paginador = new Paginador();
        $paginador->setConnection($this->connection)
            ->setServerSideParams($camposRequest)
            ->setSql($sql)
            ->setContinuaWhere(true)
            ->setCamposAFiltrar($arrParam)
            ->setGroupBy('GROUP by a.producto_id,a.deposito_id');

        return $paginador->getServerSideRegistros();



    }



    /**
     * Crea dos movimientos uno de salida y otro de entrada
     * @param array $valores
     * @return void
     * @throws Exception
     */
    private function crearTransferenciaEntreDepositos(array $valores): void
    {
        $productoRepository = new ProductoRepository($this->connection, $this->security);

        $deposito_destino_id = $valores['deposito_destino_id'];
        unset($valores['deposito_destino_id']);

        $valores['tipo_movimiento'] = '2'; //Salida de depósito origen
        $this->createRegistro($valores);
        $productoRepository->actualizoStock($valores['producto_id'], $valores['tipo_movimiento'], $valores['cantidad']);

        $valores['deposito_id'] = $deposito_destino_id;
        $valores['tipo_movimiento'] = '1'; //Ingreso en depósito destino
        $this->createRegistro($valores);
        $productoRepository->actualizoStock($valores['producto_id'], $valores['tipo_movimiento'], $valores['cantidad']);
    }

    /**
     * Crea un movimiento de stock
     * @param array $valores
     * @return void
     * @throws Exception
     */
    public function crearMovimientosDeStock(array $valores): void
    {
        $this->connection->beginTransaction();

        $valores['empresa_id'] = $this->empresaId;

        if (((int)$valores['tipo_movimiento']) === 3){
            $this->crearTransferenciaEntreDepositos($valores);
        }else{
            unset($valores['deposito_destino_id']);
            $this->createRegistro($valores);
            (new ProductoRepository($this->connection, $this->security))
                ->actualizoStock($valores['producto_id'], $valores['tipo_movimiento'], $valores['cantidad']);
        }
        $this->connection->commit();
    }

    /**
     * Genera movimientos de stock por cada ítem del comprobante
     * Genera movimientos de stock por cada ítem del comprobante
     * @param string $codigoFactura
     * @param int $empresaId
     * @return void
     * @throws Exception
     */
    public function generoMovStockFromComprobante (string $codigoFactura, int $empresaId): void
    {

        $facturaRepository = new FacturaRepository($this->connection, $this->security);
        $factura = $facturaRepository->getByCodigoFactura($codigoFactura);
        if (!$factura) {
            throw new HttpException(404, "No se encontró el comprobante: $codigoFactura al procesar los movimientos de stock");
        }

        $tipoComprobante = (new TablasAFIPRepository($this->connection))
            ->getTipoComprobanteById($factura['cabecera']['tipo_comprobante_id']);
        if (!$tipoComprobante) {
            throw new HttpException(404, "No se encuentra el tipo de comprobante para generar movimientos de Stock");
        }
        $concepto = (int)$tipoComprobante['concepto'];

        $puntoVenta = (new ContadorPuntoDeVentaRepository($this->connection, $this->security))
            ->getByNumero($factura['cabecera']['punto_venta'], $empresaId);
        if (!$puntoVenta) {
            throw new HttpException(404, "No se encuentra el punto de Venta para generar movimientos de Stock");
        }

        //Creo el movimiento de stock
        $movstock['tipo_movimiento'] = $concepto; // el concepto es igual al tipo de mov de stock...
        $movstock['fecha'] = $factura['cabecera']['fecha'];

        $productoRepository = new ProductoRepository($this->connection, $this->security);
        //recorro los movimientos del comprobante y hago los mov de stock
        foreach ($factura['movimientos'] as $movimiento){
            $movstock['producto_id'] = $movimiento['producto_id'];
            $movstock['deposito_id'] = $puntoVenta['deposito_id'];
            $movstock['factura_movimiento_id'] = $movimiento['id'];
            $movstock['cantidad'] = $movimiento['cantidad'];

            //creo el mov stock
            $this->createRegistro($movstock);
            //actualizo el stock actual del producto
            $productoRepository->actualizoStock($movimiento['producto_id'], $concepto, $movimiento['cantidad']);
        }

    }

    /**
     * Elimina todos los movimientos de stock según la empresa que desee
     * Coloca en 0 el stock actual
     * @param array $valores
     * @return void
     * @throws Exception
     */
    public function blanqueoDeStock(array $valores): void
    {
        $empresaId = $valores['empresa_id'];
        $this->connection->beginTransaction();

        $sqlDeleteMov = "DELETE FROM " . $this->nombreTabla . " WHERE empresa_id = ?";
        $this->connection->executeStatement($sqlDeleteMov, [$empresaId]);

        $sqlUpdateStock = "UPDATE producto SET stock_actual = 0 WHERE empresa_id = ?";
        $this->connection->executeStatement($sqlUpdateStock, [$empresaId]);

        $this->connection->commit();
    }
}
