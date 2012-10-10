<?php

namespace Dk\Fsm;

/**
 * A basic FSM implementation
 *
 * Your FSM classes don't have to extend this. You
 * are free to implement the \Dk\Fsm interface yourself,
 * this is just for convenience.
 */
abstract class Basic implements \Dk\Fsm
{
	/**
	 * @var string The initial and currently held state
	 */
	protected $_state = '';
	
	/**
	 * Get the current state
	 *
	 * @returns string The name of the current state
	 */
	public function getState()
	{
		return $this->_state;
	}
	
	/**
	 * Get available transitions
	 *
	 * @returns array List of possible transitions that can be made
	 */
	public function getTransitions()
	{
		return $this->_transitions[ $this->_state ];
	}
	
	/**
	 * Determine whether or not a transition can be made
	 *
	 * @param string $transition The transition to test
	 * @returns boolean Whether or not the transition can be made
	 */
	public function canTransition( $transition )
	{
		return in_array( $transition, $this->getTransitions() );
	}
	
	/**
	 * Perform a FSM transition
	 *
	 * @param string $transition The name of the transition to make
	 * @param array $params An array of parameters to pass to the transition and state instances
	 */
	public function transition( $transition, array $params )
	{
		try
		{
			// Trigger the transition on this object
			$new_state = $this->_transitionInstance( $transition )
				->transition( $this, $params );
			
			// Execute any code associated with the state
			$this->_stateInstance( $new_state )
				->transition( $this, $params );
			
			// Update this object's state
			$this->_state = $new_state;
			
			// Return the new state name
			return $this->_state;
		}
		catch ( \Exception $e )
		{
			throw new \Exception( "Invalid transition", null, $e );
		}
	}
	
	/**
	 * Get a transition instance
	 *
	 * @param string $transition The name of the transition to get an instance of
	 * @return \Dk\Fsm\Transition The transition instance
	 */
	abstract protected function _transitionInstance( $transition );
	
	/**
	 * Get a state instance
	 *
	 * @param string $state The name of the state to get an instance of
	 * @return \Dk\Fsm\State The state instance
	 */
	abstract protected function _stateInstance( $state );
}
