<?php

	abstract class shopPkgPaymentInterfaceAddonBaseConnector {
		
		protected $test_mode;
				
		abstract public function loadSettings();
		
		abstract public function getPaymentUrl($order, $description=null);
		
		abstract public function parseSuccessParams();
		
		
	}