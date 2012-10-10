#!/usr/bin/php
<?php

namespace Includes
{
	require_once '../lib/Dk/Fsm.php';
	require_once '../lib/Dk/Fsm/Transition.php';
	require_once '../lib/Dk/Fsm/State.php';
	require_once '../lib/Dk/Fsm/Basic.php';
}

/********************************************************************************
 * DOMAIN
 ********************************************************************************/

namespace Domain
{
	class User extends \Dk\Fsm\Basic
	{
		/**
		 * The current (and initial) FSM state.
		 */
		protected $_state = 'Guest';
		
		/**
		 * Mapping between states and available transitions
		 */
		protected $_transitions = array(
			'Guest' => array( 
				'Register' 
			),
			'Free' => array( 
				'Unregister', 
				'PaymentReceived', 
				'AbuseReported' 
			),
			'Premium' => array( 
				'PaymentReceived', 
				'PaymentLapsed', 
				'AbuseReported' 
			),
			'Locked' => array(
				'PaymentReceived',
				'AbuseResolved'
			)
		);
		
		/**
		 * Get a transition instance
		 *
		 * @param string $transition The name of the transition to get an instance of
		 * @return \Dk\Fsm\Transition The transition instance
		 */
		protected function _transitionInstance( $transition )
		{
			$class = "\\Domain\\User\\Transitions\\{$transition}";
			
			if ( !class_exists( $class ) )
			{
				throw new \Exception( "Invalid transition class: {$class}" );
			}
			
			return new $class();
		}
		
		/**
		 * Get a state instance
		 *
		 * @param string $state The name of the state to get an instance of
		 * @return \Dk\Fsm\State The state instance
		 */
		protected function _stateInstance( $state )
		{
			$class = "\\Domain\\User\\State\\{$state}";
			
			if ( !class_exists( $class ) )
			{
				throw new \Exception( "Invalid state class: {$class}" );
			}
			
			return new $class();
		}
		
		public function register( $email, $password )
		{
			$this->transition( 'Register', array(
				'email' => $email,
				'password' => md5( $password )
			) );
		}
		
		public function paymentReceived( $amount )
		{
			$this->transition( 'PaymentReceived', array(
				'amount' => $amount
			) );
		}
		
		public function abuseReported( $reason )
		{
			$this->transition( 'AbuseReported', array(
				'reason' => $reason
			) );
		}
		
		public function abuseResolved( $reason )
		{
			$this->transition( 'AbuseResolved', array(
				'reason' => $reason
			) );
		}
	}
}
 
namespace Domain\User\Transitions
{
	class Register extends \Dk\Fsm\Transition
	{
		public function transitionFromGuest( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Storing user record ({$params['email']}:{$params['password']})\n";
			return 'Free'; // New state
		}
	}
	
	class Unregister extends \Dk\Fsm\Transition
	{
		public function transitionFromPremium( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Deleting premium user record #{$params['id']}\n";
			return 'Guest';
		}
		
		public function transitionFromFree( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Deleting premium user record #{$params['id']}\n";
			return 'Guest';
		}
	}
	
	class PaymentReceived extends \Dk\Fsm\Transition
	{
		public function transitionFromFree( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Received first payment - Upgrading membership\n";
			$fsm->Membership->Premium = true;
			$fsm->Membership->Expires = date( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
			return 'Premium';
		}
		
		public function transitionFromPremium( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Received renewal payment - Extending membership\n";
			$fsm->Membership->Expires = date( 'Y-m-d H:i:s', strtotime( $fsm->Membership->Expires . ' +1 year' ) );
			return 'Premium';
		}
	}
	
	class PaymentLapsed extends \Dk\Fsm\Transition
	{
		public function transitionFromPremium( \Dk\Fsm $fsm, array $params )
		{
			$fsm->Membership->Payments->addLapsed();
			return 'Free';
		}
	}
	
	class AbuseReported extends \Dk\Fsm\Transition
	{
		public function transitionFromFree( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Abuse has been reported! Reason: ", $params['reason'], "\n";
			return 'Locked';
		}
		
		public function transitionFromPremium( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Abuse has been reported! Reason: ", $params['reason'], "\n";
			return 'Locked';
		}
	}
	
	class AbuseResolved extends \Dk\Fsm\Transition
	{
		public function transitionFromLocked( \Dk\Fsm $fsm, array $params )
		{
			echo "-> Abuse has been resolved! Reason: ", $params['reason'], ". ";
			
			if ( $fsm->Membership->Premium )
			{
				echo "Restoring premium membership\n";
				return 'Premium';
			}
			
			echo "Restoring free membership\n";
			return 'Free';
		}
	}
}

namespace Domain\User\State
{
	class Guest extends \Dk\Fsm\State
	{
		public function transition( \Dk\Fsm $fsm, array $params )
		{
			// Nothing to do
		}
	}
	
	class Free extends \Dk\Fsm\State
	{
		public function transition( \Dk\Fsm $fsm, array $params )
		{
			echo "() Sending welcome email to {$params['email']}\n";
		}
	}
	
	class Premium extends \Dk\Fsm\State
	{
		public function transition( \Dk\Fsm $fsm, array $params )
		{
			// Nothing to do
		}
	}
	
	class Locked extends \Dk\Fsm\State
	{
		public function transition( \Dk\Fsm $fsm, array $params )
		{
			$fsm->Locked = true;
			echo "() Account has been LOCKED.\n";
		}
	}
}

/********************************************************************************
 * Demo
 ********************************************************************************/
namespace Demo
{
	$u = new \Domain\User;
	
	echo "State is: ", $u->getState(), "\n"; // State is: Guest
	
	$u->register( 'user@examplemail.com', 'foobar' );
	
	echo "State is: ", $u->getState(), "\n"; // State is: Free
	
	$u->paymentReceived( 15.00 );
	
	echo "State is: ", $u->getState(), "\n"; // State is: Premium
	
	$u->abuseReported( "Spamming" );
	
	echo "State is: ", $u->getState(), "\n"; // State is: Locked
	
	$u->abuseResolved( "Not spam, legitimate information broadcast" );
	
	echo "State is: ", $u->getState(), "\n"; // State is: Premium
	
	$u->paymentReceived( 15.00 );
	
	echo "State is: ", $u->getState(), "\n"; // State is: Premium
}
