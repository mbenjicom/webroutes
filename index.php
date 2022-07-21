<?php

$instance = new Proxy();
Class Proxy{ // Compiled Templates | CSS Variables | Live Patching | Live Branching | A/B Testing
	private $url = 'https://fieldroutes.dev/';
	//private $url = 'https://localhost/';
	private $source_dir = '';
	private $branch = 'HEAD';//HEAD
	private $config = [
		'start_uri'=>'index',
		'start_page'=>'index',
		'start_data'=>[]
	];
	
	//globally available template variables e.g. [[!Logo]]
	private $globals = [
		"Company_name"  => "FieldRoutes",
		"Logo"          => 'FieldRoutes'
	];
	
	private $debug = false;
	// URL handler - Passes parts to function arguments - domain.com/function/arg1/arg2?query=string
	function __construct(){
		//Determine which branch we are patching
		$this->branch = @isset($_COOKIE['Branch'])? $_COOKIE['Branch'] : $this->branch;
		$this->branch = preg_replace("/[^A-Za-z0-9 _\-]/", '', $this->branch );
		
		//Get arguments from the path
		$request_parts=explode('?',$_SERVER['REQUEST_URI']);
		$request_uri=$request_parts[0];
		$query_string=@isset($request_parts[1])?$request_parts[1]:'';
		$args=explode('/', str_replace('index.php/','',$request_uri) );
		if($args[0]=='')
		  array_shift($args); // we don't need this bit if it's empty
		$function = preg_replace("/[^A-Za-z0-9 _\-]/", '', array_shift($args) );
		if($function==''){ //no uri, retrieve an app shell
			$this->index();
		}else if( method_exists( $this, $function) ){ //If it's a public method below, invoke it
			$reflection = new ReflectionMethod($this, $function);
			if( $reflection->isPublic() )
				call_user_func_array( array($this, $function), $args );
		} else { // Otherwise we'll assume it's hash
			$this->config['start_page']=$function;
			$this->config['start_data']=[];
			$this->config['start_uri']=$function;
			$this->index(); //exit('Could not find '.$function);
		}
	}
	//Returns the application shell (shell.php) associated with the specified branch or it's closest parent. $build set in local context (use in included file)
	public function index(){
		include 'login.php';
	}
	//Invoke controller function if it exists in the project folder and is public
	public function api( $controller='', $method=''){
		$controller = preg_replace( '/[^a-zA-Z0-9_]/', '', $controller);
		$file = $this->source_dir.'controller/'.$controller.'.php';

		if(!file_exists($file)){
			die('No such controller: '.$file);
		}
		include $file;
		$controller = ucfirst($controller);
		$instance = new $controller();

		if(!method_exists( $instance, $method) ) {
			die('No such method.');
		}
		$reflection = new ReflectionMethod($instance, $method);
		if( !$reflection->isPublic() ){
			die('Access Denied.');
		}
		$args = func_get_args();
		$args=array_slice($args,2);
		array_unshift($args, $_POST);
		try{
			call_user_func_array( array($instance, $method), $args);
		} catch (Exception $e){
			die( $e->getMessage() );
		}
		
	}
}
