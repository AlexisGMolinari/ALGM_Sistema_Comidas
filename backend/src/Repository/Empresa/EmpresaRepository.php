<?php

namespace App\Repository\Empresa;

use App\Repository\Contador\ContadorPuntoDeVentaRepository;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class EmpresaRepository extends TablasSimplesAbstract
{

    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, 'empresa');
		$this->empresaId = ($this->security->getUser())? $this->security->getUser()->getEmpresa(): 0; //utilizado para filtrar para los usuarios que no son contadores
    }


    /**
     * Busca los datos de la empresa del que está facturando (no por contador)
     * @throws Exception
     */
    public function getByIdInterno(): array|bool
    {
        $sql = "select * from empresa where id = ?";
        return $this->connection->fetchAssociative($sql, [$this->empresaId]);
    }

    /**
     * Función que devuelve si es responsable inscripta o no la empresa
     * Se usa para diferenciar la tasa de IVA en  facturación/presupuesto de los productos.
     * @throws Exception
     */
    public function getEsResponsableIncripto(): bool
    {
        $esRespon = false;
		$empresa = $this->getByIdInterno();
        if (intval($empresa['categoria_iva_id']) === 1) {
            $esRespon = true;
        }
        return $esRespon;
    }

	/**
	 * Trae todos los datos de empresa para la cabecera de impresión. Si se le pasa la empresa cambia la condición
	 * @param int $idEmpresa
	 * @return array|bool
	 * @throws Exception
	 */
    public function getByIdInternoCompleto(int $idEmpresa = 0): array|bool
    {
        $sql = "SELECT e.*, us.nombre, l.nombre as localidad, p.nombre as provincia, ci.nombre as categoriaIVA
            FROM empresa e
            inner join localidad l on e.localidad_id = l.id
            inner join usuarios us on e.id = us.empresa_id
            inner join provincia p on l.provincia_afip = p.codigo_afip
            inner join categorias_iva ci on e.categoria_iva_id = ci.id
            where e.id = ?";
		if ($idEmpresa === 0){
			$params = [$this->empresaId];
		}else{
			$params = [$idEmpresa];
		}
        return $this->connection->fetchAssociative($sql, $params);
    }

	/**
	 * Trae todos los puntos de ventas de la empresa logueada
	 * @return array
	 * @throws Exception
	 */
	public function getPuntosVentas(): array
	{
		return (new ContadorPuntoDeVentaRepository($this->connection, $this->security))
			->getPuntosDeLaEmpresa($this->empresaId);
	}

    /**
     * función que trae todos los P.V. electrónicos para generar Citi Vtas
     * @throws Exception
     */
    public function getPuntosVtasElectronicos(): array
    {
        $sql = "SELECT p.numero
                FROM punto_venta p
                where p.empresa_id = ?
                  and p.tienefe = 1
                  and p.activo = 1";
        return $this->connection->fetchAllAssociative($sql, [$this->empresaId]);
    }

    /**
     * Trae las empresas que No tengan en 1 el ocultar grafico; sea ROLE_USER y tenga habilitado "Graficos iniciales"
     * @return array
     * @throws Exception
     */
    public function getEmpresasConAcceso(): array
    {
        $sql = "SELECT e.id, e.nombre_fantasia FROM " . $this->nombreTabla . " e
                INNER JOIN usuarios u ON u.empresa_id = e.id
                INNER JOIN usuario_accesos ua ON ua.usuario_id = u.id
                WHERE e.ocultar_graficos != 1
                    AND u.roles LIKE '%ROLE_USER%' AND ua.acceso_id = 6
                    GROUP BY e.id";
        return $this->connection->fetchAllAssociative($sql);
    }
}







