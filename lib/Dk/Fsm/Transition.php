<?php

namespace Dk\Fsm;

/**
 * Abstract FSM Transition
 *
 * Contains all the logic for taking a FSM from one state to another
 */
abstract class Transition
{
	/**
	 * Perform the transition
	 *
	 * @param \Dk\Fsm $fsm The FSM object
	 * @param array Array of parameters
	 * @returns string The new state name
	 */
	public function transition( \Dk\Fsm $fsm, array $params )
	{
		$method = "transitionFrom{$fsm->getState()}";
		
		if ( !method_exists( $this, $method ) )
		{
			throw new \Exception( "Transition handler not found! ({$method})" );
		}
		
		// Execute transition handler
		return $this->$method( $fsm, $params );
	}
}
