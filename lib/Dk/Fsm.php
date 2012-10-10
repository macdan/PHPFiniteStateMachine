<?php

namespace Dk;

/**
 * Finite State Machine
 * 
 * An interface to declare a model as a finite state machine
 */
interface Fsm
{
	/**
	 * Get the current state
	 */
	public function getState();
	
	/**
	 * Get the transitions that can be performed
	 *
	 * @returns array List of transitions that can be made
	 */
	public function getTransitions();
	
	/**
	 * Determine whether the machine can perform a transition
	 *
	 * @param string $transition The name of the transition
	 * @return boolean
	 */
	public function canTransition( $transition );
	
	/**
	 * Transition between two states
	 *
	 * @param string $transition The name of the transition
	 * @param array $params Array of parameters to pass to the transition
	 * @return \Dk\Fsm
	 */
	public function transition( $transition, array $params );
}
