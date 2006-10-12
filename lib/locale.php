<?php
Timezone::set(isset($blog['timezone']) ? $blog['timezone'] : $service['timezone']);
mysql_query('SET time_zone = \'' . Timezone::getCanonical() . '\'');
Locale::setDirectory(ROOT . '/language');
Locale::set(isset($blog['language']) ? $blog['language'] : $service['language']);

if (!isset($blog['blogLanguage'])) {
	$blog['blogLanguage'] = $__locale['locale'];
}

if (is_file($__locale['directory'] . '/' . $blog['blogLanguage'] . ".php")) {
	$__outText = getOutLanguage($__locale['directory'] . '/' . $blog['blogLanguage'] . ".php");
}

function getOutLanguage($languageFile) {
	include($languageFile);
	return $__text;
}

// �ܺ� ��Ų�� ��� ��ȯ �Լ�.
// _t()�� ������ ������ ��������, _text()�� skin�� ����(��Ÿ����)�� ������. 1.1 ������ �߰�������.
function _text($t) {
	global $__outText;
	
	if (isset($__outText) && isset($__outText[$t])) {
		return $__outText[$t];
	} else {
		return $t;
	}
}

function _textf($t) {
	$t = _text($t);
	if (func_num_args() <= 1)
		return $t;
	for ($i = 1; $i < func_num_args(); $i++) {
		$arg = func_get_arg($i);
		$t = str_replace('%' . $i, $arg, $t);
	}
	return $t;
}

?>
