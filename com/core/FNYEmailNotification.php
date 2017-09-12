<?php

class FNYEmailNotification
{
	private $subject = '';
	private $from = '';
	private $to = '';
	private $templateName = '';
	private $templateVars = array();

	public function send()
	{
		$to = $this->to;
		$subject = $this->subject;

		$path = FNY_MAIL_TEMPLATES_PATH.$this->templateName;

		$VARS = $this->templateVars;
		ob_start();
		@include $path;
		$message = ob_get_clean();

		$headers  = 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		// Additional headers
		$headers .= 'To: ' . $this->to . "\r\n";
		$headers .= 'From: ' . $this->from . "\r\n";

		// Mail it
		wp_mail($to, $subject, $message, $headers);
	}

	public function setSubject($subject)
	{
		$this->subject = $subject;
	}

	public function setFrom($from)
	{
		$this->from = $from;
	}

	public function setTo($to)
	{
		$this->to = $to;
	}

	public function setTemplate($name)
	{
		$this->templateName = $name;
	}

	public function setTemplateVariables($vars)
	{
		$this->templateVars = $vars;
	}
}
