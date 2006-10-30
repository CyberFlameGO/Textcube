<?php
class PluginSetting {
	function PluginSetting() {
		$this->reset();
	}

	function reset() {
		$this->error =
		$this->name =
		$this->setting =
			null;
	}
	
	function open($name = '', $fields = '*', $sort = 'name') {
		global $database, $owner;
		if (!empty($name))
			$name = 'AND name = \'' . $name . '\'';
		if (!empty($sort))
			$sort = 'ORDER BY ' . $sort;
		$this->close();
		$this->_result = mysql_query("SELECT $fields FROM {$database['prefix']}Plugins WHERE owner = $owner $name $sort");
		if ($this->_result)
			$this->_count = mysql_num_rows($this->_result);
		return $this->shift();
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
				switch ($name) {
					case 'settings':
						$name = 'setting';
						break;
				}
				$this->$name = $value;
			}
			return true;
		}
		return false;
	}

	function add() {
		if (!$query = $this->_buildQuery())
			return false;
		return $query->insert();
	}
	
	function update() {
		if (!$query = $this->_buildQuery())
			return false;
		if (!$query->getAttributeCount())
			return $this->_error('nothing');
		return $query->update();
	}
	
	function getCount() {
		return (isset($this->_count) ? $this->_count : 0);
	}

	function _buildQuery() {
		if (!Validator::directory($this->name))
			return $this->_error('name');
		
		global $database, $owner;
		$query = new TableQuery($database['prefix'] . 'Plugins');
		$query->setQualifier('owner', $owner);
		$query->setQualifier('name', mysql_tt_escape_string($this->name), true);
		if (isset($this->setting))
			$query->setAttribute('settings', mysql_tt_escape_string($this->setting), true);
		return $query;
	}

	function _error($error) {
		$this->error = $error;
		return false;
	}
}
?>