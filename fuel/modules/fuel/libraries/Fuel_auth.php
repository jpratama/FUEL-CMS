<?php if (!defined('BASEPATH')) exit('No direct script access allowed'); 

class Fuel_auth {
	
	protected $_CI = NULL;
	
	function __construct(){
		$this->_CI =& get_instance();
		$this->_CI->load->library('session');
	}
	
	function valid_user()
	{
		return ($this->_CI->session->userdata($this->get_session_namespace())) ? $this->_CI->session->userdata($this->get_session_namespace()) : NULL;
	}
	
	function set_valid_user($valid_user)
	{
		$this->_CI->load->helper('string');
		$this->_CI->session->set_userdata($this->get_session_namespace(), $valid_user);
	}
	
	function get_session_namespace()
	{
		$key = 'fuel_'.md5($this->_CI->config->item('site_name', 'fuel'));
		if (!$this->_CI->session->userdata($key)) $this->_CI->session->set_userdata($key, array());
		return $key;
	}
	
	function get_fuel_trigger_cookie_name()
	{
		return $this->get_session_namespace();
	}
	
	function set_valid_user_property($prop, $val)
	{
		$user_data = $this->_CI->session->userdata($this->get_session_namespace());
		if (isset($user_data[$prop]))
		{
			$user_data[$prop] = $val;
			$this->_CI->session->set_userdata($this->get_session_namespace(), $user_data);
		}
	}
	
	function login($user, $pwd)
	{
		$this->_CI->load->module_model(FUEL_FOLDER, 'users_model');
		
		$valid_user = $this->_CI->users_model->valid_user($user, $pwd);
		if (!empty($valid_user)) {
			//$valid_user = $this->_CI->users_model->user_info($valid_user['id']);
			$this->set_valid_user($valid_user);
			return TRUE;
		}
		return FALSE;
	}
	
	function user_data($key = NULL)
	{
		$valid_user = $this->valid_user();
		if (!empty($valid_user))
		{
			if (!empty($key) && isset($valid_user[$key]))
			{
				return $valid_user[$key];
			}
			return $valid_user;
		}
		return FALSE;
	}
	
	function is_logged_in()
	{
		$user = $this->valid_user();
		return (!empty($user) AND !empty($user['user_name']));
	}
	
	function has_permission($permission, $type = 'edit')
	{
		if ($this->is_super_admin()) return TRUE; // super admin's control anything

		// get the users permissions
		$user_perms = $this->get_permissions();

		if (!empty($user_perms))
		{
			if (is_array($permission))
			{
				foreach($permission as $key => $val)
				{
					if (is_int($key) && !empty($this->_CI->module))
					{
						$permission[$val] = $this->_CI->module.'_'.$val;
					}
				}
				if (!empty($permission[$type]))
				{
					$permission = $permission[$type];
				}
				else
				{
					$permission = NULL;
				}
			}
			return (!empty($permission) AND !empty($user_perms[$permission]));
		}
		return FALSE;
	}
	
	function accessible_module($module)
	{
		$this->_CI->load->module_config('fuel', 'fuel', TRUE);
		$allowed = (array) $this->_CI->config->item('modules_allowed', 'fuel');
		return in_array($module, $allowed);
	}
	
	function get_permissions()
	{
		$valid_user = $this->valid_user();
		if (empty($valid_user['id'])) return FALSE;
		
		// get the users permissions
		$CI =& get_instance();
		$CI->load->module_model(FUEL_FOLDER, 'user_to_permissions_model');
		$user_perms = $CI->user_to_permissions_model->get_permissions($valid_user['id']);
		if (!empty($user_perms))
		{
			return $user_perms;
		}
		return NULL;
	}
	
	function is_super_admin()
	{
		$valid_user = $this->valid_user();
		
		if (!empty($valid_user['super_admin'])) {
			return ($valid_user['super_admin'] == 'yes');
		}
		return NULL;
	}
	
	function module_has_action($action)
	{
		if (empty($this->_CI->item_actions)) return FALSE;
		return in_array($action, $this->_CI->item_actions);
	}
	
	function logout()
	{
		$this->_CI->session->unset_userdata($this->get_session_namespace());
	}
}
/* End of file Fuel_auth.php */
/* Location: ./modules/fuel/libraries/fuel/Fuel_auth.php */