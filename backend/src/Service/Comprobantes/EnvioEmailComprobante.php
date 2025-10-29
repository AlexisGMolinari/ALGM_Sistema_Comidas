<?php

namespace App\Service\Comprobantes;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class EnvioEmailComprobante
{

	protected string $nombreEmpresa = '';
	protected string $emailEmpresa = '';
	protected string $emailCliente = '';
	protected string $pdfComprobante = '';
	protected string $tipoComprobante = '';


	public function __construct(private readonly MailerInterface $mailer)
	{
	}

	/**
	 * Envía comprobantes adjuntos
	 *
	 * @return void
	 * @throws TransportExceptionInterface
	 */
	public function enviar():void
	{
		$subject = $this->nombreEmpresa . ' -  ' . $this->tipoComprobante . ' (adjunto)';

		$email = (new Email())
			->from(new Address('avisos@facturasimple.com.ar', $this->nombreEmpresa))
			->to($this->emailCliente)
			//->bcc('bcc@example.com')
			->replyTo(new Address('avisos@facturasimple.com.ar', $this->nombreEmpresa))
			//->priority(Email::PRIORITY_HIGH)
			->subject($subject)
			->text($subject)
			->html($this->armoEmailHTML());

		$email->addPart(new DataPart($this->pdfComprobante, $this->tipoComprobante . '.pdf', 'application/pdf'));

		$this->mailer->send($email);
	}

	/**
	 * Genera el HTML del Email
	 * @return string
	 */
	private function armoEmailHTML(): string
	{
		return <<<EOD
<!doctype html>
<html lang="es">
	<head>
		<title>Factura Simple - Comprobante Adjunto: $this->tipoComprobante</title>
	</head>
	<body>
		<h4>Mensaje de la empresa $this->nombreEmpresa</h4>
		<hr>
		<p> El presente email es para comunicarle que se le está adjuntando un <strong>$this->tipoComprobante</strong></p>
		<hr>
	</body>
</html>
EOD;
	}


	/**
	 * @param string $nombreEmpresa
	 * @return EnvioEmailComprobante
	 */
	public function setNombreEmpresa(string $nombreEmpresa): EnvioEmailComprobante
	{
		$this->nombreEmpresa = $nombreEmpresa;
		return $this;
	}

	public function setPdfComprobante(string $pdfComprobante): EnvioEmailComprobante
	{
		$this->pdfComprobante = $pdfComprobante;
		return $this;
	}

	/**
	 * @param string $emailEmpresa
	 * @return EnvioEmailComprobante
	 */
	public function setEmailEmpresa(string $emailEmpresa): EnvioEmailComprobante
	{
		$this->emailEmpresa = $emailEmpresa;
		return $this;
	}

	/**
	 * @param string $emailCliente
	 * @return EnvioEmailComprobante
	 */
	public function setEmailCliente(string $emailCliente): EnvioEmailComprobante
	{
		$this->emailCliente = $emailCliente;
		return $this;
	}

	/**
	 * @param string $tipoComprobante
	 * @return EnvioEmailComprobante
	 */
	public function setTipoComprobante(string $tipoComprobante): EnvioEmailComprobante
	{
		$this->tipoComprobante = $tipoComprobante;
		return $this;
	}


}
