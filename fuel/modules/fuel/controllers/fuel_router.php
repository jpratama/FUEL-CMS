<?php
class Fuel_router extends Controller {

	public $segments = array();
	public $layout = '';
	public $location;
	
	function __construct()
	{
		parent::Controller();
		$this->config->load('MY_config');
		$this->config->load('fuel', TRUE);
		$this->load->library('cache');
		
	}
	
	function _remap($method)
	{
		$this->location = uri_path(TRUE);
		
		// if the rerouted file can't be found, look for the non-routed file'
		if (!file_exists(APPPATH.'views/'.$this->location.EXT))
		{
			$this->location = uri_path(FALSE);
		}
		
		if (empty($this->location)) $this->location = $this->config->item('default_home_view', 'fuel');
		$this->load->module_library(FUEL_FOLDER, 'fuel_page');

		$config = array();
		$config['location'] = $this->location;
		
		if ($this->config->item('fuel_mode', 'fuel') == 'views')
		{
			$config['render_mode'] = 'views';
			$this->fuel_page->initialize($config);
			$this->_remap_variables($method);
		}
		
		// using FUEL admin
		else
		{
			if ( $this->config->item('fuel_mode', 'fuel') != 'cms')
			{
				$config['render_mode'] = 'auto';
				$this->fuel_page->initialize($config);
				if (!$this->fuel_page->has_cms_data())
				{
					$this->_remap_variables();
					return;
				}
			}
			$this->_remap_cms();
		}
	}
	
	
	/*
	* ------------------------------------------------------
	* Checks database for page variables (FUEL CMS)
	* ------------------------------------------------------
	*/
	function _remap_cms()
	{
		$page_data = $this->fuel_page->properties();
		$this->load->helper('cookie');

		// set up cache info 
		$cache_group = $this->config->item('page_cache_group', 'fuel');
		$cache_id = fuel_cache_id();

		$output = '';
		
		
		// grab from cache if exists without checking expiration time... 
		// Also.. saving from FUEL will remove cached page so you will always preview the latest saved
		if ($this->config->item('use_page_cache', 'fuel') !== 'views' AND $this->cache->get($cache_id, $cache_group, FALSE) AND (is_true_val($this->fuel_page->is_cached)) AND !is_fuelified())
		{
			$output = $this->cache->get($cache_id, $cache_group);
		}
		else
		{
			if (!empty($this->fuel_page->layout))
			{
				
				// get output
				$output = $this->fuel_page->cms_render(TRUE, FALSE);

				// save to cache but you must not be logged in for it to save
				if ($this->config->item('use_page_cache', 'fuel') !== FALSE AND $this->config->item('use_page_cache', 'fuel') !== 'views' AND !is_fuelified())
				{
					$this->cache->save($cache_id, $output, $cache_group, $this->config->item('page_cache_ttl', 'fuel'));
				}
				
			}
			else
			{
				show_404();
			}
		}

		// fuelify
		$output = $this->fuel_page->fuelify($output);
		
		// render output
		$this->output->set_output($output);
		
		// call the post render layout hook
		$this->fuel_layouts->call_hook($page_data['layout'], 'post_render', $output);
		
	}
	
	/*
	* ------------------------------------------------------
	* If a controller method exists then call it. Otherwise, 
	* look for a corresponding view file if one exists. 
	* Eliminates the need to create controller methods with 
	* each page. It also will pull in the global and "section"
	* specific configuration variables if they exist
	* ------------------------------------------------------
	*/
	function _remap_variables(){
		
		// set up cache info 
		$cache_group = $this->config->item('page_cache_group', 'fuel');
		$cache_id = fuel_cache_id();

		if ($this->config->item('use_page_cache', 'fuel') !== 'cms' AND $this->cache->get($cache_id, $cache_group, FALSE))
		{
			$output = $this->cache->get($cache_id, $cache_group);
		}
		else
		{
			// get the output
			$output = $this->fuel_page->variables_render(TRUE, FALSE);

			// save to cache but you must not be logged in for it to save
			if ($this->config->item('use_page_cache', 'fuel') !== FALSE AND $this->config->item('use_page_cache', 'fuel') !== 'cms' AND !is_fuelified())
			{
				$this->cache->save($cache_id, $output, $cache_group, $this->config->item('page_cache_ttl', 'fuel'));
			}
		}
		
		// show 404 if output is explicitly set to FALSE
		if ($output === FALSE)
		{
			show_404();
		}
		
		// fuelify output
		$output = $this->fuel_page->fuelify($output);
		
		// render output
		$this->output->set_output($output);
		
	}

}