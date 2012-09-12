<?php

require_once( "config_templates.class.php" );

class Templates extends Config_Templates
{
	private $customvars = array();
	
	public function __construct()
	{
		/* If the templates directory is not present/readable or other configuration errors exist, terminate with an error.  --Kris */
		parent::__construct();
		
		if ( !empty( $this->errors ) )
		{
			print "<b>Unable to proceed due to error(s):</b><br /><br />\r\n\r\n";
			
			foreach ( $this->errors as $error )
			{
				print "$error<br />\r\n";
			}
			
			die( "<br />Script execution terminated." );
		}
	}
	
	/* Formulate the template filename.  --Kris */
	function filename( $template )
	{
		return $this->paths["templates"]["path"] . $template . ".template.html";
	}
	
	/* Make sure the template exists.  --Kris */
	function exists( $template )
	{
		/* Set aside "blank" as special keyword that does not require an actual template file.  --Kris */
		if ( strcmp( $template, "blank" ) == 0 )
		{
			return TRUE;
		}
		
		$templatefile = $this->filename( $template );
		
		return ( file_exists( $templatefile ) && is_file( $templatefile ) && is_readable( $templatefile ) ? TRUE : FALSE );
	}
	
	/* Display the specified template (loads $templates_dir/$args[0].template.html).  --Kris */
	function display( $template, $returndata = FALSE )
	{
		/* Set aside "blank" as special keyword that does not require an actual template file.  --Kris */
		if ( strcmp( $template, "blank" ) == 0 )
		{
			return array( "Success" => TRUE );
		}
		
		if ( !$this->exists( $template ) )
		{
			return array( "Success" => FALSE, "Reason" => "Template file '" . $this->filename( $template ) . "' does not exist or is not readable!" );
		}
		
		if ( !( $filedata = $this->load( $template ) ) )
		{
			return array( "Success" => FALSE, "Reason" => "Error loading template $template for read!" );
		}
		
		$this->customvars["TemplateName"] = $template;
		if ( !( $filedata = $this->parse( $filedata ) ) )
		{
			return array( "Success" => FALSE, "Reason" => "Error parsing template $template!" );
		}
		
		if ( $returndata == TRUE )
		{
			return array( "Success" => TRUE, "Reason" => $filedata );
		}
		else
		{
			print $filedata;
			
			return array( "Success" => TRUE );
		}
	}
	
	/* Load the template file.  --Kris */
	function load( $template )
	{
		return file_get_contents( $this->filename( $template ) );
	}
	
	/* Parse the template file.  See template_syntax.txt for specifications.  --Kris */
	function parse( $filedata )
	{
		if ( !is_array( $this->customvars ) )
		{
			return FALSE;
		}
		
		if ( empty( $this->customvars ) )
		{
			return $filedata;
		}
		
		require_once( "abstraction.class.php" );
		
		/* Variables.  --Kris */
		foreach ( $this->customvars as $varname => $value )
		{
			$filedata = str_replace( $this->opentag . $varname . $this->closetag, $value, $filedata );
		}
		
		/* Language translation aliases.  --Kris */
		foreach ( $this->languages->aliases as $alias => $dummy )
		{
			$value = $this->languages->translate( $alias );
			
			/* First handle translations without parameters.  --Kris */
			$filedata = str_replace( $this->languages_opentag . $alias . $this->languages_closetag, $value, $filedata );
			
			/* Now handle the parameters.  --Kris */
			$indexes = Abstraction::strpos_recursive( $filedata, $this->languages_opentag . $alias . $this->paramschar );
			
			foreach ( $indexes as $pos )
			{
				$start = strpos( $filedata, '&', $pos ) + 1;
				$end = strpos( $filedata, '}', $start ) - 1;
				
				$param = strtolower( substr( $filedata, $start, ($end - $start) + 1 ) );
				
				switch ( $param )
				{
					default:
						// TODO - Log Notice or Warning error for undefined parameter.  --Kris
						break;
					case "ucfirst":
						$value = ucfirst( $value );
						break;
					case "upper":
						$value = strtoupper( $value );
						break;
					case "lower":
						$value = strtolower( $value );
						break;
				}
				
				$filedata = str_replace( $this->languages_opentag . $alias . $this->paramschar . $param . $this->languages_closetag, $value, $filedata );
			}
		}
		
		/* Convert foreign characters into HTML entities.  --Kris */
		$filedata = Abstraction::convert_chars_to_entities( $filedata );
		
		return $filedata;
	}
	
	/* Add a custom variable to be parsed from the template data.  Duplicate entries will be ignored.  --Kris */
	function set( $varname, $value )
	{
		$varname = trim( $varname );
		
		if ( $varname == NULL )
		{
			return FALSE;
		}
		
		return $this->customvars[$varname] = $value;
	}
	
	/* Just in case you need to for whatever reason.  --Kris */
	function clear()
	{
		$this->customvars = array();
	}
}
