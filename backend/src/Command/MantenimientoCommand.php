<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\Empresa\EmpresaRepository;
use App\Repository\Empresa\Ventas\Comprobantes\FacturaRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:mantenimiento', description: 'Borra archivos temporales del directorio tempQR')]
class MantenimientoCommand extends Command
{
    // Utiliza la anotación para inyectar el parámetro del directorio del proyecto

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        private readonly Connection $connection, private readonly Security $security
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = new Filesystem();
        $io = new SymfonyStyle($input, $output);

        $tempDir = $this->projectDir . '/public/tempQR';

        try {
            $finder = new Finder();
            $finder->files()->in($tempDir);

            foreach ($finder as $file) {
                $filesystem->remove($file->getRealPath());
            }

            // $io->success('Archivos temporales borrados con éxito.');

            $this->procesarFacturacion($io);

        } catch (IOExceptionInterface $exception) {
            $io->error('Ocurrió un error al ejecutar Mantenimiento: ' . $exception->getMessage());
            return Command::FAILURE;
        } catch (Exception $e) {
            $io->error('Ocurrió un error al ejecutar Mantenimiento.: ' . $e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     * @throws Exception
     */
    private function procesarFacturacion(SymfonyStyle $io): void
    {
        $this->connection->executeStatement("TRUNCATE TABLE graficos_temp");
        $empresas = (new EmpresaRepository($this->connection, $this->security))->getEmpresasConAcceso();
        foreach ($empresas as $empresa) {
            $empresaId = (int) $empresa['id'];
            $facturacionMeses = (new FacturaRepository($this->connection, $this->security))->calculaFacturacionUltimos6Meses($empresaId);

            $this->connection->beginTransaction();
            $this->connection->insert('graficos_temp', [
                'empresa_id' => $empresaId,
                'mes_1' => $facturacionMeses[0]['total'] ?? 0,
                'mes_2' => $facturacionMeses[1]['total'] ?? 0,
                'mes_3' => $facturacionMeses[2]['total'] ?? 0,
                'mes_4' => $facturacionMeses[3]['total'] ?? 0,
                'mes_5' => $facturacionMeses[4]['total'] ?? 0,
                'mes_6' => $facturacionMeses[5]['total'] ?? 0,
            ]);
            $this->connection->commit();
        }
        $io->success('Facturacion de 6 meses procesada');
    }
}
