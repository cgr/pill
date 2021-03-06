<?php

/*
			 
    a PHP script that computes cyclomatic complexity of PHP source code
    Copyright (C) 2009 Charles Rowe
    Version 0.1a

	This application is inspired by Lint, developed by Pasquale Ceres
	http://ars.altervista.org/lint_php/lint_php.php
	
	and also Saikuro developed by Zev Blut
	http://saikuro.rubyforge.org/
	
	pill_lib.php is part of the Pill program

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


$DEFAULT_WARN_LEVEL  = 10;
$DEFAULT_ERROR_LEVEL = 15;

$usage =<<<USAGESTRING


usage: ./$argv[0] [file or directory] -[rweoas]

r : recursive -- If a directory is specified, it will recursively check other directories for php files.

w : warn level -- Warning which marks a function with a warning to trigger its output. If no integer is specified,
the default level will be set to $DEFAULT_WARN_LEVEL

e : error level -- Error level which marks a function with an error to trigger its output. Ig no integer is specified,
the default level will be set to $DEFAULT_ERROR_LEVEL

a : show all -- show all complexity scores regardless of warn or error levels

asp : process the code for ASP style tagging '<% echo ' hi!'; %>'

cc : DEFAULT   -- Booleans are not counted, case statements are counted

cc2 : STRICT   -- booleans and case statements are counted

cc3 : MODIFIED -- booleans are counted, switch statements are counted, case statements ARE NOT counted

By default, if no options are specified, the flags -e,-s, and -cc will automatically be applied.

USAGESTRING;


function pill($text,$complexity_type){
	$results = Array('Global Code' => 1);
	$tokens = token_get_all($text);
	$in_function = "";
	$function_key = 'Global Code';
	$brace_count = 0;
	$total_function_count = 0;
	
	for($i=0; $i< count($tokens); ) {
		$token = $tokens[$i];
				
		if (is_string($token)) {
			$token = trim($token);
		}else{
			list ($token, $value) = $token;
		}

		if($token == T_FUNCTION){
			$total_function_count++; 
			if($tokens[$i+1][0] == T_WHITESPACE && $tokens[$i+2][0] == T_STRING){
				$in_function = 1;
				$function_key = "function ".$tokens[$i+2][1]; 
				$results[$function_key] = 1; //set the new function in results with a score of 1
				$i+=2;			
			}elseif($tokens[$i+1][0] == T_WHITESPACE && $tokens[$i+2] == '&' && $tokens[$i+3][0] == T_STRING){
				//handle functions with return references
				$in_function = 1;
				$function_key = "function &".$tokens[$i+3][1]; 
				$results[$function_key] = 1; //set the new function in results with a score of 1
				$i+=3;				
			}elseif($tokens[$i+1][0] == T_WHITESPACE && $tokens[$i+2] == '&' && $tokens[$i+3][0] == T_WHITESPACE && $tokens[$i+4][0] == T_STRING){
				//handle functions with return references
				$in_function = 1;
				$function_key = "function & ".$tokens[$i+4][1]; 
				$results[$function_key] = 1; //set the new function in results with a score of 1
				$i+=4;				
			}else{
				die("unknown function format on function #".$total_function_count."\n\n");
			}
		} //end if $token == T_FUNCTION
		
		if($token == '{'){
			$brace_count++;
		}elseif($token == '}'){
			$brace_count--;
			if($brace_count == 0){
				$in_function = 0;
				$function_key = 'Global Code';
			}//end if brace_count == 0
		}// end elseif '}'	
		
		$results[$function_key] += count_valid_tokens($token, $complexity_type);
		$i++;
	}//end foreach
	
	return $results;	
}//end function pill

function count_valid_tokens($token,$complexity_type){
	$complexity = 0;
		
	switch ($token) {
		case T_BOOLEAN_AND: if($complexity_type == 2){$complexity++;}break;
		case T_BOOLEAN_OR:  if($complexity_type == 2){$complexity++;}break;
		case T_LOGICAL_AND: if($complexity_type == 2){$complexity++;}break;
		case T_LOGICAL_OR:	if($complexity_type == 2){$complexity++;}break;		
		case T_CASE:		if($complexity_type <  3){$complexity++;}break;
		case T_SWITCH: 		if($complexity_type == 3){$complexity++;}break;
		case '?': //ternary operator
		case T_CATCH:
		case T_ELSEIF:
		case T_FOR:
		case T_FOREACH:
		case T_IF:
		case T_WHILE: //will cover do/while also
			$complexity++;
		break;
	}//end switch
	return $complexity;
}// end function count_valid_tokens

function traverse_directory($dir,$complexity_type,$do_recursion,$do_asp){
	$file_results = Array();
	foreach(scandir($dir) as $key => $file){
		if (preg_match('/\.(php|php3|php4|php5)$/i',$file) ){
			$file_scores = pill(harvest_file_contents("$dir/$file",$do_asp),$complexity_type);
			$file_results = array_merge($file_results,attach_file_to_scores("$dir/$file",$file_scores));
		}elseif($do_recursion && is_dir("$dir/$file") && !preg_match('/^\./', $file)){
			$file_results = array_merge($file_results,traverse_directory("$dir/$file",$complexity_type,$do_recursion,$do_asp));
		}
	}//end foreach $file
	return $file_results;	
}//end traverse directory

function process_command_line($command_line){
	global $usage,$DEFAULT_WARN_LEVEL, $DEFAULT_ERROR_LEVEL;
	
	$options = Array(	'RECURSIVE' => false, 
						'WARN_LEVEL' => $DEFAULT_WARN_LEVEL, 
						'ERROR_LEVEL' => $DEFAULT_ERROR_LEVEL,
						'DO_WARN' => false,
						'DO_ERROR' => false,
						'DO_ALL' => false,
						'FILE_PATH' => $command_line[1],
						'ASP' => false,
						'CC_TYPE' => 1
			   );
	
	if(count($command_line) < 2 ){
		die($usage);
	}elseif(count($command_line) == 2){
		$options['DO_ERROR'] = true;
		$options['SORT'] = true;
	}
	
	for($i=2;$i<count($command_line);){
		
		
		
		
		switch($command_line[$i]){
			case '-r':	$options['RECURSIVE'] = true; break;
			
			case '-w':	$options['DO_WARN'] = true;
						if(is_numeric($command_line[$i+1])){
							$i++;
							$options['WARN_LEVEL'] = $command_line[$i];
						}
						break;
			case '-e':	$options['DO_ERROR'] = true;						
						if(is_numeric($command_line[$i+1])){
							$i++;
							$options['ERROR_LEVEL'] = $command_line[$i];
						}
						break;
			case '-a':	$options['DO_ALL'] = true; break;
			case '-asp': $options['ASP'] = true; break;
			case '-cc1' :
			case '-cc'  : $options['CC_TYPE'] = 1;break;
			case '-cc2' : $options['CC_TYPE'] = 2;break;
			case '-cc3' : $options['CC_TYPE'] = 3;break;
			default: die($usage);
		}//end switch
		
		$i++;
	}//end for $i
	return $options;
}//end function process_command_line

function score_files($file_path,$complexity_type,$do_recursion,$do_asp){
	$results = Array();
	if(is_file($file_path)){
		$file_scores = pill(harvest_file_contents($file_path,$do_asp),$complexity_type);
		$results = array_merge($results,attach_file_to_scores($file_path,$file_scores));
	}elseif(is_dir($file_path)){
		$results = traverse_directory($file_path,$complexity_type,$do_recursion,$do_asp);
	}else{
		die("\ncould not find $file_path\n");
	}
	return $results;
}//end function score_files


function attach_file_to_scores($file,$file_scores){
	$results = Array();
	foreach($file_scores as $function_name => $score){
		array_push($results,Array('file'=>$file,'function'=>$function_name,'score'=>$score));
	}
	
	return $results;
}//end attach_file_to_functions


function cmp_scores($a,$b){
	//sort by score and then by function name
	if($b['score'] > $a['score']){
		return 1;
	}elseif($b['score'] < $a['score']){
		return -1;
	}else{
		return 	strcmp($a['function'],$b['function']);		
	}
	
}

function sort_scores(&$scores){
	return uasort($scores,"cmp_scores");	
}


function categorize(&$scores,$error,$warn,$all){
	$results = Array('error' => Array(), 'warn' => Array(), 'all' => Array());

	foreach($scores as $score_key => $score_value){
		if($error && $error <= $score_value['score']){
			array_push($results['error'],$score_value);
		}elseif($warn && $warn <= $score_value['score']){
			array_push($results['warn'],$score_value);			
		}else{
			array_push($results['all'],$score_value);			
		}
	}
	$scores = $results;
} //end categorize

function print_score_output($scores,$error,$warn,$all){
	sort_scores($scores);
	categorize($scores,$error,$warn,$all);
	echo "\n";

	if(count($scores['error']) > 0 && ($error || $all) ){
		echo "Errors:\n";
		print_block($scores['error']);
	}
	echo "\n";
	if(count($scores['warn']) > 0 && ($warn || $all) ){
		echo "Warnings:\n";
		print_block($scores['warn']);
	}
	echo "\n";
	if(count($scores['all']) > 0 && $all){
		echo "Good:\n";
		print_block($scores['all']);
	}
	
}//end print_score_output

function print_block($scores){
	foreach($scores as $score_key => $score_value){
		$file = $score_value['file'];
		$function_name = $score_value['function'];
		$score = $score_value['score'];
		echo sprintf("%5d - %s : %s\n",$score,$file,$function_name);
	}
}

function harvest_file_contents($file,$process_asp){
	$text = file_get_contents($file);
	if($process_asp){
		$text = preg_replace('/<%/','<?',$text);
		$text = preg_replace('/%>/','?>',$text);	
	}
	return $text;
}//end function harvest_file

?>