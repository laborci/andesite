<?php namespace Andesite\Magic;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


interface AttachmentAdapterInterface{
	public function handle(string $action, Request $request, Response $response);
}