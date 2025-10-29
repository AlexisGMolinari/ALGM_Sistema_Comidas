<?php

namespace App\Repository\Shared;

use App\Repository\Administrador\AdministradorAccesosRepository;
use App\Repository\TablasSimplesAbstract;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;

class MigracionesRepository extends TablasSimplesAbstract
{
    public function __construct(Connection $connection, Security $security)
    {
        parent::__construct($connection, $security, '');
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getUsuarios(): array
    {
        $sql = "SELECT us.id, emp.id AS empresa_id, emp.controla_stock, emp.ocultar_graficos
                FROM usuarios us INNER JOIN empresa emp ON us.empresa_id = emp.id
                WHERE us.roles = 'ROLE_USER' AND us.activo = 1"; //" AND us.id IN (8, 52)";
        return $this->connection->fetchAllAssociative($sql);
    }


    /**
     * Busca todos los usuarios activos y le genera cada uno de los accesos; Analiza si tiene habilitado Stock y se lo
     * agrega. También si ve o no los gráficos iniciales.
     * @return void
     * @throws Exception
     */
    public function procesarAccesos():void {
        $usuarios = $this->getUsuarios();
        $accesosComunes = (new AdministradorAccesosRepository($this->connection,$this->security))->getAccesosComunes();

        $this->connection->executeQuery('truncate usuario_accesos');

        foreach ($usuarios as $usuario) {
            foreach ($accesosComunes as $accesoComun) {
                $this->connection->insert('usuario_accesos', [
                    'usuario_id' => $usuario['id'],
                    'acceso_id' => $accesoComun['id']
                ]);
            }
            if ((int)$usuario['controla_stock'] === 1) {
                $this->connection->insert('usuario_accesos', [
                    'usuario_id' => $usuario['id'],
                    'acceso_id' => 2
                ]);
            }
            if ((int)$usuario['ocultar_graficos'] === 0) {
                $this->connection->insert('usuario_accesos', [
                    'usuario_id' => $usuario['id'],
                    'acceso_id' => 6
                ]);
            }
        }
    }
}