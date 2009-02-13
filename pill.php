<?php
	
	/*
				 
	    a PHP script that computes cyclomatic complexity of PHP source code
	    Copyright (C) 2009 Charles Rowe
	    Version 0.1a

		This application is inspired by Lint, developed by Pasquale Ceres
		http://ars.altervista.org/lint_php/lint_php.php
		
		and also Saikuro developed by Zev Blut
		http://saikuro.rubyforge.org/
		
		pill.php is part of the Pill program

		This program is free software: you can redistribute it and/or modify
	    it under the terms of the GNU General Public License as published by
	    the Free Software Foundation, either version 3 of the License, or
	    (at your option) any later version.

	    This program is distributed in the hope that it will be useful,
	    but WITHOUT ANY WARRANTY; without even the implied warranty of
	    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	    GNU General Public License for more details.

	    You should have received a copy of the GNU General Public License
	    along with this program.  If not, see <http://www.gnu.org/licenses/>.

	*/
	

	require('pill_lib.php');
	
	if(array_key_exists('argc',$_SERVER)){
		$options = process_command_line($argv); //auto sets all parameters based on
		$final_results = score_files($options['FILE_PATH'],$options['CC_TYPE'],$options['RECURSIVE'],$options['ASP']);
		
		$error = ($options['DO_ERROR'] ? $options['ERROR_LEVEL'] : "");
		$warn = ($options['DO_WARN'] ? $options['WARN_LEVEL'] : "");
		
		print_score_output($final_results,$error,$warn,$options['DO_ALL']);
	}//end if array_key_exists argc
?>
