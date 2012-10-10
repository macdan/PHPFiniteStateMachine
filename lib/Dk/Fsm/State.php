<?php

namespace Dk\Fsm;

/**
 * Abstract FSM State
 *
 * Contains all the logic for when a state has been adopted
 */
abstract class State
{
	/**
	 * Perform tasks required when entering this state.
	 * This method is fired after the transition has 
	 * performed it's duties.
	 */
	abstract public function transition( \Dk\Fsm $fsm, array $params );
}
