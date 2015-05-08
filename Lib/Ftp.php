<?php
class Ftp
{
	private $conn_id;
	private $host;
	private $username;
	private $password;
	private $port;
	public  $timeout = 90;
	public  $passive = true;
	public  $ssl 	 = false;
	public  $system_type = '';
 
 
	/**
	 * 
	 * Enter description here ...
	 * @param string $host
	 * @param string $username
	 * @param string $password
	 * @param int $port
	 */
	public function __construct($host, $username, $password, $port = 21)
	{
		$this->host     = $host;
		$this->username = $username;
		$this->password = $password;
		$this->port     = $port;
	}
 
	/**
	 * 
	 * Enter description here ...
	 */
	public function connect()
	{
		if ($this->ssl == false)
		{
			$this->conn_id = ftp_connect($this->host, $this->port);
		}
		else
		{
			if (function_exists('ftp_ssl_connect'))
			{
				$this->conn_id = ftp_ssl_connect($this->host, $this->port);
			}
			else
			{
				return false;	
			}
		}
 
		$result = ftp_login($this->conn_id, $this->username, $this->password);
 
		if ($result == true)
		{
			ftp_set_option($this->conn_id, FTP_TIMEOUT_SEC, $this->timeout);
 
			if ($this->passive == true)
			{
				ftp_pasv($this->conn_id, true);
			}
			else
			{
				ftp_pasv($this->conn_id, false);
			}
 
			$this->system_type = ftp_systype($this->conn_id);
 
			return true;
		}
		else
		{
			return false;
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 */
	public function close()
	{
		if ($this->conn_id)
		{
			ftp_close($this->conn_id);
		}
	}
	
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $local_file_path
	 * @param unknown_type $remote_file_path
	 * @param unknown_type $mode
	 */
	public function put($local_file_path, $remote_file_path, $mode = FTP_ASCII)
	{
		if (ftp_put($this->conn_id, $remote_file_path, $local_file_path, $mode))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $local_file_path
	 * @param unknown_type $remote_file_path
	 * @param unknown_type $mode
	 */
	public function get($local_file_path, $remote_file_path, $mode = FTP_ASCII)
	{
		if (ftp_get($this->conn_id, $local_file_path, $remote_file_path, $mode))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $local_file_path
	 * @param unknown_type $remote_file_path
	 * @param unknown_type $mode
	 */
	public function fget($local_file_path, $remote_file_path, $mode = FTP_ASCII)
	{
		if (ftp_fget($this->conn_id, $local_file_path, $remote_file_path, $mode))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $permissions
	 * @param unknown_type $remote_filename
	 * @throws Exception
	 */
	public function chmod($permissions, $remote_filename)
	{
		if ($this->is_octal($permissions))
		{
			$result = ftp_chmod($this->conn_id, $permissions, $remote_filename);
			if ($result)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			throw new Exception('$permissions must be an octal number');
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $directory
	 */
	public function chdir($directory)
	{
		ftp_chdir($this->conn_id, $directory);
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $remote_file_path
	 */
	public function delete($remote_file_path)
	{
		if (ftp_delete($this->conn_id, $remote_file_path))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $directory
	 */
	public function make_dir($directory)
	{
		if (@ftp_mkdir($this->conn_id, $directory))
		{
			return true;
		}
		else 
		{
			return false;
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $mode
	 * @param unknown_type $path
	 * @return boolean
	 */
	function mkdir_recursive($mode, $path)
	{
	    $dir=split("/", $path);
	    $path="";
	    $ret = true;
	
	    for ($i=0;$i<count($dir);$i++)
	    {
	        $path.="/".$dir[$i];
	        if(!@ftp_chdir($this->conn_id,$path))
	        {
	            @ftp_chdir($this->conn_id,"/");
	            if(!@ftp_mkdir($this->conn_id,$path))
	            {
	                $ret=false;
	                break;
	            } else {
	                @ftp_chmod($this->conn_id, $mode, $path);
	            }
	         }
	     }
		return $ret;
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $old_name
	 * @param unknown_type $new_name
	 * @return boolean
	 */
	public function rename($old_name, $new_name)
	{
		if (ftp_rename($this->conn_id, $old_name, $new_name))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $directory
	 * @return boolean
	 */
	public function remove_dir($directory)
	{
		if (ftp_rmdir($this->conn_id, $directory))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $directory
	 * @return array
	 */
	public function dir_list($directory=".")
	{
		$contents = ftp_nlist($this->conn_id, $directory);
		return $contents;
	}

	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $directory
	 */
	public function rawlist($directory)
	{
		$contents = ftp_rawlist($this->conn_id, $directory);
		return $contents;
	}

	
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $file
	 */
	function filesize($file)
	{
		$res = ftp_size($this->conn_id, $file);
		if ($res != -1) {
		    return $res;
		} else {
		    return false;
		}
	}
	
	/**
	 * 
	 * Enter description here ...
	 */
	public function cdup()
	{
		ftp_cdup($this->conn_id);
	}
 
	/**
	 * 
	 * Enter description here ...
	 */
	public function current_dir()
	{
		return ftp_pwd($this->conn_id);
	}
 
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $i
	 */
	private function is_octal($i) 
	{
    	return decoct(octdec($i)) == $i;
	}
 
	/**
	 * 
	 * Enter description here ...
	 */
	public function __destruct()
	{
		$this->close();
	}
}
