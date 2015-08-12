<?php

	class shopPkgCashPaymentConnector extends shopPkgBasePaymentConnector {
		
		public function getName() {			
			return 'Наличные';
		}
		
		public function loadSettings() {
			
		}
		
		
		public function getPaymentUrl($order, $description=null) {			
			return null;			
		}
		
		
		public function parseSuccessParams() {
			return null;			
		}
		
		
		public function parseFailParams() {
			return null;
		}
		
		
		public function parseResultParams() {
			return null;
		}
		
		
		
	}
	
	
	
	
	