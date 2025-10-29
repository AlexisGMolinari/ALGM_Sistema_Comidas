<?php

namespace App\Repository\Empresa\Cobranzas;

use App\Repository\Empresa\Clientes\ClienteRepository;
use App\Repository\Paginador;
use App\Repository\Shared\TablasAFIPRepository;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReciboRepository extends TablasSimplesAbstract
{
    private string $fechaInicio = '2019-04-13';
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'recibo', true);
    }

	/**
	 * @throws Exception
	 */
	public function getAllPaginados(Request $request): array
	{
		$camposRequest = $request->query->all();
		$sql = "SELECT r.id, r.fecha, r.importe, r.codigo as codRecibo, c.codigo as codCliente, c.nombre as cliente 
				FROM recibo r
				LEFT JOIN cliente c ON r.cliente_id = c.id
				WHERE r.empresa_id = " . $this->empresaId .  ' ';

		$arrParam = ['r.fecha','r.importe', 'c.nombre'];

		$paginador = new Paginador();
		$paginador->setConnection($this->connection)
			->setServerSideParams($camposRequest)
			->setSql($sql)
			->setContinuaWhere(true)
			->setCamposAFiltrar($arrParam);

		return $paginador->getServerSideRegistros();
	}

	/**
	 * Busca un recibo por su código y las facturas imputadas
	 * @param string $codigo
	 * @return array
	 * @throws Exception
	 */
	public function getByCodigoCompleto(string $codigo): array
	{
		$sql = "select r.* from recibo r where r.codigo = ?";
		$cabecera = $this->connection->fetchAssociative($sql, [$codigo]);
		if (!$cabecera)
			throw new HttpException(404, 'No se encontró el recibo: ' . $codigo);

		$reciboID = (integer)$cabecera['id'];
		// para obtener el saldo sumo todos los recibos que tiene inputada esa factura hasta la fecha del propio recibo
		$sql = "SELECT fr.*, f.punto_venta, f.numero, f.total_final, f.fecha as fechaFac, r.fecha, tc.nombre, tc.concepto, "
			. "     (select  "
			. "         CASE "
			. "             WHEN tc.concepto = 1 then ((f.total_final * (-1)) - sum(fr2.importe)) "
			. "             ELSE (f.total_final - sum(fr2.importe)) "
			. "         END as saldoTotalFc "
			. "     from facturas_recibos fr2 inner join recibo r2 on fr2.recibo_id = r2.id  "
			. "     where fr2.factura_id = fr.factura_id and r2.fecha <=  r.fecha) as saldoTotalFc  "
			. "FROM facturas_recibos fr inner join factura f on fr.factura_id = f.id "
			. "inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id "
			. "inner join recibo r on fr.recibo_id = r.id  "
			. "where fr.recibo_id = ?";
		$facturasimputadas = $this->connection->fetchAllAssociative($sql, [$reciboID]);
		$devo['cabecera'] = $cabecera;
		$devo['fcImputadas'] = $facturasimputadas;
		return $devo;
	}


	/**
	 * Función que guarda el recibo y la relación si no es ctacte
	 *
	 * @param $datos array cabecera de la factura
	 * @param $idFactura int el id de la factura
	 * @throws Exception
	 */
	public function saveContado(array $datos, int $idFactura): void
	{
		//analizo si es NC de contado pongo el monto en negativo
		$tablasAfipRepository = new TablasAFIPRepository($this->connection);
		$tipoComprobante = $tablasAfipRepository->getTipoComprobanteByID($datos['tipo_comprobante_id']);
		$concepto = (int)$tipoComprobante['concepto'];
		$importeComprob = $datos['total_final'];
		if ($concepto === 1) {
			$importeComprob = $datos['total_final'] * (-1);
		}

		$condVtaID  = (int)$datos['condicion_venta_id'];
		$condVtaTxt = '';
		if ($condVtaID === 2) {
			// si es cta cte y estoy haciendo un recibo le pongo ENTREGA en el detalle
			$condVtaTxt = 'Entrega';
		} else {
			// busco la condición de venta para agregarla al detalle del recibo
			$condVta = $tablasAfipRepository->getCondicionesVentasByID($datos['condicion_venta_id']);
			$condVtaTxt = 'Pago ' . $condVta['nombre'];
		}

		// armo array de recibos con la cabecera de la factura
		$arrRecibo = ['fecha' => $datos['fecha'],
			'cliente_id'    => $datos['cliente_id'],
			'importe'       => $importeComprob,
			'codigo'        => $datos['codigo'],
			'detalle'       => $condVtaTxt
		];
		$lastInsert = $this->createRegistro($arrRecibo);

		//guardo en la tabla de relación de recibos con facturas
		$arrRecFacr = [
			'factura_id'=> $idFactura,
			'recibo_id' => $lastInsert,
			'importe'   => $datos['total_final']
		];
		$this->connection->insert('facturas_recibos', $arrRecFacr);

	}


	/**
	 * Función que guarda los datos del pago en el recibo. REcorre todas las facturas y guarda el saldo
	 * puede ser parcial o total, y si deja dinero a cuenta queda en recibos
	 * @param $datosPost array
	 * @return void
	 * @throws Exception
	 */
	public function savePago(array $datosPost): void
	{
		$sql = "SELECT f.id, rf.id as rfIde, Ifnull(rf.importe,0) as pagoRel, tc.concepto, "
			. "case "
			. "WHEN tc.concepto = 1 then ((f.total_final - Ifnull(rf.importe,0)) * (-1)) "
			. "ELSE (f.total_final - Ifnull(SUM(rf.importe),0)) "
			. " END as saldoRelacionado "
			. "FROM factura f left join facturas_recibos rf on f.id = factura_id "
			. "inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id "
			. "where f.id in (?) "
			. "group by 1 "
			. "order by saldoRelacionado";

		$stmt =  $this->connection->executeQuery($sql,
			array($datosPost['idFacturas']),
			array(ArrayParameterType::INTEGER)
		);

		$montoCobrado = (float)$datosPost['montoACobrar'];
		// por cada factura descuento el importe del recibo y la inputo total o parcial
		$cantRec = $stmt->rowCount();
		$facturas = $stmt->fetchAllAssociative();
		$regi = 0;

		$this->connection->beginTransaction();

		// guardo el recibo
		$arrRecibo = [
			'cliente_id' => $datosPost['idCliente'],
			'importe' => $datosPost['montoACobrar'],
			'detalle' => $datosPost['detallePago']
		];
		$lastInsert = $this->createRegistro($arrRecibo);

		foreach ($facturas as $row) {
			$regi++;
			$saldoRel = round(floatval($row['saldoRelacionado']), 2);
			if ($saldoRel > 0) {
				$saldo = round($montoCobrado - $saldoRel, 2);
			} else {
				// si es negativo es porque tiene dinero a favor del cliente; entonces se compensa esa factura y suma el saldo para la siguiente
				$arrRecFAc = [
					'factura_id' => (int)$row['id'],
					'recibo_id' => $lastInsert,
					'importe' => $saldoRel
				];
				$this->connection->insert('facturas_recibos', $arrRecFAc);
				$montoCobrado += ($saldoRel * (-1));
				continue;
			}

			$arrRecFAc = [
				'factura_id' => (int)$row['id'],
				'recibo_id' => $lastInsert,
			];

			if ($saldo > 0) {                                               // saldo > 0 sigo procesando las demás facturas
				if ($regi === $cantRec) {                                    // si es la última factura le imputo to do el saldo
					if ($cantRec === 1) {                                    // si es una sola factura y pagan con un monto mayor
						$arrRecFAc['importe'] = $montoCobrado;
					} else {
						$arrRecFAc['importe'] = $montoCobrado;
					}

				} else {
					$arrRecFAc['importe'] = $saldoRel;
				}
				$this->connection->insert('facturas_recibos', $arrRecFAc);
				$montoCobrado -= $saldoRel;
			} else if ($saldo === 0) {                                       //saldo = 0 termino el proceso
				$arrRecFAc['importe'] = $saldoRel;
				$this->connection->insert('facturas_recibos', $arrRecFAc);
				break;
			} else {                                                          //saldo < 0 termino el proceso (pago parcial9
				$arrRecFAc['importe'] = $montoCobrado;
				$this->connection->insert('facturas_recibos', $arrRecFAc);
				break;
			}

		}
		// actualizo el saldo del cliente
		$saldo = $this->getSaldoResumen($datosPost['idCliente']);
		$arrCliente = ['saldo' => (float)$saldo];
		$this->connection->update('cliente', $arrCliente, ['id' => $datosPost['idCliente']]);

		$this->connection->commit();
	}

    /**
     * Función que calcula el saldo que tiene el cliente, suma todas las facturas menos los recibos, menos NC
     * @throws Exception
     */
    public function getSaldoResumen(int $idCliente): float
    {
        $sql = "select 
                    (select sum(f.total_final) 
                     from factura f 
                     inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
                     where f.empresa_id = $this->empresaId
                     and f.cliente_id = ? 
                     and f.fecha > ?
                     and tc.concepto = 2) as impoFactu, 
                    (select sum(f.total_final) 
                     from factura f 
                     inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
                     where f.empresa_id = $this->empresaId
                     and f.cliente_id = ?
                     and f.fecha > ? 
                     and tc.concepto = 1) as impoNC, 
                    (select sum(r.importe) 
                     from recibo r 
                     where r.empresa_id = $this->empresaId 
                     and r.cliente_id = ? 
                     and r.fecha > ?) as impoReci ";

        $param = [$idCliente, $this->fechaInicio,  $idCliente, $this->fechaInicio, $idCliente, $this->fechaInicio];
        $totales = $this->connection->fetchAssociative($sql, $param); // el primer parámetro es idEmpresa
        return round((float)$totales['impoFactu'] - $totales['impoNC'] - $totales['impoReci'],2);
    }

    /**
     * Función que trae todos los registros de factura y recibos para el resumen de cuenta
     * @throws Exception
     */
    public function getResumenCuenta(int $idCliente, int $periodo): array
    {
        $sql = "select f.id, DATE_FORMAT(f.fecha,'%d/%m/%Y') AS fecha, Concat(LPAD(f.punto_venta,4,'0'), '-' , LPAD(f.numero,8,'0')) as numero, 
            f.total_final as importe, tc.concepto, tc.nombre, f.codigo, f.fecha as fechi, cond.nombre AS condicion   
            from factura f inner join tipo_comprobante tc on f.tipo_comprobante_id = tc.id 
            INNER JOIN condicion_de_venta cond ON f.condicion_venta_id = cond.id
            where f.empresa_id = $this->empresaId and f.cliente_id = ? and f.fecha > DATE_ADD(NOW(),INTERVAL -? MONTH) 
            union 
            select r.id, DATE_FORMAT(r.fecha,'%d/%m/%Y') AS fecha, LPAD(r.id,8,'0') as numero, 
            r.importe, 1, 'RECIBO', r.codigo, r.fecha as fechi, null  
            from recibo r 
            where r.empresa_id = $this->empresaId and r.cliente_id = ? and r.fecha > DATE_ADD(NOW(),INTERVAL -? MONTH) 
            order by 8 desc";
        $param = [$idCliente, $periodo, $idCliente, $periodo];
        return $this->connection->fetchAllAssociative($sql, $param);
    }


	/**
	 * Borra la relación del recibo con la factura, borra el recibo y actualiza el saldo del cliente
	 *
	 * @param int $id
	 * @param int $clienteId
	 * @return void
	 * @throws Exception
	 */
	public function deleteRecibo(int $id, int $clienteId):void
	{
		$this->connection->beginTransaction();

		$this->connection->delete('facturas_recibos', ['recibo_id' => $id]);
		$this->connection->delete('recibo', ['id' => $id]);

		$saldo = $this->getSaldoResumen($clienteId);
		(new ClienteRepository($this->connection, $this->security))->update(['saldo' => $saldo], $id);

		$this->connection->commit();

	}


}
