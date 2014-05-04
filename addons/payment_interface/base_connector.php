<?php

	abstract class shopPkgPaymentInterfaceAddonBaseConnector {
		
		protected $test_mode;

		abstract public function getName();
		
		abstract public function loadSettings();
		
		abstract public function getPaymentUrl($order, $description=null);
	
		abstract public function parseSuccessParams();
		
		abstract public function parseFailParams();
		
		abstract public function parseResultParams();
				
		protected function getLogDirectory($order_id) {
			$base_dir = Application::getVarDirectory() . '/logs/payment';
			$order_id_six_digit = str_pad($order_id, 6, '0', STR_PAD_LEFT);
			$directory = $base_dir . '/' . substr($order_id_six_digit, 0, 2) . '/' . substr($order_id_six_digit, 2, 2) . '/' . $order_id_six_digit;
			return $directory;   
		}
		
		
		protected function getLogPath($order_id) {
			$dir = $this->getLogDirectory($order_id);
			$dir_absolute = Application::getSitePath() . $dir;
			if (!is_dir($dir_absolute)) {
				if (!@mkdir($dir_absolute, 0777, true)) {
					die("Can't create dir $dir_absolute");
				}
			}
			$file_path = $dir_absolute . '/' . $order_id . '.log';
			return $file_path;
		}
		
		public function writeLog($order_id, $message) {
			$file_path = $this->getLogPath($order_id);
			$f = @fopen($file_path, 'a');
			if (!$f) {
				die("Can't write to $file_path");
			}
			
			$name = $this->getName();
			if ($this->test_mode) $name .= " (тестовый режим)";
			
			fwrite($f, "************************************************************************\n");
			fwrite($f, ' ' . $name . ' - ' .date("Y-m-d H:i:s") . "\n");
			fwrite($f, "************************************************************************\n");
			fwrite($f, $message . "\n\n\n");
			
			
			fclose($f);
		}
		
		
		public function readLog($order_id) {
			$file_path = $this->getLogPath($order_id);
			if (!is_file($file_path)) return '';
			return file_get_contents($file_path);
		}
		
	}
	
	