<?php namespace Andesite\Mission\Web\Routing;

interface RoutingEvent{
	const BEFORE = __CLASS__ . 'BEFORE';
	const FINISHED = __CLASS__ . 'FINISHED';
	const NOTFOUND = __CLASS__ . 'NOTFOUND';
}