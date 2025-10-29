<?php

namespace App\Repository\Administrador;

use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Clase creada para todos los métodos de conciliación de cuentas corrientes
 */
class AdminConciliacionRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, '');
    }

    /**
     * @param int $idCliente
     * @return array
     * @throws Exception
     */
    private function getFacturasCliente(int $idCliente): array
    {
        $sql = "SELECT f.id, f.fecha, f.total_final 
				FROM factura f 
				WHERE f.cliente_id = ? AND f.fecha > '2019-04-13'
				ORDER BY id";
        $facturas = $this->connection->fetchAllAssociative($sql, [$idCliente]);
        if (!$facturas) {
            throw new HttpException(404, 'No hay facturas a procesar');
        }
        return $facturas;
    }

    /**
     * Obtiene el primer recibo (el más viejo) que no está relacionado con una factura
     *
     * @param int $idCliente
     * @return array|false
     * @throws Exception
     */
    private function getRecibo(int $idCliente): array|false
    {
        $sql = "SELECT r.id, (r.importe - 
					IFNULL(
						(SELECT Sum(fr.importe) 
							FROM  facturas_recibos fr 
							where r.id = fr.recibo_id ), 0
							) 
					)AS resultado
				FROM recibo r
				WHERE r.cliente_id = ? 
				HAVING truncate(resultado,2) > 0
				ORDER BY r.id";
        return $this->connection->fetchAssociative($sql, [$idCliente]);
    }

    /**
     * Borra las relaciones entre las facturas y los recibos para un cliente
     * @param int $idCliente
     * @return void
     * @throws Exception
     */
    private function deleteRelaciones(int $idCliente):void
    {
        $sql = "DELETE facturas_recibos
				FROM facturas_recibos 
				INNER JOIN factura ON facturas_recibos.factura_id = factura.id
				WHERE factura.cliente_id = ?";
        $this->connection->executeQuery($sql,[$idCliente]);
    }

    /**
     * Inserta una relación entre fc y recibos
     * @param array $datos
     * @return void
     * @throws Exception
     */
    private function insertoRelacion(array $datos):void
    {
        $this->connection->insert('facturas_recibos', $datos);
    }

    /**
     * Concilia ctas. ctes. de clientes. Voy recorriendo las fcs del cliente y le voy imputando los
     * recibos. Si sobra saldo imputo otro recibo, si el saldo es cero paso a la próxima fc y si al recibo le sobra
     * también tomo otra factura. En definitiva voy armando las relaciones de la tabla facturas_recibos
     *
     * @param int $clienteId
     * @return void
     * @throws Exception
     */
    public function concilioCuenta(int $clienteId):void
    {
        $this->connection->beginTransaction();

        // traigo todas las facturas de ese cliente
        $facturas = $this->getFacturasCliente($clienteId);
        // borro todas las relaciones entre fc y recibos de ese cliente
        $this->deleteRelaciones($clienteId);
        $fp = fopen('../var/log/conciliacion.log','wa+');
        $sigo = true;
        foreach ($facturas as $factura) {
            if ((int)$factura['id'] === 1){
                $detengo = true; // usamos esto para detener el debuguer
            }
            $saldo = $factura['total_final'] ;
            $mensaje = '* ' . $factura['id'] . "- $saldo  -----------------------------------------------" . PHP_EOL;
            fwrite($fp,$mensaje);
            do {
                // traigo el recibo más viejo con saldo en la relación entre Fc y Recibos
                $recibo = $this->getRecibo($clienteId);
                if (!$recibo) {
                    $mensaje = "NO hay más recibos - $saldo" . PHP_EOL;
                    fwrite($fp,$mensaje);
                    break;
                }
                $saldo -= $recibo['resultado'] ;
                $mensaje = '= ' . $recibo['id'] . '- Res: ' . $recibo['resultado'] . " - $saldo" . PHP_EOL;
                fwrite($fp,$mensaje);
                if ($saldo > 0) {
                    $arr = [
                        'factura_id' => $factura['id'],
                        'recibo_id' => $recibo['id'],
                        'importe' => $recibo['resultado']
                    ];
                    $mensaje = $arr['factura_id'] . ' - ' . $arr['recibo_id'] . ' - ' . $arr['importe'] . " // $saldo";
                    $factura['total_final'] -= $recibo['resultado']; // descuento en la fc lo imputado en el recibo
                    $sigo = true;
                }else if ($saldo < 0) {
                    $arr = [
                        'factura_id' => $factura['id'],
                        'recibo_id' => $recibo['id'],
                        'importe' => $factura['total_final']
                    ];
                    $mensaje = '-: ' .$arr['factura_id'] . ' - ' . $arr['recibo_id'] . ' - ' . $arr['importe'] . " // $saldo";
                    $sigo = false;
                }else{
                    $arr = [
                        'factura_id' => $factura['id'],
                        'recibo_id' => $recibo['id'],
                        'importe' => $factura['total_final']
                    ];
                    $mensaje = '0: ' . $arr['factura_id'] . ' - ' . $arr['recibo_id'] . ' - ' . $arr['importe'] . " // $saldo";
                    $sigo = false;
                }
                $this->insertoRelacion($arr);
                fwrite($fp,$mensaje . PHP_EOL);
            }while($sigo);
        }
        fclose($fp);

        $this->connection->commit();
    }
}