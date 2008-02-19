<?php
/// Copyright (c) 2004-2008, Needlworks / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
class RefererLog {
	function RefererLog() {
		$this->reset();
	}

	function reset() {
		$this->error =
		$this->host =
		$this->url =
		$this->referred =
			null;
	}
	
	function open($filter = '', $fields = '*', $sort = 'referred DESC') {
		global $database;
		if (is_numeric($filter))
			$filter = 'AND id = ' . $filter;
		else if (!empty($filter))
			$filter = 'AND ' . $filter;
		if (!empty($sort))
			$sort = 'ORDER BY ' . $sort;
		$this->close();
		$this->_result = mysql_query("SELECT $fields FROM {$database['prefix']}RefererLogs WHERE blogid = ".getBlogId()." $filter $sort");
		if ($this->_result) {
			if ($this->_count = mysql_num_rows($this->_result))
				return $this->shift();
			else
				mysql_free_result($this->_result);
		}
		unset($this->_result);
		return false;
	}
	
	function close() {
		if (isset($this->_result)) {
			mysql_free_result($this->_result);
			unset($this->_result);
		}
		$this->_count = 0;
		$this->reset();
	}
	
	function shift() {
		$this->reset();
		if ($this->_result && ($row = mysql_fetch_assoc($this->_result))) {
			foreach ($row as $name => $value) {
				if ($name == 'blogid')
					continue;
				$this->$name = $value;
			}
			return true;
		}
		return false;
	}
	
	function add($compile = true) {
		if (!isset($this->url))
			return $this->_error('url');
		$this->host = null;
		if (!$query = $this->_buildQuery())
			return false;
		if (!$query->hasAttribute('referred'))
			$query->setAttribute('referred', 'UNIX_TIMESTAMP()');

		if (!$query->insert())
			return $this->_error('insert');
		
		if ($compile) {
			RefererStatistics::compile($this->host);
		}
		return true;
	}
	
	function getCount() {
		return (isset($this->_count) ? $this->_count : 0);
	}

	function _buildQuery() {
		global $database;
		$query = new TableQuery($database['prefix'] . 'RefererLogs');
		$query->setQualifier('blogid', getBlogId());
		if (isset($this->host)) {
			$this->host = UTF8::lessenAsEncoding(trim($url['host']), 64);
			if (empty($this->host))
				return $this->_error('host');
			$query->setAttribute('host', $this->host, true);
		}
		if (isset($this->url)) {
			$this->url = UTF8::lessenAsEncoding(trim($this->url), 255);
			if (empty($this->url))
				return $this->_error('url');
			$url = parse_url($this->url);
			if (empty($url['host']))
				return $this->_error('url');
			$this->host = UTF8::lessenAsEncoding(trim($url['host']), 64);
			$query->setAttribute('host', $this->host, true);
			if (empty($url['scheme']))
				$this->url = 'http://' . $this->url;
			$query->setAttribute('url', $this->url, true);
		}
		if (isset($this->referred)) {
			if (!Validator::number($this->referred, 1))
				return $this->_error('referred');
			$query->setAttribute('referred', $this->referred);
		}
		return $query;
	}

	function _error($error) {
		$this->error = $error;
		return false;
	}
}
?>
