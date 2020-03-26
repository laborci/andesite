<?php namespace Andesite\Mission\Web\Pipeline;

abstract class Responder extends Segment {
	abstract protected function respond();
}