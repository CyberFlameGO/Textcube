<?php
/// Copyright (c) 2004-2006, Tatter & Company / Tatter & Friends.
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/doc/LICENSE, /doc/COPYRIGHT)
class RefererStatistics {
	function RefererStatistics() {
		$this->reset();
	}

	function reset() {
		$this->error =
		$this->host =
		$this->count =
			null;
	}
	
	function open($filter = '', $fields = '*', $sort = 'count DESC') {
		global $database, $owner;
		if (is_numeric($filter))
			$filter = 'AND id = ' . $filter;
		else if (!empty($filter))
			$filter = 'AND ' . $filter;
		if (!empty($sort))
			$sort = 'ORDER BY ' . $sort;
		$this->close();
		$this->_result = mysql_query("SELECT $fields FROM {$database['prefix']}RefererStatistics WHERE owner = $owner $filter $sort");
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
				if ($name == 'owner')
					continue;
				$this->$name = $value;
			}
			return true;
		}
		return false;
	}
	
	function add() {
		if (!isset($this->count))
			$this->count = 1;
		
		if (!$query = $this->_buildQuery())
			return false;

		if ($query->doesExist()) {
			$query->setAttribute('count', "count + {$this->count}");
			if (!$query->update())
				return $this->_error('update');
		} else if (!$query->insert()) {
			return $this->_error('insert');
		}
		return true;
	}
	
	function update() {
		if (!isset($this->count))
			$this->count = 1;
		
		if (!$query = $this->_buildQuery())
			return false;

		if ($query->doesExist()) {
			if (!$query->update())
				return $this->_error('update');
		} else if (!$query->insert()) {
			return $this->_error('insert');
		}
		return true;
	}
	
	function getCount() {
		return (isset($this->_count) ? $this->_count : 0);
	}
	
	/*@static@*/
	function compile($host) {
		$instance = new RefererStatistics();
		$instance->host = $host;
		$instance->count = 1;
		return $instance->update();
	}
	
	function _buildQuery() {
		global $database, $owner;
		$this->host = mysql_lessen(trim($this->host), 64);
		if (empty($this->host))
			return $this->_error('host');
		$query = new TableQuery($database['prefix'] . 'RefererStatistics');
		$query->setQualifier('owner', $owner);
		$query->setQualifier('host', $this->host, true);
		if (isset($this->count)) {
			if (!Validator::number($this->count, 1))
				return $this->_error('count');
			$query->setAttribute('count', $this->count);
		}
		return $query;
	}

	function _error($error) {
		$this->error = $error;
		return false;
	}
}
?>